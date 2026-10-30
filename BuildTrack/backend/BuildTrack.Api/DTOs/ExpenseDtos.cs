using System.ComponentModel.DataAnnotations;

namespace BuildTrack.Api.DTOs;

public class ExpenseDto
{
    public int Id { get; set; }
    public int ProjectId { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public string CustomerName { get; set; } = string.Empty;
    public string ExpenseHeading { get; set; } = string.Empty;
    public string ExpenseDescription { get; set; } = string.Empty;
    public DateTime ExpenseDate { get; set; }
    public decimal Amount { get; set; }
    public string PaymentMethod { get; set; } = string.Empty;
    public string? ReferenceNumber { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

public class CreateExpenseRequest
{
    [Required]
    public int ProjectId { get; set; }
    [Required]
    [MaxLength(200)]
    public string ExpenseHeading { get; set; } = string.Empty;
    [MaxLength(500)]
    public string ExpenseDescription { get; set; } = string.Empty;
    [Required]
    public DateTime ExpenseDate { get; set; }
    [Required]
    public decimal Amount { get; set; }
    [MaxLength(100)]
    public string PaymentMethod { get; set; } = string.Empty;
    [MaxLength(100)]
    public string? ReferenceNumber { get; set; }
    public string? Notes { get; set; }
}

public class UpdateExpenseRequest
{
    [Required]
    public int ProjectId { get; set; }
    [Required]
    [MaxLength(200)]
    public string ExpenseHeading { get; set; } = string.Empty;
    [MaxLength(500)]
    public string ExpenseDescription { get; set; } = string.Empty;
    [Required]
    public DateTime ExpenseDate { get; set; }
    [Required]
    public decimal Amount { get; set; }
    [MaxLength(100)]
    public string PaymentMethod { get; set; } = string.Empty;
    [MaxLength(100)]
    public string? ReferenceNumber { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; }
}
