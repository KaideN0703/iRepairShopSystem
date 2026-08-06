<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Technician;
use App\Models\JobOrder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TechnicianController extends Controller
{
    public function index()
    {
        $technicians = Technician::with('user')
            ->withCount([
                'jobOrders as total_jobs_count',
                'jobOrders as active_jobs_count' => function ($q) {
                    $q->whereIn('status', ['Received', 'Diagnosing', 'Waiting for Parts', 'Under Repair', 'Testing']);
                },
                'jobOrders as completed_jobs_count' => function ($q) {
                    $q->whereIn('status', ['Ready for Pickup', 'Completed', 'Released']);
                }
            ])
            ->get();

        return view('technicians.index', compact('technicians'));
    }

    public function create()
    {
        return view('technicians.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'specialty' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $techRole = Role::findOrCreate('Technician');
        $user->assignRole($techRole);

        $tech = Technician::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'specialty' => $validated['specialty'],
            'active_jobs_count' => 0,
            'rating' => 5.00,
            'is_active' => true,
        ]);

        return redirect()->route('technicians.show', $tech)->with('success', 'Technician account registered successfully!');
    }

    public function show(Technician $technician)
    {
        $technician->load([
            'user',
            'jobOrders.customer',
            'jobOrders.device',
            'diagnoses.jobOrder'
        ]);

        $activeJobs = $technician->jobOrders()->whereIn('status', ['Received', 'Diagnosing', 'Waiting for Parts', 'Under Repair', 'Testing'])->get();
        $completedJobs = $technician->jobOrders()->whereIn('status', ['Ready for Pickup', 'Completed', 'Released'])->get();

        // Calculate average turnaround time in hours
        $avgHours = JobOrder::where('technician_id', $technician->id)
            ->whereNotNull('released_at')
            ->selectRaw('AVG(JULIANDAY(released_at) - JULIANDAY(created_at)) * 24 as avg_hours')
            ->value('avg_hours') ?? 24;

        return view('technicians.show', compact('technician', 'activeJobs', 'completedJobs', 'avgHours'));
    }

    public function edit(Technician $technician)
    {
        return view('technicians.edit', compact('technician'));
    }

    public function update(Request $request, Technician $technician)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'specialty' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $technician->update($validated);

        if ($technician->user) {
            $technician->user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'is_active' => $request->boolean('is_active'),
            ]);
        }

        return redirect()->route('technicians.show', $technician)->with('success', 'Technician profile updated!');
    }
}
