<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Customer;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $devices = Device::with('customer')
            ->when($search, function ($query, $search) {
                $query->where('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(15);

        return view('devices.index', compact('devices', 'search'));
    }

    public function create(Request $request)
    {
        $customerId = $request->query('customer_id');
        $customers = Customer::orderBy('name')->get();
        return view('devices.create', compact('customers', 'customerId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'device_type' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'passcode_pattern' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $device = Device::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'create',
            'module' => 'Devices',
            'description' => "Registered device {$device->brand} {$device->model} for customer ID #{$device->customer_id}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('customers.show', $device->customer_id)->with('success', 'Device registered successfully!');
    }

    public function show(Device $device)
    {
        $device->load(['customer', 'jobOrders.technician', 'warranties', 'attachments']);
        return view('devices.show', compact('device'));
    }

    public function edit(Device $device)
    {
        $customers = Customer::orderBy('name')->get();
        return view('devices.edit', compact('device', 'customers'));
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'device_type' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'passcode_pattern' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $device->update($validated);

        return redirect()->route('devices.show', $device)->with('success', 'Device details updated successfully!');
    }

    public function destroy(Request $request, Device $device)
    {
        $customerId = $device->customer_id;
        $device->delete();

        return redirect()->route('customers.show', $customerId)->with('success', 'Device deleted successfully!');
    }
}
