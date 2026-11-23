<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class SalaryPaymentMethod extends Model
{
    use BelongsToContractor;

    /** @var list<string> */
    public const DEFAULTS = ['Cash', 'Bank Transfer', 'UPI', 'Cheque'];
}
