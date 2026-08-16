<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Campus;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $role = UserRole::tryFrom((string) $request->query('role'));

        return view('admin.users.index', [
            'roles' => UserRole::cases(),
            'campuses' => Campus::query()->where('is_active', true)->orderBy('name')->get(),
            'users' => User::query()
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when($role, fn ($query) => $query->where('role', $role))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function store(StoreUserRequest $request, AuditTrailService $audit): RedirectResponse
    {
        $validated = $request->validated();
        $role = UserRole::from($validated['role']);

        $user = DB::transaction(function () use ($validated, $role): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $role,
                'campus_id' => $role->requiresCampus() ? $validated['campus_id'] : null,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        });

        $audit->record('user_created', $user, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role->value,
        ], $request);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "{$role->label()} account created successfully.");
    }

    public function update(UpdateUserRequest $request, User $user, AuditTrailService $audit): RedirectResponse
    {
        $validated = $request->validated();
        $role = UserRole::from($validated['role']);

        if ($request->user()->is($user) && $validated['status'] === 'inactive') {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['account' => 'You cannot deactivate your own account.']);
        }

        $before = $user->only(['name', 'email', 'role', 'campus_id', 'status']);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $role,
            'campus_id' => $role->requiresCampus() ? $validated['campus_id'] : null,
            'status' => $validated['status'],
        ];

        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = $validated['password'];
        }

        $user->forceFill($attributes)->save();

        $audit->record('user_updated', $user, [
            'before' => $before,
            'after' => $user->only(['name', 'email', 'role', 'campus_id', 'status']),
            'password_changed' => array_key_exists('password', $attributes),
        ], $request);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Account updated successfully.');
    }

    public function destroy(Request $request, User $user, AuditTrailService $audit): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['account' => 'You cannot deactivate your own account.']);
        }

        $user->forceFill(['status' => 'inactive'])->save();

        $audit->record('user_deactivated', $user, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ], $request);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Account access has been deactivated.');
    }
}
