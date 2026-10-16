<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractorStatus;
use App\Http\Controllers\Controller;
use App\Models\Contractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Activates or deactivates a contractor. Deactivating keeps all data but blocks the contractor's users
 * from signing in (and signs out any active sessions on their next request).
 */
class ContractorStatusController extends Controller
{
    public function __invoke(Request $request, Contractor $contractor): RedirectResponse
    {
        $this->authorize('update', $contractor);

        $validated = $request->validate(['status' => ['required', Rule::enum(ContractorStatus::class)]]);

        $contractor->status = ContractorStatus::from($validated['status']);
        $contractor->save();

        return back()->with('toast', $contractor->isActive()
            ? "\"{$contractor->company_name}\" is active again. Its users can sign in."
            : "\"{$contractor->company_name}\" has been deactivated. Its users can no longer sign in; data is kept.");
    }
}
