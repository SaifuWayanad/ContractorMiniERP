namespace BuildTrack.Api.DTOs;

public class OutstandingReportItem
{
    public int ProjectId { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public string CustomerName { get; set; } = string.Empty;
    public decimal? Budget { get; set; }
    public decimal TotalPurchases { get; set; }
    public decimal TotalExpenses { get; set; }
    public decimal TotalProjectCost { get; set; }
    public decimal TotalReceived { get; set; }
    public decimal? OutstandingAgainstBudget { get; set; }
    public decimal? RemainingBudget { get; set; }
    public bool IsOverBudget { get; set; }
}

public class ProjectFinancialSummary
{
    public int ProjectId { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public string CustomerName { get; set; } = string.Empty;
    public decimal? Budget { get; set; }
    public decimal TotalPurchases { get; set; }
    public decimal TotalExpenses { get; set; }
    public decimal TotalProjectCost { get; set; }
    public decimal TotalReceived { get; set; }
    public decimal? OutstandingAgainstBudget { get; set; }
    public decimal? RemainingBudget { get; set; }
    public bool IsOverBudget { get; set; }
}

// Full report DTOs
public class OutstandingReportRow
{
    public int ProjectId { get; set; }
    public string ProjectName { get; set; } = string.Empty;
    public int CustomerId { get; set; }
    public string CustomerName { get; set; } = string.Empty;
    public int? LocationId { get; set; }
    public string? LocationName { get; set; }
    public decimal? Budget { get; set; }
    public decimal TotalPurchases { get; set; }
    public decimal TotalExpenses { get; set; }
    public decimal TotalProjectCost { get; set; }
    public decimal TotalReceived { get; set; }
    public decimal? RemainingBudget { get; set; }
    public decimal? BalanceDue { get; set; }
    public decimal? BudgetUtilizationPct { get; set; }
    public bool IsOverBudget { get; set; }
    public bool IsArchived { get; set; }
    public DateTime? LastPaymentDate { get; set; }
}

public class OutstandingReportTotals
{
    public decimal TotalBudget { get; set; }
    public decimal TotalPurchases { get; set; }
    public decimal TotalExpenses { get; set; }
    public decimal TotalProjectCost { get; set; }
    public decimal TotalReceived { get; set; }
    public int ProjectCount { get; set; }
    public int OverBudgetCount { get; set; }
}

public class OutstandingReportResponse
{
    public List<OutstandingReportRow> Items { get; set; } = new();
    public OutstandingReportTotals Totals { get; set; } = new();
    public int Total { get; set; }
    public int Page { get; set; }
    public int PageSize { get; set; }
}

public class ProjectTransactionItem
{
    public string TransactionType { get; set; } = string.Empty;
    public int Id { get; set; }
    public DateTime Date { get; set; }
    public string? Reference { get; set; }
    public string Description { get; set; } = string.Empty;
    public decimal Amount { get; set; }
    public string? Notes { get; set; }
}
