<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\StockMovement;
use App\Models\JobOrderPart;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $supplierId = $request->query('supplier_id');
        $lowStockOnly = $request->boolean('low_stock');

        $parts = Part::with(['category', 'supplier'])
            ->when($categoryId, function ($q, $cat) {
                $q->where('category_id', $cat);
            })
            ->when($supplierId, function ($q, $sup) {
                $q->where('supplier_id', $sup);
            })
            ->when($lowStockOnly, function ($q) {
                $q->whereColumn('stock_quantity', '<=', 'reorder_level');
            })
            ->when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('compatible_models', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15);

        $categories = PartCategory::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        // Valuation stats
        $totalValuationCost = Part::selectRaw('SUM(stock_quantity * cost_price) as total')->value('total') ?? 0;
        $totalValuationRetail = Part::selectRaw('SUM(stock_quantity * selling_price) as total')->value('total') ?? 0;
        $lowStockCount = Part::whereColumn('stock_quantity', '<=', 'reorder_level')->count();

        return view('inventory.index', compact('parts', 'categories', 'suppliers', 'search', 'categoryId', 'supplierId', 'lowStockOnly', 'totalValuationCost', 'totalValuationRetail', 'lowStockCount'));
    }

    public function create()
    {
        $categories = PartCategory::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        return view('inventory.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:part_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'sku' => 'required|string|unique:parts,sku',
            'barcode' => 'required|string|unique:parts,barcode',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'location_rack' => 'nullable|string|max:100',
            'compatible_models' => 'nullable|string',
        ]);

        $part = Part::create($validated);

        // Record initial stock movement
        if ($part->stock_quantity > 0) {
            StockMovement::create([
                'part_id' => $part->id,
                'type' => 'in',
                'quantity' => $part->stock_quantity,
                'unit_cost' => $part->cost_price,
                'reference_type' => 'InitialStock',
                'user_id' => Auth::id(),
                'notes' => 'Initial stock on creation',
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'create',
            'module' => 'Inventory',
            'description' => "Added part SKU {$part->sku} - {$part->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('inventory.index')->with('success', "Part '{$part->name}' created successfully!");
    }

    public function show(Part $part)
    {
        $part->load(['category', 'supplier', 'stockMovements.user', 'jobOrderParts.jobOrder.customer', 'jobOrderParts.jobOrder.device']);
        return view('inventory.show', compact('part'));
    }

    public function edit(Part $part)
    {
        $categories = PartCategory::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        return view('inventory.edit', compact('part', 'categories', 'suppliers'));
    }

    public function update(Request $request, Part $part)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:part_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'sku' => 'required|string|unique:parts,sku,' . $part->id,
            'barcode' => 'required|string|unique:parts,barcode,' . $part->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'location_rack' => 'nullable|string|max:100',
            'compatible_models' => 'nullable|string',
        ]);

        $part->update($validated);

        return redirect()->route('inventory.show', $part)->with('success', 'Part information updated successfully!');
    }

    public function adjustStock(Request $request, Part $part)
    {
        $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $qty = $request->quantity;
        $type = $request->type;

        if ($type === 'out' && $part->stock_quantity < $qty) {
            return back()->with('error', "Cannot stock out {$qty} units. Current stock is {$part->stock_quantity}.");
        }

        if ($type === 'in' || $type === 'adjustment') {
            $part->increment('stock_quantity', $qty);
            $change = $qty;
        } else {
            $part->decrement('stock_quantity', $qty);
            $change = -$qty;
        }

        StockMovement::create([
            'part_id' => $part->id,
            'type' => $type,
            'quantity' => $change,
            'unit_cost' => $part->cost_price,
            'reference_type' => 'ManualAdjustment',
            'user_id' => Auth::id(),
            'notes' => $request->notes ?? "Stock {$type} adjustment",
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'stock_adjust',
            'module' => 'Inventory',
            'description' => "Adjusted stock for SKU {$part->sku} ({$type} {$qty} units)",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Stock updated successfully for '{$part->name}'!");
    }

    public function printBarcode(Part $part)
    {
        return view('inventory.barcode', compact('part'));
    }

    public function destroy(Part $part)
    {
        $name = $part->name;
        $part->delete();

        return redirect()->route('inventory.index')->with('success', "Part '{$name}' deleted from inventory.");
    }
}
