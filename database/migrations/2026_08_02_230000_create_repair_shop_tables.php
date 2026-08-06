<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique(); // e.g. CUST-1001
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Technicians
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('specialty')->default('General Repair'); // e.g., iPhone Specialist, Micro-soldering, Laptop Hardware
            $table->integer('active_jobs_count')->default(0);
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Devices
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('device_type'); // Mobile, Laptop, Tablet, Smartwatch, Other
            $table->string('brand'); // Apple, Samsung, Dell, Asus, Sony, Lenovo
            $table->string('model'); // iPhone 14 Pro, MacBook Pro M2, Galaxy S23
            $table->string('serial_number')->nullable();
            $table->string('color')->nullable();
            $table->string('passcode_pattern')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Part Categories
        Schema::create('part_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 5. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 6. Parts (Inventory)
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('part_categories')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('sku')->unique();
            $table->string('barcode')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->string('location_rack')->nullable(); // e.g. Shelf A-3
            $table->string('compatible_models')->nullable(); // e.g., iPhone 13, iPhone 14
            $table->timestamps();
        });

        // 7. Purchase Orders & Restocking
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // PO-2026-0001
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, ordered, received, cancelled
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->timestamps();
        });

        // 8. Stock Movements Log
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
            $table->string('type'); // in, out, adjustment, repair_usage
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('reference_type')->nullable(); // JobOrder, PurchaseOrder, Manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. Job Orders (Repair Tickets)
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // e.g. JO-2026-0001
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->onDelete('set null');
            
            // Stages: Received -> Diagnosing -> Waiting for Parts -> Under Repair -> Testing -> Ready for Pickup -> Completed -> Released
            $table->string('status')->default('Received');
            $table->string('priority')->default('Normal'); // Low, Normal, High, Urgent
            
            $table->text('reported_issue');
            $table->date('estimated_completion_date')->nullable();
            
            // Cost calculation fields
            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->decimal('parts_cost', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->string('discount_type')->default('fixed'); // fixed or percentage
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);

            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('qr_code')->nullable();

            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        // 10. Job Order Status History
        Schema::create('job_order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 11. Job Order Parts (Parts Usage per Job)
        Schema::create('job_order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });

        // 12. Diagnoses & Inspection Checklist
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->onDelete('set null');
            $table->json('checklist')->nullable(); // Power, Display, Touch, Cameras, Wifi, Speaker, Mic, Ports, Housing
            $table->text('identified_issues')->nullable();
            $table->text('recommended_repairs')->nullable();
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->text('technician_remarks')->nullable();
            $table->json('ai_suggestions')->nullable(); // LLM AI analysis payload
            $table->timestamps();
        });

        // 13. Invoices & Invoice Items
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // INV-2026-0001
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->string('item_type'); // labor, part, service_fee, discount
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });

        // 14. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique(); // PAY-2026-0001
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // Cash, Credit Card, GCash, Bank Transfer
            $table->timestamp('payment_date');
            $table->string('reference_number')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 15. Warranties & Claims
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->integer('warranty_period_days')->default(90);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('coverage_details')->nullable();
            $table->string('status')->default('active'); // active, expired, claimed
            $table->timestamps();
        });

        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique(); // CLM-2026-0001
            $table->foreignId('warranty_id')->constrained('warranties')->onDelete('cascade');
            $table->foreignId('job_order_id')->nullable()->constrained('job_orders')->onDelete('set null');
            $table->date('claim_date');
            $table->text('issue_description');
            $table->string('resolution_status')->default('pending'); // pending, approved, rejected, resolved
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });

        // 16. Notifications Log
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // SMS, Email, Alert
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('sent'); // sent, failed
            $table->string('triggered_by')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });

        // 17. Audit Trail Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_name')->nullable();
            $table->string('action'); // login, create, update, delete, status_change, stock_adjust
            $table->string('module'); // JobOrders, Customers, Inventory, Invoices, Security
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        // 18. Polymorphic Attachments (Photos & Signatures)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable'); // JobOrder, Diagnosis, Device, etc.
            $table->string('type'); // photo_before, photo_after, customer_signature, invoice_pdf
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size')->nullable();
            $table->timestamps();
        });

        // 19. Appointments / Service Intake Requests
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('device_type');
            $table->text('reported_issue');
            $table->dateTime('preferred_date');
            $table->string('status')->default('pending'); // pending, confirmed, converted, cancelled
            $table->timestamps();
        });

        // 20. Backups Log
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('file_path');
            $table->integer('file_size')->default(0);
            $table->string('type')->default('manual'); // manual, scheduled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications_log');
        Schema::dropIfExists('warranty_claims');
        Schema::dropIfExists('warranties');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('job_order_parts');
        Schema::dropIfExists('job_order_status_histories');
        Schema::dropIfExists('job_orders');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('parts');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('part_categories');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('technicians');
        Schema::dropIfExists('customers');
    }
};
