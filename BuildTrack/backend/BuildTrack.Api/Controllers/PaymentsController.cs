using BuildTrack.Api.Data;
using BuildTrack.Api.DTOs;
using BuildTrack.Api.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Controllers;

[ApiController]
[Route("api/payments")]
[Authorize]
public class PaymentsController : BaseController
{
    private readonly AppDbContext _db;

    public PaymentsController(AppDbContext db)
    {
        _db = db;
    }

    [HttpGet]
    public async Task<IActionResult> GetAll(
        [FromQuery] int? projectId,
        [FromQuery] int? customerId,
        [FromQuery] DateTime? startDate,
        [FromQuery] DateTime? endDate,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20,
        [FromQuery] bool includeVoided = false)
    {
        var query = _db.CustomerPayments
            .Include(cp => cp.Project).ThenInclude(p => p.Customer)
            .AsQueryable();

        if (!includeVoided)
            query = query.Where(cp => !cp.IsVoided);

        if (projectId.HasValue)
            query = query.Where(cp => cp.ProjectId == projectId.Value);

        if (customerId.HasValue)
            query = query.Where(cp => cp.Project.CustomerId == customerId.Value);

        if (startDate.HasValue)
            query = query.Where(cp => cp.PaymentDate >= startDate.Value);

        if (endDate.HasValue)
            query = query.Where(cp => cp.PaymentDate <= endDate.Value);

        var total = await query.CountAsync();
        var items = await query
            .OrderByDescending(cp => cp.PaymentDate)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .Select(cp => new PaymentDto
            {
                Id = cp.Id,
                ProjectId = cp.ProjectId,
                ProjectName = cp.Project.ProjectName,
                CustomerId = cp.Project.CustomerId,
                CustomerName = cp.Project.Customer != null ? cp.Project.Customer.CustomerName : string.Empty,
                PaymentDate = cp.PaymentDate,
                AmountReceived = cp.AmountReceived,
                PaymentMethod = cp.PaymentMethod,
                ReceiptReference = cp.ReceiptReference,
                Notes = cp.Notes,
                IsVoided = cp.IsVoided,
                CreatedAt = cp.CreatedAt,
                UpdatedAt = cp.UpdatedAt
            })
            .ToListAsync();

        return Ok(new PagedResult<PaymentDto> { Items = items, Total = total, Page = page, PageSize = pageSize });
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        var payment = await _db.CustomerPayments
            .Include(cp => cp.Project).ThenInclude(p => p.Customer)
            .FirstOrDefaultAsync(cp => cp.Id == id);

        if (payment == null) return NotFound();

        return Ok(new PaymentDto
        {
            Id = payment.Id,
            ProjectId = payment.ProjectId,
            ProjectName = payment.Project.ProjectName,
            CustomerId = payment.Project.CustomerId,
            CustomerName = payment.Project.Customer?.CustomerName ?? string.Empty,
            PaymentDate = payment.PaymentDate,
            AmountReceived = payment.AmountReceived,
            PaymentMethod = payment.PaymentMethod,
            ReceiptReference = payment.ReceiptReference,
            Notes = payment.Notes,
            IsVoided = payment.IsVoided,
            CreatedAt = payment.CreatedAt,
            UpdatedAt = payment.UpdatedAt
        });
    }

    [HttpPost]
    public async Task<IActionResult> Create([FromBody] CreatePaymentRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var payment = new CustomerPayment
        {
            ProjectId = request.ProjectId,
            PaymentDate = request.PaymentDate,
            AmountReceived = request.AmountReceived,
            PaymentMethod = request.PaymentMethod,
            ReceiptReference = request.ReceiptReference,
            Notes = request.Notes,
            IsVoided = false,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        _db.CustomerPayments.Add(payment);
        await _db.SaveChangesAsync();

        return CreatedAtAction(nameof(GetById), new { id = payment.Id }, new { id = payment.Id });
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(int id, [FromBody] UpdatePaymentRequest request)
    {
        if (!ModelState.IsValid)
            return ValidationProblem(ModelState);

        var payment = await _db.CustomerPayments.Include(cp => cp.Project).FirstOrDefaultAsync(cp => cp.Id == id);
        if (payment == null) return NotFound();

        if (payment.IsVoided)
            return Conflict(new { message = "Cannot update a voided payment." });

        payment.ProjectId = request.ProjectId;
        payment.PaymentDate = request.PaymentDate;
        payment.AmountReceived = request.AmountReceived;
        payment.PaymentMethod = request.PaymentMethod;
        payment.ReceiptReference = request.ReceiptReference;
        payment.Notes = request.Notes;
        payment.UpdatedAt = DateTime.UtcNow;

        await _db.SaveChangesAsync();
        return NoContent();
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
    {
        var payment = await _db.CustomerPayments.Include(cp => cp.Project).FirstOrDefaultAsync(cp => cp.Id == id);
        if (payment == null) return NotFound();

        payment.IsVoided = true;
        payment.UpdatedAt = DateTime.UtcNow;
        await _db.SaveChangesAsync();
        return NoContent();
    }
}
