namespace BuildTrack.Api.Models;

public class Customer
{
    public int Id { get; set; }
    public string CustomerName { get; set; } = string.Empty;
    public string PhoneNumber { get; set; } = string.Empty;
    public string Address { get; set; } = string.Empty;
    public int? LocationId { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; } = false;
    public int? CreatedByUserId { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }

    // Navigation
    public Location? Location { get; set; }
    public User? CreatedByUser { get; set; }
    public ICollection<Project> Projects { get; set; } = new List<Project>();
}
