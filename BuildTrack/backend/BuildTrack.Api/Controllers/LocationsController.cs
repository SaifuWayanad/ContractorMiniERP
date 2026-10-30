using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/locations")]
[Authorize]
public class LocationsController : BaseController
{
    private readonly AppDbContext _db;

    public LocationsController(AppDbContext db)
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
        var query = _db.Locations.AsQueryable();

        if (!includeArchived)
            query = query.Where(l => !l.IsArchived);

        if (!string.IsNullOrWhiteSpace(search))
            query = query.Where(l =>
                l.LocationName.Contains(search) ||
                l.Address.Contains(search) ||
                l.City.Contains(search));

        var total = await query.CountAsync();
        var items = await query
            .OrderBy(l => l.LocationName)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .Select(l => new LocationDto
            {
                Id = l.Id,
                LocationName = l.LocationName,
                Address = l.Address,
                City = l.City,
                Notes = l.Notes,
                IsArchived = l.IsArchived,
                CreatedAt = l.CreatedAt,
                UpdatedAt = l.UpdatedAt
            })
            .ToListAsync();

        return Ok(new PagedResult<LocationDto> { Items = items, Total = total, Page = page, PageSize = pageSize });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var location = await _db.Locations.FindAsync(id);
        if (location == null) return NotFound();

        return Ok(new LocationDto
        {
            Id = location.Id,
            LocationName = location.LocationName,
            Address = location.Address,
            City = location.City,
            Notes = location.Notes,
            IsArchived = location.IsArchived,
            CreatedAt = location.CreatedAt,
            UpdatedAt = location.UpdatedAt
        });
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] CreateLocationRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var location = new Location
        {
            LocationName = request.LocationName,
            Address = request.Address,
            City = request.City,
            Notes = request.Notes,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        _db.Locations.Add(location);
        await _db.SaveChangesAsync();

        return CreatedAtAction(nameof(GetById), new { id = location.Id }, new LocationDto
        {
            Id = location.Id,
            LocationName = location.LocationName,
            Address = location.Address,
            City = location.City,
            Notes = location.Notes,
            IsArchived = location.IsArchived,
            CreatedAt = location.CreatedAt,
            UpdatedAt = location.UpdatedAt
        });
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdateLocationRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var location = await _db.Locations.FindAsync(id);
        if (location == null) return NotFound();

        location.LocationName = request.LocationName;
        location.Address = request.Address;
        location.City = request.City;
        location.Notes = request.Notes;
        location.IsArchived = request.IsArchived;
        location.UpdatedAt = DateTime.UtcNow;

        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var location = await _db.Locations.FindAsync(id);
        if (location == null) return NotFound();

        var isUsed = await _db.Customers.AnyAsync(c => c.LocationId == id && !c.IsArchived)
            || await _db.Projects.AnyAsync(p => p.LocationId == id && !p.IsArchived);

        if (isUsed)
            return Conflict(new { message = "Cannot delete location currently in use by customers or projects." });

        location.IsArchived = true;
        location.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return NoContent();
    }
}
