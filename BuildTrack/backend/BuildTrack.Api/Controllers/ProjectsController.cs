using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/projects")]
[Authorize]
public class ProjectsController : BaseController
{
    private readonly AppDbContext _db;

    public ProjectsController(AppDbContext db)
    {
        _db = db;
    }

    [HttpGet]
    public async Task<IActionResult> GetAll(
        [FromQuery] string? search,
        [FromQuery] int? customerId,
        [FromQuery] int? locationId,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20,
        [FromQuery] bool includeArchived = false)
    {
        var baseQuery = ScopeProjects(_db.Projects, includeArchived);

        IQueryable<Project> query = baseQuery
            .Include(p => p.Customer)
            .Include(p => p.Location)
            .Include(p => p.AssignedUser);

        if (!string.IsNullOrWhiteSpace(search))
            query = query.Where(p =>
                p.ProjectName.Contains(search) ||
                p.ProjectAddress.Contains(search) ||
                p.Customer.CustomerName.Contains(search));

        if (customerId.HasValue)
            query = query.Where(p => p.CustomerId == customerId.Value);

        if (locationId.HasValue)
            query = query.Where(p => p.LocationId == locationId.Value);

        var total = await query.CountAsync();
        var items = await query
            .OrderByDescending(p => p.CreatedAt)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
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
                AssignedUserId = p.AssignedUserId,
                AssignedUserName = p.AssignedUser != null ? p.AssignedUser.FullName : null,
                CreatedAt = p.CreatedAt,
                UpdatedAt = p.UpdatedAt
            })
            .ToListAsync();

        return Ok(new PagedResult<ProjectDto> { Items = items, Total = total, Page = page, PageSize = pageSize });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var query = _db.Projects
            .Include(p => p.Customer)
            .Include(p => p.Location)
            .Include(p => p.AssignedUser)
            .AsQueryable();
        if (!IsAdmin) query = query.Where(p => p.AssignedUserId == CurrentUserId);
        var project = await query.FirstOrDefaultAsync(p => p.Id == id);

        if (project == null) return NotFound();

        return Ok(new ProjectDto
        {
            Id = project.Id,
            ProjectName = project.ProjectName,
            CustomerId = project.CustomerId,
            CustomerName = project.Customer?.CustomerName ?? string.Empty,
            LocationId = project.LocationId,
            LocationName = project.Location?.LocationName,
            ProjectAddress = project.ProjectAddress,
            StartDate = project.StartDate,
            EndDate = project.EndDate,
            Budget = project.Budget,
            Description = project.Description,
            IsArchived = project.IsArchived,
            AssignedUserId = project.AssignedUserId,
            AssignedUserName = project.AssignedUser?.FullName,
            CreatedAt = project.CreatedAt,
            UpdatedAt = project.UpdatedAt
        });
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] CreateProjectRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var duplicate = await _db.Projects.AnyAsync(p => p.ProjectName == request.ProjectName && !p.IsArchived);
        if (duplicate)
            return Conflict(new { message = $"A project named '{request.ProjectName}' already exists." });

        var project = new Project
        {
            ProjectName = request.ProjectName,
            CustomerId = request.CustomerId,
            LocationId = request.LocationId,
            ProjectAddress = request.ProjectAddress,
            StartDate = request.StartDate,
            EndDate = request.EndDate,
            Budget = request.Budget,
            Description = request.Description,
            AssignedUserId = request.AssignedUserId ?? (!IsAdmin ? CurrentUserId : null),
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        _db.Projects.Add(project);
        await _db.SaveChangesAsync();

        return CreatedAtAction(nameof(GetById), new { id = project.Id }, new { id = project.Id });
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdateProjectRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var project = await _db.Projects.FindAsync(id);
        if (project == null) return NotFound();

        project.ProjectName = request.ProjectName;
        project.CustomerId = request.CustomerId;
        project.LocationId = request.LocationId;
        project.ProjectAddress = request.ProjectAddress;
        project.StartDate = request.StartDate;
        project.EndDate = request.EndDate;
        project.Budget = request.Budget;
        project.Description = request.Description;
        project.IsArchived = request.IsArchived;
        project.AssignedUserId = request.AssignedUserId;
        project.UpdatedAt = DateTime.UtcNow;

        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var project = await _db.Projects.FindAsync(id);
        if (project == null) return NotFound();

        project.IsArchived = true;
        project.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpGet("{id}/summary")]
    public async Task<IActionResult> GetSummary(int id)
    {
        var project = await _db.Projects
            .Include(p => p.Customer)
            .Include(p => p.AssignedUser)
            .FirstOrDefaultAsync(p => p.Id == id);

        if (project == null) return NotFound();

        var totalPurchases = (decimal)(await _db.Purchases
            .Where(p => p.ProjectId == id && !p.IsArchived)
            .SumAsync(p => (double?)p.LineTotal) ?? 0.0);

        var totalExpenses = (decimal)(await _db.Expenses
            .Where(e => e.ProjectId == id && !e.IsArchived)
            .SumAsync(e => (double?)e.Amount) ?? 0.0);

        var totalReceived = (decimal)(await _db.CustomerPayments
            .Where(cp => cp.ProjectId == id && !cp.IsVoided)
            .SumAsync(cp => (double?)cp.AmountReceived) ?? 0.0);

        var totalProjectCost = totalPurchases + totalExpenses;
        decimal? outstandingAgainstBudget = project.Budget.HasValue
            ? project.Budget.Value - totalReceived
            : null;
        decimal? remainingBudget = project.Budget.HasValue
            ? project.Budget.Value - totalProjectCost
            : null;
        var isOverBudget = project.Budget.HasValue && totalProjectCost > project.Budget.Value;

        return Ok(new ProjectFinancialSummary
        {
            ProjectId = project.Id,
            ProjectName = project.ProjectName,
            CustomerName = project.Customer?.CustomerName ?? string.Empty,
            Budget = project.Budget,
            TotalPurchases = totalPurchases,
            TotalExpenses = totalExpenses,
            TotalProjectCost = totalProjectCost,
            TotalReceived = totalReceived,
            OutstandingAgainstBudget = outstandingAgainstBudget,
            RemainingBudget = remainingBudget,
            IsOverBudget = isOverBudget
        });
    }
}
