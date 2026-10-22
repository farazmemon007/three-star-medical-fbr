<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\PriceLog;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Milon\Barcode\DNS1D;

class ProductController extends Controller
{
    public function getPrice(Request $request)
    {
        $product = Product::find($request->product_id);

        if (! $product) {
            return response()->json(['retail_price' => 0]);
        }

        // Standardizing to per-piece pricing
        $price = $product->sale_price_per_piece ?? 0;

        return response()->json([
            'retail_price' => $price,
            'size_mode' => $product->size_mode,
            'pieces_per_box' => $product->pieces_per_box,
            'price_per_m2' => $product->price_per_m2,
            'sale_price_per_box' => $product->sale_price_per_box,
            'sale_price_per_piece' => $product->sale_price_per_piece,
            'purchase_price_per_piece' => $product->purchase_price_per_piece,
            'height' => $product->height,
            'width' => $product->width,
            'item_code' => $product->item_code,
        ]);
    }

    public function productget()
    {
        $products = Product::all();

        return response()->json($products);
    }

    // NOTE: Stock adjustments via the product form have been removed.
    // All stock changes must go through Purchase (GRN) or Sale transactions.
    // Use StockService::credit() / StockService::debit() only.


    public function getDetails($id)
    {
        $user = auth()->user();
        $branchId = $user->getBranchId() ?: 1;

        $product = Product::with(['packings', 'unit'])
            ->withSum(['warehouseStocks' => function ($q) use ($user) {
                $branchId = $user->getBranchId() ?: 1;
                $q->whereHas('warehouse', function ($wh) use ($branchId) {
                    $wh->where('branch_id', $branchId);
                });
            }], 'total_pieces')
            ->addSelect([
                'last_purchased_price' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->whereNull('purchases.deleted_at')
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchase_items.price')
                    ->limit(1)
            ])
            ->findOrFail($id);

        return response()->json($product);
    }

