using System.ComponentModel.DataAnnotations;

namespace BuildTrack.Api.DTOs;

public class LocationDto
{
    public int Id { get; set; }
    public string LocationName { get; set; } = string.Empty;
    public string Address { get; set; } = string.Empty;
    public string City { get; set; } = string.Empty;
    public string? Notes { get; set; }
    public bool IsArchived { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

public class CreateLocationRequest
{
    [Required]
    [MaxLength(200)]
    public string LocationName { get; set; } = string.Empty;
    [MaxLength(500)]
    public string Address { get; set; } = string.Empty;
    [MaxLength(100)]
    public string City { get; set; } = string.Empty;
    public string? Notes { get; set; }
}

public class UpdateLocationRequest
{
    [Required]
    [MaxLength(200)]
    public string LocationName { get; set; } = string.Empty;
    [MaxLength(500)]
    public string Address { get; set; } = string.Empty;
    [MaxLength(100)]
    public string City { get; set; } = string.Empty;
    public string? Notes { get; set; }
    public bool IsArchived { get; set; }
}
