using System.ComponentModel.DataAnnotations;

namespace BuildTrack.Api.DTOs;

public class CustomerDto
{
    public int Id { get; set; }
    public string CustomerName { get; set; } = string.Empty;
    public string PhoneNumber { get; set; } = string.Empty;
    public string Address { get; set; } = string.Empty;
    public int? LocationId { get; set; }
    public string? LocationName { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

public class CreateCustomerRequest
{
    [Required]
    [MaxLength(200)]
    public string CustomerName { get; set; } = string.Empty;
    [MaxLength(50)]
    public string PhoneNumber { get; set; } = string.Empty;
    [MaxLength(500)]
    public string Address { get; set; } = string.Empty;
    public int? LocationId { get; set; }
    public string? Notes { get; set; }
}

public class UpdateCustomerRequest
{
    [Required]
    [MaxLength(200)]
    public string CustomerName { get; set; } = string.Empty;
    [MaxLength(50)]
    public string PhoneNumber { get; set; } = string.Empty;
    [MaxLength(500)]
    public string Address { get; set; } = string.Empty;
    public int? LocationId { get; set; }
    public string? Notes { get; set; }
    public bool IsArchived { get; set; }
}
