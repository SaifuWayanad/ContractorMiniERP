using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;
using BuildTrack.Api.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using System.Security.Claims;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/users")]
[Authorize(Roles = "Admin")]
public class UsersController : ControllerBase
{
    private readonly AppDbContext _db;
    private readonly IAuthService _auth;

    public UsersController(AppDbContext db, IAuthService auth) { _db = db; _auth = auth; }

    [HttpGet]
    public async Task<IActionResult> GetAll([FromQuery] string? search, [FromQuery] int page = 1, [FromQuery] int pageSize = 20)
    {
        var query = _db.Users.AsQueryable();
        if (!string.IsNullOrWhiteSpace(search))
            query = query.Where(u => u.Username.Contains(search) || u.FullName.Contains(search) || u.Email.Contains(search));
        var total = await query.CountAsync();
        var items = await query.OrderBy(u => u.FullName)
            .Skip((page - 1) * pageSize).Take(pageSize)
            .Select(u => new UserDto { Id = u.Id, Username = u.Username, FullName = u.FullName, Email = u.Email, Role = u.Role, IsActive = u.IsActive, CreatedAt = u.CreatedAt, UpdatedAt = u.UpdatedAt })
            .ToListAsync();
        return Ok(new PagedResult<UserDto> { Items = items, Total = total, Page = page, PageSize = pageSize });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var u = await _db.Users.FindAsync(id);
        if (u == null) return NotFound();
        return Ok(new UserDto { Id = u.Id, Username = u.Username, FullName = u.FullName, Email = u.Email, Role = u.Role, IsActive = u.IsActive, CreatedAt = u.CreatedAt, UpdatedAt = u.UpdatedAt });
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] CreateUserRequest req)
    {
        if (!ModelState.IsValid) return BadRequest(new ValidationProblemDetails(ModelState));
        if (req.Role != "Admin" && req.Role != "Contractor")
            return BadRequest(new ProblemDetails { Title = "Invalid role", Detail = "Role must be Admin or Contractor." });
        if (await _db.Users.AnyAsync(u => u.Username == req.Username))
            return Conflict(new ProblemDetails { Title = "Username taken", Detail = $"Username '{req.Username}' already exists." });
        var user = new User
        {
            Username = req.Username,
            PasswordHash = _auth.HashPassword(req.Password),
            FullName = req.FullName,
            Email = req.Email ?? string.Empty,
            Role = req.Role,
            IsActive = true,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };
        _db.Users.Add(user);
        await _db.SaveChangesAsync();
        return CreatedAtAction(nameof(GetById), new { id = user.Id }, new UserDto { Id = user.Id, Username = user.Username, FullName = user.FullName, Email = user.Email, Role = user.Role, IsActive = user.IsActive, CreatedAt = user.CreatedAt, UpdatedAt = user.UpdatedAt });
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdateUserRequest req)
    {
        if (!ModelState.IsValid) return BadRequest(new ValidationProblemDetails(ModelState));
        var user = await _db.Users.FindAsync(id);
        if (user == null) return NotFound();
        if (req.Role != "Admin" && req.Role != "Contractor")
            return BadRequest(new ProblemDetails { Title = "Invalid role" });
        // Prevent deactivating self
        var currentUserId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        if (id == currentUserId && !req.IsActive)
            return Conflict(new ProblemDetails { Title = "Cannot deactivate yourself." });
        user.FullName = req.FullName;
        user.Email = req.Email ?? string.Empty;
        user.Role = req.Role;
        user.IsActive = req.IsActive;
        if (!string.IsNullOrWhiteSpace(req.Password))
            user.PasswordHash = _auth.HashPassword(req.Password);
        user.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return Ok(new UserDto { Id = user.Id, Username = user.Username, FullName = user.FullName, Email = user.Email, Role = user.Role, IsActive = user.IsActive, CreatedAt = user.CreatedAt, UpdatedAt = user.UpdatedAt });
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var currentUserId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        if (id == currentUserId)
            return Conflict(new ProblemDetails { Title = "Cannot delete yourself." });
        var user = await _db.Users.FindAsync(id);
        if (user == null) return NotFound();
        // Soft-deactivate instead of hard delete
        user.IsActive = false;
        user.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return NoContent();
    }

    // Endpoint for dropdowns — returns only active contractors
    [HttpGet("contractors")]
    public async Task<IActionResult> GetContractors()
    {
        var contractors = await _db.Users
            .Where(u => u.IsActive && u.Role == "Contractor")
            .OrderBy(u => u.FullName)
            .Select(u => new { u.Id, u.FullName, u.Username })
            .ToListAsync();
        return Ok(contractors);
    }
}
