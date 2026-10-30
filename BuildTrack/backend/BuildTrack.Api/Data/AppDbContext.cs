using BuildTrack.Api.Models;
using Microsoft.EntityFrameworkCore;

namespace BuildTrack.Api.Data;

public class AppDbContext : DbContext
{
    public AppDbContext(DbContextOptions<AppDbContext> options) : base(options) { }

    public DbSet<User> Users => Set<User>();
    public DbSet<Customer> Customers => Set<Customer>();
    public DbSet<Location> Locations => Set<Location>();
    public DbSet<Project> Projects => Set<Project>();
    public DbSet<Product> Products => Set<Product>();
    public DbSet<Purchase> Purchases => Set<Purchase>();
    public DbSet<Expense> Expenses => Set<Expense>();
    public DbSet<CustomerPayment> CustomerPayments => Set<CustomerPayment>();

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        base.OnModelCreating(modelBuilder);

        // User
        modelBuilder.Entity<User>(e =>
        {
            e.HasKey(u => u.Id);
            e.HasIndex(u => u.Username).IsUnique();
            e.Property(u => u.Username).HasMaxLength(100).IsRequired();
            e.Property(u => u.PasswordHash).IsRequired();
            e.Property(u => u.FullName).HasMaxLength(200).IsRequired();
            e.Property(u => u.Email).HasMaxLength(200).IsRequired();
            e.Property(u => u.Role).HasMaxLength(20).IsRequired();
            e.Property(u => u.IsActive).HasDefaultValue(true);
            e.Property(u => u.CreatedAt).HasDefaultValueSql("datetime('now')");
            e.Property(u => u.UpdatedAt).HasDefaultValueSql("datetime('now')");
        });

        // Location
        modelBuilder.Entity<Location>(e =>
        {
            e.HasKey(l => l.Id);
            e.Property(l => l.LocationName).HasMaxLength(200).IsRequired();
            e.Property(l => l.Address).HasMaxLength(500);
            e.Property(l => l.City).HasMaxLength(100);
            e.Property(l => l.CreatedAt).HasDefaultValueSql("datetime('now')");
            e.Property(l => l.UpdatedAt).HasDefaultValueSql("datetime('now')");
        });

        // Customer
        modelBuilder.Entity<Customer>(e =>
        {
            e.HasKey(c => c.Id);
            e.Property(c => c.CustomerName).HasMaxLength(200).IsRequired();
            e.Property(c => c.PhoneNumber).HasMaxLength(50);
            e.Property(c => c.Address).HasMaxLength(500);
            e.Property(c => c.CreatedAt).HasDefaultValueSql("datetime('now')");
            e.Property(c => c.UpdatedAt).HasDefaultValueSql("datetime('now')");
            e.HasOne(c => c.Location)
             .WithMany(l => l.Customers)
             .HasForeignKey(c => c.LocationId)
             .OnDelete(DeleteBehavior.SetNull);
        });

        // Project
        modelBuilder.Entity<Project>(e =>
        {
            e.HasKey(p => p.Id);
            e.Property(p => p.ProjectName).HasMaxLength(200).IsRequired();
            e.Property(p => p.ProjectAddress).HasMaxLength(500);
            e.Property(p => p.Budget).HasColumnType("decimal(18,2)");
            e.Property(p => p.CreatedAt).HasDefaultValueSql("datetime('now')");
            e.Property(p => p.UpdatedAt).HasDefaultValueSql("datetime('now')");
            e.HasOne(p => p.Customer)
             .WithMany(c => c.Projects)
             .HasForeignKey(p => p.CustomerId)
             .OnDelete(DeleteBehavior.Restrict);
            e.HasOne(p => p.Location)
             .WithMany(l => l.Projects)
             .HasForeignKey(p => p.LocationId)
             .OnDelete(DeleteBehavior.SetNull);
            e.HasOne(p => p.AssignedUser)
             .WithMany(u => u.AssignedProjects)
             .HasForeignKey(p => p.AssignedUserId)
             .OnDelete(DeleteBehavior.SetNull);
        });

        // Product
        modelBuilder.Entity<Product>(e =>
        {
            e.HasKey(p => p.Id);
            e.Property(p => p.ProductName).HasMaxLength(200).IsRequired();
            e.Property(p => p.UnitOfMeasure).HasMaxLength(50);
            e.Property(p => p.DefaultUnitRate).HasColumnType("decimal(18,2)");
            e.Property(p => p.CreatedAt).HasDefaultValueSql("datetime('now')");
            e.Property(p => p.UpdatedAt).HasDefaultValueSql("datetime('now')");
        });

        // Purchase
        modelBuilder.Entity<Purchase>(e =>
        {
            e.HasKey(p => p.Id);
            e.Property(p => p.Quantity).HasColumnType("decimal(18,4)");
            e.Property(p => p.UnitAmount).HasColumnType("decimal(18,2)");
            e.Property(p => p.LineTotal).HasColumnType("decimal(18,2)");
            e.Property(p => p.Supplier).HasMaxLength(200);
            e.Property(p => p.InvoiceReference).HasMaxLength(100);
            e.Property(p => p.CreatedAt).HasDefaultValueSql("datetime('now')");
            e.Property(p => p.UpdatedAt).HasDefaultValueSql("datetime('now')");
            e.HasOne(p => p.Project)
             .WithMany(pr => pr.Purchases)
             .HasForeignKey(p => p.ProjectId)
             .OnDelete(DeleteBehavior.Restrict);
            e.HasOne(p => p.Product)
             .WithMany(pr => pr.Purchases)
             .HasForeignKey(p => p.ProductId)
             .OnDelete(DeleteBehavior.Restrict);
        });

        // Expense
        modelBuilder.Entity<Expense>(e =>
        {
            e.HasKey(ex => ex.Id);
            e.Property(ex => ex.ExpenseHeading).HasMaxLength(200).IsRequired();
            e.Property(ex => ex.ExpenseDescription).HasMaxLength(500);
            e.Property(ex => ex.Amount).HasColumnType("decimal(18,2)");
            e.Property(ex => ex.PaymentMethod).HasMaxLength(100);
            e.Property(ex => ex.ReferenceNumber).HasMaxLength(100);
            e.Property(ex => ex.CreatedAt).HasDefaultValueSql("datetime('now')");
            e.Property(ex => ex.UpdatedAt).HasDefaultValueSql("datetime('now')");
            e.HasOne(ex => ex.Project)
             .WithMany(p => p.Expenses)
             .HasForeignKey(ex => ex.ProjectId)
             .OnDelete(DeleteBehavior.Restrict);
        });

        // CustomerPayment
        modelBuilder.Entity<CustomerPayment>(e =>
        {
            e.HasKey(cp => cp.Id);
            e.Property(cp => cp.AmountReceived).HasColumnType("decimal(18,2)");
            e.Property(cp => cp.PaymentMethod).HasMaxLength(100);
            e.Property(cp => cp.ReceiptReference).HasMaxLength(100);
            e.Property(cp => cp.CreatedAt).HasDefaultValueSql("datetime('now')");
            e.Property(cp => cp.UpdatedAt).HasDefaultValueSql("datetime('now')");
            e.HasOne(cp => cp.Project)
             .WithMany(p => p.CustomerPayments)
             .HasForeignKey(cp => cp.ProjectId)
             .OnDelete(DeleteBehavior.Restrict);
        });
    }
}
