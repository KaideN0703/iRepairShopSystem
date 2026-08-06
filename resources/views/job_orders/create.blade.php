@extends('layouts.app')

@section('title', 'Create Repair Job Ticket')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="jobOrderForm()">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-ir-bone">Create Repair Job Ticket</h2>
            <p class="text-sm text-ir-bone/70 mt-1">Register new device intake and open a repair order</p>
        </div>
        <a href="{{ route('job_orders.index') }}" class="px-4 py-2 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-sm font-medium">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back to List
        </a>
    </div>

    <form action="{{ route('job_orders.store') }}" method="POST" class="bg-ir-carbon border border-ir-copper rounded-md p-6 shadow-lg space-y-6">
        @csrf

        <!-- Customer & Device Selection -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Customer -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="customer_id" class="text-xs font-semibold text-ir-bone uppercase tracking-wider">Select Customer *</label>
                    <a href="{{ route('customers.create') }}" class="text-xs text-ir-gold hover:underline">+ Register New Customer</a>
                </div>
                <select id="customer_id" name="customer_id" x-model="selectedCustomerId" @change="onCustomerChange()" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
                    <option value="">-- Choose Customer --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ (old('customer_id', $customerId) == $c->id) ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->customer_code }}) - {{ $c->phone }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Device -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="device_id" class="text-xs font-semibold text-ir-bone uppercase tracking-wider">Select Device *</label>
                    <a :href="'{{ route('devices.create') }}?customer_id=' + selectedCustomerId" class="text-xs text-ir-gold hover:underline">+ Add New Device</a>
                </div>
                <select id="device_id" name="device_id" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
                    <option value="">-- Choose Device --</option>
                    <template x-for="dev in availableDevices" :key="dev.id">
                        <option :value="dev.id" x-text="dev.brand + ' ' + dev.model + ' (' + (dev.color || 'No color') + ')'"></option>
                    </template>
                </select>
            </div>

        </div>

        <!-- Technician & Priority -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="technician_id" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Assign Technician</label>
                <select id="technician_id" name="technician_id" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
                    <option value="">Unassigned (Queue)</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->name }} ({{ $tech->specialty }}) - Active: {{ $tech->active_jobs_count }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="priority" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Priority Level *</label>
                <select id="priority" name="priority" required class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
                    <option value="Normal">Normal</option>
                    <option value="Low">Low</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                </select>
            </div>

            <div>
                <label for="estimated_completion_date" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Est. Completion Date</label>
                <input type="date" id="estimated_completion_date" name="estimated_completion_date" value="{{ date('Y-m-d', strtotime('+2 days')) }}" class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none">
            </div>
        </div>

        <!-- Issue Description -->
        <div>
            <label for="reported_issue" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Reported Device Issue *</label>
            <textarea id="reported_issue" name="reported_issue" rows="3" required placeholder="Describe the customer's reported symptoms (e.g. shattered screen, battery drains quickly, no power)..." class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none"></textarea>
        </div>

        <!-- Initial Cost Calculation -->
        <div class="p-5 rounded-md bg-ir-void border border-ir-copper space-y-4">
            <h4 class="text-xs font-bold text-ir-bone/70 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-calculator text-ir-gold"></i> Initial Cost Estimate Setup
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="labor_cost" class="block text-xs font-medium text-ir-bone/70 mb-1">Labor Cost (₱)</label>
                    <input type="number" step="0.01" id="labor_cost" name="labor_cost" value="40.00" x-model="laborCost" class="w-full px-3 py-2 rounded-lg bg-ir-carbon border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
                </div>

                <div>
                    <label for="service_fee" class="block text-xs font-medium text-ir-bone/70 mb-1">Service / Diagnostic Fee (₱)</label>
                    <input type="number" step="0.01" id="service_fee" name="service_fee" value="10.00" x-model="serviceFee" class="w-full px-3 py-2 rounded-lg bg-ir-carbon border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
                </div>

                <div>
                    <label for="discount_type" class="block text-xs font-medium text-ir-bone/70 mb-1">Discount Type</label>
                    <select id="discount_type" name="discount_type" x-model="discountType" class="w-full px-3 py-2 rounded-lg bg-ir-carbon border border-ir-copper text-sm text-ir-bone focus:outline-none">
                        <option value="fixed">Fixed (₱)</option>
                        <option value="percentage">Percentage (%)</option>
                    </select>
                </div>

                <div>
                    <label for="discount_value" class="block text-xs font-medium text-ir-bone/70 mb-1">Discount Value</label>
                    <input type="number" step="0.01" id="discount_value" name="discount_value" value="0.00" x-model="discountVal" class="w-full px-3 py-2 rounded-lg bg-ir-carbon border border-ir-copper text-sm text-ir-bone focus:outline-none focus:border-ir-gold">
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="customer_notes" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Customer-Facing Notes</label>
                <textarea id="customer_notes" name="customer_notes" rows="2" placeholder="Notes printed on repair receipt for customer..." class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none"></textarea>
            </div>

            <div>
                <label for="internal_notes" class="block text-xs font-semibold text-ir-bone uppercase tracking-wider mb-2">Internal Staff Notes</label>
                <textarea id="internal_notes" name="internal_notes" rows="2" placeholder="Private internal bench notes..." class="w-full px-4 py-3 rounded-md bg-ir-void border border-ir-copper text-ir-bone text-sm focus:border-ir-gold focus:outline-none"></textarea>
            </div>
        </div>

        <div class="pt-4 border-t border-ir-copper flex justify-end gap-3">
            <a href="{{ route('job_orders.index') }}" class="px-5 py-3 rounded-md bg-ir-carbon hover:bg-ir-carbon text-ir-bone text-sm font-medium">Cancel</a>
            <button type="submit" class="px-6 py-3 rounded-md bg-ir-gold hover:bg-ir-amber-deep text-ir-bone text-sm font-semibold transition-colors">
                Create Repair Ticket & Generate QR
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function jobOrderForm() {
        return {
            customers: @json($customers),
            selectedCustomerId: '{{ old('customer_id', $customerId) }}',
            availableDevices: [],
            laborCost: 40.00,
            serviceFee: 10.00,
            discountType: 'fixed',
            discountVal: 0.00,
            init() {
                if (this.selectedCustomerId) {
                    this.onCustomerChange();
                }
            },
            onCustomerChange() {
                const c = this.customers.find(item => item.id == this.selectedCustomerId);
                if (c && c.devices) {
                    this.availableDevices = c.devices;
                } else {
                    this.availableDevices = [];
                }
            }
        }
    }
</script>
@endpush
@endsection
