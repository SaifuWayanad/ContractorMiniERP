<?php

namespace App\Http\Controllers;

use App\Enums\SalaryPaymentStatus;
use App\Http\Requests\SalaryPaymentRequest;
use App\Models\Employee;
use App\Models\SalaryPayment;
use App\Models\Expense;
use App\Models\SalaryPaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalaryPayment::class);

        $linkedEmployee = $request->user()->linkedEmployee();

        $filters = [
            'search'      => $request->string('search')->trim()->value(),
            'employee_id' => $linkedEmployee ? $linkedEmployee->id : ($request->integer('employee_id') ?: null),
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

        $employees = $linkedEmployee
            ? collect([$linkedEmployee->id => $linkedEmployee->name])
            : Employee::orderBy('name')->pluck('name', 'id');

        return view('salary-payments.index', [
            'payments'        => $payments,
            'filters'         => $filters,
            'totalPaid'       => $totalPaid,
            'employees'       => $employees,
            'isEmployeePortal' => $linkedEmployee !== null,
        ]);
    }

    public function bulk(Request $request): View
    {
        $this->authorize('create', SalaryPayment::class);

        $month = $request->integer('month') ?: now()->month;
        $year  = $request->integer('year') ?: now()->year;

        $employees = Employee::orderBy('name')->get(['id', 'name', 'designation', 'salary_type', 'salary_amount']);

        $existing = SalaryPayment::where('period_month', $month)
            ->where('period_year', $year)
            ->get()
            ->keyBy('employee_id');

        $paymentMethods = SalaryPaymentMethod::orderBy('name')->pluck('name');
        $statuses       = SalaryPaymentStatus::options();
        $months         = array_combine(range(1, 12), ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);

        return view('salary-payments.bulk', compact('employees', 'existing', 'month', 'year', 'paymentMethods', 'statuses', 'months'));
    }

    public function saveBulk(Request $request): RedirectResponse
    {
        $this->authorize('create', SalaryPayment::class);

        $validated = $request->validate([
            'month'          => ['required', 'integer', 'min:1', 'max:12'],
            'year'           => ['required', 'integer', 'min:2000', 'max:2100'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'status'         => ['required', 'string'],
            'rows'           => ['nullable', 'array'],
            'rows.*.include' => ['nullable'],
            'rows.*.amount'  => ['nullable', 'numeric', 'min:0'],
            'rows.*.notes'   => ['nullable', 'string', 'max:500'],
        ]);

        $month  = $validated['month'];
        $year   = $validated['year'];
        $saved  = 0;
        $totalAmount = 0;

        foreach ($validated['rows'] ?? [] as $employeeId => $data) {
            if (empty($data['include'])) {
                continue;
            }

            $amount = (float) ($data['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            SalaryPayment::updateOrCreate(
                ['employee_id' => (int) $employeeId, 'period_month' => $month, 'period_year' => $year],
                [
                    'amount'         => $amount,
                    'payment_date'   => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'] ?: null,
                    'status'         => $validated['status'],
                    'notes'          => $data['notes'] ?: null,
                ]
            );
            $saved++;
            $totalAmount += $amount;
        }

        // Create / update a consolidated expense entry for this payroll run
        if ($saved > 0) {
            $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            $monthName  = $monthNames[$month - 1];
            $ref        = "PAYROLL-{$year}-{$month}";

            Expense::updateOrCreate(
                ['reference_number' => $ref],
                [
                    'heading'        => "Monthly Pay — {$monthName} {$year}",
                    'category'       => 'Salary & Wages',
                    'is_work_related'=> false,
                    'expense_date'   => $validated['payment_date'],
                    'amount'         => $totalAmount,
                    'payment_method' => $validated['payment_method'] ?: null,
                    'notes'          => "{$saved} employee(s) — {$monthName} {$year} payroll",
                    'project_id'     => null,
                ]
            );
        }

        return back()->with('toast', "Saved {$saved} salary payment(s).");
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
            'payment'        => $payment,
            'employees'      => Employee::orderBy('name')->pluck('name', 'id'),
            'statuses'       => SalaryPaymentStatus::options(),
            'paymentMethods' => SalaryPaymentMethod::orderBy('name')->pluck('name'),
            'months'         => array_combine(range(1, 12), ['January','February','March','April','May','June','July','August','September','October','November','December']),
        ];
    }
}
