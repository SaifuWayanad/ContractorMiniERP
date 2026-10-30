namespace BuildTrack.Api.Models;

public class Location
{
    public int Id { get; set; }
    public string LocationName { get; set; } = string.Empty;
    public string Address { get; set; } = string.Empty;
    public string City { get; set; } = string.Empty;
    public string? Notes { get; set; }
    public bool IsArchived { get; set; } = false;
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }

    // Navigation
    public ICollection<Customer> Customers { get; set; } = new List<Customer>();
    public ICollection<Project> Projects { get; set; } = new List<Project>();
}