    // ===== High Performance Select2 Search (Ajax) =====
    public function ajaxSearch(Request $request)
    {
        $term = $request->get('term') ?? $request->get('q') ?? '';

        $user = auth()->user();
        $branchId = $user->getBranchId() ?: ($request->branch_id ?: 1);
        $query = Product::query()
            ->select('id', 'item_name', 'item_code', 'barcode_path', 'size_mode', 'pieces_per_box', 'purchase_price_per_piece', 'hs_code')
            ->select('products.*')
            ->with(['packings', 'unit'])
            ->withSum(['warehouseStocks' => function ($q) use ($branchId) {
                $q->whereHas('warehouse', function ($wh) use ($branchId) {
                    $wh->where('branch_id', $branchId);
                });
            }], 'total_pieces') /* Sum PIECES, not boxes */
            ->addSelect([
                'last_purchased_price' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->whereNull('purchases.deleted_at')
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchase_items.price')
                    ->limit(1),
                'last_purchased_date' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->whereNull('purchases.deleted_at')
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchases.purchase_date')
                    ->limit(1)
            ])
            ->where(function ($q) use ($term) {
                $q->where('item_name', 'like', "%{$term}%")
                    ->orWhere('item_code', 'like', "%{$term}%")
                    ->orWhere('barcode_path', 'like', "%{$term}%");
            });

        $products = $query->paginate(10); // Lazy loading (10 per request)

        $results = $products->map(function ($p) use ($branchId) {
            // Get total pieces from warehouse stocks
            $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);
            $ppb = $p->pieces_per_box > 0 ? $p->pieces_per_box : 1;

            // Global Stock Display: Pieces only (as requested)
            $stockDisplay = $stockPieces . " Pcs";

            return [
                'id' => $p->id,
                'text' => $p->item_name." (SKU: {$p->item_code})", // Enhanced text for selection
                // Custom attributes for template
                'sku' => $p->item_code ?? '',
                'hs_code' => $p->hs_code ?? '',
                'stock' => $stockDisplay,
                'stock_pieces' => $stockPieces, // Raw pieces for validation
                'name' => $p->item_name,
                'size_mode' => $p->size_mode,
                'pieces_per_box' => $ppb,
                'ppb' => $ppb, // Legacy
                'trade_price' => floatval($p->last_purchased_price) ?: ($p->purchase_price_per_piece ?? 0),
                'purchase_price_per_m2' => floatval($p->last_purchased_price) ?: ($p->purchase_price_per_m2 ?? 0),
                'purchase_price_per_piece' => ($p->purchase_price_per_piece ?? 0) ?: floatval($p->last_purchased_price),
                'purchase_price_per_box' => ($p->purchase_price_per_piece ?? 0) * $ppb,
                'sale_price_per_piece' => $p->sale_price_per_piece ?? 0,
                'sale_price_per_box' => ($p->sale_price_per_piece ?? 0) * $ppb,
                'retail_price' => $p->sale_price_per_piece ?? 0,
                'uom_name' => $p->unit->name ?? 'Piece',
                'debug_last_price' => $p->last_purchased_price,
                'debug_last_date' => $p->last_purchased_date,
                'debug_branch_used' => $branchId,
                'height' => $p->height ?? 0,
                'length' => $p->height ?? 0, // Alias for purchase snapshot
                'width' => $p->width ?? 0,
                'pieces_per_m2' => $p->pieces_per_m2 ?? 0,
                'packings' => $p->packings->map(function($pkg) {
                    return [
                        'id' => $pkg->id,
                        'name' => $pkg->name,
                        'pieces_per_box' => $pkg->pieces_per_box,
                        'purchase_price' => $pkg->purchase_price, // Per piece
                        'sale_price' => $pkg->sale_price,       // Per piece
                    ];
                }),
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $products->hasMorePages()],
        ]);
    }

    public function getProductFilters()
    {
        return response()->json([
            'categories' => \App\Models\Category::orderBy('name')->get(['id', 'name']),
            'brands'     => \App\Models\Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function searchProducts(Request $request)
    {
        $term = $request->get('q', '');

        $user = auth()->user();
        $branchId = $user->getBranchId() ?: ($request->branch_id ?: 1);

        $products = Product::query()
            ->with(['category_relation', 'sub_category_relation', 'brand', 'packings', 'unit'])
            ->withSum(['warehouseStocks' => function ($q) use ($branchId) {
                $q->whereHas('warehouse', function ($wh) use ($branchId) {
                    $wh->where('branch_id', $branchId);
                });
            }], 'total_pieces')
            ->addSelect([
                'last_purchased_price' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchase_items.price')
                    ->limit(1),
                'last_purchased_date' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchases.purchase_date')
                    ->limit(1)
            ])
            ->when($term, function ($query) use ($term) {
                $query->where('item_name', 'like', "%{$term}%")
                    ->orWhere('item_code', 'like', "%{$term}%")
                    ->orWhereHas('category_relation', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('sub_category_relation', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            })
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->sub_category_id, fn ($q) => $q->where('sub_category_id', $request->sub_category_id))
            ->when($request->brand_id, fn ($q) => $q->where('brand_id', $request->brand_id))
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'results' => $products->map(function ($p, $key) use ($branchId) {
            $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);

            // Global Stock Display: Pieces only (as requested)
            $stockDisplay = $stockPieces . " Pcs";
            $ppb = $p->pieces_per_box > 0 ? $p->pieces_per_box : 1;

            return [
                'id' => $p->id,
                'item_code' => $p->item_code,
                'hs_code' => $p->hs_code ?? '',
                'item_name' => $p->item_name,
                'image' => $p->image ? asset('uploads/products/'.$p->image) : null,
                'category_name' => $p->category_relation->name ?? '-',
                'sub_category_name' => $p->sub_category_relation->name ?? '-',
                'height' => $p->height ?? null,
                'width' => $p->width ?? null,
                'pieces_per_box' => $ppb,
                'size_mode' => $p->size_mode,
                'stock' => $stockDisplay,
                'trade_price' => floatval($p->last_purchased_price) ?: ($p->purchase_price_per_piece ?? 0),
                'purchase_price_per_piece' => ($p->purchase_price_per_piece ?? 0) ?: floatval($p->last_purchased_price),
                'purchase_price_per_box' => ($p->purchase_price_per_piece ?? 0) * $ppb,
                'sale_price_per_piece' => $p->sale_price_per_piece ?? 0,
                'sale_price_per_box' => ($p->sale_price_per_piece ?? 0) * $ppb,
                'retail_price' => $p->sale_price_per_piece ?? 0,
                'debug_last_price' => $p->last_purchased_price,
                'debug_last_date' => $p->last_purchased_date,
                'debug_branch_used' => $branchId,
                'total_m2' => number_format($p->total_m2 ?? 0, 2),
                'price_per_m2' => number_format($p->price_per_m2 ?? 0, 2),
                'total_price' => number_format($p->total_price ?? 0, 2),
                'brand_name' => $p->brand->name ?? '-',
                'uom_name' => $p->unit->name ?? 'Piece',
                'packings' => $p->packings->map(function($pkg) {
                    return [
                        'id' => $pkg->id,
                        'name' => $pkg->name,
                        'pieces_per_box' => $pkg->pieces_per_box,
                        'purchase_price' => $pkg->purchase_price, // Per piece
                        'sale_price' => $pkg->sale_price,       // Per piece
                    ];
                }),
            ];
        }),
            'pagination' => [
                'more'         => $products->hasMorePages(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    // ===== Get warehouses that have stock for a product =====
    public function getProductWarehouses(Request $request, $id)
    {
        $user = auth()->user();
        $product = \App\Models\Product::find($id);
        
        if (!$product) {
            return response()->json([]);
        }

        $ppb = (float) ($product->pieces_per_box > 0 ? $product->pieces_per_box : 1);
        $sizeMode = $product->size_mode ?? 'by_pieces';
        $branchId = $user ? $user->getBranchId() : null;

        $stocks = \App\Models\WarehouseStock::with('warehouse')
            ->where('product_id', $id)
            ->when(!$request->has('include_empty'), fn($q) => $q->where('total_pieces', '>', 0))
            ->when($branchId, function ($q, $bid) {
                $q->whereHas('warehouse', fn($w) => $w->where('branch_id', $bid));
            })
            ->get();

        $warehouses = $stocks->map(function ($s) use ($ppb, $sizeMode) {
            $totalPieces = (float) $s->total_pieces;
            
            // Format stock display as total pieces (User requested pieces only)
            $disp = $totalPieces . " Pcs";

            return [
                'id'             => $s->warehouse_id,
                'name'           => $s->warehouse->warehouse_name ?? 'Warehouse #' . $s->warehouse_id,
                'total_pieces'   => $totalPieces,
                'stock_display'  => $disp,
                'ppb'            => $ppb,
                'size_mode'      => $sizeMode,
            ];
        })->unique('id')->values();

        return response()->json($warehouses);
    }

    // ===== List page =====
    public function product(Request $request)
    {
        $search = trim((string)$request->input('search'));

        $query = Product::with([
            'category_relation',
            'sub_category_relation',
            'unit',
            'brand',
            'packings',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'LIKE', '%' . $search . '%')
                  ->orWhere('item_code', 'LIKE', '%' . $search . '%')
                  ->orWhereHas('category_relation', function ($cat) use ($search) {
                      $cat->where('name', 'LIKE', '%' . $search . '%');
                  })
                  ->orWhereHas('sub_category_relation', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', '%' . $search . '%');
                  })
                  ->orWhereHas('brand', function ($b) use ($search) {
                      $b->where('name', 'LIKE', '%' . $search . '%');
                  });
            });
        }

        $products = $query->latest()->paginate(10);

        if ($search !== '') {
            $products->appends(['search' => $search]);
        }

        $categories = Category::get();

        return view('admin_panel.product.index', compact('products', 'categories', 'search'));
    }

    public function productview($id)
    {
        $user = auth()->user();
        $product = Product::with([
            'category_relation',
            'sub_category_relation',
            'brand',
            'unit',
            'packings',
            'warehouseStocks' => function ($q) use ($user) {
                $branchId = $user->getBranchId() ?: 1;
                $q->whereHas('warehouse', function ($wh) use ($branchId) {
                    $wh->where('branch_id', $branchId);
                });
            },
        ])->find($id);

        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Calculate derived fields
        $totalPieces = $product->warehouseStocks->sum('total_pieces');
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

        $boxes = 0;
        $loose = 0;

        if ($product->size_mode === 'by_cartons' || $product->size_mode === 'by_size') {
            $boxes = floor($totalPieces / $ppb);
            $loose = $totalPieces % $ppb;
        } else {
            // For by_pieces, boxes is essentially the piece count if we treat it largely
            // But strict interpretation:
            $boxes = $totalPieces;
            $loose = 0;
        }

        // Append these purely for the view (not saved in DB)
        $product->setAttribute('calculated_total_stock_qty', $totalPieces);
        $product->setAttribute('calculated_boxes_quantity', $boxes);
        $product->setAttribute('calculated_loose_pieces', $loose);

        return response()->json($product);
    }

    // //////////////////////

    // /////////////////////////

    // ===== Create page =====
    public function view_store()
    {
        $categories = Category::select('id', 'name')->get();
        $units = Unit::select('id', 'name')->get();
        $brands = Brand::select('id', 'name')->get();
        
        $user = auth()->user();
        if ($user->hasRole('Super Admin')) {
            $warehouses = \App\Models\Warehouse::with('branch')->select('id', 'warehouse_name', 'location', 'branch_id')->get();
        } else {
            $warehouses = \App\Models\Warehouse::where('branch_id', $user->branch_id)->select('id', 'warehouse_name', 'location', 'branch_id')->get();
        }

        return view('admin_panel.product.create', compact('categories', 'units', 'brands', 'warehouses'));
    }

    // ===== Dependent subcategories =====
    public function getSubcategories($category_id)
    {
        $subcategories = Subcategory::where('category_id', $category_id)->get();

        return response()->json($subcategories);
    }

    // ===== Barcode =====
    public function generateBarcode(Request $request)
    {
        $barcodeNumber = $request->filled('code') ? $request->code : rand(100000000000, 999999999999);
        $barcodePNG = (new DNS1D)->getBarcodePNG($barcodeNumber, 'C39', 3, 50);
        $barcodeImage = 'data:image/png;base64,'.$barcodePNG;

        return response()->json([
            'barcode_number' => $barcodeNumber,
            'barcode_image' => $barcodeImage,
        ]);
    }

    // ===== Store product =====
    // ===== Store product =====
    public function store_product(Request $request)
    {
        if (! Auth::id()) {
            return $request->wantsJson()
                ? response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401)
                : redirect()->route('login');
        }

        $validation = $this->validateProductRequest($request);
        if ($validation->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'errors' => $validation->errors()], 422);
            }
            return redirect()->back()->withErrors($validation)->withInput();
        }

        $packings = $request->input('packings', []);
        $mode = 'by_pieces';
        $piecesPerBox = 1;
        
        foreach ($packings as $p) {
            if (intval($p['pieces_per_box'] ?? 1) > 1) {
                $mode = 'by_cartons';
                break;
            }
        }
        
        $first = reset($packings);
        $piecesPerBox = intval($first['pieces_per_box'] ?? 1);
        $buy = floatval($first['purchase_price'] ?? 0);
        $sell = floatval($first['sale_price'] ?? 0);

        if ($mode === 'by_cartons') {
            $salePricePerPiece = $sell;
            $salePricePerBox = $sell * $piecesPerBox;
            $purchasePricePerPiece = $buy;
            $purchasePricePerBox = $buy * $piecesPerBox;
        } else {
            $salePricePerPiece = $sell;
            $salePricePerBox = $sell;
            $purchasePricePerPiece = $buy;
            $purchasePricePerBox = $buy;
        }

        $userId = Auth::id();
        $itemCode = $request->item_code;
        if (empty($itemCode)) {
            $last = Product::orderBy('id', 'desc')->first();
            $itemCode = $last ? ('ITEM-'.str_pad($last->id + 1, 4, '0', STR_PAD_LEFT)) : 'ITEM-0001';
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = 'uploads/products/'.$filename;
        }

        DB::transaction(function () use ($request, $userId, $itemCode, $imagePath, $mode, $piecesPerBox, 
            $salePricePerPiece, $salePricePerBox, $purchasePricePerPiece, $purchasePricePerBox, $packings) {

            $product = Product::create([
                'creater_id' => $userId,
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'item_code' => $itemCode,
                'item_name' => $request->product_name,
                'barcode_path' => $request->barcode_path ?? rand(100000000000, 999999999999),
                'brand_id' => $request->brand_id,
                'model' => $request->model,
                'mdr' => $request->mdr,
                'hs_code' => $request->hs_code,
                'image' => $imagePath,
                'color' => $request->color ? json_encode(array_values(array_filter($request->color))) : null,
                'size_mode' => $mode,
                'pieces_per_box' => $piecesPerBox,
                'sale_price_per_box' => $salePricePerBox,
                'sale_price_per_piece' => $salePricePerPiece,
                'purchase_price_per_piece' => $purchasePricePerPiece,
                'purchase_price_per_box' => $purchasePricePerBox,
                'is_fridge' => $request->is_fridge ? 1 : 0,
                'is_non_fridge' => $request->is_non_fridge ? 1 : 0,
                'is_fast_moving' => $request->is_fast_moving ? 1 : 0,
                'is_slow_moving' => $request->is_slow_moving ? 1 : 0,
                'is_part' => 0,
                'is_assembled' => 0,
            ]);

            $warehouseId = $request->warehouse_id ?: \App\Models\Warehouse::min('id');
            if ($warehouseId) {
                WarehouseStock::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'total_pieces' => 0,
                    'remarks' => 'Initial Entry',
                ]);
            }

            foreach ($packings as $pData) {
                if (!empty($pData['name'])) {
                    \App\Models\ProductUom::create([
                        'product_id' => $product->id,
                        'name' => $pData['name'],
                        'pieces_per_box'  => intval($pData['pieces_per_box'] ?? 1),
                        'purchase_price' => floatval($pData['purchase_price'] ?? 0),
                        'sale_price' => floatval($pData['sale_price'] ?? 0),
                    ]);
                }
            }
        });

        return $request->wantsJson() 
            ? response()->json(['status' => 'success', 'message' => 'Product created successfully'])
            : redirect()->back()->with('success', 'Product created successfully');
    }

    /*
    // ===== Parts search (for BOM modal) with real available qty =====
        public function searchPartName(Request $request)
    {
        $q = $request->get('q', '');

        $parts = Product::where('is_part', 1)
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->where(function ($x) use ($q) {
                $x->where('products.item_name', 'like', "%{$q}%")
                  ->orWhere('products.item_code', 'like', "%{$q}%");
            })
            ->groupBy('products.id', 'products.item_name', 'products.item_code', 'products.unit_id')
            ->selectRaw('products.id, products.item_name, products.item_code, products.unit_id, COALESCE(SUM(stocks.qty),0) as available_qty')
            ->limit(20)
            ->get();

        return response()->json($parts->map(function ($p) {
            return [
                'id'            => $p->id,
                'item_name'     => $p->item_name,
                'item_code'     => $p->item_code,
                'unit'          => optional(Unit::find($p->unit_id))->name ?? '',
                'available_qty' => (float)$p->available_qty,
            ];
        }));
    }
    */

    public function update(Request $request, $id)
    {
        $userId = auth()->id();

        $validation = $this->validateProductRequest($request);
        if ($validation->fails()) {
            return $request->wantsJson() 
                ? response()->json(['status' => 'error', 'errors' => $validation->errors()], 422)
                : redirect()->back()->withErrors($validation)->withInput();
        }

        $packings = $request->input('packings', []);
        $mode = 'by_pieces';
        $piecesPerBox = 1;

        foreach ($packings as $p) {
            if (intval($p['pieces_per_box'] ?? 1) > 1) {
                $mode = 'by_cartons';
                break;
            }
        }
        
        $first = reset($packings);
        $piecesPerBox = intval($first['pieces_per_box'] ?? 1);
        $buy = floatval($first['purchase_price'] ?? 0);
        $sell = floatval($first['sale_price'] ?? 0);

        if ($mode === 'by_cartons') {
            $salePricePerPiece = $sell;
            $salePricePerBox = $sell * $piecesPerBox;
            $purchasePricePerPiece = $buy;
            $purchasePricePerBox = $buy * $piecesPerBox;
        } else {
            $salePricePerPiece = $sell;
            $salePricePerBox = $sell;
            $purchasePricePerPiece = $buy;
            $purchasePricePerBox = $buy;
        }

        $imagePath = Product::where('id', $id)->value('image');
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = 'uploads/products/'.$filename;
        }

        DB::transaction(function () use ($request, $id, $userId, $imagePath, $mode, $piecesPerBox,
            $salePricePerPiece, $salePricePerBox, $purchasePricePerPiece, $purchasePricePerBox, $packings) {

            $product = Product::findOrFail($id);
            $oldPurchasePrice = (float) $product->purchase_price_per_piece;
            $oldSalePrice = (float) $product->sale_price_per_piece;

            $product->update([
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'item_code' => $request->item_code,
                'item_name' => $request->product_name,
                'barcode_path' => $request->barcode_path,
                'brand_id' => $request->brand_id,
                'model' => $request->model,
                'mdr' => $request->mdr,
                'hs_code' => $request->hs_code,
                'image' => $imagePath,
                'color' => $request->color ? json_encode(array_values(array_filter($request->color))) : null,
                'size_mode' => $mode,
                'pieces_per_box' => $piecesPerBox,
                'sale_price_per_box' => $salePricePerBox,
                'sale_price_per_piece' => $salePricePerPiece,
                'purchase_price_per_piece' => $purchasePricePerPiece,
                'purchase_price_per_box' => $purchasePricePerBox,
                'is_fridge' => $request->is_fridge ? 1 : 0,
                'is_non_fridge' => $request->is_non_fridge ? 1 : 0,
                'is_fast_moving' => $request->is_fast_moving ? 1 : 0,
                'is_slow_moving' => $request->is_slow_moving ? 1 : 0,
                'updated_at' => now(),
            ]);

            // Log manual price changes if any
            if ($oldPurchasePrice != $purchasePricePerPiece) {
                PriceLog::log($id, 'purchase', $oldPurchasePrice, $purchasePricePerPiece, 'MANUAL', null, "Manual purchase price update");
            }
            if ($oldSalePrice != $salePricePerPiece) {
                PriceLog::log($id, 'sale', $oldSalePrice, $salePricePerPiece, 'MANUAL', null, "Manual sale price update");
            }

            $existingUoms = \App\Models\ProductUom::where('product_id', $id)->get()->keyBy('name');
            $submittedNames = collect($packings)->pluck('name')->filter()->toArray();

            foreach ($existingUoms as $name => $uom) {
                if (!in_array($name, $submittedNames) && $uom->warehouseStocks()->sum('total_pieces') == 0) {
                    $uom->delete();
                }
            }

            foreach ($packings as $pData) {
                if (!empty($pData['name'])) {
                    \App\Models\ProductUom::updateOrCreate(
                        ['product_id' => $id, 'name' => $pData['name']],
                        [
                            'pieces_per_box'  => intval($pData['pieces_per_box'] ?? 1),
                            'purchase_price'  => floatval($pData['purchase_price'] ?? 0),
                            'sale_price'      => floatval($pData['sale_price'] ?? 0),
                        ]
                    );
                }
            }
        });

        return $request->wantsJson() 
            ? response()->json(['status' => 'success', 'message' => 'Product updated successfully'])
            : redirect()->back()->with('success', 'Product updated successfully');
    }

    // ===== Edit view =====
    public function edit($id)
    {
        $product = Product::with('packings', 'category_relation', 'sub_category_relation', 'unit', 'brand')->findOrFail($id);
        $categories = Category::all();
        $subcategories = SubCategory::all();
        $brands = Brand::all();
        
        $user = auth()->user();
        if ($user->hasRole('Super Admin')) {
            $warehouses = \App\Models\Warehouse::with('branch')->select('id', 'warehouse_name', 'location', 'branch_id')->get();
        } else {
            $warehouses = \App\Models\Warehouse::where('branch_id', $user->branch_id)->select('id', 'warehouse_name', 'location', 'branch_id')->get();
        }

        return view('admin_panel.product.edit', compact('product', 'categories', 'subcategories', 'brands', 'warehouses'));
    }

    // ===== Barcode view =====
    public function barcode($id)
    {
        $product = Product::findOrFail($id);

        return view('admin_panel.product.barcode', compact('product'));
    }

    // Shared validation rules
    private function validateProductRequest(Request $request)
    {
        $rules = [
            'product_name' => 'required|string|max:255',
            'category_id' => 'required',
            'item_code' => 'required|string|max:255',
            'sub_category_id' => 'nullable',
            'brand_id' => 'required',
            'unit' => 'nullable',
            'model' => 'nullable',
            'mdr' => 'nullable',
            'color' => 'nullable|array',
            'packings' => 'required|array|min:1',
            'packings.*.name' => 'required|string',
            'packings.*.pieces_per_box' => 'required|integer|min:1',
            'packings.*.purchase_price' => 'nullable|numeric|min:0',
            'packings.*.sale_price' => 'nullable|numeric|min:0',
        ];

        return \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
    }

    // AJAX Validation Endpoint
    public function validateForm(Request $request)
    {
        $validator = $this->validateProductRequest($request);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        return response()->json(['status' => 'success', 'message' => 'Valid']);
    }

    public function downloadTemplate()
    {
        $data = [
            // Header row
            [
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>PRODUCT NAME</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>ITEM CODE</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>CATEGORY</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>SUBCATEGORY</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>BRAND</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>HS CODE</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>MODEL SERIES</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>MDR</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>COLOR/TAGS</center></b></style>',
                '<style bgcolor="#FFFFF2CC" color="#333333"><b><center>PACK NAME</center></b></style>',
                '<style bgcolor="#FFFFF2CC" color="#333333"><b><center>PCS PER PACK</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>PURCHASE PRICE</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>SALE PRICE</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>IS FRIDGE</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>IS NON FRIDGE</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>IS FAST MOVING</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>IS SLOW MOVING</center></b></style>',
                '<style bgcolor="#1A6B3C" color="#FFFFFF"><b><center>WAREHOUSE</center></b></style>'
            ],
            // Row 1
            [
                'DENGUE IGG/IGM DEVICE', 'A1', 'RAPID DEVICES', 'ABBOTT', 'ABBOTT', '3822.1900',
                '', '0002781', '', '1x25', 25, 500, 650, 0, 1, 1, 0, 'ABBOTT'
            ],
            // Row 2
            [
                'HBSAG DEVICE', 'A2', 'RAPID DEVICES', 'ABBOTT', 'ABBOTT', '3822.1900',
                '', '0000350', '', '1X30', 30, 450, 600, 0, 1, 0, 0, 'RAPID DEVICES'
            ]
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $xlsx->setColWidth('A', 30);
        $xlsx->setColWidth('B', 12);
        $xlsx->setColWidth('C', 18);
        $xlsx->setColWidth('D', 18);
        $xlsx->setColWidth('E', 18);
        $xlsx->setColWidth('F', 12);
        $xlsx->setColWidth('G', 16);
        $xlsx->setColWidth('H', 12);
        $xlsx->setColWidth('I', 14);
        $xlsx->setColWidth('J', 14);
        $xlsx->setColWidth('K', 12);
        $xlsx->setColWidth('L', 15);
        $xlsx->setColWidth('M', 12);
        $xlsx->setColWidth('N', 10);
        $xlsx->setColWidth('O', 12);
        $xlsx->setColWidth('P', 13);
        $xlsx->setColWidth('Q', 13);
        $xlsx->setColWidth('R', 14);

        $tmpPath = storage_path('app/product_template_' . uniqid() . '.xlsx');
        $xlsx->saveAs($tmpPath);

        return response()->download($tmpPath, 'product_import_template.xlsx', [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
        ])->deleteFileAfterSend(true);
    }




    public function importProducts(Request $request)
    {
        $request->validate(['file' => 'required|file']);

        try {
            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'format_error',
                    'message' => 'No valid spreadsheet file was uploaded.',
                ], 400);
            }

            $file      = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, ['csv', 'xlsx'])) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'format_error',
                    'message' => 'Only CSV (.csv) and Excel (.xlsx) files are supported.',
                ], 400);
            }

            // ── Parse file into $rawRows ──────────────────────────────────────
            $rawRows = [];

            if ($extension === 'xlsx') {
                if ($xlsx = \Shuchkin\SimpleXLSX::parse($file->getRealPath())) {
                    $rawRows = $xlsx->rows();
                } else {
                    return response()->json([
                        'status'  => 'error',
                        'type'    => 'format_error',
                        'message' => 'Unable to parse the Excel file: ' . \Shuchkin\SimpleXLSX::parseError(),
                    ], 400);
                }
            } else {
                $handle = fopen($file->getRealPath(), 'r');
                if (!$handle) {
                    return response()->json([
                        'status'  => 'error',
                        'type'    => 'format_error',
                        'message' => 'Unable to open the uploaded file.',
                    ], 400);
                }

                // Strip UTF-8 BOM if present
                $bom = fread($handle, 3);
                if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
                    rewind($handle);
                }

                // Auto-detect delimiter
                $firstLine     = fgets($handle);
                rewind($handle);
                // Skip BOM again after rewind
                $bom2 = fread($handle, 3);
                if ($bom2 !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
                    rewind($handle);
                }

                $commaCount     = substr_count($firstLine, ',');
                $semicolonCount = substr_count($firstLine, ';');
                $tabCount       = substr_count($firstLine, "\t");

                $delimiter = ',';
                if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
                    $delimiter = ';';
                } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                    $delimiter = "\t";
                }

                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rawRows[] = $row;
                }
                fclose($handle);
            }

            if (empty($rawRows)) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'format_error',
                    'message' => 'Spreadsheet has no content.',
                ], 400);
            }

            // ── Normalize header row ──────────────────────────────────────────
            $rawHeaders = array_shift($rawRows);

            // Normalise: strip control chars, lowercase, spaces→underscores
            $normalizeKey = function (string $h): string {
                $h = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $h);
                $h = trim(strtolower($h));
                $h = str_replace([' ', '-', '/', '_'], '_', $h);
                return preg_replace('/_+/', '_', rtrim($h, '_'));
            };

            $normalizedHeaders = array_map($normalizeKey, $rawHeaders);

            /*
             * Flexible column-name aliases.
             * Maps every accepted variant → canonical field name.
             * This lets us handle both the template format AND the user's
             * original products_raw.csv format simultaneously.
             */
            $aliases = [
                // Product name
                'product_name'  => 'item_name',
                'name'          => 'item_name',
                'item_name'     => 'item_name',

                // Item code
                'item_code'     => 'item_code',
                'code'          => 'item_code',

                // Category
                'category'      => 'category',

                // Subcategory
                'subcategory'   => 'sub_category',
                'sub_category'  => 'sub_category',

                // Brand
                'brand'         => 'brand',

                // HS Code
                'hs_code'       => 'hs_code',

                // Model
                'model_series'  => 'model',
                'model'         => 'model',

                // MDR
                'mdr'           => 'mdr',

                // Color / Tags
                'color_tags'    => 'color',
                'color'         => 'color',
                'tags'          => 'color',

                // Packing name (goes to product_uoms.name)
                'pack_name'     => 'pack_name',
                'packing'       => 'pack_name',
                'packing_name'  => 'pack_name',
                'packing_1_name'=> 'pack_name',
                'unit'          => 'pack_name',

                // Pieces per pack/box
                'pcs_per_pack'  => 'pieces_per_box',
                'pieces_per_pack'=> 'pieces_per_box',
                'pieces_per_box'=> 'pieces_per_box',
                'pcs_per_box'   => 'pieces_per_box',
                'pcs_box'       => 'pieces_per_box',

                // Prices
                'purchase_price' => 'purchase_price',
                'buy_price'      => 'purchase_price',
                'sale_price'     => 'sale_price',
                'sell_price'     => 'sale_price',

                // Boolean flags
                'is_fridge'      => 'is_fridge',
                'is_non_fridge'  => 'is_non_fridge',
                'is_fast_moving' => 'is_fast_moving',
                'is_slow_moving' => 'is_slow_moving',

                // Warehouse
                'warehouse'      => 'warehouse',
            ];

            // Build fieldMap: canonical_name → column_index
            $fieldMap = [];
            foreach ($normalizedHeaders as $idx => $rawKey) {
                $canonical = $aliases[$rawKey] ?? $rawKey;
                // Only store first occurrence to avoid duplicates
                if (!isset($fieldMap[$canonical])) {
                    $fieldMap[$canonical] = $idx;
                }
            }

            // ── Require at minimum: item_name, category, brand ───────────────
            $requiredFields = [
                'item_name' => 'Product Name  (column: PRODUCT NAME or Item Name)',
                'category'  => 'Category',
                'brand'     => 'Brand',
            ];

            $missing = [];
            foreach ($requiredFields as $field => $label) {
                if (!isset($fieldMap[$field])) {
                    $missing[] = $label;
                }
            }

            if (!empty($missing)) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'column_mismatch',
                    'message' => 'Required columns not found in your file:<br><strong>'
                                 . implode(', ', $missing)
                                 . '</strong><br>Please use the provided template.',
                ], 400);
            }

            // ── Helper: safely read a field value ────────────────────────────
            $val = function (string $field, $default = '') use (&$row, &$fieldMap): string {
                if (isset($fieldMap[$field]) && isset($row[$fieldMap[$field]])) {
                    $v = trim((string)$row[$fieldMap[$field]]);
                    return $v === '' ? (string)$default : $v;
                }
                return (string)$default;
            };

            // ── Pre-load default warehouse (defaults to branch shop) ─────────
            $user = auth()->user();
            $activeBranchId = $user->isSuperAdmin()
                ? (session('super_admin_branch_id') ?? \App\Models\Branch::min('id'))
                : $user->branch_id;

            $shopWarehouse = \App\Models\Warehouse::where('branch_id', $activeBranchId)
                ->where('type', 'shop')
                ->first()
                ?? \App\Models\Warehouse::where('type', 'shop')->first()
                ?? \App\Models\Warehouse::first();

            $defaultWarehouseId = $shopWarehouse ? $shopWarehouse->id : \App\Models\Warehouse::min('id');
            $userId             = auth()->id();

            $rowCount      = 0;
            $importedCount = 0;
            $skippedCount  = 0;
            $dummyCount    = 0;
            $duplicateCount = 0;
            $errors        = [];
            $validationErrors = [];

            $autoFillDummy = $request->boolean('auto_fill_dummy') || $request->input('auto_fill_dummy') == '1';

            DB::beginTransaction();

            foreach ($rawRows as $row) {
                $rowCount++;

                // Skip completely empty rows
                if (empty(array_filter(array_map('trim', $row)))) {
                    $skippedCount++;
                    continue;
                }

                // ── Required field validation & Dummy Auto-fill ───────────────
                $itemName     = trim($val('item_name'));
                $categoryName = trim($val('category'));
                $brandName    = trim($val('brand'));

                $usedDummy = false;

                if (empty($itemName)) {
                    if ($autoFillDummy) {
                        $itemName  = "[DUMMY] Item Row {$rowCount}";
                        $usedDummy = true;
                    } else {
                        $validationErrors[] = "Row {$rowCount}: Product Name is empty.";
                        continue;
                    }
                }

                if (empty($categoryName)) {
                    if ($autoFillDummy) {
                        $categoryName = "Unspecified Category (Dummy)";
                        $usedDummy    = true;
                    } else {
                        $validationErrors[] = "Row {$rowCount}: Category is empty for '{$itemName}'.";
                        continue;
                    }
                }

                if (empty($brandName)) {
                    if ($autoFillDummy) {
                        $brandName = "Unspecified Brand (Dummy)";
                        $usedDummy = true;
                    } else {
                        $validationErrors[] = "Row {$rowCount}: Brand is empty for '{$itemName}'.";
                        continue;
                    }
                }

                if ($usedDummy) {
                    $dummyCount++;
                }

                // ── Duplicate Check 1: item_code already exists ───────────────
                $itemCode = trim($val('item_code'));
                if ($itemCode !== '' && Product::where('item_code', $itemCode)->exists()) {
                    $duplicateCount++;
                    $errors[] = "Row {$rowCount}: Item Code '{$itemCode}' already exists — skipped.";
                    continue;
                }

                // ── Duplicate Check 2: same item_name + brand already exists ──
                $existsByName = Product::whereRaw('LOWER(TRIM(item_name)) = ?', [strtolower($itemName)])
                    ->whereHas('brand', function ($q) use ($brandName) {
                        $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($brandName)]);
                    })
                    ->exists();
                if ($existsByName) {
                    $duplicateCount++;
                    $errors[] = "Row {$rowCount}: Product '{$itemName}' by brand '{$brandName}' already exists — skipped.";
                    continue;
                }

                // ── Resolve or create: Category ───────────────────────────────
                $category   = Category::where('name', 'LIKE', trim($categoryName))->first()
                              ?? Category::create(['name' => trim($categoryName)]);
                $categoryId = $category->id;

                // ── Resolve or create: Subcategory ────────────────────────────
                $subCategoryId  = null;
                $subcategoryName = trim($val('sub_category'));
                if ($subcategoryName !== '') {
                    $subcategory = Subcategory::where('name', 'LIKE', $subcategoryName)
                                             ->where('category_id', $categoryId)
                                             ->first()
                                  ?? Subcategory::create([
                                         'name'        => $subcategoryName,
                                         'category_id' => $categoryId,
                                     ]);
                    $subCategoryId = $subcategory->id;
                }

                // ── Resolve or create: Brand ──────────────────────────────────
                $brand   = Brand::where('name', 'LIKE', trim($brandName))->first()
                           ?? Brand::create(['name' => trim($brandName)]);
                $brandId = $brand->id;

                // ── Resolve or create: Unit (default: Piece) ──────────────────
                $unitName = trim($val('unit', 'Piece')) ?: 'Piece';
                $unit     = Unit::where('name', 'LIKE', $unitName)->first()
                            ?? Unit::create(['name' => $unitName]);
                $unitId   = $unit->id;

                // ── Packing info ──────────────────────────────────────────────
                // pack_name  → product_uoms.name (e.g. "1X25", "2X100")
                // pieces_per_box → product_uoms.pieces_per_box + products.pieces_per_box
                $packName    = trim($val('pack_name', $unitName)) ?: $unitName;
                $piecesPerBox = max(1, (int)$val('pieces_per_box', 1));
                $buyPrice     = (float)$val('purchase_price', 0);
                $sellPrice    = (float)$val('sale_price', 0);

                // Derive size_mode
                $sizeMode = ($piecesPerBox > 1) ? 'by_cartons' : 'by_pieces';

                // Calculate per-piece and per-box prices
                if ($sizeMode === 'by_cartons') {
                    $salePricePerPiece      = $sellPrice;
                    $salePricePerBox        = $sellPrice * $piecesPerBox;
                    $purchasePricePerPiece  = $buyPrice;
                    $purchasePricePerBox    = $buyPrice * $piecesPerBox;
                } else {
                    $salePricePerPiece      = $sellPrice;
                    $salePricePerBox        = $sellPrice;
                    $purchasePricePerPiece  = $buyPrice;
                    $purchasePricePerBox    = $buyPrice;
                }

                // ── Item Code: use provided or auto-generate ──────────────────
                // (already read above for duplicate check; just auto-generate if empty)
                if ($itemCode === '') {
                    $last     = Product::orderBy('id', 'desc')->first();
                    $nextId   = $last ? ($last->id + 1) : 1;
                    $itemCode = 'ITEM-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                }

                // Ensure uniqueness (append suffix if somehow still duplicate)
                $originalCode = $itemCode;
                $suffix = 1;
                while (Product::where('item_code', $itemCode)->exists()) {
                    $itemCode = $originalCode . '-' . $suffix++;
                }

                // ── Color / Tags ──────────────────────────────────────────────
                $colorRaw = trim($val('color'));
                $colorJson = null;
                if ($colorRaw !== '') {
                    $colorTags = array_values(array_filter(array_map('trim', explode(',', $colorRaw))));
                    $colorJson = !empty($colorTags) ? json_encode($colorTags) : null;
                }

                // ── Boolean flags ─────────────────────────────────────────────
                $boolVal = function (string $f) use ($val): int {
                    $v = strtolower(trim($val($f, '0')));
                    return in_array($v, ['1', 'yes', 'true', 'y'], true) ? 1 : 0;
                };

                // ── Resolve Warehouse (optional column) ───────────────────────
                $warehouseId = $defaultWarehouseId;
                $warehouseName = trim($val('warehouse'));
                if ($warehouseName !== '') {
                    $wh = \App\Models\Warehouse::where('warehouse_name', 'LIKE', $warehouseName)->first();
                    if ($wh) {
                        $warehouseId = $wh->id;
                    }
                }

                // ── 1. INSERT into products ───────────────────────────────────
                $product = Product::create([
                    'creater_id'              => $userId,
                    'category_id'             => $categoryId,
                    'sub_category_id'         => $subCategoryId,
                    'brand_id'                => $brandId,
                    'unit_id'                 => $unitId,
                    'item_code'               => $itemCode,
                    'item_name'               => $itemName,
                    'barcode_path'            => rand(100000000000, 999999999999),
                    'model'                   => $val('model') ?: null,
                    'mdr'                     => $val('mdr')   ?: null,
                    'hs_code'                 => $val('hs_code') ?: null,
                    'color'                   => $colorJson,
                    'size_mode'               => $sizeMode,
                    'pieces_per_box'          => $piecesPerBox,
                    'sale_price_per_piece'    => $salePricePerPiece,
                    'sale_price_per_box'      => $salePricePerBox,
                    'purchase_price_per_piece'=> $purchasePricePerPiece,
                    'purchase_price_per_box'  => $purchasePricePerBox,
                    'is_fridge'               => $boolVal('is_fridge'),
                    'is_non_fridge'           => $boolVal('is_non_fridge'),
                    'is_fast_moving'          => $boolVal('is_fast_moving'),
                    'is_slow_moving'          => $boolVal('is_slow_moving'),
                    'is_part'                 => 0,
                    'is_assembled'            => 0,
                ]);

                // ── 2. INSERT into product_uoms (packing) ─────────────────────
                \App\Models\ProductUom::create([
                    'product_id'    => $product->id,
                    'name'          => $packName,          // e.g. "1X25", "2X100"
                    'pieces_per_box'=> $piecesPerBox,
                    'purchase_price'=> $buyPrice,
                    'sale_price'    => $sellPrice,
                ]);

                // ── 3. INSERT into warehouse_stocks (initial entry, qty = 0) ──
                if ($warehouseId) {
                    WarehouseStock::create([
                        'warehouse_id' => $warehouseId,
                        'product_id'   => $product->id,
                        'quantity'     => 0,
                        'total_pieces' => 0,
                        'remarks'      => 'Imported — Initial Entry',
                    ]);
                }

                $importedCount++;
            }

            // ── Abort if any validation errors (missing name, category, brand) ──
            if (!empty($validationErrors)) {
                DB::rollBack();
                $shown = array_slice($validationErrors, 0, 10);
                $more  = count($validationErrors) > 10 ? '<br>... and ' . (count($validationErrors) - 10) . ' more errors.' : '';
                return response()->json([
                    'status'        => 'error',
                    'type'          => 'format_error',
                    'can_auto_fill' => true,
                    'message'       => 'Import aborted. Fix the following errors and re-upload:<br>'
                                       . implode('<br>', $shown) . $more,
                ], 400);
            }

            // ── Commit all valid rows ─────────────────────────────────────────
            DB::commit();

            $dummyMsg = ($dummyCount > 0) ? " (including <strong>{$dummyCount}</strong> rows with dummy data)" : "";
            $dupMsg   = ($duplicateCount > 0) ? " ({$duplicateCount} duplicates skipped)" : "";

            return response()->json([
                'status'          => 'success',
                'imported_count'  => $importedCount,
                'skipped_count'   => $skippedCount,
                'dummy_count'     => $dummyCount,
                'duplicate_count' => $duplicateCount,
                'errors'          => $errors,
                'message'         => "✅ Successfully imported <strong>{$importedCount}</strong> products"
                                     . $dummyMsg . $dupMsg
                                     . ($skippedCount ? " ({$skippedCount} empty rows skipped)." : '.'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'type'    => 'server_error',
                'message' => 'Server error during import: ' . $e->getMessage()
                             . ' (Line ' . $e->getLine() . ')',
            ], 500);
        }
    }

    /**
     * Quick update HS Code for a product from Sale screen modal.
     */
    public function quickUpdateHsCode(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'hs_code'    => 'nullable|string|max:100',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $cleanHs = trim($request->hs_code ?? '');
            $product->update(['hs_code' => $cleanHs]);

            return response()->json([
                'status'     => 'success',
                'product_id' => $product->id,
                'hs_code'    => $cleanHs,
                'message'    => "HS Code for '{$product->item_name}' updated to '{$cleanHs}'",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update HS Code: ' . $e->getMessage(),
            ], 500);
        }
    }
}
