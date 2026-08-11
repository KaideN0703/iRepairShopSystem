<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index()
    {
        $this->authorize('suppliers.view');
        $suppliers = Supplier::withCount(['parts', 'purchaseOrders'])->latest()->get();
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $this->authorize('suppliers.manage');
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('suppliers.manage');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier profile created!');
    }

    public function show(Supplier $supplier)
    {
        $this->authorize('suppliers.view');
        $supplier->load(['parts', 'purchaseOrders.items.part']);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $this->authorize('suppliers.manage');
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('suppliers.manage');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated successfully!');
    }

    public function createPurchaseOrder(Supplier $supplier)
    {
        $this->authorize('suppliers.manage');

        $parts = Part::where('supplier_id', $supplier->id)->get();
        if ($parts->isEmpty()) {
            $parts = Part::all();
        }
        return view('suppliers.create_po', compact('supplier', 'parts'));
    }

    public function storePurchaseOrder(Request $request, Supplier $supplier)
    {
        $this->authorize('suppliers.manage');

        $request->validate([
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:parts,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $latestPO = PurchaseOrder::latest('id')->first();
        $nextId = $latestPO ? $latestPO->id + 1 : 1;
        $poNumber = 'PO-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $po = PurchaseOrder::create([
            'po_number' => $poNumber,
            'supplier_id' => $supplier->id,
            'status' => 'ordered',
            'expected_date' => $request->expected_date,
            'notes' => $request->notes,
            'total_amount' => 0,
        ]);

        $total = 0;
        foreach ($request->items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_cost'];
            $total += $lineTotal;

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'part_id' => $item['part_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'total_cost' => $lineTotal,
            ]);

            // Automatically receive stock if status is ordered/received
            $part = Part::find($item['part_id']);
            if ($part) {
                $part->increment('stock_quantity', $item['quantity']);

                StockMovement::create([
                    'part_id' => $part->id,
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'reference_type' => 'PurchaseOrder',
                    'reference_id' => $po->id,
                    'user_id' => Auth::id(),
                    'notes' => "Restocked via Purchase Order {$poNumber}",
                ]);
            }
        }

        $po->total_amount = $total;
        $po->save();

        return redirect()->route('suppliers.show', $supplier)->with('success', "Purchase order {$poNumber} created and parts restocked!");
    }
}
