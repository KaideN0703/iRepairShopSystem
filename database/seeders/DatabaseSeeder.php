<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\Device;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\Part;
use App\Models\JobOrder;
use App\Models\JobOrderStatusHistory;
use App\Models\JobOrderPart;
use App\Models\Diagnosis;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\NotificationsLog;
use App\Models\AuditLog;
use App\Models\Appointment;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Permissions — delegated to RolesAndPermissionsSeeder
        $this->call(RolesAndPermissionsSeeder::class);


        // 2. Users & Technicians
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@irepair.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (555) 019-2831',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $manager = User::create([
            'name' => 'Elena Rostova',
            'email' => 'manager@irepair.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (555) 012-9988',
            'is_active' => true,
        ]);
        $manager->assignRole('shop_manager');

        $techUser1 = User::create([
            'name' => 'Marcus Vance',
            'email' => 'tech1@irepair.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (555) 014-4321',
            'is_active' => true,
        ]);
        $techUser1->assignRole('technician');

        $techUser2 = User::create([
            'name' => 'Sarah Lin',
            'email' => 'tech2@irepair.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (555) 017-8899',
            'is_active' => true,
        ]);
        $techUser2->assignRole('technician');

        $inventoryUser = User::create([
            'name' => 'Alex Rivera',
            'email' => 'inventory@irepair.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (555) 018-7711',
            'is_active' => true,
        ]);
        $inventoryUser->assignRole('inventory_staff');

        $cashierUser = User::create([
            'name' => 'Emily Davis',
            'email' => 'cashier@irepair.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (555) 013-6622',
            'is_active' => true,
        ]);
        $cashierUser->assignRole('cashier');

        $tech1 = Technician::create([
            'user_id' => $techUser1->id,
            'name' => 'Marcus Vance',
            'email' => 'tech1@irepair.com',
            'phone' => '+1 (555) 014-4321',
            'specialty' => 'iPhone & Micro-soldering Specialist',
            'active_jobs_count' => 3,
            'rating' => 4.90,
            'is_active' => true,
        ]);

        $tech2 = Technician::create([
            'user_id' => $techUser2->id,
            'name' => 'Sarah Lin',
            'email' => 'tech2@irepair.com',
            'phone' => '+1 (555) 017-8899',
            'specialty' => 'MacBook & Laptop Hardware Specialist',
            'active_jobs_count' => 2,
            'rating' => 4.85,
            'is_active' => true,
        ]);

        // 3. Suppliers
        $sup1 = Supplier::create([
            'name' => 'iTech Parts Global',
            'contact_person' => 'Robert Thorne',
            'email' => 'sales@itechparts.com',
            'phone' => '+1 (800) 555-0100',
            'address' => '450 Tech Way, San Jose, CA',
        ]);
        $sup2 = Supplier::create([
            'name' => 'ChipSource Direct',
            'contact_person' => 'Lisa Wong',
            'email' => 'support@chipsource.com',
            'phone' => '+1 (800) 555-0199',
            'address' => '12 Commerce Blvd, Austin, TX',
        ]);
        $sup3 = Supplier::create([
            'name' => 'Apex Screen Supplies',
            'contact_person' => 'David Miller',
            'email' => 'orders@apexscreens.com',
            'phone' => '+1 (800) 555-0144',
            'address' => '88 Innovation Dr, Seattle, WA',
        ]);

        // 4. Part Categories
        $catDisplays = PartCategory::create(['name' => 'Displays & Glass', 'slug' => 'displays-glass', 'description' => 'OLED, LCD, and Touchscreen panels']);
        $catBatteries = PartCategory::create(['name' => 'Batteries', 'slug' => 'batteries', 'description' => 'OEM and High-Capacity battery replacements']);
        $catPorts = PartCategory::create(['name' => 'Charging & Power', 'slug' => 'charging-power', 'description' => 'USB-C, Lightning ports, and power ICs']);
        $catBoards = PartCategory::create(['name' => 'Logic Boards & Microchips', 'slug' => 'logic-boards', 'description' => 'Connectors, capacitors, power ICs']);
        $catStorage = PartCategory::create(['name' => 'RAM & Storage SSD', 'slug' => 'storage-ram', 'description' => 'NVMe SSDs, Memory modules']);
        $catHousing = PartCategory::create(['name' => 'Housing & Cameras', 'slug' => 'housing-cameras', 'description' => 'Rear glass, camera modules, chassis']);

        // 5. Parts Catalog
        $partsData = [
            [
                'category_id' => $catDisplays->id, 'supplier_id' => $sup3->id,
                'sku' => 'DISP-IP14P-OLED', 'barcode' => '890123450001',
                'name' => 'iPhone 14 Pro OLED Display Assembly (OEM Grade)',
                'description' => 'Original quality Super Retina XDR OLED screen assembly with TrueTone chip.',
                'cost_price' => 110.00, 'selling_price' => 199.99, 'stock_quantity' => 14, 'reorder_level' => 5,
                'location_rack' => 'Shelf A-1', 'compatible_models' => 'iPhone 14 Pro',
            ],
            [
                'category_id' => $catBatteries->id, 'supplier_id' => $sup1->id,
                'sku' => 'BATT-IP13-OEM', 'barcode' => '890123450002',
                'name' => 'iPhone 13 High-Capacity Battery (3240mAh)',
                'description' => 'Zero-cycle battery replacement with adhesive kit.',
                'cost_price' => 18.50, 'selling_price' => 49.99, 'stock_quantity' => 22, 'reorder_level' => 8,
                'location_rack' => 'Shelf B-2', 'compatible_models' => 'iPhone 13',
            ],
            [
                'category_id' => $catDisplays->id, 'supplier_id' => $sup3->id,
                'sku' => 'DISP-MBP16-M1', 'barcode' => '890123450003',
                'name' => 'MacBook Pro 16" M1 Max Liquid Retina XDR Display (Space Gray)',
                'description' => 'Complete top assembly panel in Space Gray.',
                'cost_price' => 380.00, 'selling_price' => 650.00, 'stock_quantity' => 3, 'reorder_level' => 4, // LOW STOCK!
                'location_rack' => 'Shelf A-4', 'compatible_models' => 'MacBook Pro 16" A2485',
            ],
            [
                'category_id' => $catBatteries->id, 'supplier_id' => $sup1->id,
                'sku' => 'BATT-MBP13-A1708', 'barcode' => '890123450004',
                'name' => 'MacBook Pro 13" A1708/A1706 Replacement Battery',
                'description' => 'OEM A1819 Li-ion battery pack with removal tools.',
                'cost_price' => 42.00, 'selling_price' => 99.00, 'stock_quantity' => 2, 'reorder_level' => 5, // LOW STOCK!
                'location_rack' => 'Shelf B-1', 'compatible_models' => 'MacBook Pro 13" 2016-2017',
            ],
            [
                'category_id' => $catPorts->id, 'supplier_id' => $sup2->id,
                'sku' => 'PORT-SGS23-USBC', 'barcode' => '890123450005',
                'name' => 'Samsung Galaxy S23 USB-C Charging Port Board',
                'description' => 'Flex cable assembly with microphone and cellular antenna port.',
                'cost_price' => 12.00, 'selling_price' => 34.99, 'stock_quantity' => 18, 'reorder_level' => 5,
                'location_rack' => 'Shelf C-1', 'compatible_models' => 'Samsung Galaxy S23 (SM-S911B)',
            ],
            [
                'category_id' => $catDisplays->id, 'supplier_id' => $sup3->id,
                'sku' => 'DISP-SGS23U-AMOL', 'barcode' => '890123450006',
                'name' => 'Samsung Galaxy S23 Ultra Dynamic AMOLED 2X Display (Black Frame)',
                'description' => 'Full curved display with digitizer and aluminum side frame.',
                'cost_price' => 185.00, 'selling_price' => 299.00, 'stock_quantity' => 7, 'reorder_level' => 3,
                'location_rack' => 'Shelf A-2', 'compatible_models' => 'Samsung Galaxy S23 Ultra',
            ],
            [
                'category_id' => $catStorage->id, 'supplier_id' => $sup2->id,
                'sku' => 'SSD-NVME-1TB', 'barcode' => '890123450007',
                'name' => 'Crucial P3 1TB PCIe 4.0 NVMe M.2 SSD',
                'description' => 'High-performance laptop upgrade storage drive (Read up to 5000MB/s).',
                'cost_price' => 55.00, 'selling_price' => 89.99, 'stock_quantity' => 10, 'reorder_level' => 4,
                'location_rack' => 'Shelf D-3', 'compatible_models' => 'Universal M.2 NVMe Laptops',
            ],
            [
                'category_id' => $catHousing->id, 'supplier_id' => $sup1->id,
                'sku' => 'CAM-IP13P-REAR', 'barcode' => '890123450008',
                'name' => 'iPhone 13 Pro Triple Rear Camera Module',
                'description' => 'Original replacement rear camera unit with OIS stabilization.',
                'cost_price' => 68.00, 'selling_price' => 129.99, 'stock_quantity' => 1, 'reorder_level' => 3, // LOW STOCK!
                'location_rack' => 'Shelf E-1', 'compatible_models' => 'iPhone 13 Pro / 13 Pro Max',
            ],
        ];

        $parts = [];
        foreach ($partsData as $pData) {
            $parts[$pData['sku']] = Part::create($pData);
        }

        // 6. Customers & Devices
        $c1 = Customer::create([
            'customer_code' => 'CUST-1001',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1 (555) 234-5678',
            'address' => '742 Evergreen Terrace, Springfield',
            'notes' => 'VIP Customer - Frequent phone repairs',
        ]);
        $d1 = Device::create([
            'customer_id' => $c1->id,
            'device_type' => 'Mobile',
            'brand' => 'Apple',
            'model' => 'iPhone 14 Pro',
            'serial_number' => 'DNPGX9910L',
            'color' => 'Deep Purple',
            'passcode_pattern' => '123456',
            'notes' => 'Cracked screen from fall, touch unresponsive',
        ]);

        $c2 = Customer::create([
            'customer_code' => 'CUST-1002',
            'name' => 'Alice Smith',
            'email' => 'alice.smith@example.com',
            'phone' => '+1 (555) 345-6789',
            'address' => '1048 Ocean Drive, Miami, FL',
        ]);
        $d2 = Device::create([
            'customer_id' => $c2->id,
            'device_type' => 'Laptop',
            'brand' => 'Apple',
            'model' => 'MacBook Pro 16" M1 Max',
            'serial_number' => 'C02G4112MD',
            'color' => 'Space Gray',
            'passcode_pattern' => 'macbook2026',
            'notes' => 'Liquid spill near keyboard. No power.',
        ]);

        $c3 = Customer::create([
            'customer_code' => 'CUST-1003',
            'name' => 'Robert Johnson',
            'email' => 'rjohnson@example.com',
            'phone' => '+1 (555) 456-7890',
            'address' => '302 Sunset Blvd, Los Angeles, CA',
        ]);
        $d3 = Device::create([
            'customer_id' => $c3->id,
            'device_type' => 'Mobile',
            'brand' => 'Samsung',
            'model' => 'Galaxy S23 Ultra',
            'serial_number' => 'RF8N3001XX',
            'color' => 'Phantom Black',
            'passcode_pattern' => 'Pattern L-shape',
            'notes' => 'Battery drains within 2 hours.',
        ]);

        $c4 = Customer::create([
            'customer_code' => 'CUST-1004',
            'name' => 'Clara Oswald',
            'email' => 'clara.o@example.com',
            'phone' => '+1 (555) 567-8901',
            'address' => '15 Baker Street, London, UK',
        ]);
        $d4 = Device::create([
            'customer_id' => $c4->id,
            'device_type' => 'Laptop',
            'brand' => 'Lenovo',
            'model' => 'ThinkPad X1 Carbon Gen 10',
            'serial_number' => 'PF2990AX',
            'color' => 'Black',
            'passcode_pattern' => 'thinkpad99',
            'notes' => 'Sluggish system, requests 1TB NVMe SSD storage upgrade',
        ]);

        // 7. Job Orders across various pipeline stages
        $jo1 = JobOrder::create([
            'ticket_number' => 'JO-2026-0001',
            'customer_id' => $c1->id,
            'device_id' => $d1->id,
            'technician_id' => $tech1->id,
            'status' => 'Under Repair',
            'priority' => 'High',
            'reported_issue' => 'Shattered front glass screen, touch digitizer glitching',
            'estimated_completion_date' => now()->addDays(1),
            'labor_cost' => 50.00,
            'parts_cost' => 199.99,
            'service_fee' => 15.00,
            'discount_type' => 'percentage',
            'discount_value' => 10.00, // 10%
            'total_cost' => 238.49,
            'customer_notes' => 'Customer requested TrueTone data transfer to new OLED screen',
            'internal_notes' => 'Original display adhesive cleaned. Tested replacement OLED panel.',
            'qr_code' => 'JO-2026-0001',
        ]);
        JobOrderStatusHistory::create(['job_order_id' => $jo1->id, 'user_id' => $admin->id, 'status_from' => null, 'status_to' => 'Received', 'remarks' => 'Intake registered']);
        JobOrderStatusHistory::create(['job_order_id' => $jo1->id, 'user_id' => $techUser1->id, 'status_from' => 'Received', 'status_to' => 'Diagnosing', 'remarks' => 'Visual inspection complete']);
        JobOrderStatusHistory::create(['job_order_id' => $jo1->id, 'user_id' => $techUser1->id, 'status_from' => 'Diagnosing', 'status_to' => 'Under Repair', 'remarks' => 'Part reserved. Assembly underway.']);

        JobOrderPart::create([
            'job_order_id' => $jo1->id,
            'part_id' => $parts['DISP-IP14P-OLED']->id,
            'quantity' => 1,
            'unit_price' => 199.99,
            'total_price' => 199.99,
        ]);

        Diagnosis::create([
            'job_order_id' => $jo1->id,
            'technician_id' => $tech1->id,
            'checklist' => [
                'power' => 'Pass', 'display' => 'Fail (Shattered)', 'touch' => 'Fail',
                'cameras' => 'Pass', 'wifi' => 'Pass', 'speaker' => 'Pass', 'mic' => 'Pass', 'ports' => 'Pass'
            ],
            'identified_issues' => 'Shattered OLED screen and faulty touch digitizer.',
            'recommended_repairs' => 'Full OEM OLED display assembly replacement.',
            'estimated_cost' => 264.99,
            'technician_remarks' => 'Frame is straight with no corner dents. Safe for screen drop-in.',
            'ai_suggestions' => [
                'diagnosis' => 'Display Assembly Failure',
                'confidence' => 0.96,
                'recommended_actions' => [
                    'Replace front screen with OEM OLED panel',
                    'Calibrate TrueTone sensor',
                    'Apply new IP68 waterproof display seal'
                ],
                'estimated_time_hours' => 1.5,
                'suggested_parts' => ['DISP-IP14P-OLED']
            ],
        ]);

        // Live Progress Updates for JO-2026-0001
        $jo1->current_percentage = 60;
        $jo1->save();

        \App\Models\RepairProgressUpdate::create([
            'job_order_id' => $jo1->id,
            'posted_by' => $techUser1->id,
            'pipeline_stage' => 'Diagnosing',
            'percentage' => 20,
            'description' => 'Initial device teardown complete. Screen removed and TrueTone sensor read.',
            'is_customer_visible' => true,
        ]);

        $u2 = \App\Models\RepairProgressUpdate::create([
            'job_order_id' => $jo1->id,
            'posted_by' => $techUser1->id,
            'pipeline_stage' => 'Under Repair',
            'percentage' => 60,
            'description' => 'OEM Display panel mounted. Additional corroded display FPC connector discovered during teardown.',
            'is_customer_visible' => true,
        ]);

        \App\Models\RepairApprovalRequest::create([
            'job_order_id' => $jo1->id,
            'repair_progress_update_id' => $u2->id,
            'requested_by' => $techUser1->id,
            'title' => 'FPC Display Connector Micro-soldering Repair',
            'description' => 'Found minor pin corrosion on motherboard display connector FPC socket. Recommended micro-soldering cleanup to prevent display flickering.',
            'additional_cost' => 35.00,
            'additional_time_days' => 1,
            'status' => 'pending',
        ]);

        // Job Order 2: Ready for Pickup
        $jo2 = JobOrder::create([
            'ticket_number' => 'JO-2026-0002',
            'customer_id' => $c2->id,
            'device_id' => $d2->id,
            'technician_id' => $tech2->id,
            'status' => 'Ready for Pickup',
            'priority' => 'Urgent',
            'reported_issue' => 'Liquid spill near keyboard. No power on attempt.',
            'estimated_completion_date' => now(),
            'labor_cost' => 120.00,
            'parts_cost' => 380.00,
            'service_fee' => 20.00,
            'discount_type' => 'fixed',
            'discount_value' => 20.00,
            'total_cost' => 500.00,
            'customer_notes' => 'Please save documents on desktop if possible.',
            'internal_notes' => 'Ultrasonic board cleaning performed. Corrosion around USB-C power rail fixed.',
            'qr_code' => 'JO-2026-0002',
        ]);
        JobOrderStatusHistory::create(['job_order_id' => $jo2->id, 'user_id' => $admin->id, 'status_from' => null, 'status_to' => 'Received', 'remarks' => 'Intake registered']);
        JobOrderStatusHistory::create(['job_order_id' => $jo2->id, 'user_id' => $techUser2->id, 'status_from' => 'Received', 'status_to' => 'Diagnosing', 'remarks' => 'Microscope inspection']);
        JobOrderStatusHistory::create(['job_order_id' => $jo2->id, 'user_id' => $techUser2->id, 'status_from' => 'Diagnosing', 'status_to' => 'Under Repair', 'remarks' => 'Cleaning corroded ICs']);
        JobOrderStatusHistory::create(['job_order_id' => $jo2->id, 'user_id' => $techUser2->id, 'status_from' => 'Under Repair', 'status_to' => 'Testing', 'remarks' => 'Stress test passed']);
        JobOrderStatusHistory::create(['job_order_id' => $jo2->id, 'user_id' => $techUser2->id, 'status_from' => 'Testing', 'status_to' => 'Ready for Pickup', 'remarks' => 'SMS notification sent to customer']);

        // Invoice for Job 2
        $inv2 = Invoice::create([
            'invoice_number' => 'INV-2026-0002',
            'job_order_id' => $jo2->id,
            'customer_id' => $c2->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => 520.00,
            'tax_amount' => 0.00,
            'discount_amount' => 20.00,
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
            'payment_status' => 'paid',
        ]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'item_type' => 'labor', 'description' => 'Logic Board Ultrasonic Clean & Micro-soldering', 'quantity' => 1, 'unit_price' => 120.00, 'total_price' => 120.00]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'item_type' => 'part', 'description' => 'MacBook Pro 16" Liquid Retina Assembly', 'quantity' => 1, 'unit_price' => 380.00, 'total_price' => 380.00]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'item_type' => 'fee', 'description' => 'Diagnostic & Rush Fee', 'quantity' => 1, 'unit_price' => 20.00, 'total_price' => 20.00]);

        Payment::create([
            'payment_number' => 'PAY-2026-0001',
            'invoice_id' => $inv2->id,
            'amount' => 500.00,
            'payment_method' => 'Credit Card',
            'payment_date' => now(),
            'reference_number' => 'TXN-99882211',
            'user_id' => $cashierUser->id,
            'notes' => 'Paid in full via Visa card',
        ]);

        // Job Order 3: Diagnosing
        $jo3 = JobOrder::create([
            'ticket_number' => 'JO-2026-0003',
            'customer_id' => $c3->id,
            'device_id' => $d3->id,
            'technician_id' => $tech1->id,
            'status' => 'Diagnosing',
            'priority' => 'Normal',
            'reported_issue' => 'Battery drains within 2 hours and device gets extremely hot during charging',
            'estimated_completion_date' => now()->addDays(2),
            'labor_cost' => 30.00,
            'parts_cost' => 0.00,
            'service_fee' => 10.00,
            'discount_type' => 'fixed',
            'discount_value' => 0.00,
            'total_cost' => 40.00,
            'customer_notes' => null,
            'internal_notes' => 'Current draw testing on power supply.',
            'qr_code' => 'JO-2026-0003',
        ]);

        // Job Order 4: Released with Warranty
        $jo4 = JobOrder::create([
            'ticket_number' => 'JO-2026-0004',
            'customer_id' => $c4->id,
            'device_id' => $d4->id,
            'technician_id' => $tech2->id,
            'status' => 'Released',
            'priority' => 'Normal',
            'reported_issue' => 'SSD Upgrade to 1TB NVMe + OS Reinstallation',
            'estimated_completion_date' => now()->subDays(3),
            'labor_cost' => 45.00,
            'parts_cost' => 89.99,
            'service_fee' => 0.00,
            'discount_type' => 'fixed',
            'discount_value' => 0.00,
            'total_cost' => 134.99,
            'customer_notes' => 'Customer requested dual-boot configuration.',
            'internal_notes' => 'Crucial 1TB SSD installed. Benchmarked at 4800MB/s.',
            'qr_code' => 'JO-2026-0004',
            'released_at' => now()->subDays(2),
        ]);

        $w4 = Warranty::create([
            'job_order_id' => $jo4->id,
            'customer_id' => $c4->id,
            'device_id' => $d4->id,
            'warranty_period_days' => 90,
            'start_date' => now()->subDays(2),
            'end_date' => now()->subDays(2)->addDays(90),
            'coverage_details' => '90-Day Parts & Labor Warranty for SSD storage upgrade.',
            'status' => 'active',
        ]);

        // 8. Notifications Log & Audit Trail
        NotificationsLog::create([
            'type' => 'SMS',
            'recipient' => '+1 (555) 345-6789',
            'subject' => 'Repair Ready',
            'message' => 'Your MacBook Pro (Ticket #JO-2026-0002) is ready for pickup at iRepairShop! Total balance: ₱500.00.',
            'status' => 'sent',
            'triggered_by' => 'Status Change',
            'reference_type' => 'JobOrder',
            'reference_id' => $jo2->id,
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'user_name' => 'System Admin',
            'action' => 'login',
            'module' => 'Security',
            'description' => 'User logged in successfully',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
        ]);

        AuditLog::create([
            'user_id' => $techUser1->id,
            'user_name' => 'Marcus Vance',
            'action' => 'status_change',
            'module' => 'JobOrders',
            'description' => 'Updated ticket JO-2026-0001 status from Diagnosing to Under Repair',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
        ]);

        // 9. Appointments
        Appointment::create([
            'customer_name' => 'Sarah Connor',
            'phone' => '+1 (555) 901-2345',
            'email' => 'sarah.c@example.com',
            'device_type' => 'iPhone 15 Pro Max',
            'reported_issue' => 'Rear glass cracked, needs back glass laser replacement.',
            'preferred_date' => now()->addDays(1)->setHour(14)->setMinute(0),
            'status' => 'confirmed',
        ]);
    }
}
