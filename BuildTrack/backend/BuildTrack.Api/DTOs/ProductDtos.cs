using System.ComponentModel.DataAnnotations;

namespace BuildTrack.Api.DTOs;

public class ProductDto
{
    public int Id { get; set; }
    public string ProductName { get; set; } = string.Empty;
    public string UnitOfMeasure { get; set; } = string.Empty;
    public decimal? DefaultUnitRate { get; set; }
    public string? Description { get; set; }
    public bool IsActive { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

public class CreateProductRequest
{
    [Required]
    [MaxLength(200)]
    public string ProductName { get; set; } = string.Empty;
    [MaxLength(50)]
    public string UnitOfMeasure { get; set; } = string.Empty;
    public decimal? DefaultUnitRate { get; set; }
    public string? Description { get; set; }
}

public class UpdateProductRequest
{
    [Required]
    [MaxLength(200)]
    public string ProductName { get; set; } = string.Empty;
    [MaxLength(50)]
    public string UnitOfMeasure { get; set; } = string.Empty;
    public decimal? DefaultUnitRate { get; set; }
    public string? Description { get; set; }
    public bool IsActive { get; set; } = true;
}
