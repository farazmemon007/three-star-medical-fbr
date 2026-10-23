<?php

namespace App\Http\Controllers;

use App\Http\Traits\BranchScoped;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\PriceLog;
use App\Models\ProductBatch;
use App\Models\ProductBooking;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    use BranchScoped;

    public function index(Request $request)
    {
        if ($request->mode == 'so') {
            return $this->orderIndex();
        }

        return $this->receiptIndex();
    }

    public function orderIndex()
    {
        $branchId = $this->getBranchId();
        $sales = Sale::with(['customer_relation', 'items.product', 'returns', 'payments', 'createdBy'])
            ->whereIn('sale_status', ['draft'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        return view('admin_panel.sale.sale_order.index', compact('sales'));
    }

    public function receiptIndex()
    {
        $branchId = $this->getBranchId();
        $sales = Sale::with(['customer_relation', 'items.product', 'returns', 'payments', 'createdBy'])
            ->whereIn('sale_status', ['post', 'un-post'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        return view('admin_panel.sale.sale_receipt_note.index', compact('sales'));
    }

    public function addsale(Request $request)
    {
        $branchId = $this->getBranchId();
        $Customer = Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $Warehouse = Warehouse::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $sales = Sale::with(['customer_relation', 'items.product'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();
        $nextInvoice = Sale::generateInvoiceNo($branchId, $request->mode == 'so' ? 'SO-' : 'SIN-');

        // Filter accounts (Cash/Bank) for Payment Voucher
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();

        // Fetch active employees with 'is_sale_officer' designation for commission attribution
        $employees = \App\Models\Hr\Employee::active()
            ->whereHas('designation', function ($q) {
                $q->where('is_sale_officer', 1);
            })
            ->orderBy('first_name')
            ->get();

        $dcNotesRaw = \App\Models\DeliveryNote::with(['sale', 'customer', 'items.product', 'items.saleItem', 'items.uom'])
            ->where(function($query) {
                $query->whereHas('sale', function($q) {
                    $q->whereIn('sale_status', ['delivered', 'in_delivery']);
                })
                ->orWhere(function($q) {
                    $q->where('delivery_type', 'advance')
                      ->whereNull('sale_id');
                });
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $dcNotes = collect();
        
        // Group DCs that have a sale_id (Standard flow)
        $standardGroups = $dcNotesRaw->whereNotNull('sale_id')->groupBy('sale_id');
        foreach ($standardGroups as $saleId => $group) {
            $firstDc = $group->first();
            $virtualDc = new \stdClass();
            $virtualDc->id = $firstDc->id;
            $virtualDc->dc_no = $group->pluck('dc_no')->implode(', ');
            $virtualDc->delivery_date = $firstDc->delivery_date;
            $virtualDc->customer_id = $firstDc->customer_id;
            $virtualDc->sale_id = $firstDc->sale_id;
            $virtualDc->sale = $firstDc->sale;
            $virtualDc->customer = $firstDc->customer;
            
            $allItems = collect();
            foreach($group as $dc) {
                $allItems = $allItems->concat($dc->items);
            }
            $virtualDc->items = $allItems;
            $virtualDc->net_amount = $group->sum('net_amount');
            $virtualDc->is_advance = false;
            
            $dcNotes->push($virtualDc);
        }

        // Add Advance DCs individually
        $advanceDcs = $dcNotesRaw->where('delivery_type', 'advance')->whereNull('sale_id');
        foreach ($advanceDcs as $dc) {
            $virtualDc = new \stdClass();
            $virtualDc->id = $dc->id;
            $virtualDc->dc_no = $dc->dc_no;
            $virtualDc->delivery_date = $dc->delivery_date;
            $virtualDc->customer_id = $dc->customer_id;
            $virtualDc->sale_id = null;
            $virtualDc->sale = null;
            $virtualDc->customer = $dc->customer;
            $virtualDc->items = $dc->items;
            $virtualDc->net_amount = $dc->net_amount;
            $virtualDc->is_advance = true;
            
            $dcNotes->push($virtualDc);
        }

        return view('admin_panel.sale.sale_receipt_note.create', compact('Warehouse', 'Customer', 'nextInvoice', 'accounts', 'employees', 'sales', 'dcNotes'));
    }

    public function searchpname(Request $request)
    {
        $q = $request->get('q');
        $warehouseId = $request->get('warehouse_id');

        if (!$warehouseId) {
            $branchId = $this->getBranchId();
            $defaultWh = Warehouse::when($branchId, fn($q) => $q->where('branch_id', $branchId))->first();
            $warehouseId = $defaultWh ? $defaultWh->id : 1;
        }

        $products = Product::with(['brand', 'packings'])
            ->leftJoin('warehouse_stocks', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'warehouse_stocks.product_id')
                    ->where('warehouse_stocks.warehouse_id', $warehouseId);
            })
            ->where(function ($query) use ($q) {
                $query->where('products.item_name', 'like', "%{$q}%")
                    ->orWhere('products.item_code', 'like', "%{$q}%")
                    ->orWhere('products.barcode_path', 'like', "%{$q}%");
            })
            ->select(
                'products.*',
                'warehouse_stocks.total_pieces as wh_stock',
                'warehouse_stocks.quantity as wh_box_qty'
            )
            ->limit(50)
            ->get()
            ->map(function ($product) {
                // Robust Calculation for Search Dropdown
                $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                $boxQty = (float) $product->wh_box_qty;
                $calcPieces = $boxQty * $ppb;

                // If calculated pieces from boxes differs from stored pieces, trust boxes
                if (abs($calcPieces - $product->wh_stock) > 0.1) {
                    $product->wh_stock = $calcPieces;
                }

                return $product;
            });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $sale = new Sale;
        if ($request->filled('draft_id')) {
            $sale = Sale::find($request->draft_id) ?? new Sale;
        }

        return $this->processSale($request, $sale);
    }

    public function edit(Sale $sale)
    {
        //
    }

    public function getDcDetails($id)
    {
        $dc = \App\Models\DeliveryNote::with(['items.product.packings', 'items.saleItem', 'customer', 'sale.employee', 'sale.items.product.packings'])->findOrFail($id);
        
        // Format dates cleanly as YYYY-MM-DD
        $dc->delivery_date_formatted = $dc->delivery_date ? \Carbon\Carbon::parse($dc->delivery_date)->format('Y-m-d') : '';
        $dc->so_date_formatted = ($dc->sale && ($dc->sale->so_date ?? $dc->sale->sale_date)) ? \Carbon\Carbon::parse($dc->sale->so_date ?? $dc->sale->sale_date)->format('Y-m-d') : '';
        $dc->sale_date_formatted = ($dc->sale && $dc->sale->sale_date) ? \Carbon\Carbon::parse($dc->sale->sale_date)->format('Y-m-d') : '';
        
        $dc->customer_name = $dc->customer->customer_name ?? '';
        $dc->employee_id = $dc->sale->employee_id ?? '';
        $dc->employee_name = ($dc->sale && $dc->sale->employee) ? trim(($dc->sale->employee->first_name ?? '') . ' ' . ($dc->sale->employee->last_name ?? '')) : '';
        $dc->commission_percentage = $dc->sale->commission_percentage ?? 0;

        // If this DC is part of a sale that has multiple DCs, fetch all items across those DCs
        if ($dc->sale_id) {
            $allDcs = \App\Models\DeliveryNote::where('sale_id', $dc->sale_id)->with(['items.product.packings', 'items.saleItem'])->get();
            $allItems = collect();
            foreach ($allDcs as $d) {
                $allItems = $allItems->concat($d->items);
            }
            $dc->setRelation('items', $allItems);
            $dc->grouped_dc_no = $allDcs->pluck('dc_no')->implode(', ');
        } else {
            $dc->grouped_dc_no = $dc->dc_no;
        }

        return response()->json($dc);
    }

    public function getDraftDetails($id)
    {
        $sale = Sale::with(['items.product.packings', 'customer_relation', 'employee'])->findOrFail($id);
        
        $sale->so_date_formatted = ($sale->so_date ?? $sale->sale_date) ? \Carbon\Carbon::parse($sale->so_date ?? $sale->sale_date)->format('Y-m-d') : '';
        $sale->sale_date_formatted = $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d') : '';
        $sale->dc_date_formatted = ''; // Draft SO has no DC date
        
        $sale->customer_name = $sale->customer_relation->customer_name ?? '';
        $sale->employee_name = $sale->employee ? trim(($sale->employee->first_name ?? '') . ' ' . ($sale->employee->last_name ?? '')) : '';

        return response()->json($sale);
    }

    public function convertFromBooking($id)
    {
        $booking = ProductBooking::findOrFail($id);
        $customers = Customer::all();
        $products = explode(',', $booking->product);
        $codes = explode(',', $booking->product_code);
        $brands = explode(',', $booking->brand);
        $units = explode(',', $booking->unit);
        $prices = explode(',', $booking->per_price);
        $discounts = explode(',', $booking->per_discount);
        $qtys = explode(',', $booking->qty);
        $totals = explode(',', $booking->per_total);
        $colors_json = json_decode($booking->color, true);

        $items = [];
        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name' => $product->item_name ?? $p,
                'item_code' => $product->item_code ?? ($codes[$index] ?? ''),
                'uom' => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit' => $product->unit_id ?? ($units[$index] ?? ''),
                'price' => floatval($prices[$index] ?? 0),
                'discount' => floatval($discounts[$index] ?? 0),
                'qty' => intval($qtys[$index] ?? 1),
                'total' => floatval($totals[$index] ?? 0),
                'color' => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.booking_edit', [
            'Customer' => $customers,
            'booking' => $booking,
            'bookingItems' => $items,
        ]);
    }

    public function saleretun($id)
    {
        $sale = Sale::with(['items.product.unit', 'items.product.brand', 'customer_relation'])->findOrFail($id);
        $customers = Customer::all();
        $items = $this->_getSaleItems($sale);

        // Get Cash/Bank accounts for payment voucher
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();

        // Calculate return deadline from database settings
        $returnDeadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);
        $returnDeadline = $sale->created_at->copy()->addDays($returnDeadlineDays);
        $isWithinDeadline = now()->lte($returnDeadline);

        // Get already returned quantities for this sale
        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereIn('return_status', ['approved', 'completed'])
            ->get();

        // Calculate max returnable for each item
        foreach ($items as &$item) {
            $returned = 0;
            foreach ($alreadyReturned as $return) {
                $returnProductIds = explode(',', $return->product_code);
                $returnQtys = explode(',', $return->qty);

                foreach ($returnProductIds as $idx => $code) {
                    if (trim($code) === $item['item_code']) {
                        $returned += (float) ($returnQtys[$idx] ?? 0);
                    }
                }
            }

            $item['already_returned'] = $returned;
            $item['max_returnable'] = max(0, $item['qty'] - $returned);
        }

        return view('admin_panel.sale.return.create', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
            'accounts' => $accounts,
            'returnDeadline' => $returnDeadline,
            'isWithinDeadline' => $isWithinDeadline,
            'returnDeadlineDays' => $returnDeadlineDays,
        ]);
    }

    public function storeSaleReturn(Request $request)
    {
        // Validation
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'customer' => 'required|exists:customers,id',
            'product_id' => 'required|array|min:1',
            'qty' => 'required|array',
            'payment_account_id' => 'required|array|min:1',
            'payment_amount' => 'required|array|min:1',
            'quality_status' => 'nullable|in:good,damaged,defective,pending_inspection',
        ]);

        // Load the sale
        $sale = Sale::findOrFail($request->sale_id);

        // --- VALIDATION 1: Return Deadline Policy ---
        $returnDeadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);

        // Check if returns are disabled (0 days = no returns allowed)
        if ($returnDeadlineDays == 0) {
            return back()->with('error', 'Returns are currently disabled by store policy.');
        }

        $returnDeadline = $sale->created_at->copy()->addDays($returnDeadlineDays);
        $isWithinDeadline = now()->lte($returnDeadline);

        // Check if return is past deadline
        if (! $isWithinDeadline) {
            $user = auth()->user();
            $isSuperAdmin = $user->hasRole('Super Admin');
            $canApprovePastDeadline = $user->can_approve_past_deadline_returns ?? false;

            // Only Super Admin or users with special permission can approve past deadline returns
            if (! $isSuperAdmin && ! $canApprovePastDeadline) {
                $daysLate = now()->diffInDays($returnDeadline);

                return back()->with('error', "Return period expired! This sale is {$daysLate} days past the {$returnDeadlineDays}-day return deadline (Sale Date: {$sale->created_at->format('d-M-Y')}). Only Super Admin can approve past deadline returns.");
            }

            // Log that this is a past-deadline return approved by authorized user
            \Log::info("Past deadline return approved by {$user->name} (ID: {$user->id}) for Sale #{$sale->id}");
        }

        // --- VALIDATION 2: Partial Return - Prevent returning more than sold ---
        $product_ids = $request->product_id ?? [];
        $quantities = $request->qty ?? [];

        // Get already returned quantities
        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereIn('return_status', ['approved', 'completed', 'pending']) // Include pending to prevent duplicate submissions
            ->get();

        foreach ($product_ids as $index => $product_id) {
            $returnQty = (float) ($quantities[$index] ?? 0);

            if ($returnQty <= 0) {
                continue;
            }

            // Find original sale item
            $saleItem = $sale->items->where('product_id', $product_id)->first();

            if (! $saleItem) {
                return back()->with('error', "Product ID {$product_id} was not found in the original sale.");
            }

            // Calculate already returned quantity for this product
            $previouslyReturned = 0;
            foreach ($alreadyReturned as $return) {
                $returnProductIds = $request->product_id;
                $returnQtys = explode(',', $return->qty);

                // Match by product_id in the combined string
                $productCodes = explode(',', $return->product_code);
                foreach ($productCodes as $idx => $code) {
                    $product = \App\Models\Product::where('item_code', trim($code))->first();
                    if ($product && $product->id == $product_id) {
                        $previouslyReturned += (float) ($returnQtys[$idx] ?? 0);
                    }
                }
            }

            $maxReturnable = $saleItem->total_pieces - $previouslyReturned;

            if ($returnQty > $maxReturnable) {
                $productName = $saleItem->product_name ?? "Product #{$product_id}";

                return back()->with('error', "Cannot return {$returnQty} pieces of '{$productName}'. Maximum returnable: {$maxReturnable} pieces (Sold: {$saleItem->total_pieces}, Already Returned: {$previouslyReturned}).");
            }
        }

        DB::beginTransaction();

        try {
            $warehouseId = (int) ($request->input('warehouse_id', 1));

            $product_names = $request->product ?? [];
            $product_codes = $request->item_code ?? [];
            $brands = $request->uom ?? [];
            $units = $request->unit ?? [];
            $prices = $request->price ?? [];
            $discounts = $request->item_disc ?? [];
            $totals = $request->total ?? [];
            $colors = $request->color ?? [];

            $combined_products = $combined_codes = $combined_brands = $combined_units = [];
            $combined_prices = $combined_discounts = $combined_qtys = $combined_totals = $combined_colors = [];

            $jsonItems = [];
            $total_items = 0;

            // Process each returned item logic
            foreach ($product_ids as $index => $product_id) {
                $qty = max(0.0, (float) ($quantities[$index] ?? 0));
                $price = max(0.0, (float) ($prices[$index] ?? 0));

                if (! $product_id || $qty <= 0) {
                    continue;
                }

                // Add to JSON structure for later processing
                $jsonItems[] = [
                    'product_id' => $product_id,
                    'qty' => $qty,
                    'price' => $price,
                ];

                $combined_products[] = $product_names[$index] ?? '';
                $combined_codes[] = $product_codes[$index] ?? '';
                $combined_brands[] = $brands[$index] ?? '';
                $combined_units[] = $units[$index] ?? '';
                $combined_prices[] = $price;
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[] = $qty;
                $combined_totals[] = $totals[$index] ?? 0;

                $decodedColor = $colors[$index] ?? [];
                $combined_colors[] = is_array($decodedColor) ? json_encode($decodedColor) : json_encode((array) json_decode($decodedColor, true));

                // NOTE: Stock updates are now deferred to the approval step
                $total_items += $qty;
            }

            // Prepare Payment Details
            $paymentAccountIds = $request->payment_account_id ?? [];
            $paymentAmounts = $request->payment_amount ?? [];
            $jsonPayments = [];

            foreach ($paymentAccountIds as $idx => $id) {
                if (($paymentAmounts[$idx] ?? 0) > 0) {
                    $jsonPayments[] = ['account_id' => $id, 'amount' => $paymentAmounts[$idx]];
                }
            }

            // Create Sale Return Record
            $saleReturn = new SaleReturn;
            $saleReturn->sale_id = $request->sale_id;
            $saleReturn->customer = $request->customer;
            $saleReturn->reference = $request->reference;
            $saleReturn->product = implode(',', $combined_products);
            $saleReturn->product_code = implode(',', $combined_codes);
            $saleReturn->brand = implode(',', $combined_brands);
            $saleReturn->unit = implode(',', $combined_units);
            $saleReturn->per_price = implode(',', $combined_prices);
            $saleReturn->per_discount = implode(',', $combined_discounts);
            $saleReturn->qty = implode(',', $combined_qtys);
            $saleReturn->per_total = implode(',', $combined_totals);
            $saleReturn->color = json_encode($combined_colors);
            $saleReturn->total_amount_Words = $request->total_amount_Words;
            $saleReturn->total_bill_amount = $request->total_subtotal;
            $saleReturn->total_extradiscount = $request->total_extra_cost;
            $saleReturn->total_net = $request->total_net;
            $saleReturn->cash = $request->cash;
            $saleReturn->card = $request->card;
            $saleReturn->change = $request->change;
            $saleReturn->total_items = $total_items;
            $saleReturn->return_note = $request->return_note;

            // Store comprehensive data for later processing
            $saleReturn->refund_details = json_encode([
                'items' => $jsonItems,
                'payments' => $jsonPayments,
                'warehouse_id' => $warehouseId,
                'customer_id' => $request->customer,
                'total_net' => $request->total_net,
            ]);

            // --- WORKFLOW STATUS FIELDS ---
            $autoApproveThreshold = \App\Models\SystemSetting::get('return_auto_approve_threshold', 0);
            $returnAmount = (float) $request->total_net;
            $requireApproval = \App\Models\SystemSetting::get('return_require_approval', true);

            if (($autoApproveThreshold > 0 && $returnAmount <= $autoApproveThreshold) || ! $requireApproval) {
                $saleReturn->return_status = 'approved';
                // Dates set in _processApproval
            } else {
                $saleReturn->return_status = 'pending';
            }

            // Quality Status
            $saleReturn->quality_status = $request->quality_status ?? 'pending_inspection';
            if ($request->quality_status && in_array($request->quality_status, ['good', 'damaged', 'defective'])) {
                $saleReturn->inspected_by = auth()->id();
            }

            // Return Deadline
            $saleReturn->return_deadline = $returnDeadline;
            $saleReturn->is_within_deadline = $isWithinDeadline;

            $saleReturn->save();

            // If Approved, process transactions immediately
            if ($saleReturn->return_status === 'approved') {
                $this->_processApproval($saleReturn);
            }

            DB::commit();

            // Create notification for super admins
            try {
                \App\Models\SystemNotification::createSaleReturnNotification($saleReturn, $sale);
            } catch (\Exception $e) {
                \Log::error('Notification creation failed: '.$e->getMessage());
                // Don't fail the return process if notification fails
            }

            return redirect()->route('sale.index')->with('success', 'Sale return processed successfully with journal entries and payment voucher.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sale Return Error: '.$e->getMessage());

            return back()->with('error', 'Sale return failed: '.$e->getMessage());
        }
    }

    public function salereturnview()
    {
        $branchId = $this->getBranchId();
        $SaleReturns = SaleReturn::with(['sale.customer_relation', 'customer_relation'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate stats
        $stats = [
            'total' => $SaleReturns->count(),
            'pending' => $SaleReturns->where('return_status', 'pending')->count(),
            'approved' => $SaleReturns->where('return_status', 'approved')->count(),
            'rejected' => $SaleReturns->where('return_status', 'rejected')->count(),
            'completed' => $SaleReturns->where('return_status', 'completed')->count(),
        ];

        return view('admin_panel.sale.return.index', compact('SaleReturns', 'stats'));
    }

    /**
     * Approve a sale return
     */
    public function approveReturn($id)
    {
        try {
            $return = SaleReturn::findOrFail($id);

            // Check if already processed
            if ($return->return_status !== 'pending') {
                return back()->with('error', 'This return has already been processed.');
            }

            DB::beginTransaction();

            // If we have refund_details, it means we used the new workflow where
            // actions were deferred until approval.
            if (! empty($return->refund_details)) {
                $this->_processApproval($return);
            } else {
                // Legacy support: If no refund_details, it means stock/accounting
                // were likely done at creation time (old behavior), so just update status.
                $return->return_status = 'approved';
                $return->approved_by = auth()->id();
                $return->approved_at = now();
                $return->save();
            }

            DB::commit();

            return back()->with('success', 'Return approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Return approval failed: '.$e->getMessage());

            return back()->with('error', 'Failed to approve return: '.$e->getMessage());
        }
    }

    /**
     * Process the physical and financial transactions for a return
     */
    private function _processApproval($saleReturn)
    {
        $data = is_string($saleReturn->refund_details) ? json_decode($saleReturn->refund_details, true) : $saleReturn->refund_details;

        if (! $data) {
            throw new \Exception('Invalid return data for processing');
        }

        $warehouseId = $data['warehouse_id'] ?? 1; // Default to 1 if missing
        $items = $data['items'] ?? [];
        $payments = $data['payments'] ?? [];
        $srMovements = [];

        // 1. Update Stock & Original Sale Items
        $sale = Sale::find($saleReturn->sale_id);

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $qty = (float) $item['qty'];

            if ($qty <= 0) {
                continue;
            }

            // Update Warehouse Stock — UOM-aware credit for Sale Return
            $uomId      = $item['uom_id'] ?? null;
            $branchId   = \App\Models\Warehouse::where('id', $warehouseId)->value('branch_id') ?? 1;
            StockService::credit($productId, $uomId, $warehouseId, $branchId, $qty);

            // Prepare Stock Movement
            $srMovements[] = [
                'product_id' => $productId,
                'type' => 'in',
                'qty' => $qty,
                'ref_type' => 'SR',
                'ref_id' => $saleReturn->id,
                'note' => 'Sale return approved',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Update Sales Item (Decrement sold quantity)
            if ($sale && $sale->items) {
                $saleItem = $sale->items->where('product_id', $productId)->first();
                if ($saleItem) {
                    $saleItem->total_pieces = max(0, $saleItem->total_pieces - $qty);
                    $prod = \App\Models\Product::find($productId);
                    $ppb = ($prod && $prod->pieces_per_box > 0) ? (int)$prod->pieces_per_box : 1;
                    
                    $boxes = intdiv((int)$saleItem->total_pieces, $ppb);
                    $remPieces = (int)$saleItem->total_pieces % $ppb;
                    
                    $saleItem->qty = (float)($boxes . '.' . $remPieces);
                    $saleItem->loose_pieces = $remPieces;
                    $saleItem->save();
                }
            }
        }

        // Inert Stock Movements
        if (! empty($srMovements)) {
            DB::table('stock_movements')->insert($srMovements);
        }

        // 2. Journal Entries
        $customer = Customer::find($data['customer_id'] ?? $saleReturn->customer);
        $returnAmount = (float) $saleReturn->total_net;
        $date = now()->format('Y-m-d');
        $journalService = app(\App\Services\JournalEntryService::class);
        $balanceService = app(\App\Services\BalanceService::class);

        // Accounts
        $arAccountId = $balanceService->getAccountsReceivableId();
        $salesAccountId = $balanceService->getSalesRevenueId();

        if ($returnAmount > 0) {
            // Debit Sales Return (or Sales Revenue)
            $journalService->recordEntry(
                $saleReturn,
                $salesAccountId,
                $returnAmount,
                0,
                "Sale Return #{$saleReturn->id} - Invoice #{$sale->invoice_no}",
                $date
            );

            // Credit Customer (AR)
            $journalService->recordEntry(
                $saleReturn,
                $arAccountId,
                0,
                $returnAmount,
                "Sale Return #{$saleReturn->id} - Invoice #{$sale->invoice_no}",
                $date,
                $customer
            );
        }

        // 3. Process Payments (Refunds)
        $totalPaid = 0;
        foreach ($payments as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            $accountId = $payment['account_id'];

            if ($amount <= 0 || ! $accountId) {
                continue;
            }

            $totalPaid += $amount;

            // Credit Cash/Bank (Money Out)
            $journalService->recordEntry(
                $saleReturn,
                $accountId,
                0,
                $amount,
                "Refund Payment for Sale Return #{$saleReturn->id}",
                $date
            );
        }

        // Debit Customer (AR) for the refund amount (since we paid them back)
        // Logic: Return credited AR (balance down). Refund debits AR (balance up/neutralized).
        // Net result: Sales reversed, Cash paid out. Customer balance neutral.
        if ($totalPaid > 0) {
            $journalService->recordEntry(
                $saleReturn,
                $arAccountId,
                $totalPaid,
                0,
                "Refund Payment for Sale Return #{$saleReturn->id}",
                $date,
                $customer
            );

            // Create Payment Voucher Record
            \App\Models\CustomerPayment::create([
                'customer_id' => $customer->id,
                'admin_or_user_id' => auth()->id(),
                'voucher_no' => 'SR-'.$saleReturn->id,
                'payment_date' => $date,
                'payment_method' => 'Cash',
                'amount' => $totalPaid,
                'note' => "Refund for Sale Return #{$saleReturn->id}",
                'type' => 'refund',
            ]);

            // Auto-Generate Payment Voucher (PV) for Refund
            try {
                $pvid = \App\Models\PaymentVoucher::generateInvoiceNo();
                // Extract accounts and amounts
                $pvAccounts = [];
                $pvAmounts = [];
                foreach ($payments as $p) {
                    if (($p['amount'] ?? 0) > 0) {
                        $pvAccounts[] = $p['account_id'];
                        $pvAmounts[] = $p['amount'];
                    }
                }

                \App\Models\PaymentVoucher::create([
                    'pvid' => $pvid,
                    'party_id' => json_encode([$customer->id]),
                    'type' => json_encode(['customer']),
                    'total_amount' => $totalPaid,
                    'receipt_date' => $date,
                    'entry_date' => $date,
                    'row_account_id' => json_encode($pvAccounts),
                    'row_account_head' => json_encode([]),
                    'amount' => json_encode($pvAmounts),
                    'remarks' => "Refund for Sale Return #{$saleReturn->id}",
                ]);
            } catch (\Exception $e) {
                \Log::error('Refund PV Creation Failed: '.$e->getMessage());
            }
        }

        // 4. Update Status
        $saleReturn->return_status = 'approved';
        $saleReturn->approved_by = auth()->id();
        $saleReturn->approved_at = now();
        $saleReturn->save();
    }

    /**
     * Reject a sale return
     */
    public function rejectReturn(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            $return = SaleReturn::findOrFail($id);

            // Check if already processed
            if ($return->return_status !== 'pending') {
                return back()->with('error', 'This return has already been processed.');
            }

            // Update return status
            $return->return_status = 'rejected';
            $return->approved_by = auth()->id();
            $return->approved_at = now();
            $return->rejection_reason = $request->rejection_reason;
            $return->save();

            return back()->with('success', 'Return rejected successfully!');

        } catch (\Exception $e) {
            \Log::error('Return rejection failed: '.$e->getMessage());

            return back()->with('error', 'Failed to reject return: '.$e->getMessage());
        }
    }

    /**
     * Show detailed sale return information
     */
    public function saleReturnDetail($id)
    {
        $saleReturn = SaleReturn::with('customer_relation')->findOrFail($id);
        $sale = Sale::with(['customer_relation', 'items.product'])->findOrFail($saleReturn->sale_id);

        // Parse return items
        $returnItems = [];
        $productNames = explode(',', $saleReturn->product);
        $productCodes = explode(',', $saleReturn->product_code);
        $brands = explode(',', $saleReturn->brand);
        $units = explode(',', $saleReturn->unit);
        $prices = explode(',', $saleReturn->per_price);
        $discounts = explode(',', $saleReturn->per_discount);
        $quantities = explode(',', $saleReturn->qty);
        $totals = explode(',', $saleReturn->per_total);

        for ($i = 0; $i < count($productNames); $i++) {
            $returnItems[] = [
                'product_name' => $productNames[$i] ?? '',
                'product_code' => $productCodes[$i] ?? '',
                'brand' => $brands[$i] ?? '',
                'unit' => $units[$i] ?? '',
                'price' => $prices[$i] ?? 0,
                'discount' => $discounts[$i] ?? 0,
                'quantity' => $quantities[$i] ?? 0,
                'total' => $totals[$i] ?? 0,
            ];
        }

        // Get payment details
        $payments = \App\Models\CustomerPayment::where('note', 'like', "%Sale Return #{$saleReturn->id}%")->get();

        // Get journal entries
        $journalEntries = \App\Models\JournalEntry::where('source_type', 'App\Models\SaleReturn')
            ->where('source_id', $saleReturn->id)
            ->with('account')
            ->get();

        // Get approver and inspector info
        $approver = $saleReturn->approved_by ? \App\Models\User::find($saleReturn->approved_by) : null;
        $inspector = $saleReturn->inspected_by ? \App\Models\User::find($saleReturn->inspected_by) : null;

        return view('admin_panel.sale.return.detail', compact(
            'saleReturn',
            'sale',
            'returnItems',
            'payments',
            'journalEntries',
            'approver',
            'inspector'
        ));
    }

    public function saleinvoice($id)
    {
        $sale = Sale::with(['customer_relation.salesOfficer', 'items.batches', 'items.product.unit', 'items.product.brand', 'items.product.category_relation'])->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        // Calculate Balances for Invoice
        $previousBalance = 0;
        $currentBalance = 0;

        // Find the Journal Entry for this Sale (Debit side)
        // We look for where source is Sale and it's a Debit (Customer side)
        $journalEntry = \App\Models\JournalEntry::where('source_type', \App\Models\Sale::class)
            ->where('source_id', $sale->id)
            ->where('debit', '>', 0) // The debit to customer
            ->first();

        if ($journalEntry && $sale->customer_id) {
            // Calculate Previous Balance: Sum of (Dr - Cr) for all entries BEFORE this one
            $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->where('party_id', $sale->customer_id)
                ->where('id', '<', $journalEntry->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

            // Current Balance (after this bill, before payment if payment is separate)
            $currentBalance = $previousBalance + $sale->total_net;

            // Note: If payment was made, it's usually a separate receipt voucher (even if immediate).
            // So "Current Balance" here naturally excludes the payment unless we look for payment next.
            // But the user wants "Previous Bal +/- Current Amount = Net".
        } else {
            // Fallback if no journal entry (legacy or draft)
            $customer = $sale->customer_relation;
            $previousBalance = $customer->opening_balance ?? 0;
            // This is an estimate if we can't find the entry
        }

        return view('admin_panel.sale.saleinvoice', [
            'sale' => $sale,
            'saleItems' => $items,
            'previousBalance' => $previousBalance,
            'currentBalance' => $currentBalance,
        ]);
    }

    public function saleedit($id)
    {
        $branchId = $this->getBranchId();

        // 1. Fetch Sale with relations
        $sale = Sale::with(['items.product.warehouseStocks', 'customer_relation', 'employee', 'branch', 'payments.account'])->findOrFail($id);

        // 2. Data for Dropdowns
        $customer = Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $warehouse = Warehouse::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();
        
        $employees = \App\Models\Hr\Employee::active()
            ->whereHas('designation', function ($q) {
                $q->where('is_sale_officer', 1);
            })
            ->orderBy('first_name')
            ->get();

        // 3. For Import Modals (SO and DC)
        $sales = Sale::with(['customer_relation', 'items.product'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        $dcNotes = \App\Models\DeliveryNote::with(['sale', 'customer', 'items.product', 'items.saleItem'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $nextInvoiceNumber = $sale->invoice_no;

        // 4. Return the Edit Sale View
        return view('admin_panel.sale.edit_sale', compact('warehouse', 'customer', 'nextInvoiceNumber', 'accounts', 'sale', 'employees', 'sales', 'dcNotes'));
    }

    public function updatesale(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        if (in_array($sale->sale_status, ['posted', 'cancelled', 'returned'])) {
            return redirect()->back()->with('error', 'Cannot edit a '.$sale->sale_status.' sale.');
        }

        return $this->processSale($request, $sale);
    }

    public function saledc($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.saledc', ['sale' => $sale, 'saleItems' => $items]);
    }

    public function salereceipt($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        // Logic for Previous Balance (copied from saleinvoice)
        $journalEntry = \App\Models\JournalEntry::where('source_type', \App\Models\Sale::class)
            ->where('source_id', $sale->id)
            ->where('debit', '>', 0) // The debit to customer
            ->first();

        $previousBalance = 0;
        $currentBalance = 0;

        if ($journalEntry && $sale->customer_id) {
            // Calculate Previous Balance: Sum of (Dr - Cr) for all entries BEFORE this one
            $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->where('party_id', $sale->customer_id)
                ->where('id', '<', $journalEntry->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

            // Current Balance
            $currentBalance = $previousBalance + $sale->total_net;
        } else {
            // Fallback if no journal entry (legacy or draft)
            $customer = $sale->customer_relation;
            if ($customer) {
                // Try to get balance from ledger or master (simplified fallback)
                $previousBalance = $customer->previous_balance ?? 0;
            }
            $currentBalance = $previousBalance + $sale->total_net;
        }

        return view('admin_panel.sale.salereceipt', [
            'sale' => $sale,
            'saleItems' => $items,
            'previousBalance' => $previousBalance,
            'currentBalance' => $currentBalance,
        ]);
    }

    public function postFinal(Request $request)
    {
        // If the request contains full form data, we process it as an update + post
        // If it only contains an ID, we just transition state?
        // Based on previous code, it receives form data.
        $request->merge(['action' => 'post']);

        $id = $request->booking_id ?? $request->draft_id;
        if ($id) {
            $sale = Sale::findOrFail($id);
            if ($sale->sale_status === 'post') {
                return response()->json(['ok' => true, 'msg' => 'Already Posted', 'invoice_url' => route('sales.invoice', $sale->id)]);
            }

            return $this->processSale($request, $sale);
        }

        return $this->processSale($request, new Sale);
    }

    public function unpost($id)
    {
        try {
            DB::beginTransaction();
            $sale = Sale::with('items')->findOrFail($id);

            if ($sale->sale_status !== 'post') {
                return redirect()->back()->with('error', 'Only posted SRNs can be un-posted.');
            }

            // check permissions
            if (!auth()->user()->can('sales.unpost') && !auth()->user()->isSuperAdmin()) {
                return redirect()->back()->with('error', 'You do not have permission to un-post.');
            }

            // 2. Reverse Accounting (Vouchers, Ledger, Payments)
            $transactionService = app(\App\Services\TransactionService::class);
            $transactionService->reverseSaleAccounting($sale);

            // Revert Stock for Direct Sale (SIN) - only if no delivery notes exist (DC manages its own stock)
            $hasDcOnSale = \App\Models\DeliveryNote::where('sale_id', $sale->id)->where('status', '!=', 'cancelled')->exists();
            if ($sale->mode === 'sin' && !$hasDcOnSale) {
                $this->handleStockImpact($sale, 'in');

                // Revert batch stock
                foreach ($sale->items as $item) {
                    $batchDeductions = DB::table('sale_item_batches')->where('sale_item_id', $item->id)->get();
                    foreach ($batchDeductions as $bd) {
                        $batch = \App\Models\ProductBatch::find($bd->product_batch_id);
                        if ($batch) {
                            $batch->qty_remaining += $bd->qty_deducted;
                            if ($batch->qty_remaining > 0 && $batch->status === 'consumed') {
                                $batch->status = ($batch->exp_date && \Carbon\Carbon::parse($batch->exp_date)->lt(now())) ? 'expired' : 'active';
                            }
                            $batch->save();
                        }
                    }
                }
            }

            // 3. Update Status
            $sale->update(['sale_status' => 'un-post']);

            DB::commit();
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SRN un-posted successfully and reverted to draft.'
                ]);
            }

            return redirect()->back()->with('success', 'SRN un-posted successfully and reverted to draft.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("SRN Un-post Error: " . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $sale = Sale::with('items')->findOrFail($id);
            
            // Check if any item has been delivered
            $deliveredSum = $sale->items->sum('delivered_qty');
            if ($deliveredSum > 0) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'Cannot delete sale order. Delivery has already been processed for some or all items.'
                ], 422);
            }

            DB::transaction(function() use ($sale) {
                // Delete items
                $sale->items()->delete();
                // Delete sale
                $sale->delete();
            });

            return response()->json([
                'ok' => true,
                'msg' => 'Sale Order deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'msg' => 'Failed to delete: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processSale(Request $request, Sale $sale)
    {
        // 1. Validation
        $request->validate([
            'customer' => 'required|exists:customers,id',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array|min:1',
            'warehouse_id' => 'required|array',
        ]);

        // No longer preventing duplicate products as per user request to allow different UOMs/batches etc. in same sale.
        // if (count($request->product_id) !== count(array_unique($request->product_id))) {
        //     throw \Illuminate\Validation\ValidationException::withMessages(['product_id' => 'Duplicate products are not allowed in a single sale. Please merge quantities.']);
        // }

        if ($request->mode == 'so') {
            $status = 'draft';
        } else {
            $status = $request->action === 'post' ? 'post' : 'un-post';
        }

        // Concurrency Safe Transaction
        return DB::transaction(function () use ($request, $sale, $status) {

            // 2. Prepare Header Data
            $isNew = ! $sale->exists;
            $sale->customer_id           = $request->customer;
            $sale->employee_id           = $request->sales_officer_id;
            $sale->commission_percentage = $request->filled('commission_percentage') ? floatval($request->commission_percentage) : null;
            $sale->reference             = $request->reference;
            $sale->sale_date             = $request->purchase_date;
            $sale->vendor_bill_no        = $request->vendor_bill_no;
            $sale->dc_date               = $request->dc_date;
            $sale->order_no              = $request->order_no;
            $sale->sale_order_no         = $request->sale_order_no;
            $sale->so_date               = $request->so_date;
            
            $sale->total_amount_Words = $request->total_amount_Words;
            $sale->sale_status = $status;
            $sale->enable_hs_code = $request->enable_hs_code ? 1 : 0;
            $sale->branch_id   = $this->getBranchId();
            $sale->mode        = $request->mode ?? 'sin';
            if ($isNew) {
                $sale->created_by = auth()->id() ?? 1;
            }

            // Credit Days & Due Date (Optional)
            if ($request->filled('credit_days') && $request->credit_days > 0) {
                $creditDays = (int) $request->credit_days;
                $sale->credit_days = $creditDays;

                $baseDate = $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date) : now();
                $sale->due_date = $baseDate->addDays($creditDays);
            } else {
                $sale->credit_days = null;
                $sale->due_date = null;
            }

            // ONLY generate or set invoice number if it doesn't already have one,
            // OR if converting an existing draft Sale Order (SO-*) into a Sale Invoice Note (mode == 'sin')
            $isConvertingSoToSin = ($request->mode == 'sin' && $sale->invoice_no && str_starts_with($sale->invoice_no, 'SO-'));

            if (!$sale->invoice_no || $isConvertingSoToSin) {
                if ($isConvertingSoToSin && !$sale->sale_order_no) {
                    $sale->sale_order_no = $sale->invoice_no;
                }

                // Check if user provided manual invoice number
                if ($request->filled('invoice_no') && !str_starts_with(trim($request->invoice_no), 'SO-')) {
                    $manualInvoice = trim($request->invoice_no);

                    // Check for duplicates
                    $exists = Sale::where('invoice_no', $manualInvoice)
                        ->when($sale->id, fn($q) => $q->where('id', '!=', $sale->id))
                        ->exists();
                    if ($exists) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'invoice_no' => "Invoice number '{$manualInvoice}' already exists. Please use a different number or leave blank for auto-generation.",
                        ]);
                    }

                    $sale->invoice_no = $manualInvoice;
                } else {
                    // Auto-generate unique invoice number
                    $prefix = $request->mode == 'so' ? 'SO-' : ($request->mode == 'sin' ? 'SIN-' : 'SRN-');
                    $sale->invoice_no = $this->generateUniqueInvoiceNo($prefix);
                }
            }

            // We will calculate totals from verified items
            $total_bill = 0;
            $total_gst = 0;
            $total_inc_tax = 0;
            $total_adv_tax = 0;
            $total_items = 0;

            $sale->save(); // Save first to get ID

            // 3. Process Items
            $hasDcOnSale = $request->filled('dc_id') || ($sale->id && \App\Models\DeliveryNote::where('sale_id', $sale->id)->where('status', '!=', 'cancelled')->exists());

            // Delete old items if updating
            if (! $isNew) {
                // If this is a direct sale (SIN) and was previously posted, restore warehouse & batch stocks before deleting
                // (Only for direct sales that do NOT have delivery notes, because DC manages its own stock)
                if ($sale->mode == 'sin' && $sale->sale_status == 'post' && !$hasDcOnSale) {
                    $this->handleStockImpact($sale, 'in');

                    // Restore batch stocks
                    foreach ($sale->items as $item) {
                        $deductions = DB::table('sale_item_batches')->where('sale_item_id', $item->id)->get();
                        foreach ($deductions as $d) {
                            $batch = \App\Models\ProductBatch::where('id', $d->product_batch_id)->lockForUpdate()->first();
                            if ($batch) {
                                $batch->qty_remaining += $d->qty_deducted;
                                if ($batch->qty_remaining > 0 && $batch->status === 'consumed') {
                                    $batch->status = ($batch->exp_date && \Carbon\Carbon::parse($batch->exp_date)->lt(now())) ? 'expired' : 'active';
                                }
                                $batch->save();
                            }
                        }
                    }
                }

                // Delete old batch connections
                foreach ($sale->items as $item) {
                    DB::table('sale_item_batches')->where('sale_item_id', $item->id)->delete();
                }

                SaleItem::where('sale_id', $sale->id)->delete();
            }

            \Log::info('Processing Sale: ' . json_encode($request->only(['action', 'mode', 'customer', 'product_id', 'qty', 'loose_pieces'])));
            $productIds = $request->product_id;
            $quantities = $request->qty;
            $warehouses = $request->warehouse_id;
            $discounts = $request->item_disc ?? [];
            $packingNames = $request->pieces_per_box ?? []; 
            $uomFactors  = $request->item_uom_factor ?? [];

            foreach ($productIds as $index => $pid) {
                if (! $pid) {
                    \Log::info("Skip index $index: No product ID");
                    continue;
                }

                $qtyInput = (float) ($quantities[$index] ?? 0);
                $looseInput = (float) ($request->loose_pieces[$index] ?? 0);
                \Log::info("Item index $index: PID=$pid, Qty=$qtyInput, Loose=$looseInput");
                if ($qtyInput <= 0 && $looseInput <= 0) {
                    \Log::info("Skip index $index: zero qty");
                    continue;
                }

                $product = Product::findOrFail($pid);
                $uomName = $packingNames[$index] ?? 'Piece';
                $uomFactor = (float) ($uomFactors[$index] ?? 1);

                // Use SIN- for Sale Invoice Note and SO- for Sale Order
                // fall back to price_per_piece[] for legacy forms, then to DB sale price.
                $inputPrice = (float) ($request->price[$index] ?? $request->price_per_piece[$index] ?? 0);
                $oldMasterSalePrice = (float) $product->sale_price_per_piece;

                $dbPrice = $inputPrice > 0 ? $inputPrice : ($oldMasterSalePrice > 0 ? $oldMasterSalePrice : 0);

                // Update Master Price and Log if changed
                if ($inputPrice > 0 && $inputPrice != $oldMasterSalePrice) {
                    $product->update(['sale_price_per_piece' => $inputPrice]);
                    PriceLog::log($pid, 'sale', $oldMasterSalePrice, $inputPrice, 'SRN', $sale->invoice_no, "Sale price updated via SRN #{$sale->invoice_no}");
                }

                \Log::info("SaleItem #{$index}: Product {$product->item_name}, InputPrice: {$inputPrice}, FinalPrice: {$dbPrice}");

                $uomIdRaw = $request->uom_id[$index] ?? null;
                $isNewUom = ($uomIdRaw === 'NEW' || ($request->is_new_uom[$index] ?? '0') === '1');
                
                // If user provided a manual/typed UOM (e.g. KG, Number, Piece), prioritize it
                if (!empty($request->item_uom[$index])) {
                    $uomName = trim($request->item_uom[$index]);
                } elseif (!empty($request->uom[$index])) {
                    $uomName = trim($request->uom[$index]);
                } else {
                    $uomName = $isNewUom ? ($request->uom_name[$index] ?? $uomName) : ($packingNames[$index] ?? 'Piece');
                }

                if ($isNewUom) {
                    $uom = \App\Models\ProductUom::create([
                        'product_id' => $pid,
                        'name' => $uomName,
                        'pieces_per_box' => $uomFactor,
                        'sale_price' => $dbPrice * $uomFactor // Rough estimate
                    ]);
                    $resolvedUomId = $uom->id;
                } else {
                    $resolvedUomId = (is_numeric($uomIdRaw) && $uomIdRaw > 0) ? $uomIdRaw : null;
                }

                $ppb = (float) ($uomFactors[$index] ?? 1);
                
                // Paid Quantity
                $boxes = (float) ($request->qty[$index] ?? 0);
                $loose = (float) ($request->loose_pieces[$index] ?? 0);
                $totalPieces = ($boxes * $ppb) + $loose;
                $storedQtyBox = $boxes; // Store input boxes

                // Free Quantity (Now just total pieces in one field)
                $freeTotalPieces = (float) ($request->free_loose_pieces[$index] ?? 0);
                $storedFreeQty = $ppb > 0 ? (float)($freeTotalPieces / $ppb) : $freeTotalPieces;

                $discount = (float) ($discounts[$index] ?? 0);
                $discType = $request->item_disc_type[$index] ?? 'amount'; // 'amount' refers to total row discount (new default)
                $gstRate   = (float) ($request->gst[$index] ?? 0);
                $incTaxPct = (float) ($request->inc_tax[$index] ?? 0);  // Now treated as %
                $advTaxVal = (float) ($request->adv_tax[$index] ?? 0);
                $advTaxType = $request->adv_tax_type[$index] ?? 'percent';

                // Calculate Line Subtotal (after item discount) - Box Qty * Price
                $billableQty = $boxes + ($ppb > 0 ? ($loose / $ppb) : 0);
                $subTotal = round((float) ($billableQty * $dbPrice), 2);
                $calcDiscountAmount = 0;
                $calcDiscountPercent = 0;

                if ($discType === 'percent') {
                     $calcDiscountAmount = round($subTotal * $discount / 100, 2);
                     $calcDiscountPercent = $discount;
                } else {
                     // Fixed Amount (now treated as TOTAL row discount)
                     $calcDiscountAmount = round($discount, 2);
                     $calcDiscountPercent = $subTotal > 0 ? round(($discount / $subTotal) * 100, 2) : 0;
                }
                
                $postDiscSub = max(0, round($subTotal - $calcDiscountAmount, 2));

                // Pakistan Standard line calculation:
                // GST is ADDED on post-disc subtotal
                $rowGstAmount = round($postDiscSub * ($gstRate / 100), 2);
                // WHT is DEDUCTED (applied on post-disc subtotal, NOT on GST)
                $incTaxAmt    = round($postDiscSub * ($incTaxPct / 100), 2);
                // Advance Tax is ADDED
                $advTaxAmt    = ($advTaxType === 'percent') ? round($postDiscSub * ($advTaxVal / 100), 2) : round($advTaxVal, 2);
                // Line total stored: subtotal + GST - WHT + Adv
                $lineTotal    = round($postDiscSub + $rowGstAmount - $incTaxAmt + $advTaxAmt, 2);

                $saleItem = new SaleItem;
                $saleItem->sale_id = $sale->id;
                $saleItem->product_id = $pid;
                $saleItem->warehouse_id = $warehouses[$index] ?? 1;
                $saleItem->product_name = $product->item_name; // Store name snapshot

                $saleItem->qty = $storedQtyBox; // Store as Box equivalent for consistency
                $saleItem->total_pieces = $totalPieces;
                $saleItem->uom_id = $resolvedUomId;
                $saleItem->uom_name = $uomName;
                $saleItem->uom_factor = $uomFactor;
                $saleItem->loose_pieces = $loose;

                $saleItem->free_qty = $storedFreeQty;
                $saleItem->free_total_pieces = $freeTotalPieces;
                $saleItem->hs_code = $request->hs_code[$index] ?? $product->hs_code;

                $saleItem->price = $dbPrice;
                $saleItem->discount_percent = $calcDiscountPercent;
                $saleItem->discount_amount = $calcDiscountAmount;
                $saleItem->gst_percent = $gstRate;
                $saleItem->gst_amount  = $rowGstAmount;
                $saleItem->inc_tax     = $incTaxPct;   // store % for reference
                $saleItem->adv_tax     = $advTaxVal;   // store value for reference
                $saleItem->total = $lineTotal;

                // Meta
                $saleItem->brand_id = $product->brand_id;
                $saleItem->unit_id = $product->unit_id;
                $saleItem->size_mode = $product->size_mode;

                $saleItem->save();

                // Link Batches (Lot/Expiry tracking) and deduct if direct sale (SIN)
                $batchId = isset($request->batch_id[$index]) ? $request->batch_id[$index] : null;
                if ($request->filled('dc_id')) {
                    $dcId = (int)$request->dc_id;
                    $dcItem = \App\Models\DeliveryNoteItem::where('dc_note_id', $dcId)
                        ->where('product_id', $pid)
                        ->whereNull('sale_item_id')
                        ->first();
                    if ($dcItem) {
                        $dcItem->update(['sale_item_id' => $saleItem->id]);
                        
                        // Update delivered_qty on SaleItem to match delivered pcs
                        $saleItem->update(['delivered_qty' => $totalPieces]);
                        
                        // Link the batch deductions to this SaleItem
                        DB::table('sale_item_batches')
                            ->where('delivery_note_item_id', $dcItem->id)
                            ->update(['sale_item_id' => $saleItem->id]);
                    }
                } elseif ($hasDcOnSale) {
                    // Sale has active Delivery Notes: link any unlinked delivery note items to this sale item
                    $dcItems = \App\Models\DeliveryNoteItem::whereHas('dcNote', function($q) use ($sale) {
                            $q->where('sale_id', $sale->id)->where('status', '!=', 'cancelled');
                        })
                        ->where('product_id', $pid)
                        ->get();
                    $totalDelivered = 0;
                    foreach ($dcItems as $dci) {
                        $dci->update(['sale_item_id' => $saleItem->id]);
                        $totalDelivered += (float)$dci->total_pieces;
                        DB::table('sale_item_batches')
                            ->where('delivery_note_item_id', $dci->id)
                            ->update(['sale_item_id' => $saleItem->id]);
                    }
                    if ($totalDelivered > 0) {
                        $saleItem->update(['delivered_qty' => $totalDelivered]);
                    }
                } elseif ($request->mode == 'sin' && $status === 'post') {
                    $deductions = \App\Http\Controllers\ProductBatchController::deductFromBatches(
                        $pid,
                        $totalPieces,
                        $saleItem->warehouse_id,
                        $batchId,
                        $sale->branch_id
                    );
                    if (!empty($deductions[0]['warehouse_id'])) {
                        $saleItem->update(['warehouse_id' => $deductions[0]['warehouse_id']]);
                    }
                    foreach ($deductions as $d) {
                        DB::table('sale_item_batches')->insert([
                            'sale_item_id' => $saleItem->id,
                            'product_batch_id' => $d['batch_id'],
                            'qty_deducted' => $d['qty'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    if ($batchId) {
                        DB::table('sale_item_batches')->insert([
                            'sale_item_id' => $saleItem->id,
                            'product_batch_id' => $batchId,
                            'qty_deducted' => $totalPieces,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        // Auto-link the first available batch if not explicitly selected
                        $firstBatch = \App\Models\ProductBatch::where('product_id', $pid)
                            ->orderBy('exp_date', 'asc')
                            ->first();
                        if ($firstBatch) {
                            DB::table('sale_item_batches')->insert([
                                'sale_item_id' => $saleItem->id,
                                'product_batch_id' => $firstBatch->id,
                                'qty_deducted' => $totalPieces,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                $total_bill    += $postDiscSub;
                $total_gst     += $rowGstAmount;
                $total_inc_tax += $incTaxAmt;   // sum of WHT rupee amounts
                $total_adv_tax += $advTaxAmt;   // sum of Adv Tax rupee amounts
                $total_items += $totalPieces;
            }

            // Update Sale Totals
            $sale->total_bill_amount = round($total_bill, 2); // Pre-tax Pre-bill-disc
            $billDiscVal = (float) ($request->discount ?? 0);
            $billDiscType = $request->discount_type ?? 'amount';
            
            $calcBillDiscAmount = 0;
            if ($billDiscType === 'percent') {
                $calcBillDiscAmount = round($total_bill * ($billDiscVal / 100), 2);
            } else {
                $calcBillDiscAmount = round($billDiscVal, 2);
            }

            $sale->total_extradiscount = round($calcBillDiscAmount, 2);
            $sale->total_gst = round($total_gst, 2);
            $sale->total_inc_tax = round($total_inc_tax, 2);
            $sale->total_adv_tax = round($total_adv_tax, 2);
            
            $sale->total_freight  = (float)($request->freight_charges ?? $request->freight ?? $request->sum_freight ?? 0);
            $sale->total_expense  = (float)($request->extra_cost ?? $request->expense ?? $request->sum_expense ?? 0);
            $sale->total_fixed_tax = (float)($request->apply_gst ?? $request->sum_apply_gst ?? 0);

            // Pakistan Standard Net:
            // Invoice Total = (net_post_disc + freight + expense) + GST
            // Net Payable   = Invoice Total - WHT - Adv
            $netPostDisc        = round($total_bill - $calcBillDiscAmount, 2);
            $gstBase            = round($netPostDisc + $sale->total_freight + $sale->total_expense, 2);
            $invoiceTotal       = round($gstBase + $total_gst, 2);
            $sale->total_net    = round($invoiceTotal - $total_inc_tax + $total_adv_tax, 2);
            $sale->total_items  = $total_items;

            $sale->cash = $request->cash ?? 0;
            $sale->change = ($sale->cash - $sale->total_net);

            // Store Payment Details for Persistence (Draft/Editing)
            $payAccounts = $request->payment_account_id ?? [];
            $payAmounts  = $request->payment_amount ?? [];
            $paymentData = [];
            foreach($payAccounts as $idx => $accId) {
                if ($accId && ($payAmounts[$idx] ?? 0) > 0) {
                    $paymentData[] = [
                        'account_id' => $accId,
                        'amount' => (float)$payAmounts[$idx]
                    ];
                }
            }
            $sale->payment_details = $paymentData;

            // Calculate total_commission on tax-exclusive base (net amount minus GST & Sale Tax)
            if ($sale->commission_percentage !== null && floatval($sale->commission_percentage) > 0) {
                $gst = floatval($sale->total_gst ?? 0);
                $advTax = floatval($sale->total_adv_tax ?? 0);
                $taxExclusiveBase = max(0, floatval($sale->total_net) - $gst - $advTax);
                $sale->total_commission = round($taxExclusiveBase * (floatval($sale->commission_percentage) / 100), 2);
            }

            $sale->save();

            // 4. Handle Status Logic
            if ($status === 'post') {
                \Log::info('Proceeding to Auto-Receipt & Ledger logic for Sale #'.$sale->invoice_no);

                // 1. DEDUCT STOCK FROM WAREHOUSE (Only for direct sales with no delivery notes)
                if ($sale->mode == 'sin' && !$request->filled('dc_id') && !$hasDcOnSale) {
                    $this->handleStockImpact($sale, 'out');
                }

                // 2. LEGACY LEDGER: Post Invoice First (Increases Balance)
                // This ensures the CustomerLedger has the Debit entry before we potentially Credit it with a receipt.
                $this->updateLedger($sale);

                try {
                    $journalService = app(\App\Services\JournalEntryService::class);
                    $balanceService = app(\App\Services\BalanceService::class);

                    // Get account IDs dynamically
                    $arAccountId = $balanceService->getAccountsReceivableId($sale->branch_id);
                    $salesAccountId = $balanceService->getSalesRevenueId($sale->branch_id);
                    $date = $sale->created_at->format('Y-m-d');
                    $custForVoucher = $sale->customer_relation ?? \App\Models\Customer::find($sale->customer_id);

                    // --- LEDGER POSTING (THE INVOICE) ---
                    // Post directly to journal entries without creating a separate Journal Voucher document.
                    // In proper ERP, the sale invoice itself is the source document that posts to GL.
                    if ($custForVoucher && $sale->total_net > 0 && $arAccountId && $salesAccountId) {
                        // Dr Accounts Receivable (Customer owes us money)
                        $journalService->recordEntry(
                            $sale,
                            $arAccountId,
                            $sale->total_net,
                            0,
                            "Sale Invoice #{$sale->invoice_no}",
                            $date,
                            $custForVoucher
                        );

                        // Cr Sales Revenue (Income earned)
                        $journalService->recordEntry(
                            $sale,
                            $salesAccountId,
                            0,
                            $sale->total_net,
                            "Sale Invoice #{$sale->invoice_no}",
                            $date
                        );
                    }

                    // --- AUTO RECEIPT (THE PAYMENT) ---
                    $transactionService = app(\App\Services\TransactionService::class);
                    $transactionService->createReceiptFromSale(
                        $sale,
                        $request->input('payment_account_id', []),
                        $request->input('payment_amount', [])
                    );
                } catch (\Throwable $e) {
                    \Log::error('Professional Ledger Posting Error: '.$e->getMessage());
                }
            }

            // If created from an Advance DC, link the Delivery Note
            if ($request->filled('dc_id')) {
                $dcId = (int)$request->dc_id;
                \App\Models\DeliveryNote::where('id', $dcId)->update([
                    'sale_id' => $sale->id
                ]);
            }

            // If AJAX/JSON response needed
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'booking_id' => $sale->id,
                    'msg' => 'Sale '.ucfirst($status).' Successfully',
                    'invoice_url' => route('sales.invoice', $sale->id),
                ]);
            }

            return redirect()->route('sale.index')->with('success', 'Sale saved as '.$status);
        });

        // Trigger Credit Notifications after transaction commits
        if ($status === 'post') {
            try {
                $notificationService = app(\App\Services\CreditNotificationService::class);
                $notificationService->checkSaleOverdue($sale);
                if ($sale->customer_relation) {
                    $notificationService->checkCustomerCreditLimit($sale->customer_relation);
                }
            } catch (\Exception $e) {
                \Log::error('Credit Notification Error: ' . $e->getMessage());
            }
        }

        return redirect()->route('sale.index')->with('success', 'Sale saved as '.$status);
    }

    private function handleStockImpact(Sale $sale, $type = 'out')
    {
        // Type: 'out' (Sale Posted), 'in' (Sale Cancelled), 'return' (Returned)

        if (! $sale->relationLoaded('items')) {
            $sale->load('items.product');
        }

        $branchId = $sale->branch_id ?? 1;

        foreach ($sale->items as $item) {
            $warehouseId = $item->warehouse_id;
            $productId   = $item->product_id;
            $uomId       = $item->uom_id ?? null;
            $qtyPieces   = (float) ($item->total_pieces ?? 0);

            if ($qtyPieces <= 0) {
                continue;
            }

            if ($type === 'out') {
                // Deduct main warehouse inventory via StockService
                StockService::debit($productId, $uomId, $warehouseId, $branchId, $qtyPieces);

                // Movement log
                DB::table('stock_movements')->insert([
                    'product_id'  => $productId,
                    'type'        => 'out',
                    'qty'         => -$qtyPieces,
                    'ref_type'    => 'sale',
                    'ref_id'      => $sale->id,
                    'note'        => 'Sale Posted #'.$sale->invoice_no,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

            } elseif ($type === 'in' || $type === 'return') {
                StockService::credit($productId, $uomId, $warehouseId, $branchId, $qtyPieces);

                DB::table('stock_movements')->insert([
                    'product_id'  => $productId,
                    'type'        => 'in',
                    'qty'         => $qtyPieces,
                    'ref_type'    => 'sale_'.$type,
                    'ref_id'      => $sale->id,
                    'note'        => 'Sale '.ucfirst($type).' #'.$sale->invoice_no,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    private function updateLedger(Sale $sale)
    {
        $customer_id = $sale->customer_id;
        if (! $customer_id) {
            return;
        }

        $ledger = CustomerLedger::where('customer_id', $customer_id)
            ->orderBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'), 'desc')
            ->orderBy('id', 'desc')
            ->first();
        // Fallback: If no ledger, check Customer Master
        if (! $ledger) {
            $cust = \App\Models\Customer::find($customer_id);
            $prev_bal = $cust->opening_balance ?? $cust->previous_balance ?? 0;
        } else {
            $prev_bal = $ledger->closing_balance;
        }

        $new_bal = $prev_bal + $sale->total_net;

        \Log::info("Legacy Ledger (Invoice): Customer #{$customer_id}. Prev: {$prev_bal} + Sale: {$sale->total_net} = New: {$new_bal}");
        $createdAt = $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->setTime(date('H'), date('i'), date('s')) : now();

        CustomerLedger::create([
            'customer_id'      => $sale->customer_id,
            'branch_id'        => $sale->branch_id,
            'admin_or_user_id' => auth()->id() ?? 1,
            'description'      => "Sale Confirmed #{$sale->invoice_no}",
            'debit'            => (float)$sale->total_net,
            'credit'           => 0,
            'previous_balance' => $prev_bal,
            'opening_balance'  => 0,
            'closing_balance'  => $new_bal,
            'source_type'      => \App\Models\Sale::class,
            'source_id'        => $sale->id,
            'created_at'       => $createdAt,
        ]);

        $txService = app(\App\Services\TransactionService::class);
        if (method_exists($txService, 'recalculateCustomerLedger')) {
            $txService->recalculateCustomerLedger($customer_id);
        }
    }

    /**
     * Auto-generate receipt voucher when sale is posted
     */
    private function autoGenerateReceiptVoucher(Sale $sale, Request $request)
    {
        // Get account IDs from request (from receipt voucher section)
        $accountIds = $request->input('receipt_account_id', []);
        $amounts = $request->input('receipt_amount', []);

        // If no accounts selected, use default cash account
        if (empty($accountIds) || empty(array_filter($accountIds))) {
            $accountIds = [1]; // Default to account ID 1 (Cash)
            $amounts = [$sale->cash];
        }

        // Generate unique RVID
        $lastRV = \App\Models\ReceiptsVoucher::orderBy('id', 'desc')->first();
        $nextNumber = $lastRV ? (int) filter_var($lastRV->rvid, FILTER_SANITIZE_NUMBER_INT) + 1 : 1;
        $rvid = 'RV-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Create receipt voucher
        \App\Models\ReceiptsVoucher::create([
            'rvid' => $rvid,
            'party_id' => $sale->customer_id,
            'type' => 'customer',
            'total_amount' => $sale->cash,
            'receipt_date' => now()->format('Y-m-d'),
            'row_account_id' => json_encode($accountIds),
            'row_account_head' => json_encode([]), // Can be populated if needed
            'row_amount' => json_encode($amounts),
            'remarks' => 'Auto-generated from Sale Invoice #'.$sale->invoice_no,
            'processed' => true, // Mark as processed since it's linked to sale
        ]);
    }

    private function _getSaleItems($sale)
    {
        return $sale->items->map(function ($item) {
            $firstBatch = $item->batches->first();
            if (!$firstBatch) {
                // Fallback for old sales: pull the oldest available batch for this product
                $firstBatch = \App\Models\ProductBatch::where('product_id', $item->product_id)
                    ->orderBy('exp_date', 'asc')
                    ->first();
            }
            return [
                'product_id' => $item->product_id,
                'category_name' => $item->product->category_relation->name ?? '',
                'item_name' => ($item->product_name ?? $item->product->item_name ?? 'Item') . ' ' . ($item->product->brand->name ?? ''),
                'item_code' => $item->product->item_code ?? '',
                'brand' => $item->product->brand->name ?? '',
                'unit' => $item->product->unit->name ?? 'Piece',
                'uom_name' => (!empty($item->uom_name) && !str_starts_with($item->uom_name, '1x'))
                    ? $item->uom_name 
                    : (($item->product->unit->name ?? '') ?: ($item->uom_name ?? 'Piece')),
                'lot_number' => $firstBatch->batch_number ?? '-',
                'exp_date' => $firstBatch && $firstBatch->exp_date ? $firstBatch->exp_date->format('d/m/Y') : '-',
                'qty' => (float) ($item->qty > 0 ? $item->qty : ($item->total_pieces > 0 ? $item->total_pieces : 1)),
                'qty_box' => (float) $item->qty,
                'total_pieces' => (int) $item->total_pieces,
                'loose_pieces' => (int) $item->loose_pieces,
                'free_qty' => (float) $item->free_qty,
                'free_total_pieces' => (float) $item->free_total_pieces,
                'hs_code' => $item->hs_code ?: ($item->product->hs_code ?? ''),
                'price' => (float) $item->price,
                'discount' => (float) $item->discount_percent,
                'discount_percent' => (float) $item->discount_percent,
                'discount_amount' => (float) $item->discount_amount,
                'total' => (float) $item->total,
                'gst' => (float) ($item->gst_percent ?? $item->gst ?? 0),
                'gst_percent' => (float) ($item->gst_percent ?? $item->gst ?? 0),
                'gst_amount' => (float) ($item->gst_amount ?? 0),
                'inc_tax' => (float) ($item->inc_tax ?? 0),
                'adv_tax' => (float) ($item->adv_tax ?? 0),
                'further_tax_rate' => (float) ($item->further_tax_rate ?? $item->f_tax_rate ?? 0),
                'further_tax_amount' => (float) ($item->further_tax_amount ?? $item->further_tax ?? $item->f_tax_value ?? 0),
                'uom_factor' => (int) ($item->uom_factor ?: (($item->total_pieces > 0 && (float)$item->qty > 0) ? round($item->total_pieces / (float)$item->qty) : 1)),
                'color' => json_decode($item->color, true) ?? [],
                'pieces_per_box' => $item->product->pieces_per_box ?? 1,
                'price_per_piece' => ($item->total_pieces > 0) ? ($item->total / $item->total_pieces) : 0,
                'height' => $item->product->height ?? 0,
                'width' => $item->product->width ?? 0,
                'pieces_per_m2' => $item->product->pieces_per_m2 ?? 0,
                'size_mode' => $item->size_mode ?? $item->product->size_mode ?? 'std',
                'batches' => $item->batches->map(function ($b) {
                    return [
                        'batch_number' => $b->batch_number,
                        'exp_date' => $b->exp_date ? $b->exp_date->format('d-m-Y') : 'N/A',
                        'qty' => $b->pivot->qty_deducted,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Generate a unique invoice number with duplicate checking
     */
    private function generateUniqueInvoiceNo($prefix = 'INV-')
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $lastSale = Sale::where('invoice_no', 'LIKE', $prefix.'%')->orderBy('id', 'desc')->first();

            if (! $lastSale || ! $lastSale->invoice_no) {
                $invoiceNo = $prefix.'0001';
            } else {
                // Extract numeric part
                $lastNumber = (int) str_replace($prefix, '', $lastSale->invoice_no);
                // Increment + format
                $invoiceNo = $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }

            // Check if this invoice number already exists
            $exists = Sale::where('invoice_no', $invoiceNo)->exists();

            if (! $exists) {
                return $invoiceNo;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        // Fallback: use timestamp-based unique number
        return $prefix.date('YmdHis');
    }
}
