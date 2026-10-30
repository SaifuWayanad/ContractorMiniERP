using System.ComponentModel.DataAnnotations;

namespace BuildTrack.Api.DTOs;

public class PurchaseDto
{
    public int Id { get; set; }
    public int ProjectId { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public string CustomerName { get; set; } = string.Empty;
    public int ProductId { get; set; }
    public string ProductName { get; set; } = string.Empty;
    public DateTime PurchaseDate { get; set; }
    public decimal Quantity { get; set; }
    public decimal UnitAmount { get; set; }
    public decimal LineTotal { get; set; }
    public string Supplier { get; set; } = string.Empty;
    public string? InvoiceReference { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

public class CreatePurchaseRequest
{
    [Required]
    [Range(1, int.MaxValue, ErrorMessage = "A valid project must be selected.")]
    public int ProjectId { get; set; }
    [Required]
    [Range(1, int.MaxValue, ErrorMessage = "A valid product must be selected.")]
    public int ProductId { get; set; }
    [Required]
    public DateTime PurchaseDate { get; set; }
    public decimal Quantity { get; set; }
    public decimal UnitAmount { get; set; }
    public decimal? LineTotal { get; set; }
    [MaxLength(200)]
    public string Supplier { get; set; } = string.Empty;
    [MaxLength(100)]
    public string? InvoiceReference { get; set; }
    public string? Notes { get; set; }
}

public class UpdatePurchaseRequest
{
    [Required]
    public int ProjectId { get; set; }
    [Required]
    public int ProductId { get; set; }
    [Required]
    public DateTime PurchaseDate { get; set; }
    public decimal Quantity { get; set; }
    public decimal UnitAmount { get; set; }
    public decimal? LineTotal { get; set; }
    [MaxLength(200)]
    public string Supplier { get; set; } = string.Empty;
    [MaxLength(100)]
    public string? InvoiceReference { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; }
}
