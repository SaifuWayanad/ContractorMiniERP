using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace BuildTrack.Api.Controllers;

public class BaseController : ControllerBase
{
    protected int CurrentUserId =>
        int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);

    protected string CurrentUserRole =>
        User.FindFirstValue(ClaimTypes.Role) ?? "Admin";

    protected bool IsAdmin => CurrentUserRole == "Admin";

    // Returns a queryable filter expression for projects this user can see
    protected IQueryable<BuildTrack.Api.Models.Project> ScopeProjects(
        Microsoft.EntityFrameworkCore.DbSet<BuildTrack.Api.Models.Project> projects,
        bool includeArchived = false)
    {
        var q = projects.AsQueryable();
        if (!includeArchived) q = q.Where(p => !p.IsArchived);
        if (!IsAdmin) q = q.Where(p => p.AssignedUserId == CurrentUserId);
        return q;
    }
}
