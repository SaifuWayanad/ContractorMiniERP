namespace BuildTrack.Api.Models;

public class Purchase
{
    public int Id { get; set; }
    public int ProjectId { get; set; }
    public int ProductId { get; set; }
    public DateTime PurchaseDate { get; set; }
    public decimal Quantity { get; set; }
    public decimal UnitAmount { get; set; }
    public decimal LineTotal { get; set; }
    public string Supplier { get; set; } = string.Empty;
    public string? InvoiceReference { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; } = false;
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }

    // Navigation
    public Project Project { get; set; } = null!;
    public Product Product { get; set; } = null!;
}
