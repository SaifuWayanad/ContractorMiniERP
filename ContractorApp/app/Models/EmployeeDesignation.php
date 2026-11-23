<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A designation/role a contractor can assign to employees (e.g. Mason, Supervisor).
 * Employees store the name in employees.designation, so renaming never breaks existing records.
 */
#[Fillable(['name'])]
class EmployeeDesignation extends Model
{
    use BelongsToContractor;

    /** @var list<string> */
    public const DEFAULTS = ['Supervisor', 'Mason', 'Carpenter', 'Electrician', 'Plumber', 'Helper'];

    public static function seedDefaults(int $contractorId): void
    {
        foreach (self::DEFAULTS as $name) {
            $designation = new self(['name' => $name]);
            $designation->contractor_id = $contractorId;
            $designation->save();
        }
    }
}
