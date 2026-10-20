<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A "type of work" a contractor can pick for a project (e.g. Interior, Exterior).
 * Projects store the name in projects.type_of_work, so renaming or deleting a type never breaks old projects.
 */
#[Fillable(['name'])]
class WorkType extends Model
{
    use BelongsToContractor;

    /** @var list<string> */
    public const DEFAULTS = ['Interior', 'Exterior', 'Outside', 'Completed'];

    public static function seedDefaults(int $contractorId): void
    {
        foreach (self::DEFAULTS as $name) {
            // contractor_id is not mass assignable, so it is set explicitly.
            $workType = new self(['name' => $name]);
            $workType->contractor_id = $contractorId;
            $workType->save();
        }
    }
}
