<?php

namespace Database\Seeders;

use App\Enums\ClientStatus;
use App\Enums\ContractorStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserStatus;
use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\Role;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts and data for local development. Every account's password is "password".
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->user(null, Role::SUPER_ADMIN, 'Priya Menon', 'admin@contractorapp.test');
        $this->user(null, Role::ADMIN, 'Rahul Nair', 'manager@contractorapp.test');

        $this->seedContractor(
            [
                'company_name' => 'BuildRight Contracting', 'contact_person' => 'John Mathew', 'email' => 'office@buildright.test',
                'phone' => '9847000001', 'address' => '12 MG Road', 'city' => 'Kochi', 'state' => 'Kerala', 'postal_code' => '682016',
                'website' => 'https://buildright.example', 'tax_number' => '32ABCDE1234F1Z5', 'license_number' => 'KL-CON-2019-0457',
            ],
            owner: ['John Mathew', 'john@buildright.test'],
            staff: ['Anu Joseph', 'anu@buildright.test'],
            clients: [
                [
                    'client' => ['name' => 'Thomas Varghese', 'company' => 'Green Valley Developers', 'contact_person' => 'Thomas Varghese', 'phone' => '9847012345', 'email' => 'thomas@greenvalley.test', 'city' => 'Kakkanad'],
                    'projects' => [
                        [
                            'project' => ['name' => 'Green Valley Villas', 'type_of_work' => 'Residential – Villas', 'status' => ProjectStatus::Active, 'budget' => 32000000, 'brand_preferred' => 'UltraTech', 'expected_qty' => 4500, 'specification_details' => 'OPC 53 grade for slabs, PPC for plastering', 'description' => '12 villas with shared clubhouse. Foundation work done for 8.'],
                            'started_days_ago' => 150, 'ends_in_days' => 60,
                            'location' => ['name' => 'Villa plots', 'address' => 'Survey No. 112, Kakkanad–Pallikkara Rd', 'city' => 'Kakkanad', 'district' => 'Ernakulam', 'state' => 'Kerala', 'postal_code' => '682030', 'latitude' => 10.0159, 'longitude' => 76.3419],
                            'works' => [
                                ['Foundation pour – Villas 9-12', WorkPriority::High, WorkStatus::InProgress, 2],
                                ['Site inspection with engineer', WorkPriority::Medium, WorkStatus::Pending, 1],
                                ['Plastering – Villas 1-4', WorkPriority::Medium, WorkStatus::Completed, -6],
                            ],
                        ],
                    ],
                ],
                [
                    'client' => ['name' => 'Abdul Rasheed', 'company' => 'Harbour Homes', 'contact_person' => 'Abdul Rasheed', 'phone' => '9895123456', 'email' => 'rasheed@harbourhomes.test', 'city' => 'Kozhikode'],
                    'projects' => [
                        [
                            'project' => ['name' => 'Harbour View Apartments', 'type_of_work' => 'Residential – Apartment', 'status' => ProjectStatus::Planning, 'budget' => 85000000, 'brand_preferred' => 'Ramco', 'expected_qty' => 12000, 'specification_details' => 'G+9, RCC framed structure', 'notes' => 'Owner comparing quotes from two suppliers.'],
                            'started_days_ago' => 20, 'ends_in_days' => 420,
                            'location' => ['name' => 'Beach Road plot', 'address' => 'Near Beach Rd, Kozhikode South', 'city' => 'Kozhikode', 'district' => 'Kozhikode', 'state' => 'Kerala', 'postal_code' => '673001'],
                            'works' => [
                                ['Soil testing', WorkPriority::High, WorkStatus::Pending, 5],
                                ['Submit building permit', WorkPriority::Urgent, WorkStatus::Pending, 3],
                            ],
                        ],
                    ],
                ],
                [
                    'client' => ['name' => "St. Mary's School", 'company' => "St. Mary's Educational Trust", 'contact_person' => 'Fr. Joseph Mathew', 'phone' => '9446098765', 'email' => 'office@stmarys.test', 'city' => 'Pala'],
                    'projects' => [
                        [
                            'project' => ['name' => "St. Mary's School Extension", 'type_of_work' => 'Institutional', 'status' => ProjectStatus::Completed, 'budget' => 9500000, 'brand_preferred' => 'ACC', 'expected_qty' => 2200, 'specification_details' => 'New classroom block, 3 floors'],
                            'started_days_ago' => 200, 'ends_in_days' => -10, 'completed_days_ago' => 8,
                            'location' => ['name' => 'School campus', 'address' => 'Church Rd, Pala', 'city' => 'Pala', 'district' => 'Kottayam', 'state' => 'Kerala', 'postal_code' => '686575'],
                            'works' => [
                                ['Final handover inspection', WorkPriority::High, WorkStatus::Completed, -9],
                            ],
                        ],
                        [
                            'project' => ['name' => "St. Mary's Chapel Renovation", 'type_of_work' => 'Renovation', 'status' => ProjectStatus::OnHold, 'budget' => 2800000, 'expected_qty' => 400, 'notes' => 'Waiting for trust committee approval.'],
                            'started_days_ago' => 40, 'ends_in_days' => 5,
                            'location' => ['name' => 'Chapel', 'address' => 'Church Rd, Pala', 'city' => 'Pala', 'district' => 'Kottayam', 'state' => 'Kerala', 'postal_code' => '686575'],
                            'works' => [],
                        ],
                    ],
                ],
                [
                    'client' => ['name' => 'Meera Pillai', 'company' => null, 'contact_person' => 'Meera Pillai', 'phone' => '9744556677', 'email' => 'meera.pillai@example.test', 'city' => 'Thrissur', 'status' => ClientStatus::Inactive],
                    'projects' => [],
                ],
            ],
        );

        $this->seedContractor(
            [
                'company_name' => 'Coastal Constructions', 'contact_person' => 'Prakash Shetty', 'email' => 'info@coastal.test',
                'phone' => '9880000002', 'address' => 'Plot 7, Baikampady Industrial Area', 'city' => 'Mangaluru', 'state' => 'Karnataka', 'postal_code' => '575011',
                'tax_number' => '29FGHIJ5678K1Z2', 'license_number' => 'KA-CON-2017-1123',
            ],
            owner: ['Prakash Shetty', 'prakash@coastal.test'],
            staff: null,
            clients: [
                [
                    'client' => ['name' => 'Metro Logistics', 'company' => 'Metro Logistics Pvt Ltd', 'contact_person' => 'Kiran Shetty', 'phone' => '9880011223', 'email' => 'projects@metrologistics.test', 'city' => 'Mangaluru'],
                    'projects' => [
                        [
                            'project' => ['name' => 'Coastal Warehouse Complex', 'type_of_work' => 'Industrial – Warehouse', 'status' => ProjectStatus::Active, 'budget' => 46000000, 'brand_preferred' => 'Dalmia', 'expected_qty' => 8000, 'specification_details' => 'PEB structure with RCC flooring'],
                            'started_days_ago' => 90, 'ends_in_days' => 75,
                            'location' => ['name' => 'Warehouse site', 'address' => 'Plot 7, Baikampady Industrial Area', 'city' => 'Mangaluru', 'district' => 'Dakshina Kannada', 'state' => 'Karnataka', 'postal_code' => '575011'],
                            'works' => [
                                ['Roof sheeting', WorkPriority::High, WorkStatus::InProgress, 10],
                                ['Floor hardener application', WorkPriority::Medium, WorkStatus::Pending, 20],
                            ],
                        ],
                    ],
                ],
                [
                    'client' => ['name' => 'Nandini Rao', 'company' => 'Whitefield Tech Parks', 'contact_person' => 'Nandini Rao', 'phone' => '9900223344', 'email' => 'nandini@wtp.test', 'city' => 'Bengaluru'],
                    'projects' => [
                        [
                            'project' => ['name' => 'Whitefield Tech Park – Block C', 'type_of_work' => 'Commercial – Office', 'status' => ProjectStatus::Active, 'budget' => 150000000, 'brand_preferred' => 'UltraTech', 'expected_qty' => 25000, 'specification_details' => 'Ready-mix concrete M30 for slabs'],
                            'started_days_ago' => 60, 'ends_in_days' => 12,
                            'location' => ['name' => 'Block C', 'address' => 'ITPL Main Rd, Whitefield', 'city' => 'Bengaluru', 'district' => 'Bengaluru Urban', 'state' => 'Karnataka', 'postal_code' => '560066'],
                            'works' => [
                                ['Slab casting – 6th floor', WorkPriority::Urgent, WorkStatus::Pending, 2],
                            ],
                        ],
                    ],
                ],
                [
                    'client' => ['name' => 'Ramesh Gowda', 'company' => null, 'contact_person' => 'Ramesh Gowda', 'phone' => '9845566778', 'email' => 'ramesh.gowda@example.test', 'city' => 'Mysuru'],
                    'projects' => [
                        [
                            'project' => ['name' => 'Individual House – Ramesh', 'type_of_work' => 'Residential – House', 'status' => ProjectStatus::Planning, 'budget' => 4200000, 'brand_preferred' => 'Birla A1', 'expected_qty' => 650, 'description' => 'Ground + 1 floor, 2,400 sq ft.'],
                            'started_days_ago' => 10, 'ends_in_days' => 180,
                            'location' => ['name' => 'House plot', 'address' => '4th Cross, Saraswathipuram', 'city' => 'Mysuru', 'district' => 'Mysuru', 'state' => 'Karnataka', 'postal_code' => '570009'],
                            'works' => [],
                        ],
                    ],
                ],
            ],
        );

        $this->seedContractor(
            [
                'company_name' => 'Deccan Builders', 'contact_person' => 'Srinivas Reddy', 'email' => 'contact@deccan.test',
                'phone' => '9866000003', 'address' => 'Financial District', 'city' => 'Hyderabad', 'state' => 'Telangana', 'postal_code' => '500032',
            ],
            owner: ['Srinivas Reddy', 'srinivas@deccan.test'],
            staff: null,
            clients: [
                [
                    'client' => ['name' => 'Gachibowli Residency Association', 'company' => null, 'contact_person' => 'Srinivas Reddy', 'phone' => '9866554433', 'email' => 'gra@example.test', 'city' => 'Hyderabad'],
                    'projects' => [
                        [
                            'project' => ['name' => 'Gachibowli Residency', 'type_of_work' => 'Residential – Apartment', 'status' => ProjectStatus::Planning, 'budget' => 210000000, 'expected_qty' => 30000, 'specification_details' => 'Two towers, G+14'],
                            'started_days_ago' => 30, 'ends_in_days' => 600,
                            'location' => ['name' => 'Tower site', 'address' => 'Financial District, Nanakramguda', 'city' => 'Hyderabad', 'district' => 'Rangareddy', 'state' => 'Telangana', 'postal_code' => '500032'],
                            'works' => [],
                        ],
                    ],
                ],
            ],
            status: ContractorStatus::Inactive,
        );
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array{0: string, 1: string}  $owner
     * @param  array{0: string, 1: string}|null  $staff
     * @param  list<array<string, mixed>>  $clients
     */
    private function seedContractor(array $details, array $owner, ?array $staff, array $clients, ContractorStatus $status = ContractorStatus::Active): void
    {
        $contractor = new Contractor($details);
        $contractor->status = $status;
        $contractor->created_at = now()->subMonths(8);
        $contractor->save();

        $ownerUser = $this->user($contractor, Role::CONTRACTOR_OWNER, ...$owner);
        if ($staff !== null) {
            $this->user($contractor, Role::CONTRACTOR_STAFF, ...$staff);
        }

        foreach ($clients as $index => $clientData) {
            $clientCreatedAt = now()->subDays(220 - $index * 25);
            $client = new Client(['country' => 'India', 'state' => $details['state'], 'status' => ClientStatus::Active, ...$clientData['client']]);
            $client->contractor_id = $contractor->id;
            $client->created_at = $clientCreatedAt;
            $client->save();
            $this->log($ownerUser, $client, "{$ownerUser->name} created client \"{$client->name}\"", $clientCreatedAt);

            foreach ($clientData['projects'] as $projectData) {
                $this->seedProject($contractor, $client, $ownerUser, $projectData);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedProject(Contractor $contractor, Client $client, User $owner, array $data): void
    {
        $createdAt = now()->subDays($data['started_days_ago'] + 7);

        $project = new Project([
            ...$data['project'],
            'client_id' => $client->id,
            'project_number' => sprintf('PRJ-%04d', Project::withoutGlobalScopes()->where('contractor_id', $contractor->id)->withTrashed()->count() + 1),
            'start_date' => today()->subDays($data['started_days_ago']),
            'expected_end_date' => today()->addDays($data['ends_in_days']),
            'actual_end_date' => isset($data['completed_days_ago']) ? today()->subDays($data['completed_days_ago']) : null,
        ]);
        $project->contractor_id = $contractor->id;
        $project->created_at = $createdAt;
        $project->save();
        $this->log($owner, $project, "{$owner->name} created project \"{$project->name}\"", $createdAt);

        $location = new ProjectLocation(['country' => 'India', 'contact_person' => $client->contact_person, 'contact_phone' => $client->phone, ...$data['location']]);
        $location->contractor_id = $contractor->id;
        $location->project_id = $project->id;
        $location->created_at = $createdAt->copy()->addHour();
        $location->save();
        $this->log($owner, $location, "{$owner->name} created location \"{$location->name}\"", $location->created_at);

        foreach ($data['works'] as [$title, $priority, $status, $dueInDays]) {
            $work = new Work(['title' => $title, 'priority' => $priority, 'status' => $status, 'start_date' => today()->subDays(3), 'due_date' => today()->addDays($dueInDays)]);
            $work->contractor_id = $contractor->id;
            $work->project_id = $project->id;
            $work->client_id = $client->id;
            $work->created_at = now()->subDays(max(1, 14 - $dueInDays));
            $work->completed_at = $status === WorkStatus::Completed ? today()->addDays($dueInDays)->setTime(16, 30) : null;
            $work->save();
            $this->log($owner, $work, "{$owner->name} created work \"{$work->title}\"", $work->created_at);

            if ($status === WorkStatus::Completed) {
                $this->log($owner, $work, "{$owner->name} marked \"{$work->title}\" as completed", $work->completed_at, 'completed');
            }
        }

        if ($project->status === ProjectStatus::Completed) {
            $this->log($owner, $project, "{$owner->name} marked \"{$project->name}\" as completed", now()->subDays($data['completed_days_ago']), 'completed');
        }
    }

    private function user(?Contractor $contractor, string $roleSlug, string $name, string $email): User
    {
        $username = strtolower(str_replace(' ', '.', $name));
        $user = new User(['name' => $name, 'username' => $username, 'email' => $email, 'password' => Hash::make('password')]);
        $user->contractor_id = $contractor?->id;
        $user->role_id = Role::where('slug', $roleSlug)->value('id');
        $user->status = UserStatus::Active;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function log(User $user, Project|Client|ProjectLocation|Work $subject, string $description, Carbon $at, string $action = 'created'): void
    {
        $log = new ActivityLog([
            'action' => $action,
            'entity_type' => str($subject::class)->classBasename()->snake()->value(),
            'entity_id' => $subject->id,
            'description' => $description,
            'ip_address' => '127.0.0.1',
        ]);
        $log->user_id = $user->id;
        $log->contractor_id = $subject->contractor_id;
        $log->created_at = $at;
        $log->save();
    }
}
