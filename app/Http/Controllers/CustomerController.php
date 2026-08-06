<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Device;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $customers = Customer::withCount(['devices', 'jobOrders', 'warranties'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        return view('customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $latestCustomer = Customer::latest('id')->first();
        $nextId = $latestCustomer ? $latestCustomer->id + 1 : 1001;
        $validated['customer_code'] = 'CUST-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $customer = Customer::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'create',
            'module' => 'Customers',
            'description' => "Created customer profile {$customer->customer_code} - {$customer->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer registered successfully!');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'devices.jobOrders',
            'jobOrders.device',
            'jobOrders.technician',
            'invoices',
            'warranties.device',
            'warranties.jobOrder'
        ]);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'update',
            'module' => 'Customers',
            'description' => "Updated customer profile {$customer->customer_code}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer profile updated successfully!');
    }

    public function destroy(Request $request, Customer $customer)
    {
        $code = $customer->customer_code;
        $name = $customer->name;
        $customer->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'delete',
            'module' => 'Customers',
            'description' => "Deleted customer profile {$code} ({$name})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully!');
    }
}
