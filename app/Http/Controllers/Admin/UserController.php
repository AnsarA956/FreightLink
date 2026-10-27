<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Roles the admin can give here. Driver accounts are created on the Drivers page.
    private const ROLES = ['customer', 'staff', 'admin'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'role'   => ['nullable', Rule::in(['customer', 'staff', 'admin', 'driver'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::with('driver')
            ->withCount('shipments')
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['required', 'string', 'max:20'],
            'role'     => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'],
            'role'      => $data['role'],
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);

        return back()->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        // Drivers have a licence record, so they are edited on the Drivers page
        if ($user->role === 'driver' && $user->driver) {
            return redirect()->route('admin.drivers.edit', $user->driver);
        }

        $isWalkIn = str_ends_with($user->email, '@freightlink.local');

        return view('admin.users.edit', [
            'user'     => $user,
            'isSelf'   => $user->id === auth()->id(),
            'isWalkIn' => $isWalkIn,
            'roles'    => self::ROLES,
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->role === 'driver') {
            return redirect()->route('admin.users.index')
                ->with('error', 'Driver accounts are edited on the Drivers page.');
        }

        $isSelf = $user->id === auth()->id();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'    => ['required', 'string', 'max:20'],
            // The role field is not sent when the admin edits their own account
            'role'     => [Rule::requiredIf(! $isSelf), Rule::in(self::ROLES)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // Nobody can change their own role
        $role = $isSelf ? $user->role : $data['role'];

        // A user with shipments is a customer with a history, so the role must stay "customer"
        if ($role !== $user->role && $role !== 'customer' && $user->shipments()->exists()) {
            return back()->withInput()->withErrors([
                'role' => 'This user has shipments, so the role cannot be changed to ' . $role . '.',
            ]);
        }

        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        $user->role  = $role;

        // Only change the password if a new one was typed
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    // Deactivate or reactivate an account (REQ-5)
    public function toggle(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Account activated.' : 'Account deactivated.');
    }
}