using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/dashboard")]
[Authorize]
public class DashboardController : BaseController
{
    private readonly AppDbContext _db;

    public DashboardController(AppDbContext db)
    {
        _db = db;
    }

    [HttpGet]
    public async Task<IActionResult> Get()
    {
        IEnumerable<int>? visibleProjectIds = null;

        var totalProjects = visibleProjectIds != null
            ? visibleProjectIds.Count()
            : await _db.Projects.CountAsync(p => !p.IsArchived);

        int totalCustomers;
        if (visibleProjectIds != null)
        {
            var myCustomerIds = await _db.Projects
                .Where(p => visibleProjectIds.Contains(p.Id))
                .Select(p => p.CustomerId)
                .Distinct()
                .ToListAsync();
            totalCustomers = myCustomerIds.Count;
        }
        else
        {
            totalCustomers = await _db.Customers.CountAsync(c => !c.IsArchived);
        }

        var purchaseQuery = _db.Purchases.Where(p => !p.IsArchived);
        if (visibleProjectIds != null) purchaseQuery = purchaseQuery.Where(p => visibleProjectIds.Contains(p.ProjectId));
        var totalPurchaseValue = (decimal)(await purchaseQuery.SumAsync(p => (double?)p.LineTotal) ?? 0.0);

        var expenseQuery = _db.Expenses.Where(e => !e.IsArchived);
        if (visibleProjectIds != null) expenseQuery = expenseQuery.Where(e => visibleProjectIds.Contains(e.ProjectId));
        var totalExpenses = (decimal)(await expenseQuery.SumAsync(e => (double?)e.Amount) ?? 0.0);

        var paymentQuery = _db.CustomerPayments.Where(cp => !cp.IsVoided);
        if (visibleProjectIds != null) paymentQuery = paymentQuery.Where(cp => visibleProjectIds.Contains(cp.ProjectId));
        var totalReceipts = (decimal)(await paymentQuery.SumAsync(cp => (double?)cp.AmountReceived) ?? 0.0);

        var totalOutstanding = (totalPurchaseValue + totalExpenses) - totalReceipts;

        var recentProjectsQuery = _db.Projects
            .Where(p => !p.IsArchived)
            .Include(p => p.Customer)
            .AsQueryable();
        if (visibleProjectIds != null)
            recentProjectsQuery = recentProjectsQuery.Where(p => visibleProjectIds.Contains(p.Id));

        var recentProjects = await recentProjectsQuery
            .OrderByDescending(p => p.CreatedAt)
            .Take(5)
            .Select(p => new RecentProjectDto
            {
                Id = p.Id,
                ProjectName = p.ProjectName,
                CustomerName = p.Customer.CustomerName,
                Budget = p.Budget,
                CreatedAt = p.CreatedAt
            })
            .ToListAsync();

        return Ok(new DashboardDto
        {
            TotalProjects = totalProjects,
            TotalCustomers = totalCustomers,
            TotalPurchaseValue = totalPurchaseValue,
            TotalExpenses = totalExpenses,
            TotalReceipts = totalReceipts,
            TotalOutstanding = totalOutstanding,
            RecentProjects = recentProjects
        });
    }
}
