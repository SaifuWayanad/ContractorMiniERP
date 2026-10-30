using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;

namespace BuildTrack.Api.Services;

public interface IAuthService
{
    Task<LoginResponse?> LoginAsync(string username, string password);
    Task<User?> GetUserByIdAsync(int id);
    Task<bool> ChangePasswordAsync(int userId, string currentPassword, string newPassword);
    string HashPassword(string password);
    bool VerifyPassword(string password, string hash);
}
