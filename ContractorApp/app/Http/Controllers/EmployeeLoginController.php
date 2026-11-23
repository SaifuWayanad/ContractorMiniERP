<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EmployeeLoginController extends Controller
{
    public function create(Employee $employee): \Illuminate\View\View
    {
        $this->authorize('update', $employee);

        abort_if($employee->hasLogin(), 403, 'This employee already has a login.');

        return view('employees.create-login', compact('employee'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_if($employee->hasLogin(), 403, 'This employee already has a login.');

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $staffRole = Role::where('slug', Role::CONTRACTOR_STAFF)->firstOrFail();

        $user = DB::transaction(function () use ($validated, $employee, $staffRole): User {
            $user = new User([
                'name'     => $employee->name,
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->contractor_id = $employee->contractor_id;
            $user->role_id       = $staffRole->id;
            $user->status        = UserStatus::Active;
            $user->save();

            $employee->user_id = $user->id;
            $employee->saveQuietly();

            return $user;
        });

        return to_route('employees.show', $employee)->with('toast', "Login created for {$employee->name}.");
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($employee->hasLogin(), 404);

        DB::transaction(function () use ($employee): void {
            $user = User::find($employee->user_id);
            $employee->user_id = null;
            $employee->saveQuietly();
            $user?->delete();
        });

        return to_route('employees.show', $employee)->with('toast', 'Login removed.');
    }
}
