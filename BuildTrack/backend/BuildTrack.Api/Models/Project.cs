namespace BuildTrack.Api.Models;

public class Project
{
    public int Id { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public int CustomerId { get; set; }
    public int? LocationId { get; set; }
    public string ProjectAddress { get; set; } = string.Empty;
    public DateTime? StartDate { get; set; }
    public DateTime? EndDate { get; set; }
    public decimal? Budget { get; set; }
    public string? Description { get; set; }
    public bool IsArchived { get; set; } = false;
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }

    public int? AssignedUserId { get; set; }

    // Navigation
    public Customer Customer { get; set; } = null!;
    public Location? Location { get; set; }
    public User? AssignedUser { get; set; }
    public ICollection<Purchase> Purchases { get; set; } = new List<Purchase>();
    public ICollection<Expense> Expenses { get; set; } = new List<Expense>();
    public ICollection<CustomerPayment> CustomerPayments { get; set; } = new List<CustomerPayment>();
}
