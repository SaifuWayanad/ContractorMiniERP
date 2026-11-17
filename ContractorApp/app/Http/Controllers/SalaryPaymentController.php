<?php

namespace App\Http\Controllers;

use App\Enums\SalaryPaymentStatus;
use App\Http\Requests\SalaryPaymentRequest;
use App\Models\Employee;
use App\Models\SalaryPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalaryPayment::class);

        $filters = [
            'search'      => $request->string('search')->trim()->value(),
            'employee_id' => $request->integer('employee_id') ?: null,
            'month'       => $request->string('month')->value() ?: null,
        ];

        $baseQuery = SalaryPayment::with('employee')
            ->when($filters['employee_id'], fn (Builder $q, int $eid) => $q->where('employee_id', $eid))
            ->when($filters['month'], function (Builder $q, string $m): void {
                [$year, $month] = explode('-', $m);
                $q->where('period_year', $year)->where('period_month', $month);
            })
            ->when($filters['search'] !== '', fn (Builder $q) => $q->whereHas('employee', fn (Builder $e) => $e->where('name', 'like', '%'.$filters['search'].'%')));

        $totalPaid = (clone $baseQuery)->where('status', 'paid')->sum('amount');

        $payments = $baseQuery
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        $employees = Employee::orderBy('name')->pluck('name', 'id');

        return view('salary-payments.index', [
            'payments'   => $payments,
            'filters'    => $filters,
            'totalPaid'  => $totalPaid,
            'employees'  => $employees,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SalaryPayment::class);

        return view('salary-payments.create', $this->formData(new SalaryPayment([
            'employee_id'  => $request->integer('employee_id') ?: null,
            'period_month' => now()->month,
            'period_year'  => now()->year,
            'payment_date' => today()->format('Y-m-d'),
            'status'       => SalaryPaymentStatus::Paid->value,
        ])));
    }

    public function store(SalaryPaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', SalaryPayment::class);

        SalaryPayment::create($request->validated());

        return to_route('salary-payments.index')->with('toast', 'Salary payment recorded.');
    }

    public function edit(SalaryPayment $salaryPayment): View
    {
        $this->authorize('update', $salaryPayment);

        return view('salary-payments.edit', $this->formData($salaryPayment));
    }

    public function update(SalaryPaymentRequest $request, SalaryPayment $salaryPayment): RedirectResponse
    {
        $this->authorize('update', $salaryPayment);

        $salaryPayment->update($request->validated());

        return to_route('salary-payments.index')->with('toast', 'Salary payment updated.');
    }

    public function destroy(SalaryPayment $salaryPayment): RedirectResponse
    {
        $this->authorize('delete', $salaryPayment);

        $salaryPayment->delete();

        return to_route('salary-payments.index')->with('toast', 'Salary payment deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(SalaryPayment $payment): array
    {
        return [
            'payment'    => $payment,
            'employees'  => Employee::where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'statuses'   => SalaryPaymentStatus::options(),
            'paymentMethods' => collect(['Cash', 'Bank Transfer', 'UPI', 'Cheque']),
            'months'     => array_combine(range(1, 12), ['January','February','March','April','May','June','July','August','September','October','November','December']),
        ];
    }
}
