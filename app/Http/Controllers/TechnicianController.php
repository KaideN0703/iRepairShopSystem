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
        $this->authorize('technicians.manage');

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
        $this->authorize('technicians.manage');
        return view('technicians.create');
    }

    public function store(Request $request)
    {
        $this->authorize('technicians.manage');

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

        $techRole = Role::findOrCreate('technician');
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
        // Technicians can view their own profile; managers can view all
        abort_unless(
            auth()->user()->can('technicians.manage') || auth()->user()->technician?->id === $technician->id,
            403
        );

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
        $this->authorize('technicians.manage');
        return view('technicians.edit', compact('technician'));
    }

    public function update(Request $request, Technician $technician)
    {
        $this->authorize('technicians.manage');

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

    /**
     * Lightweight availability endpoint for cashier assignment picker.
     * Returns only { id, name, active_jobs_count } — no contact info or performance data.
     * Gated by technicians.view.availability.
     */
    public function availability()
    {
        $this->authorize('technicians.view.availability');

        $technicians = Technician::where('is_active', true)
            ->withCount([
                'jobOrders as open_job_count' => function ($q) {
                    $q->whereIn('status', ['Received', 'Diagnosing', 'Waiting for Parts', 'Under Repair', 'Testing']);
                },
            ])
            ->get(['id', 'name', 'specialty'])
            ->map(fn ($t) => [
                'id'             => $t->id,
                'name'           => $t->name,
                'specialty'      => $t->specialty,
                'open_job_count' => $t->open_job_count,
            ]);

        return response()->json($technicians);
    }

    /**
     * Shared handoff visibility endpoint for cashier & technician.
     * Returns read-only colleague name + current customer/job assignment without contact info.
     * Gated by technicians.view.assignments.
     */
    public function assignments()
    {
        $this->authorize('technicians.view.assignments');

        $technicians = Technician::where('is_active', true)
            ->with(['jobOrders' => function ($q) {
                $q->whereIn('status', ['Received', 'Diagnosing', 'Waiting for Parts', 'Under Repair', 'Testing'])
                  ->with('customer:id,name');
            }])
            ->get(['id', 'name'])
            ->map(function ($t) {
                $currentJob = $t->jobOrders->first();
                return [
                    'id'               => $t->id,
                    'name'             => $t->name,
                    'current_customer' => $currentJob?->customer?->name ?? 'None',
                    'job_status'       => $currentJob?->status ?? 'Available',
                ];
            });

        return response()->json($technicians);
    }
}
