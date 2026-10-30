using System.ComponentModel.DataAnnotations;

namespace BuildTrack.Api.DTOs;

public class UserDto
{
    public int Id { get; set; }
    public string Username { get; set; } = string.Empty;
    public string FullName { get; set; } = string.Empty;
    public string Email { get; set; } = string.Empty;
    public string Role { get; set; } = string.Empty;
    public bool IsActive { get; set; }
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

public class CreateUserRequest
{
    [Required]
    [MaxLength(100)]
    public string Username { get; set; } = string.Empty;
    [Required]
    public string Password { get; set; } = string.Empty;
    [Required]
    [MaxLength(200)]
    public string FullName { get; set; } = string.Empty;
    [MaxLength(200)]
    public string? Email { get; set; }
    [Required]
    public string Role { get; set; } = string.Empty;  // "Admin" or "Contractor"
}

public class UpdateUserRequest
{
    [Required]
    [MaxLength(200)]
    public string FullName { get; set; } = string.Empty;
    [MaxLength(200)]
    public string? Email { get; set; }
    [Required]
    public string Role { get; set; } = string.Empty;
    public bool IsActive { get; set; }
    public string? Password { get; set; }  // null = don't change
}
