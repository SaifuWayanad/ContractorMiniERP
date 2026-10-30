<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Product;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BuildTrackApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'username' => 'admin', 'password_hash' => Hash::make('admin123'), 'full_name' => 'Admin',
            'email' => '', 'role' => User::ADMIN, 'is_active' => true,
        ]);
    }

    private function contractor(string $name = 'contractor'): User
    {
        return User::create([
            'username' => $name, 'password_hash' => Hash::make('secret1'), 'full_name' => ucfirst($name),
            'email' => '', 'role' => User::CONTRACTOR, 'is_active' => true,
        ]);
    }

    private function customer(): Customer
    {
        return Customer::create(['customer_name' => 'Acme', 'phone_number' => '', 'address' => '', 'is_archived' => false]);
    }

    private function project(Customer $customer, array $overrides = []): Project
    {
        return Project::create($overrides + [
            'project_name' => 'Tower', 'customer_id' => $customer->id, 'project_address' => '',
            'budget' => 1000, 'is_archived' => false,
        ]);
    }

    public function test_login_returns_a_token_and_user_info(): void
    {
        $this->admin();

        $response = $this->postJson('/api/auth/login', ['username' => 'admin', 'password' => 'admin123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'expiresAt', 'user' => ['id', 'username', 'fullName', 'role', 'isActive']]);

        $this->withToken($response->json('token'))->getJson('/api/auth/me')
            ->assertOk()->assertJsonPath('username', 'admin');
    }

    public function test_wrong_password_and_inactive_users_are_rejected(): void
    {
        $this->admin();
        $this->postJson('/api/auth/login', ['username' => 'admin', 'password' => 'nope'])->assertStatus(401);

        User::where('username', 'admin')->update(['is_active' => false]);
        $this->postJson('/api/auth/login', ['username' => 'admin', 'password' => 'admin123'])->assertStatus(401);
    }

    public function test_protected_routes_need_a_token(): void
    {
        $this->getJson('/api/customers')->assertStatus(401);
    }

    public function test_contractor_creating_a_project_is_assigned_to_it(): void
    {
        $user = $this->contractor();
        $customer = $this->customer();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/projects', ['projectName' => 'Villa', 'customerId' => $customer->id])
            ->assertCreated()->json('id');

        $this->assertSame($user->id, Project::find($id)->assigned_user_id);
    }

    public function test_customer_with_active_projects_cannot_be_deleted(): void
    {
        Sanctum::actingAs($this->admin());
        $customer = $this->customer();
        $this->project($customer);

        $this->deleteJson("/api/customers/{$customer->id}")->assertStatus(409)
            ->assertJsonPath('message', 'Cannot delete customer with active projects.');
    }

    public function test_customer_without_projects_is_archived_on_delete(): void
    {
        Sanctum::actingAs($this->admin());
        $customer = $this->customer();

        $this->deleteJson("/api/customers/{$customer->id}")->assertNoContent();
        $this->assertTrue($customer->fresh()->is_archived);
    }

    public function test_location_in_use_cannot_be_deleted(): void
    {
        Sanctum::actingAs($this->admin());
        $location = Location::create(['location_name' => 'Yard', 'address' => '', 'city' => '', 'is_archived' => false]);
        $customer = $this->customer();
        $customer->update(['location_id' => $location->id]);

        $this->deleteJson("/api/locations/{$location->id}")->assertStatus(409);
    }

    public function test_purchase_line_total_is_quantity_times_unit_amount(): void
    {
        Sanctum::actingAs($this->admin());
        $project = $this->project($this->customer());
        $product = Product::create(['product_name' => 'Cement', 'unit_of_measure' => 'bag', 'is_active' => true]);

        $id = $this->postJson('/api/purchases', [
            'projectId' => $project->id, 'productId' => $product->id, 'purchaseDate' => '2026-10-20',
            'quantity' => 4, 'unitAmount' => 350.5, 'supplier' => 'Ultra',
        ])->assertCreated()->json('id');

        $this->assertEquals(1402.0, (float) Purchase::find($id)->line_total);
    }

    public function test_purchase_with_unknown_project_is_rejected(): void
    {
        Sanctum::actingAs($this->admin());
        $product = Product::create(['product_name' => 'Sand', 'unit_of_measure' => 'cft', 'is_active' => true]);

        $this->postJson('/api/purchases', [
            'projectId' => 999, 'productId' => $product->id, 'purchaseDate' => '2026-10-20',
            'quantity' => 1, 'unitAmount' => 1,
        ])->assertStatus(422);
    }

    public function test_product_used_by_a_purchase_cannot_be_deleted(): void
    {
        Sanctum::actingAs($this->admin());
        $project = $this->project($this->customer());
        $product = Product::create(['product_name' => 'Steel', 'unit_of_measure' => 'kg', 'is_active' => true]);
        Purchase::create([
            'project_id' => $project->id, 'product_id' => $product->id, 'purchase_date' => '2026-10-20',
            'quantity' => 1, 'unit_amount' => 10, 'line_total' => 10, 'supplier' => '', 'is_archived' => false,
        ]);

        $this->deleteJson("/api/products/{$product->id}")->assertStatus(409);
    }

    public function test_voided_payment_cannot_be_updated_and_delete_voids_it(): void
    {
        Sanctum::actingAs($this->admin());
        $project = $this->project($this->customer());
        $payment = CustomerPayment::create([
            'project_id' => $project->id, 'payment_date' => '2026-10-21', 'amount_received' => 300,
            'payment_method' => 'Cash', 'is_voided' => false,
        ]);

        $this->putJson("/api/payments/{$payment->id}", [
            'projectId' => $project->id, 'paymentDate' => '2026-10-21', 'amountReceived' => 400,
        ])->assertNoContent();

        $this->deleteJson("/api/payments/{$payment->id}")->assertNoContent();
        $this->assertTrue($payment->fresh()->is_voided);

        $this->putJson("/api/payments/{$payment->id}", [
            'projectId' => $project->id, 'paymentDate' => '2026-10-21', 'amountReceived' => 1,
        ])->assertStatus(409);
    }

    public function test_project_summary_and_outstanding_report_totals(): void
    {
        Sanctum::actingAs($this->admin());
        $customer = $this->customer();
        $project = $this->project($customer, ['budget' => 1000]);
        $product = Product::create(['product_name' => 'Brick', 'unit_of_measure' => 'nos', 'is_active' => true]);

        Purchase::create([
            'project_id' => $project->id, 'product_id' => $product->id, 'purchase_date' => '2026-10-20',
            'quantity' => 100, 'unit_amount' => 5, 'line_total' => 500, 'supplier' => '', 'is_archived' => false,
        ]);
        Expense::create([
            'project_id' => $project->id, 'expense_heading' => 'Labour', 'expense_description' => '',
            'expense_date' => '2026-10-21', 'amount' => 200, 'payment_method' => '', 'is_archived' => false,
        ]);
        CustomerPayment::create([
            'project_id' => $project->id, 'payment_date' => '2026-10-22', 'amount_received' => 300,
            'payment_method' => 'Bank', 'is_voided' => false,
        ]);

        $this->getJson("/api/projects/{$project->id}/summary")->assertOk()
            ->assertJsonPath('totalProjectCost', 700)
            ->assertJsonPath('totalReceived', 300)
            ->assertJsonPath('remainingBudget', 300)
            ->assertJsonPath('isOverBudget', false);

        $this->getJson('/api/reports/outstanding')->assertOk()
            ->assertJsonPath('totals.totalPurchases', 500)
            ->assertJsonPath('totals.totalExpenses', 200)
            ->assertJsonPath('totals.totalReceived', 300)
            ->assertJsonPath('items.0.balanceDue', 700);
    }

    public function test_contractor_cannot_see_another_users_project_transactions(): void
    {
        $owner = $this->contractor('owner');
        $other = $this->contractor('other');
        $project = $this->project($this->customer(), ['assigned_user_id' => $owner->id]);

        Sanctum::actingAs($other);
        $this->getJson("/api/reports/outstanding/{$project->id}/transactions")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->getJson("/api/reports/outstanding/{$project->id}/transactions")->assertOk();
    }

    public function test_user_management_is_admin_only(): void
    {
        Sanctum::actingAs($this->contractor());

        $this->getJson('/api/users')->assertForbidden();
        $this->getJson('/api/users/contractors')->assertForbidden();
    }

    public function test_user_rules_match_the_dotnet_api(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);

        $this->postJson('/api/users', [
            'username' => 'x', 'password' => 'p', 'fullName' => 'X', 'role' => 'Boss',
        ])->assertStatus(400);

        $this->postJson('/api/users', [
            'username' => 'admin', 'password' => 'p', 'fullName' => 'X', 'role' => 'Contractor',
        ])->assertStatus(409);

        $this->putJson("/api/users/{$admin->id}", [
            'fullName' => 'Admin', 'role' => 'Admin', 'isActive' => false,
        ])->assertStatus(409);

        $this->deleteJson("/api/users/{$admin->id}")->assertStatus(409);

        $this->postJson('/api/users', [
            'username' => 'ravi', 'password' => 'secret1', 'fullName' => 'Ravi', 'role' => 'Contractor',
        ])->assertCreated()->assertJsonPath('role', 'Contractor');

        $this->getJson('/api/users/contractors')->assertOk()->assertJsonPath('0.username', 'ravi');
    }

    public function test_dashboard_totals(): void
    {
        Sanctum::actingAs($this->admin());
        $project = $this->project($this->customer());
        Expense::create([
            'project_id' => $project->id, 'expense_heading' => 'Fuel', 'expense_description' => '',
            'expense_date' => '2026-10-21', 'amount' => 150, 'payment_method' => '', 'is_archived' => false,
        ]);

        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('totalProjects', 1)
            ->assertJsonPath('totalCustomers', 1)
            ->assertJsonPath('totalExpenses', 150)
            ->assertJsonPath('totalOutstanding', 150)
            ->assertJsonCount(1, 'recentProjects');
    }
}
