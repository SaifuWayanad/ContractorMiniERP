<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $table = 'company_profile';

    protected $fillable = [
        'legal_name',
        'address',
        'tax_number',
        'phone',
        'email',
        'logo_path',
        'invoice_footer',
        'timezone',
        'day_count_convention',
        'require_full_return_invoicing',
        'require_invoice_before_closure',
        'allow_unpaid_closure',
    ];

    protected function casts(): array
    {
        return [
            'require_full_return_invoicing' => 'boolean',
            'require_invoice_before_closure' => 'boolean',
            'allow_unpaid_closure' => 'boolean',
        ];
    }
}
