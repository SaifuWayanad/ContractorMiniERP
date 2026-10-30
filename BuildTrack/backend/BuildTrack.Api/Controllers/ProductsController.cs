using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/products")]
[Authorize]
public class ProductsController : BaseController
{
    private readonly AppDbContext _db;

    public ProductsController(AppDbContext db)
    {
        _db = db;
    }

    [HttpGet]
    public async Task<IActionResult> GetAll(
        [FromQuery] string? search,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20,
        [FromQuery] bool includeArchived = false)
    {
        var query = _db.Products.AsQueryable();

        if (!includeArchived)
            query = query.Where(p => p.IsActive);

        if (!IsAdmin)
            query = query.Where(p => p.CreatedByUserId == CurrentUserId);

        if (!string.IsNullOrWhiteSpace(search))
            query = query.Where(p =>
                p.ProductName.Contains(search) ||
                (p.Description != null && p.Description.Contains(search)));

        var total = await query.CountAsync();
        var items = await query
            .OrderBy(p => p.ProductName)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .Select(p => new ProductDto
            {
                Id = p.Id,
                ProductName = p.ProductName,
                UnitOfMeasure = p.UnitOfMeasure,
                DefaultUnitRate = p.DefaultUnitRate,
                Description = p.Description,
                IsActive = p.IsActive,
                CreatedAt = p.CreatedAt,
                UpdatedAt = p.UpdatedAt
            })
            .ToListAsync();

        return Ok(new PagedResult<ProductDto> { Items = items, Total = total, Page = page, PageSize = pageSize });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var query = _db.Products.AsQueryable();
        if (!IsAdmin) query = query.Where(p => p.CreatedByUserId == CurrentUserId);
        var product = await query.FirstOrDefaultAsync(p => p.Id == id);
        if (product == null) return NotFound();

        return Ok(new ProductDto
        {
            Id = product.Id,
            ProductName = product.ProductName,
            UnitOfMeasure = product.UnitOfMeasure,
            DefaultUnitRate = product.DefaultUnitRate,
            Description = product.Description,
            IsActive = product.IsActive,
            CreatedAt = product.CreatedAt,
            UpdatedAt = product.UpdatedAt
        });
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] CreateProductRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var product = new Product
        {
            ProductName = request.ProductName,
            UnitOfMeasure = request.UnitOfMeasure,
            DefaultUnitRate = request.DefaultUnitRate,
            Description = request.Description,
            IsActive = true,
            CreatedByUserId = CurrentUserId,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        _db.Products.Add(product);
        await _db.SaveChangesAsync();

        return CreatedAtAction(nameof(GetById), new { id = product.Id }, new ProductDto
        {
            Id = product.Id,
            ProductName = product.ProductName,
            UnitOfMeasure = product.UnitOfMeasure,
            DefaultUnitRate = product.DefaultUnitRate,
            Description = product.Description,
            IsActive = product.IsActive,
            CreatedAt = product.CreatedAt,
            UpdatedAt = product.UpdatedAt
        });
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdateProductRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var product = await _db.Products.FindAsync(id);
        if (product == null) return NotFound();

        product.ProductName = request.ProductName;
        product.UnitOfMeasure = request.UnitOfMeasure;
        product.DefaultUnitRate = request.DefaultUnitRate;
        product.Description = request.Description;
        product.IsActive = request.IsActive;
        product.UpdatedAt = DateTime.UtcNow;

        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var product = await _db.Products.FindAsync(id);
        if (product == null) return NotFound();

        var isUsed = await _db.Purchases.AnyAsync(p => p.ProductId == id && !p.IsArchived);
        if (isUsed)
            return Conflict(new { message = "Cannot delete product that is in use by purchases." });

        product.IsActive = false;
        product.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return NoContent();
    }
}
