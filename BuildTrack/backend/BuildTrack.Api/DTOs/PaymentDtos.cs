using System.ComponentModel.DataAnnotations;

namespace BuildTrack.Api.DTOs;

public class PaymentDto
{
    public int Id { get; set; }
    public int ProjectId { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public int? CustomerId { get; set; }
    public string CustomerName { get; set; } = string.Empty;
    public DateTime PaymentDate { get; set; }
    public decimal AmountReceived { get; set; }
    public string PaymentMethod { get; set; } = string.Empty;
    public string? ReceiptReference { get; set; }
    public string? Notes { get; set; }
    public bool IsVoided { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

public class CreatePaymentRequest
{
    [Required]
    public int ProjectId { get; set; }
    [Required]
    public DateTime PaymentDate { get; set; }
    [Required]
    public decimal AmountReceived { get; set; }
    [MaxLength(100)]
    public string PaymentMethod { get; set; } = string.Empty;
    [MaxLength(100)]
    public string? ReceiptReference { get; set; }
    public string? Notes { get; set; }
}

public class UpdatePaymentRequest
{
    [Required]
    public int ProjectId { get; set; }
    [Required]
    public DateTime PaymentDate { get; set; }
    [Required]
    public decimal AmountReceived { get; set; }
    [MaxLength(100)]
    public string PaymentMethod { get; set; } = string.Empty;
    [MaxLength(100)]
    public string? ReceiptReference { get; set; }
    public string? Notes { get; set; }
}
