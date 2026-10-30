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
        var projectsBase = _db.Projects.Where(p => !p.IsArchived);
        if (!IsAdmin) projectsBase = projectsBase.Where(p => p.AssignedUserId == CurrentUserId);

        var totalProjects = await projectsBase.CountAsync();

        int totalCustomers;
        if (!IsAdmin)
        {
            var myCustomerIds = await projectsBase
                .Select(p => p.CustomerId)
                .Distinct()
                .ToListAsync();
            totalCustomers = myCustomerIds.Count;
        }
        else
        {
            totalCustomers = await _db.Customers.CountAsync(c => !c.IsArchived);
        }

        var visibleProjectIds = IsAdmin
            ? null
            : await projectsBase.Select(p => p.Id).ToListAsync();

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
        if (!IsAdmin)
            recentProjectsQuery = recentProjectsQuery.Where(p => p.AssignedUserId == CurrentUserId);

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
