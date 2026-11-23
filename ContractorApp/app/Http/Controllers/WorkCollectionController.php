<?php

namespace App\Http\Controllers;

use App\Models\ProjectPayment;
use App\Models\Work;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkCollectionController extends Controller
{
    public function store(Request $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validate([
            'amount_received'   => ['required', 'numeric', 'min:0.01'],
            'payment_date'      => ['required', 'date'],
            'payment_method'    => ['nullable', 'string', 'max:100'],
            'receipt_reference' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = new ProjectPayment($validated);
        $payment->work_id    = $work->id;
        $payment->project_id = $work->project_id;
        $payment->save();

        return back()->with('toast', 'Collection recorded.');
    }

    public function destroy(Work $work, ProjectPayment $payment): RedirectResponse
    {
        $this->authorize('update', $work);

        $payment->delete();

        return back()->with('toast', 'Collection deleted.');
    }
}
