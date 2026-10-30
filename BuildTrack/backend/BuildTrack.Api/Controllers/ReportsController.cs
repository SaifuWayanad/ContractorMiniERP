using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/reports")]
[Authorize]
public class ReportsController : BaseController
{
    private readonly AppDbContext _db;

    public ReportsController(AppDbContext db)
    {
        _db = db;
    }

    [HttpGet("outstanding")]
    public async Task<IActionResult> GetOutstanding(
        [FromQuery] string? search,
        [FromQuery] int? customerId,
        [FromQuery] int? projectId,
        [FromQuery] int? locationId,
        [FromQuery] DateTime? dateFrom,
        [FromQuery] DateTime? dateTo,
        [FromQuery] bool? includeArchived,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20)
    {
        var query = _db.Projects
            .Include(p => p.Customer)
            .Include(p => p.Location)
            .AsQueryable();

        // Status filter: default = active only
        var showArchived = includeArchived ?? false;
        if (!showArchived)
            query = query.Where(p => !p.IsArchived);

        if (!string.IsNullOrWhiteSpace(search))
            query = query.Where(p =>
                p.ProjectName.Contains(search) ||
                (p.Customer != null && p.Customer.CustomerName.Contains(search)));

        if (customerId.HasValue)
            query = query.Where(p => p.CustomerId == customerId.Value);

        if (projectId.HasValue)
            query = query.Where(p => p.Id == projectId.Value);

        if (locationId.HasValue)
            query = query.Where(p => p.LocationId == locationId.Value);

        var total = await query.CountAsync();

        // All matching IDs for global summary totals
        var allIds = await query.Select(p => p.Id).ToListAsync();

        // Paginated project rows
        var pagedProjects = await query
            .OrderBy(p => p.ProjectName)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .ToListAsync();

        var pagedIds = pagedProjects.Select(p => p.Id).ToList();

        // Per-page aggregates (date-filtered if requested)
        var purchQuery = _db.Purchases.Where(pu => !pu.IsArchived && pagedIds.Contains(pu.ProjectId));
        if (dateFrom.HasValue) purchQuery = purchQuery.Where(pu => pu.PurchaseDate >= dateFrom.Value);
        if (dateTo.HasValue)   purchQuery = purchQuery.Where(pu => pu.PurchaseDate <= dateTo.Value);

        var expQuery = _db.Expenses.Where(e => !e.IsArchived && pagedIds.Contains(e.ProjectId));
        if (dateFrom.HasValue) expQuery = expQuery.Where(e => e.ExpenseDate >= dateFrom.Value);
        if (dateTo.HasValue)   expQuery = expQuery.Where(e => e.ExpenseDate <= dateTo.Value);

        var purchSums = await purchQuery
            .GroupBy(pu => pu.ProjectId)
            .Select(g => new { ProjectId = g.Key, Total = (double?)g.Sum(pu => (double?)pu.LineTotal) ?? 0.0 })
            .ToDictionaryAsync(x => x.ProjectId, x => (decimal)x.Total);

        var expSums = await expQuery
            .GroupBy(e => e.ProjectId)
            .Select(g => new { ProjectId = g.Key, Total = (double?)g.Sum(e => (double?)e.Amount) ?? 0.0 })
            .ToDictionaryAsync(x => x.ProjectId, x => (decimal)x.Total);

        var payData = await _db.CustomerPayments
            .Where(cp => !cp.IsVoided && pagedIds.Contains(cp.ProjectId))
            .GroupBy(cp => cp.ProjectId)
            .Select(g => new
            {
                ProjectId = g.Key,
                Total = (double?)g.Sum(cp => (double?)cp.AmountReceived) ?? 0.0,
                LastDate = g.Max(cp => (DateTime?)cp.PaymentDate)
            })
            .ToDictionaryAsync(x => x.ProjectId, x => new { Total = (decimal)x.Total, x.LastDate });

        var items = pagedProjects.Select(p =>
        {
            var purch = purchSums.GetValueOrDefault(p.Id);
            var exp   = expSums.GetValueOrDefault(p.Id);
            var pay   = payData.GetValueOrDefault(p.Id);
            var totalCost     = purch + exp;
            var totalReceived = pay?.Total ?? 0m;

            decimal? remainingBudget  = p.Budget.HasValue ? p.Budget.Value - totalCost : null;
            decimal? balanceDue       = p.Budget.HasValue ? p.Budget.Value - totalReceived : null;
            decimal? utilizationPct   = (p.Budget.HasValue && p.Budget.Value > 0)
                                        ? Math.Round(totalCost / p.Budget.Value * 100, 1)
                                        : null;

            return new OutstandingReportRow
            {
                ProjectId          = p.Id,
                ProjectName        = p.ProjectName,
                CustomerId         = p.CustomerId,
                CustomerName       = p.Customer?.CustomerName ?? string.Empty,
                LocationId         = p.LocationId,
                LocationName       = p.Location?.LocationName,
                Budget             = p.Budget,
                TotalPurchases     = purch,
                TotalExpenses      = exp,
                TotalProjectCost   = totalCost,
                TotalReceived      = totalReceived,
                RemainingBudget    = remainingBudget,
                BalanceDue         = balanceDue,
                BudgetUtilizationPct = utilizationPct,
                IsOverBudget       = p.Budget.HasValue && totalCost > p.Budget.Value,
                IsArchived         = p.IsArchived,
                LastPaymentDate    = pay?.LastDate,
            };
        }).ToList();

        // Global totals across ALL filtered projects (same date filter)
        var allPurchQuery = _db.Purchases.Where(pu => !pu.IsArchived && allIds.Contains(pu.ProjectId));
        if (dateFrom.HasValue) allPurchQuery = allPurchQuery.Where(pu => pu.PurchaseDate >= dateFrom.Value);
        if (dateTo.HasValue)   allPurchQuery = allPurchQuery.Where(pu => pu.PurchaseDate <= dateTo.Value);

        var allExpQuery = _db.Expenses.Where(e => !e.IsArchived && allIds.Contains(e.ProjectId));
        if (dateFrom.HasValue) allExpQuery = allExpQuery.Where(e => e.ExpenseDate >= dateFrom.Value);
        if (dateTo.HasValue)   allExpQuery = allExpQuery.Where(e => e.ExpenseDate <= dateTo.Value);

        var globalPurch    = (decimal)(await allPurchQuery.SumAsync(pu => (double?)pu.LineTotal) ?? 0.0);
        var globalExp      = (decimal)(await allExpQuery.SumAsync(e => (double?)e.Amount) ?? 0.0);
        var globalReceived = (decimal)(await _db.CustomerPayments
            .Where(cp => !cp.IsVoided && allIds.Contains(cp.ProjectId))
            .SumAsync(cp => (double?)cp.AmountReceived) ?? 0.0);
        var globalBudget   = (decimal)(await query.SumAsync(p => (double?)p.Budget) ?? 0.0);
        var overBudgetCount = items.Count(r => r.IsOverBudget);

        return Ok(new OutstandingReportResponse
        {
            Items = items,
            Totals = new OutstandingReportTotals
            {
                TotalBudget      = globalBudget,
                TotalPurchases   = globalPurch,
                TotalExpenses    = globalExp,
                TotalProjectCost = globalPurch + globalExp,
                TotalReceived    = globalReceived,
                ProjectCount     = total,
                OverBudgetCount  = overBudgetCount,
            },
            Total    = total,
            Page     = page,
            PageSize = pageSize,
        });
    }

