<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A salary type a contractor can assign to employees (e.g. Daily, Monthly, Weekly).
 * Employees store the name string in employees.salary_type.
 */
#[Fillable(['name'])]
class EmployeeSalaryType extends Model
{
    use BelongsToContractor;

    /** @var list<string> */
    public const DEFAULTS = ['Daily', 'Monthly'];

    public static function seedDefaults(int $contractorId): void
    {
        foreach (self::DEFAULTS as $name) {
            $type = new self(['name' => $name]);
            $type->contractor_id = $contractorId;
            $type->save();
        }
    }
}
