namespace BuildTrack.Api.DTOs;

public class DashboardDto
{
    public int TotalProjects { get; set; }
    public int TotalCustomers { get; set; }
    public decimal TotalPurchaseValue { get; set; }
    public decimal TotalExpenses { get; set; }
    public decimal TotalReceipts { get; set; }
    public decimal TotalOutstanding { get; set; }
    public IEnumerable<RecentProjectDto> RecentProjects { get; set; } = Enumerable.Empty<RecentProjectDto>();
}

public class RecentProjectDto
{
    public int Id { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public string CustomerName { get; set; } = string.Empty;
    public decimal? Budget { get; set; }
    public DateTime CreatedAt { get; set; }
}
