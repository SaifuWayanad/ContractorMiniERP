using System.ComponentModel.DataAnnotations;

namespace BuildTrack.Api.DTOs;

public class ProjectDto
{
    public int Id { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public int CustomerId { get; set; }
    public string CustomerName { get; set; } = string.Empty;
    public int? LocationId { get; set; }
    public string? LocationName { get; set; }
    public string ProjectAddress { get; set; } = string.Empty;
    public DateTime? StartDate { get; set; }
    public DateTime? EndDate { get; set; }
    public decimal? Budget { get; set; }
    public string? Description { get; set; }
    public bool IsArchived { get; set; }
    public int? AssignedUserId { get; set; }
    public string? AssignedUserName { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

public class CreateProjectRequest
{
    [Required]
    [MaxLength(200)]
    public string ProjectName { get; set; } = string.Empty;
    [Required]
    public int CustomerId { get; set; }
    public int? LocationId { get; set; }
    [MaxLength(500)]
    public string ProjectAddress { get; set; } = string.Empty;
    public DateTime? StartDate { get; set; }
    public DateTime? EndDate { get; set; }
    public decimal? Budget { get; set; }
    public string? Description { get; set; }
    public int? AssignedUserId { get; set; }
}

public class UpdateProjectRequest
{
    [Required]
    [MaxLength(200)]
    public string ProjectName { get; set; } = string.Empty;
    [Required]
    public int CustomerId { get; set; }
    public int? LocationId { get; set; }
    [MaxLength(500)]
    public string ProjectAddress { get; set; } = string.Empty;
    public DateTime? StartDate { get; set; }
    public DateTime? EndDate { get; set; }
    public decimal? Budget { get; set; }
    public string? Description { get; set; }
    public bool IsArchived { get; set; }
    public int? AssignedUserId { get; set; }
}
