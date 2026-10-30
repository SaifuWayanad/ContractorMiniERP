using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/expenses")]
[Authorize]
public class ExpensesController : BaseController
{
    private readonly AppDbContext _db;

    public ExpensesController(AppDbContext db)
    {
        _db = db;
    }

    [HttpGet]
    public async Task<IActionResult> GetAll(
        [FromQuery] int? projectId,
        [FromQuery] int? customerId,
        [FromQuery] string? expenseHeading,
        [FromQuery] DateTime? startDate,
        [FromQuery] DateTime? endDate,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20,
        [FromQuery] bool includeArchived = false)
    {
        var query = _db.Expenses
            .Include(e => e.Project).ThenInclude(p => p.Customer)
            .AsQueryable();

        if (!includeArchived)
            query = query.Where(e => !e.IsArchived);

        if (projectId.HasValue)
            query = query.Where(e => e.ProjectId == projectId.Value);

        if (customerId.HasValue)
            query = query.Where(e => e.Project.CustomerId == customerId.Value);

        if (!string.IsNullOrWhiteSpace(expenseHeading))
            query = query.Where(e => e.ExpenseHeading.Contains(expenseHeading));

        if (startDate.HasValue)
            query = query.Where(e => e.ExpenseDate >= startDate.Value);

        if (endDate.HasValue)
            query = query.Where(e => e.ExpenseDate <= endDate.Value);

        var total = await query.CountAsync();
        var items = await query
            .OrderByDescending(e => e.ExpenseDate)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .Select(e => new ExpenseDto
            {
                Id = e.Id,
                ProjectId = e.ProjectId,
                ProjectName = e.Project != null ? e.Project.ProjectName : string.Empty,
                CustomerName = e.Project != null && e.Project.Customer != null ? e.Project.Customer.CustomerName : string.Empty,
                ExpenseHeading = e.ExpenseHeading,
                ExpenseDescription = e.ExpenseDescription,
                ExpenseDate = e.ExpenseDate,
                Amount = e.Amount,
                PaymentMethod = e.PaymentMethod,
                ReferenceNumber = e.ReferenceNumber,
                Notes = e.Notes,
                IsArchived = e.IsArchived,
                CreatedAt = e.CreatedAt,
                UpdatedAt = e.UpdatedAt
            })
            .ToListAsync();

        return Ok(new PagedResult<ExpenseDto> { Items = items, Total = total, Page = page, PageSize = pageSize });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var expense = await _db.Expenses
            .Include(e => e.Project).ThenInclude(p => p.Customer)
            .FirstOrDefaultAsync(e => e.Id == id);

        if (expense == null) return NotFound();

        return Ok(new ExpenseDto
        {
            Id = expense.Id,
            ProjectId = expense.ProjectId,
            ProjectName = expense.Project.ProjectName,
            CustomerName = expense.Project.Customer?.CustomerName ?? string.Empty,
            ExpenseHeading = expense.ExpenseHeading,
            ExpenseDescription = expense.ExpenseDescription,
            ExpenseDate = expense.ExpenseDate,
            Amount = expense.Amount,
            PaymentMethod = expense.PaymentMethod,
            ReferenceNumber = expense.ReferenceNumber,
            Notes = expense.Notes,
            IsArchived = expense.IsArchived,
            CreatedAt = expense.CreatedAt,
            UpdatedAt = expense.UpdatedAt
        });
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] CreateExpenseRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var expense = new Expense
        {
            ProjectId = request.ProjectId,
            ExpenseHeading = request.ExpenseHeading,
            ExpenseDescription = request.ExpenseDescription,
            ExpenseDate = request.ExpenseDate,
            Amount = request.Amount,
            PaymentMethod = request.PaymentMethod,
            ReferenceNumber = request.ReferenceNumber,
            Notes = request.Notes,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        _db.Expenses.Add(expense);
        await _db.SaveChangesAsync();

        return CreatedAtAction(nameof(GetById), new { id = expense.Id }, new { id = expense.Id });
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdateExpenseRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var expense = await _db.Expenses.Include(e => e.Project).FirstOrDefaultAsync(e => e.Id == id);
        if (expense == null) return NotFound();

        expense.ProjectId = request.ProjectId;
        expense.ExpenseHeading = request.ExpenseHeading;
        expense.ExpenseDescription = request.ExpenseDescription;
        expense.ExpenseDate = request.ExpenseDate;
        expense.Amount = request.Amount;
        expense.PaymentMethod = request.PaymentMethod;
        expense.ReferenceNumber = request.ReferenceNumber;
        expense.Notes = request.Notes;
        expense.IsArchived = request.IsArchived;
        expense.UpdatedAt = DateTime.UtcNow;

        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var expense = await _db.Expenses.Include(e => e.Project).FirstOrDefaultAsync(e => e.Id == id);
        if (expense == null) return NotFound();

        expense.IsArchived = true;
        expense.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return NoContent();
    }
}
