using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace BuildTrack.Api.Migrations
{
    /// <inheritdoc />
    public partial class AddCreatedByUserToCustomerAndLocation : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.AddColumn<int>(
                name: "CreatedByUserId",
                table: "Locations",
                type: "INTEGER",
                nullable: true);

            migrationBuilder.AddColumn<int>(
                name: "CreatedByUserId",
                table: "Customers",
                type: "INTEGER",
                nullable: true);

            migrationBuilder.CreateIndex(
                name: "IX_Locations_CreatedByUserId",
                table: "Locations",
                column: "CreatedByUserId");

            migrationBuilder.CreateIndex(
                name: "IX_Customers_CreatedByUserId",
                table: "Customers",
                column: "CreatedByUserId");

            migrationBuilder.AddForeignKey(
                name: "FK_Customers_Users_CreatedByUserId",
                table: "Customers",
                column: "CreatedByUserId",
                principalTable: "Users",
                principalColumn: "Id",
                onDelete: ReferentialAction.SetNull);

            migrationBuilder.AddForeignKey(
                name: "FK_Locations_Users_CreatedByUserId",
                table: "Locations",
                column: "CreatedByUserId",
                principalTable: "Users",
                principalColumn: "Id",
                onDelete: ReferentialAction.SetNull);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropForeignKey(
                name: "FK_Customers_Users_CreatedByUserId",
                table: "Customers");

            migrationBuilder.DropForeignKey(
                name: "FK_Locations_Users_CreatedByUserId",
                table: "Locations");

            migrationBuilder.DropIndex(
                name: "IX_Locations_CreatedByUserId",
                table: "Locations");

            migrationBuilder.DropIndex(
                name: "IX_Customers_CreatedByUserId",
                table: "Customers");

            migrationBuilder.DropColumn(
                name: "CreatedByUserId",
                table: "Locations");

            migrationBuilder.DropColumn(
                name: "CreatedByUserId",
                table: "Customers");
        }
    }
}
