<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectPaymentRequest;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProjectPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProjectPayment::class);

        $search  = $request->string('search')->trim()->value();
        $filters = [
            'search'     => $search,
            'client_id'  => $request->integer('client_id') ?: null,
            'project_id' => $request->integer('project_id') ?: null,
        ];

        $payments = ProjectPayment::query()
            ->with('project:id,name')
            ->when($filters['client_id'], fn (Builder $q, int $cid) => $q->whereHas('project', fn (Builder $p) => $p->where('client_id', $cid)))
            ->when($filters['project_id'], fn (Builder $q, int $pid) => $q->where('project_id', $pid))
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('receipt_reference', 'like', $like)
                    ->orWhere('payment_method', 'like', $like);
            }))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        $clients  = Client::orderBy('name')->pluck('name', 'id');
        $projects = $filters['client_id']
            ? Project::where('client_id', $filters['client_id'])->orderBy('name')->pluck('name', 'id')
            : Project::orderBy('name')->pluck('name', 'id');

        return view('payments.index', [
            'payments' => $payments,
            'filters'  => $filters,
            'clients'  => $clients,
            'projects' => $projects,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ProjectPayment::class);

        $payment = new ProjectPayment(['payment_date' => today()->toDateString()]);
        $payment->project_id = $request->integer('project_id') ?: null;

        return view('payments.create', $this->formData($payment));
    }

    public function store(ProjectPaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', ProjectPayment::class);

        $payment = ProjectPayment::create($request->validated());

        $redirect = $request->input('from') === 'project'
            ? to_route('projects.show', $payment->project_id)
            : to_route('payments.index');

        return $redirect->with('toast', 'Payment recorded.');
    }

    public function edit(ProjectPayment $payment): View
    {
        $this->authorize('update', $payment);

        return view('payments.edit', $this->formData($payment));
    }

    public function update(ProjectPaymentRequest $request, ProjectPayment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $payment->update($request->validated());

        $redirect = $request->input('from') === 'project'
            ? to_route('projects.show', $payment->project_id)
            : to_route('payments.index');

        return $redirect->with('toast', 'Payment updated.');
    }

    public function destroy(ProjectPayment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $projectId = $payment->project_id;
        $payment->delete();

        return to_route('projects.show', $projectId)->with('toast', 'Payment deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(ProjectPayment $payment): array
    {
        return [
            'payment'        => $payment,
            'projects'       => Project::query()
                ->where(fn (Builder $q) => $q->open()->orWhere('id', $payment->project_id))
                ->orderBy('name')->pluck('name', 'id'),
            'paymentMethods' => $this->paymentMethods(),
        ];
    }

    private function paymentMethods(): Collection
    {
        $defaults = collect(['Bank Transfer', 'Card', 'Cash', 'Cheque', 'UPI']);
        $fromExpenses = Expense::whereNotNull('payment_method')->distinct()->pluck('payment_method');
        $fromPayments = ProjectPayment::whereNotNull('payment_method')->distinct()->pluck('payment_method');

        return $defaults->merge($fromExpenses)->merge($fromPayments)->unique()->sort()->values();
    }
}
