using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/customers")]
[Authorize]
public class CustomersController : BaseController
{
    private readonly AppDbContext _db;

    public CustomersController(AppDbContext db)
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
        var query = _db.Customers
            .Include(c => c.Location)
            .AsQueryable();

        if (!includeArchived)
            query = query.Where(c => !c.IsArchived);

        if (!string.IsNullOrWhiteSpace(search))
            query = query.Where(c =>
                c.CustomerName.Contains(search) ||
                c.PhoneNumber.Contains(search) ||
                c.Address.Contains(search));

        var total = await query.CountAsync();
        var items = await query
            .OrderBy(c => c.CustomerName)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .Select(c => new CustomerDto
            {
                Id = c.Id,
                CustomerName = c.CustomerName,
                PhoneNumber = c.PhoneNumber,
                Address = c.Address,
                LocationId = c.LocationId,
                LocationName = c.Location != null ? c.Location.LocationName : null,
                Notes = c.Notes,
                IsArchived = c.IsArchived,
                CreatedAt = c.CreatedAt,
                UpdatedAt = c.UpdatedAt
            })
            .ToListAsync();

        return Ok(new PagedResult<CustomerDto> { Items = items, Total = total, Page = page, PageSize = pageSize });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var customer = await _db.Customers
            .Include(c => c.Location)
            .FirstOrDefaultAsync(c => c.Id == id);

        if (customer == null) return NotFound();

        return Ok(new CustomerDto
        {
            Id = customer.Id,
            CustomerName = customer.CustomerName,
            PhoneNumber = customer.PhoneNumber,
            Address = customer.Address,
            LocationId = customer.LocationId,
            LocationName = customer.Location?.LocationName,
            Notes = customer.Notes,
            IsArchived = customer.IsArchived,
            CreatedAt = customer.CreatedAt,
            UpdatedAt = customer.UpdatedAt
        });
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] CreateCustomerRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var customer = new Customer
        {
            CustomerName = request.CustomerName,
            PhoneNumber = request.PhoneNumber,
            Address = request.Address,
            LocationId = request.LocationId,
            Notes = request.Notes,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        _db.Customers.Add(customer);
        await _db.SaveChangesAsync();

        return CreatedAtAction(nameof(GetById), new { id = customer.Id }, new CustomerDto
        {
            Id = customer.Id,
            CustomerName = customer.CustomerName,
            PhoneNumber = customer.PhoneNumber,
            Address = customer.Address,
            LocationId = customer.LocationId,
            Notes = customer.Notes,
            IsArchived = customer.IsArchived,
            CreatedAt = customer.CreatedAt,
            UpdatedAt = customer.UpdatedAt
        });
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdateCustomerRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var customer = await _db.Customers.FindAsync(id);
        if (customer == null) return NotFound();

        customer.CustomerName = request.CustomerName;
        customer.PhoneNumber = request.PhoneNumber;
        customer.Address = request.Address;
        customer.LocationId = request.LocationId;
        customer.Notes = request.Notes;
        customer.IsArchived = request.IsArchived;
        customer.UpdatedAt = DateTime.UtcNow;

        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var customer = await _db.Customers.FindAsync(id);
        if (customer == null) return NotFound();

        var hasActiveProjects = await _db.Projects
            .AnyAsync(p => p.CustomerId == id && !p.IsArchived);

        if (hasActiveProjects)
            return Conflict(new { message = "Cannot delete customer with active projects." });

        customer.IsArchived = true;
        customer.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpGet("{id}/projects")]
    public async Task<IActionResult> GetProjects(int id, [FromQuery] bool includeArchived = false)
    {
        var customer = await _db.Customers.FindAsync(id);
        if (customer == null) return NotFound();

        var query = _db.Projects
            .Include(p => p.Customer)
            .Include(p => p.Location)
            .Where(p => p.CustomerId == id);

        if (!includeArchived)
            query = query.Where(p => !p.IsArchived);

        var projects = await query
            .OrderByDescending(p => p.CreatedAt)
            .Select(p => new ProjectDto
            {
                Id = p.Id,
                ProjectName = p.ProjectName,
                CustomerId = p.CustomerId,
                CustomerName = p.Customer.CustomerName,
                LocationId = p.LocationId,
                LocationName = p.Location != null ? p.Location.LocationName : null,
                ProjectAddress = p.ProjectAddress,
                StartDate = p.StartDate,
                EndDate = p.EndDate,
                Budget = p.Budget,
                Description = p.Description,
                IsArchived = p.IsArchived,
                CreatedAt = p.CreatedAt,
                UpdatedAt = p.UpdatedAt
            })
            .ToListAsync();

        return Ok(projects);
    }
}
