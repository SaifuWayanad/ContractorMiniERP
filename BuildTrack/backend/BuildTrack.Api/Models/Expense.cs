namespace BuildTrack.Api.Models;

public class Expense
{
    public int Id { get; set; }
    public int ProjectId { get; set; }
    public string ExpenseHeading { get; set; } = string.Empty;
    public string ExpenseDescription { get; set; } = string.Empty;
    public DateTime ExpenseDate { get; set; }
    public decimal Amount { get; set; }
    public string PaymentMethod { get; set; } = string.Empty;
    public string? ReferenceNumber { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; } = false;
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }

    // Navigation
    public Project Project { get; set; } = null!;
}
