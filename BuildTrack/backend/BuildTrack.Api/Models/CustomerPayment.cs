namespace BuildTrack.Api.Models;

public class CustomerPayment
{
    public int Id { get; set; }
    public int ProjectId { get; set; }
    public DateTime PaymentDate { get; set; }
    public decimal AmountReceived { get; set; }
    public string PaymentMethod { get; set; } = string.Empty;
    public string? ReceiptReference { get; set; }
    public string? Notes { get; set; }
    public bool IsVoided { get; set; } = false;
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }

    // Navigation
    public Project Project { get; set; } = null!;
}
