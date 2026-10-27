<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = Driver::with('user')->orderBy('id')->get();

        return view('admin.drivers.index', compact('drivers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['required', 'string', 'max:20'],
            'password'   => ['required', 'string', 'min:8'],
            'license_no' => ['required', 'string', 'max:50', 'unique:drivers,license_no'],
            'status'     => ['required', Rule::in(Driver::STATUSES)],
        ]);

        // Create the login account and the driver record together, or neither
        DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'],
                'password'  => $data['password'],
                'role'      => 'driver',
                'is_active' => true,
            ]);

            Driver::create([
                'user_id'    => $user->id,
                'license_no' => $data['license_no'],
                'status'     => $data['status'],
            ]);
        });

        return back()->with('success', 'Driver added.');
    }

    public function edit(Driver $driver)
    {
        $driver->load('user');

        return view('admin.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $user = $driver->user;

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'      => ['required', 'string', 'max:20'],
            'password'   => ['nullable', 'string', 'min:8'],
            'license_no' => ['required', 'string', 'max:50', Rule::unique('drivers', 'license_no')->ignore($driver->id)],
            'status'     => ['required', Rule::in(Driver::STATUSES)],
        ]);

        DB::transaction(function () use ($data, $user, $driver) {
            $user->name  = $data['name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'];

            // Only change the password if a new one was typed
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            $driver->update([
                'license_no' => $data['license_no'],
                'status'     => $data['status'],
            ]);
        });

        return redirect()->route('admin.drivers.index')->with('success', 'Driver updated.');
    }

    // Deactivate or reactivate the driver's login account
    public function toggle(Driver $driver)
    {
        $user = $driver->user;
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Driver account activated.' : 'Driver account deactivated.');
    }
}