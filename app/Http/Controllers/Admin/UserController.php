<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\Agency;
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
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            if ($role === UserRole::ScholarshipAgency) {
                Agency::create([
                    'user_id' => $user->id,
                    'agency_name' => $user->name,
                    'contact_person' => $user->name,
                    'email' => $user->email,
                    'status' => 'active',
                ]);
            }

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
}
