<?php

namespace Tests;

use App\Models\Contractor;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every test starts with the permissions and built-in roles.
     */
    protected bool $seed = true;

    protected string $seeder = AccessControlSeeder::class;

    /**
     * An active contractor and its owner user.
     *
     * Create contractor-owned records BEFORE calling actingAs(): while a contractor user is signed in,
     * new records are always assigned to that user's contractor (that is the isolation rule under test).
     *
     * @return array{0: Contractor, 1: User}
     */
    protected function contractorWithOwner(array $contractorAttributes = []): array
    {
        $contractor = Contractor::factory()->create($contractorAttributes);

        return [$contractor, User::factory()->ownerOf($contractor)->create()];
    }
}
