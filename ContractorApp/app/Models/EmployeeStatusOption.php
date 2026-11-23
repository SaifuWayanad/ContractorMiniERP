<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * An employment status a contractor can assign to employees (e.g. Active, Inactive, On Leave).
 * Employees store the name string in employees.status.
 */
#[Fillable(['name'])]
class EmployeeStatusOption extends Model
{
    use BelongsToContractor;

    protected $table = 'employee_statuses';

    /** @var list<string> */
    public const DEFAULTS = ['Active', 'Inactive', 'On Leave', 'Terminated'];

    public static function seedDefaults(int $contractorId): void
    {
        foreach (self::DEFAULTS as $name) {
            $status = new self(['name' => $name]);
            $status->contractor_id = $contractorId;
            $status->save();
        }
    }

    /**
     * CSS badge class for well-known status names.
     */
    public static function badgeClass(string $name): string
    {
        return match (strtolower($name)) {
            'active'     => 'badge-soft-success',
            'inactive'   => 'badge-soft-secondary',
            'on leave'   => 'badge-soft-warning',
            'terminated' => 'badge-soft-danger',
            default      => 'badge-soft-info',
        };
    }
}
