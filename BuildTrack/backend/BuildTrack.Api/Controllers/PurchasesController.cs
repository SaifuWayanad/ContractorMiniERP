using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/purchases")]
[Authorize]
public class PurchasesController : BaseController
{
    private readonly AppDbContext _db;

    public PurchasesController(AppDbContext db)
    {
        _db = db;
    }

    [HttpGet]
    public async Task<IActionResult> GetAll(
        [FromQuery] int? projectId,
        [FromQuery] int? customerId,
        [FromQuery] int? productId,
        [FromQuery] DateTime? startDate,
        [FromQuery] DateTime? endDate,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20,
        [FromQuery] bool includeArchived = false)
    {
        var query = _db.Purchases
            .Include(p => p.Project).ThenInclude(pr => pr.Customer)
            .Include(p => p.Product)
            .AsQueryable();

        if (!includeArchived)
            query = query.Where(p => !p.IsArchived);

        if (!IsAdmin)
            query = query.Where(p => p.Project.AssignedUserId == CurrentUserId);

        if (projectId.HasValue)
            query = query.Where(p => p.ProjectId == projectId.Value);

        if (customerId.HasValue)
            query = query.Where(p => p.Project.CustomerId == customerId.Value);

        if (productId.HasValue)
            query = query.Where(p => p.ProductId == productId.Value);

        if (startDate.HasValue)
            query = query.Where(p => p.PurchaseDate >= startDate.Value);

        if (endDate.HasValue)
            query = query.Where(p => p.PurchaseDate <= endDate.Value);

        var total = await query.CountAsync();
        var items = await query
            .OrderByDescending(p => p.PurchaseDate)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .Select(p => new PurchaseDto
            {
                Id = p.Id,
                ProjectId = p.ProjectId,
                ProjectName = p.Project != null ? p.Project.ProjectName : string.Empty,
                CustomerName = p.Project != null && p.Project.Customer != null ? p.Project.Customer.CustomerName : string.Empty,
                ProductId = p.ProductId,
                ProductName = p.Product.ProductName,
                PurchaseDate = p.PurchaseDate,
                Quantity = p.Quantity,
                UnitAmount = p.UnitAmount,
                LineTotal = p.LineTotal,
                Supplier = p.Supplier,
                InvoiceReference = p.InvoiceReference,
                Notes = p.Notes,
                IsArchived = p.IsArchived,
                CreatedAt = p.CreatedAt,
                UpdatedAt = p.UpdatedAt
            })
            .ToListAsync();

        return Ok(new PagedResult<PurchaseDto> { Items = items, Total = total, Page = page, PageSize = pageSize });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var purchase = await _db.Purchases
            .Include(p => p.Project).ThenInclude(pr => pr.Customer)
            .Include(p => p.Product)
            .FirstOrDefaultAsync(p => p.Id == id);

        if (purchase == null) return NotFound();

        return Ok(new PurchaseDto
        {
            Id = purchase.Id,
            ProjectId = purchase.ProjectId,
            ProjectName = purchase.Project?.ProjectName ?? string.Empty,
            CustomerName = purchase.Project?.Customer?.CustomerName ?? string.Empty,
            ProductId = purchase.ProductId,
            ProductName = purchase.Product.ProductName,
            PurchaseDate = purchase.PurchaseDate,
            Quantity = purchase.Quantity,
            UnitAmount = purchase.UnitAmount,
            LineTotal = purchase.LineTotal,
            Supplier = purchase.Supplier,
            InvoiceReference = purchase.InvoiceReference,
            Notes = purchase.Notes,
            IsArchived = purchase.IsArchived,
            CreatedAt = purchase.CreatedAt,
            UpdatedAt = purchase.UpdatedAt
        });
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] CreatePurchaseRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var purchase = new Purchase
        {
            ProjectId = request.ProjectId,
            ProductId = request.ProductId,
            PurchaseDate = request.PurchaseDate,
            Quantity = request.Quantity,
            UnitAmount = request.UnitAmount,
            LineTotal = request.LineTotal.HasValue && request.LineTotal.Value > 0
                ? request.LineTotal.Value
                : request.Quantity * request.UnitAmount,
            Supplier = request.Supplier,
            InvoiceReference = request.InvoiceReference,
            Notes = request.Notes,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        _db.Purchases.Add(purchase);
        try
        {
            await _db.SaveChangesAsync();
        }
        catch (Microsoft.EntityFrameworkCore.DbUpdateException)
        {
            return BadRequest(new { errors = new[] { "Invalid project or product — please select valid options and try again." } });
        }

        return CreatedAtAction(nameof(GetById), new { id = purchase.Id }, new { id = purchase.Id });
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdatePurchaseRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var purchase = await _db.Purchases.Include(p => p.Project).FirstOrDefaultAsync(p => p.Id == id);
        if (purchase == null) return NotFound();

        purchase.ProjectId = request.ProjectId;
        purchase.ProductId = request.ProductId;
        purchase.PurchaseDate = request.PurchaseDate;
        purchase.Quantity = request.Quantity;
        purchase.UnitAmount = request.UnitAmount;
        purchase.LineTotal = request.LineTotal.HasValue && request.LineTotal.Value > 0
            ? request.LineTotal.Value
            : request.Quantity * request.UnitAmount;
        purchase.Supplier = request.Supplier;
        purchase.InvoiceReference = request.InvoiceReference;
        purchase.Notes = request.Notes;
        purchase.IsArchived = request.IsArchived;
        purchase.UpdatedAt = DateTime.UtcNow;

        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var purchase = await _db.Purchases.Include(p => p.Project).FirstOrDefaultAsync(p => p.Id == id);
        if (purchase == null) return NotFound();

        purchase.IsArchived = true;
        purchase.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return NoContent();
    }
}