    [HttpGet("outstanding/{projectId}/transactions")]
    public async Task<IActionResult> GetProjectTransactions(int projectId)
    {
        var project = await _db.Projects
            .FirstOrDefaultAsync(p => p.Id == projectId);

        if (project == null) return NotFound();

        if (!IsAdmin && project.AssignedUserId != CurrentUserId)
            return Forbid();

        var transactions = new List<ProjectTransactionItem>();

        var purchases = await _db.Purchases
            .Include(pu => pu.Product)
            .Where(pu => pu.ProjectId == projectId && !pu.IsArchived)
            .OrderByDescending(pu => pu.PurchaseDate)
            .ToListAsync();

        transactions.AddRange(purchases.Select(pu => new ProjectTransactionItem
        {
            TransactionType = "Purchase",
            Id          = pu.Id,
            Date        = pu.PurchaseDate,
            Reference   = pu.InvoiceReference,
            Description = pu.Product?.ProductName ?? "Purchase",
            Amount      = pu.LineTotal,
            Notes       = pu.Notes,
        }));

        var expenses = await _db.Expenses
            .Where(e => e.ProjectId == projectId && !e.IsArchived)
            .OrderByDescending(e => e.ExpenseDate)
            .ToListAsync();

        transactions.AddRange(expenses.Select(e => new ProjectTransactionItem
        {
            TransactionType = "Expense",
            Id          = e.Id,
            Date        = e.ExpenseDate,
            Reference   = e.ReferenceNumber,
            Description = e.ExpenseHeading,
            Amount      = e.Amount,
            Notes       = e.Notes,
        }));

        var payments = await _db.CustomerPayments
            .Where(cp => cp.ProjectId == projectId && !cp.IsVoided)
            .OrderByDescending(cp => cp.PaymentDate)
            .ToListAsync();

        transactions.AddRange(payments.Select(cp => new ProjectTransactionItem
        {
            TransactionType = "Payment",
            Id          = cp.Id,
            Date        = cp.PaymentDate,
            Reference   = cp.ReceiptReference,
            Description = "Customer Payment",
            Amount      = cp.AmountReceived,
            Notes       = cp.Notes,
        }));

        return Ok(transactions.OrderByDescending(t => t.Date).ToList());
    }
}
