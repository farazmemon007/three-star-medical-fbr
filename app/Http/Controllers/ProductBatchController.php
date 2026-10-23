<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Warehouse;
use App\Http\Traits\BranchScoped;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBatchController extends Controller
{
    use BranchScoped;

    // ======================= Per-Product Batch List =======================

    public function index($productId)
    {
        $product  = Product::findOrFail($productId);
        $batches  = ProductBatch::with('warehouse')
            ->where('product_id', $productId)
            ->when($this->getBranchId(), fn($q, $bid) => $q->where('branch_id', $bid))
            ->orderBy('exp_date', 'asc')
            ->get();

        return view('admin_panel.product.batches', compact('product', 'batches'));
    }

    // ======================= Opening Stock Form =======================

    public function openingStockForm()
    {
        Warehouse::ensureShopWarehousesExists();

        $products   = Product::with('packings')->orderBy('item_name')->get();
        $branchId   = $this->getBranchId();

        $shops = Warehouse::where('type', 'shop')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('warehouse_name')
            ->get();

        $warehouses = Warehouse::where('type', 'warehouse')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('warehouse_name')
            ->get();

        return view('admin_panel.product.batch_opening_stock', compact('products', 'shops', 'warehouses'));
    }

    public function storeOpeningBatch(Request $request)
    {
        $request->validate([
            'rows'                   => 'required|array|min:1',
            'rows.*.product_id'      => 'required|exists:products,id',
            'rows.*.warehouse_id'    => 'required|exists:warehouses,id',
            'rows.*.batch_number'    => 'nullable|string|max:100',
            'rows.*.mfg_date'        => 'nullable|date',
            'rows.*.exp_date'        => 'nullable|date',
            'rows.*.qty'             => 'required|numeric|min:0.01',
        ]);

        $branchId = $this->getBranchId();
        DB::transaction(function () use ($request, $branchId) {
            foreach ($request->rows as $row) {
                $batchBranchId = $branchId ?: (\App\Models\Warehouse::where('id', $row['warehouse_id'])->value('branch_id') ?: 1);
                
                $rawBatch = trim((string)($row['batch_number'] ?? ''));
                $isNoBatch = !empty($row['is_no_batch']) || empty($rawBatch) || in_array(strtoupper($rawBatch), ['NO-BATCH', 'NO BATCH', 'DEFAULT', 'N/A', 'NONE']);
                $batchNumber = $isNoBatch ? 'NO-BATCH' : $rawBatch;

                $rawExp = trim((string)($row['exp_date'] ?? ''));
                $isNoExp = !empty($row['is_no_expiry']) || empty($rawExp) || $rawExp === '2099-12-31';
                $expDate = $isNoExp ? '2099-12-31' : $rawExp;

                $batch = ProductBatch::create([
                    'product_id'      => $row['product_id'],
                    'warehouse_id'    => $row['warehouse_id'],
                    'branch_id'       => $batchBranchId,
                    'purchase_item_id' => null,
                    'batch_number'    => $batchNumber,
                    'mfg_date'        => !empty($row['mfg_date']) ? $row['mfg_date'] : null,
                    'exp_date'        => $expDate,
                    'qty_received'    => $row['qty'],
                    'qty_remaining'   => $row['qty'],
                    'source_type'     => 'opening_stock',
                    'status'          => 'active',
                ]);

                // Credit the live stock table (warehouse_stocks)
                \App\Services\StockService::credit(
                    $row['product_id'],
                    null,
                    $row['warehouse_id'],
                    $batchBranchId,
                    $row['qty']
                );

                // Insert into stock_movements ledger
                $batchLabel = $isNoBatch ? 'No Batch' : "Batch: {$batchNumber}";
                $expLabel = $isNoExp ? 'No Expiry' : "EXP: {$expDate}";
                $movementData = [
                    'product_id' => $row['product_id'],
                    'type'       => 'in',
                    'qty'        => $row['qty'],
                    'ref_type'   => 'INIT',
                    'ref_id'     => $batch->id,
                    'note'       => "Opening stock batch entry ({$batchLabel}, {$expLabel})",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (\Schema::hasColumn('stock_movements', 'branch_id')) {
                    $movementData['branch_id'] = $batchBranchId;
                }
                DB::table('stock_movements')->insert($movementData);
            }
        });

        return response()->json(['success' => true, 'message' => 'Opening stock batches saved successfully.']);
    }

    // ======================= AJAX: Batches for a Product (used in Sale form) =======================

    public function getForProduct(Request $request, $productId)
    {
        $warehouseId = $request->get('warehouse_id');

        $query = ProductBatch::with('product')
            ->where('product_id', $productId);

        if (!$request->has('include_empty')) {
            $query->available();
        }

        $batches = $query->when($this->getBranchId(), fn($q, $bid) => $q->where('branch_id', $bid))
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->orderBy('exp_date', 'asc')
            ->get(['id', 'batch_number', 'exp_date', 'mfg_date', 'qty_remaining', 'product_id', 'warehouse_id']);

        return response()->json($batches->map(function($b) {
            $mode = $b->product->size_mode ?? 'standard';
            $ppb = $b->product->pieces_per_box > 0 ? (int)$b->product->pieces_per_box : 1;
            
            // Check for unmigrated legacy decimal values securely
            $piecesFloat = (float) $b->qty_remaining;
            if ($mode === 'by_cartons' || $mode === 'by_carton') {
                $cleanStr = rtrim(rtrim((string)$b->qty_remaining, '0'), '.');
                if ($piecesFloat < 1000 && str_contains($cleanStr, '.')) {
                    $parts = explode('.', $cleanStr);
                    $bx = (int)($parts[0] ?? 0);
                    $pc = (int)($parts[1] ?? 0);
                    $oldPieces = ($bx * $ppb) + $pc;
                    if ($oldPieces > 0) $piecesFloat = $oldPieces;
                }
                $boxes = floor($piecesFloat / $ppb);
                $rem = round($piecesFloat - ($boxes * $ppb));
                $qtyStr = $rem > 0 ? "{$boxes}box+{$rem}piece" : "{$boxes}box";
            } else {
                $qtyStr = rtrim(rtrim((string)$piecesFloat, '0'), '.');
            }

            $isNoExp = $b->exp_date && $b->exp_date->year >= 2090;
            $expDisp = $isNoExp ? 'No Expiry' : $b->exp_date->format('M Y');
            $expLabel = $isNoExp ? 'No Expiry' : $b->exp_date->format('d M Y');
            $isNoBatch = in_array(strtoupper(trim((string)$b->batch_number)), ['NO-BATCH', 'NO BATCH', 'DEFAULT', 'N/A', 'NONE', '']);
            $batchDisp = $isNoBatch ? 'No Batch' : "Batch {$b->batch_number}";

            return [
                'id'            => $b->id,
                'label'         => "{$batchDisp} | EXP: {$expDisp} | Qty: {$qtyStr}",
                'batch_number'  => $isNoBatch ? 'No Batch' : $b->batch_number,
                'exp_date'      => $b->exp_date ? $b->exp_date->format('Y-m-d') : '',
                'exp_label'     => $expLabel,
                'qty_remaining' => $b->qty_remaining,
                'days_to_expiry'=> $b->days_to_expiry,
                'expiry_status' => $b->expiry_status,
                'warehouse_id'  => $b->warehouse_id,
            ];
        }));
    }

    // ======================= Expiry Report =======================

    public function expiryReport(Request $request)
    {
        $daysInput = $request->get('days', 180);
        $days      = ($daysInput === 'all') ? 0 : (int) $daysInput;
        $status    = $request->get('status', 'all'); 
        $catId     = $request->get('category_id');
        $brandId   = $request->get('brand_id');
        $whId      = $request->get('warehouse_id');

        $query = ProductBatch::with(['product.category_relation', 'product.brand', 'warehouse'])
            ->when($this->getBranchId(), fn($q, $bid) => $q->where('branch_id', $bid))
            ->where('qty_remaining', '>', 0)
            ->orderBy('exp_date', 'asc');

        if ($status === 'expired') {
            $query->where('exp_date', '<', now()->toDateString());
        } elseif ($status === 'expiring') {
            $query->whereBetween('exp_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
        } elseif ($days > 0) {
            $query->where('exp_date', '<=', now()->addDays($days)->toDateString());
        }

        if ($catId && $catId !== 'all') {
            $query->whereHas('product', fn($q) => $q->where('category_id', $catId));
        }
        if ($brandId && $brandId !== 'all') {
            $query->whereHas('product', fn($q) => $q->where('brand_id', $brandId));
        }
        if ($whId && $whId !== 'all') {
            $query->where('warehouse_id', $whId);
        }

        $batches = $query->get();

        // Extra data for filters
        $categories = \App\Models\Category::orderBy('name')->get();
        $brands     = \App\Models\Brand::orderBy('name')->get();
        $warehouses = Warehouse::when($this->getBranchId(), fn($q, $bid) => $q->where('branch_id', $bid))->get();

        $summary = [
            'expired'  => $batches->filter(fn($b) => $b->expiry_status === 'expired')->count(),
            'critical' => $batches->filter(fn($b) => $b->expiry_status === 'critical')->count(),
            'warning'  => $batches->filter(fn($b) => $b->expiry_status === 'warning')->count(),
            'ok'       => $batches->filter(fn($b) => $b->expiry_status === 'ok')->count(),
        ];

        return view('admin_panel.reports.expiry_report', [
            'batches' => $batches,
            'days'    => $daysInput,
            'status'  => $status,
            'summary' => $summary,
            'categories' => $categories,
            'brands'     => $brands,
            'warehouses' => $warehouses,
            'catId'      => $catId,
            'brandId'    => $brandId,
            'whId'       => $whId,
        ]);
    }

    // ======================= FEFO Deduction (called by SaleController) =======================

    /**
     * Deducts qty from batches using FEFO (or a specific batch).
     * Returns array of deductions: [['batch_id' => X, 'qty' => Y], ...]
     *
     * @param int $productId
     * @param float $qtyNeeded
     * @param int $warehouseId
     * @param int|null $batchId  — if set, deduct only from this batch
     */
    public static function deductFromBatches(int $productId, float $qtyNeeded, int $warehouseId, ?int $batchId = null, ?int $branchId = null): array
    {
        $branchId = $branchId ?? request()->active_branch_id ?? session('super_admin_branch_id') ?? 1;

        return DB::transaction(function () use ($productId, $qtyNeeded, $warehouseId, $batchId, $branchId) {
            $deductions = [];
            $remaining  = $qtyNeeded;

            if ($batchId) {
                // Manual batch selection
                $batch = ProductBatch::where('id', $batchId)
                    ->where('product_id', $productId)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->first();

                if (!$batch) {
                    $batch = ProductBatch::where('id', $batchId)->lockForUpdate()->first();
                }

                if ($batch) {
                    $batch->qty_remaining = max(0, (float)$batch->qty_remaining - $remaining);
                    if ($batch->qty_remaining <= 0) {
                        $batch->status = 'consumed';
                    }
                    $batch->save();
                    $deductions[] = [
                        'batch_id'     => $batch->id,
                        'qty'          => $remaining,
                        'warehouse_id' => $batch->warehouse_id ?? $warehouseId,
                    ];
                } else {
                    $fallbackBatch = ProductBatch::firstOrCreate(
                        [
                            'product_id'   => $productId,
                            'warehouse_id' => $warehouseId,
                            'branch_id'    => $branchId,
                            'batch_number' => 'NO-BATCH',
                        ],
                        [
                            'mfg_date'      => null,
                            'exp_date'      => null,
                            'qty_received'  => 0,
                            'qty_remaining' => 0,
                            'source_type'   => 'opening_stock',
                            'status'        => 'consumed',
                        ]
                    );
                    $deductions[] = [
                        'batch_id'     => $fallbackBatch->id,
                        'qty'          => $remaining,
                        'warehouse_id' => $warehouseId,
                    ];
                }
                $remaining = 0;
            } else {
                // FEFO Auto mode — sort by earliest expiry
                // 1. Try preferred warehouse first
                $batches = ProductBatch::available()
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('branch_id', $branchId)
                    ->orderByRaw('CASE WHEN exp_date IS NULL THEN 1 ELSE 0 END, exp_date ASC, id ASC')
                    ->lockForUpdate()
                    ->get();

                // 2. Fallback: If preferred warehouse doesn't have enough stock, query other warehouses in the same branch
                if ($batches->sum('qty_remaining') < $remaining) {
                    $otherBatches = ProductBatch::available()
                        ->where('product_id', $productId)
                        ->where('branch_id', $branchId)
                        ->where('warehouse_id', '!=', $warehouseId)
                        ->orderByRaw('CASE WHEN exp_date IS NULL THEN 1 ELSE 0 END, exp_date ASC, id ASC')
                        ->lockForUpdate()
                        ->get();
                    $batches = $batches->concat($otherBatches);
                }

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;

                    $deductQty = min((float)$batch->qty_remaining, $remaining);
                    $batch->qty_remaining -= $deductQty;
                    if ($batch->qty_remaining <= 0) {
                        $batch->status = 'consumed';
                    }
                    $batch->save();

                    $deductions[] = ['batch_id' => $batch->id, 'qty' => $deductQty, 'warehouse_id' => $batch->warehouse_id];
                    $remaining   -= $deductQty;
                }

                if ($remaining > 0) {
                    // Check if there is live unbatched stock in warehouse_stocks for this product
                    $whStocks = DB::table('warehouse_stocks')
                        ->where('product_id', $productId)
                        ->where('branch_id', $branchId)
                        ->where('total_pieces', '>', 0)
                        ->orderByRaw("CASE WHEN warehouse_id = {$warehouseId} THEN 0 ELSE 1 END")
                        ->get();

                    foreach ($whStocks as $ws) {
                        if ($remaining <= 0) break;

                        $trackedInBatches = (float) ProductBatch::where('product_id', $productId)
                            ->where('warehouse_id', $ws->warehouse_id)
                            ->where('branch_id', $branchId)
                            ->where('status', 'active')
                            ->sum('qty_remaining');

                        $unbatchedInWarehouse = max(0, (float)$ws->total_pieces - $trackedInBatches);

                        if ($unbatchedInWarehouse > 0) {
                            $autoBatch = ProductBatch::create([
                                'product_id'       => $productId,
                                'warehouse_id'     => $ws->warehouse_id,
                                'branch_id'        => $branchId,
                                'batch_number'     => 'NO-BATCH',
                                'mfg_date'         => null,
                                'exp_date'         => null,
                                'qty_received'     => $unbatchedInWarehouse,
                                'qty_remaining'    => $unbatchedInWarehouse,
                                'source_type'      => 'opening_stock',
                                'status'           => 'active',
                            ]);

                            $deductQty = min((float)$autoBatch->qty_remaining, $remaining);
                            $autoBatch->qty_remaining -= $deductQty;
                            if ($autoBatch->qty_remaining <= 0) {
                                $autoBatch->status = 'consumed';
                            }
                            $autoBatch->save();

                            $deductions[] = [
                                'batch_id'     => $autoBatch->id,
                                'qty'          => $deductQty,
                                'warehouse_id' => $ws->warehouse_id,
                            ];
                            $remaining -= $deductQty;
                        }
                    }
                }

                // If still remaining (insufficient stock / 0 stock), create or link to fallback batch so sale is never blocked
                if ($remaining > 0) {
                    $fallbackBatch = ProductBatch::firstOrCreate(
                        [
                            'product_id'   => $productId,
                            'warehouse_id' => $warehouseId,
                            'branch_id'    => $branchId,
                            'batch_number' => 'NO-BATCH',
                        ],
                        [
                            'mfg_date'      => null,
                            'exp_date'      => null,
                            'qty_received'  => 0,
                            'qty_remaining' => 0,
                            'source_type'   => 'opening_stock',
                            'status'        => 'consumed',
                        ]
                    );

                    $deductions[] = [
                        'batch_id'     => $fallbackBatch->id,
                        'qty'          => $remaining,
                        'warehouse_id' => $warehouseId,
                    ];
                    $remaining = 0;
                }
            }

            return $deductions;
        });
    }

    /**
     * Returns qty back to a specific batch (used for DC Cancel/Edit).
     */
    public static function returnToBatch(int $batchId, float $qtyToReturn): bool
    {
        return DB::transaction(function () use ($batchId, $qtyToReturn) {
            $batch = ProductBatch::find($batchId);
            if (!$batch) return false;

            $batch->qty_remaining += $qtyToReturn;
            if ($batch->qty_remaining > 0) {
                $batch->status = 'active';
            }
            return $batch->save();
        });
    }

    // ======================= Batch Actions (Ledger & Discard) =======================

    public function batchLedger($id)
    {
        $batch = ProductBatch::with(['product', 'warehouse', 'purchaseItem.purchase'])->findOrFail($id);
        
        $movements = collect();

        // 1. Opening / Source
        if ($batch->source_type === 'opening_stock') {
            $movements->push([
                'date' => $batch->created_at,
                'type' => 'IN',
                'ref'  => 'Opening Stock',
                'qty'  => $batch->qty_received,
                'note' => 'Initial batch entry'
            ]);
        } elseif ($batch->purchaseItem) {
            $movements->push([
                'date' => $batch->purchaseItem->purchase->purchase_date ?? $batch->created_at,
                'type' => 'IN',
                'ref'  => 'Purchase #' . ($batch->purchaseItem->purchase->invoice_no ?? 'N/A'),
                'qty'  => $batch->qty_received,
                'note' => 'Stock inward from purchase'
            ]);
        }

        // 2. Deliveries (OUT)
        $deliveries = \App\Models\DeliveryNoteItem::where('batch_id', $batch->id)
            ->with('dcNote.customer')
            ->get();
        
        foreach ($deliveries as $di) {
            $movements->push([
                'date' => $di->dcNote->delivery_date ?? $di->created_at,
                'type' => 'OUT',
                'ref'  => 'Delivery #' . ($di->dcNote->dc_no ?? 'N/A'),
                'qty'  => $di->total_pieces,
                'note' => 'Customer: ' . ($di->dcNote->customer->customer_name ?? 'N/A')
            ]);
        }

        // 3. Returns (IN)
        $returns = \App\Models\DeliveryReturnNoteItem::where('batch_id', $batch->id)
            ->with('deliveryReturnNote.customer')
            ->get();
        
        foreach ($returns as $ri) {
            $movements->push([
                'date' => $ri->deliveryReturnNote->return_date,
                'type' => 'IN',
                'ref'  => 'Return #' . $ri->deliveryReturnNote->return_no,
                'qty'  => $ri->total_pieces,
                'note' => 'Customer: ' . ($ri->deliveryReturnNote->customer->customer_name ?? 'N/A')
            ]);
        }

        // 4. Manual Discards
        $discards = DB::table('stock_movements')
            ->where('ref_type', 'batch_discard')
            ->where('ref_id', $batch->id)
            ->get();
        
        foreach ($discards as $mv) {
            $movements->push([
                'date' => $mv->created_at,
                'type' => 'OUT',
                'ref'  => 'Manual Discard',
                'qty'  => $mv->qty,
                'note' => $mv->note
            ]);
        }

        $movements = $movements->sortBy('date');

        return view('admin_panel.reports.batch_ledger', compact('batch', 'movements'));
    }

    public function discard(Request $request, $id)
    {
        $batch = ProductBatch::findOrFail($id);
        $qty = $batch->qty_remaining;

        if ($qty <= 0) {
            return response()->json(['success' => false, 'message' => 'Batch already empty.']);
        }

        DB::transaction(function () use ($batch, $qty, $request) {
            $batch->qty_remaining = 0;
            $batch->status = 'discarded';
            $batch->save();

            $whStock = \App\Models\WarehouseStock::where('warehouse_id', $batch->warehouse_id)
                ->where('product_id', $batch->product_id)
                ->first();
            
            if ($whStock) {
                $whStock->total_pieces -= $qty;
                $ppb = $batch->product->pieces_per_box > 0 ? $batch->product->pieces_per_box : 1;
                $boxes = intdiv((int)max(0, $whStock->total_pieces), (int)$ppb);
                $rem = (int)max(0, $whStock->total_pieces) % (int)$ppb;
                $whStock->quantity = (float)($boxes . '.' . $rem);
                $whStock->save();
            }

            DB::table('stock_movements')->insert([
                'product_id' => $batch->product_id,
                'type' => 'out',
                'qty' => $qty,
                'ref_type' => 'batch_discard',
                'ref_id' => $batch->id,
                'note' => $request->get('note', 'Manual batch discard/disposal') . ' (Batch: ' . $batch->batch_number . ')',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Batch stock cleared and movement recorded.']);
    }
}
