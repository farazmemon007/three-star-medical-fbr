@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        /* Page Header */
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.025em;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* ERP Cards */
        .erp-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            margin-bottom: 1.25rem;
            transition: box-shadow 0.2s ease-in-out;
        }

        .erp-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }

        .erp-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff;
            border-radius: 12px 12px 0 0;
        }

        .erp-card-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .erp-card-body {
            padding: 1.5rem;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.375rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
            background-color: #f8fafc;
            color: #1e293b;
            transition: all 0.2s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.01);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-control:read-only {
            background-color: #f1f5f9;
            border-color: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
        }

        /* Table Styles */
        .erp-table-wrapper {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }

        .erp-table {
            margin-bottom: 0;
            width: 100%;
            border-collapse: collapse;
        }

        .erp-table thead {
            background-color: #f8fafc;
        }

        .erp-table thead th {
            padding: 0.875rem 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
            vertical-align: middle;
        }

        .erp-table tbody td {
            padding: 0.5rem 0.4rem;
            font-size: 0.85rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            background: #ffffff;
        }

        .erp-table tbody tr:hover td {
            background-color: #f8fafc;
        }

        .erp-table tbody tr:last-child td {
            border-bottom: none;
        }

        .erp-table input.form-control {
            padding: 0.375rem 0.3rem;
            background-color: #ffffff;
            border: 1px solid transparent;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 4px;
            box-shadow: none;
        }

        .erp-table input.form-control:focus {
            border-color: #3b82f6;
            background-color: #ffffff;
            outline: none;
            z-index: 3;
        }

        /* Input Group Polish */
        .erp-table .input-group {
            flex-wrap: nowrap;
        }

        .erp-table .input-group .form-control {
            border: 1px solid #e2e8f0;
            border-right: none;
            border-radius: 6px 0 0 6px !important;
            flex: 1 1 auto !important;
            min-width: 60px !important;
            width: 100% !important;
        }

        .erp-table .input-group .btn,
        .erp-table .input-group .input-group-text {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0 6px 6px 0 !important;
            background-color: #f8fafc;
            color: #475569;
            padding: 0 0.4rem;
            transition: all 0.2s;
            min-width: 32px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }

        .erp-table input.form-control:read-only {
            background-color: transparent;
            border-color: transparent;
            font-weight: 500;
            cursor: default;
            text-align: right;
        }

        .input-highlight {
            font-weight: 700 !important;
            color: #059669 !important;
            font-size: 0.95rem;
        }

        /* Summary Totals */
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }

        .summary-total-row {
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-top: 1rem;
        }

        .summary-total-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-total-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #2563eb;
        }

        /* Buttons */
        .btn-erp {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-erp-primary {
            background: linear-gradient(to bottom, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .btn-erp-primary:hover {
            background: linear-gradient(to bottom, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .btn-erp-success {
            background: linear-gradient(to bottom, #10b981, #059669);
            color: white;
            box-shadow: 0 1px 2px rgba(5, 150, 105, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .btn-erp-secondary {
            background-color: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        /* Modern ERP Sale Action Buttons */
        .btn-sale-action {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.25rem;
            border-radius: 8px;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            border: none;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            text-transform: uppercase;
            box-sizing: border-box;
            line-height: 1.2;
        }

        .btn-sale-confirm {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
        }
        .btn-sale-confirm:hover:not(:disabled) {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(5, 150, 105, 0.35);
            color: #ffffff !important;
        }
        .btn-sale-confirm:disabled {
            background: #e2e8f0 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
            transform: none !important;
        }

        .btn-sale-fbr {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
        }
        .btn-sale-fbr:hover:not(:disabled) {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(2, 132, 199, 0.35);
            color: #ffffff !important;
        }
        .btn-sale-fbr:disabled {
            background: #f8fafc !important;
            color: #64748b !important;
            border: 1.5px dashed #cbd5e1 !important;
            box-shadow: none !important;
            cursor: not-allowed !important;
            transform: none !important;
            opacity: 1 !important;
        }
        .btn-sale-fbr.is-posted {
            background: #ecfdf5 !important;
            color: #047857 !important;
            border: 1.5px solid #10b981 !important;
            box-shadow: none !important;
            cursor: default !important;
        }

        .btn-sale-draft {
            background: #ffffff;
            color: #475569 !important;
            border: 1.5px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .btn-sale-draft:hover:not(:disabled) {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #1e293b !important;
            transform: translateY(-1px);
        }

        .btn-erp-secondary:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
            color: #1e293b;
        }

        .btn-erp-danger-ghost {
            background: transparent;
            color: #ef4444;
            padding: 0.375rem;
            border-radius: 6px;
        }

        /* Badges */
        .erp-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .compact-lbl {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
            vertical-align: middle;
        }

        .compact-input {
            padding: 0.25rem 0.5rem;
            font-size: 13px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            width: 100%;
            transition: all 0.2s;
        }

        .compact-select {
            padding: 0.25rem 0.5rem;
            font-size: 13px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            width: 100%;
            background-color: #fff;
        }

        .compact-td {
            padding: 0.3rem 0.5rem !important;
            vertical-align: middle;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

                <form id="saleForm" action="{{ route('sales.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" id="action" value="post">
                    <input type="hidden" name="draft_id" id="draft_id_input" value="{{ $sale->id }}">
                    <input type="hidden" name="mode" value="{{ $sale->sale_status == 'booked' ? 'so' : 'srn' }}">

                    <!-- Page Header Top -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="page-title"><i class="bi bi-pencil-square me-2 text-primary"></i>
                                Edit {{ $sale->sale_status == 'booked' ? 'Sale Order' : 'Sale Invoice Note' }}
                            </h1>
                            <p class="page-subtitle mb-0">Modify existing outward stock record #{{ $sale->invoice_no }}</p>
                        </div>
                        <div class="d-flex gap-3">
                            <a href="{{ route('sale.receipt.index') }}" class="btn-erp btn-erp-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Registry
                            </a>
                        </div>
                    </div>

                    <div class="erp-card mb-3">
                        <div class="erp-card-header">
                            <h3 class="erp-card-title"><i class="bi bi-info-square text-primary"></i> Order Details</h3>
                            <span class="erp-badge"><i class="bi bi-pencil me-1 text-primary"></i>Editing Mode</span>
                        </div>
                        <div class="erp-card-body p-3 bg-light">
                            <div class="row gx-3 gy-3">
                                <div class="col-lg-8">
                                    <div class="bg-white border rounded p-3 shadow-sm h-100">
                                        <table class="table table-borderless table-sm mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="compact-lbl compact-td" style="width: 15%;">BRANCH :</td>
                                                    <td colspan="3" class="compact-td">
                                                        <input type="text" class="compact-input shadow-sm text-secondary bg-light"
                                                            style="max-width:300px; font-weight:600;"
                                                            value="{{ strtoupper($sale->branch->name ?? 'N/A') }}" readonly>
                                                        <input type="hidden" name="branch_id" value="{{ $sale->branch_id }}">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">SALE INVC #</td>
                                                    <td class="text-secondary fw-bold compact-td" style="width: 35%;">
                                                        <input type="text" name="invoice_no" readonly value="{{ $sale->invoice_no }}"
                                                            class="bg-transparent border-0 text-secondary p-0 w-100 fw-bold" style="outline: none;">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td" style="width: 25%;">CUSTOMER REF #</td>
                                                    <td style="width: 25%;" class="compact-td">
                                                        <input type="text" name="vendor_bill_no" value="{{ $sale->vendor_bill_no }}" class="compact-input shadow-sm">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">ORDER DATE :</td>
                                                    <td class="compact-td">
                                                        <input type="date" name="purchase_date" value="{{ $sale->sale_date }}" class="compact-input shadow-sm text-secondary" style="max-width: 200px;">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td">ORDER #</td>
                                                    <td class="compact-td">
                                                        <input type="text" name="order_no" value="{{ $sale->order_no }}" class="compact-input shadow-sm">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">SO #</td>
                                                    <td class="text-secondary compact-td">
                                                        <input type="text" name="sale_order_no" value="{{ $sale->sale_order_no }}" class="bg-transparent border-0 text-secondary p-0 w-100" style="outline: none;">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td">SO DATE :</td>
                                                    <td class="compact-td">
                                                        <input type="date" name="so_date" value="{{ $sale->so_date }}" class="compact-input shadow-sm text-secondary">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">PROJECT :</td>
                                                    <td class="compact-td">
                                                        <select name="project" class="compact-select text-secondary shadow-sm">
                                                            <option value="">(N/A)</option>
                                                        </select>
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td">JOB # / REF</td>
                                                    <td class="compact-td">
                                                        <input type="text" name="reference" value="{{ $sale->reference }}" class="compact-input shadow-sm">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="bg-white border rounded p-3 shadow-sm h-100 d-flex flex-column">
                                        <div class="form-group mb-3">
                                            <label class="form-label" style="font-size: 11px;">CUSTOMER ACCOUNT</label>
                                            <div class="input-group shadow-sm">
                                                <button type="button" class="btn input-group-text bg-light border-end-0" data-toggle="modal" data-target="#customerModal">
                                                    <i class="bi bi-person text-primary fs-5"></i>
                                                </button>
                                                <input type="hidden" name="customer" id="customer_id" value="{{ $sale->customer_id }}">
                                                <input type="text" id="customer_name_display" class="form-control border-start-0 ps-0 fw-bold bg-white text-dark" readonly
                                                    value="{{ $sale->customer_relation->customer_name ?? 'Select Customer' }}">
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="form-label" style="font-size: 11px;">SALES OFFICER</label>
                                            <div class="input-group shadow-sm">
                                                <button type="button" class="btn input-group-text bg-light border-end-0" data-toggle="modal" data-target="#officerModal">
                                                    <i class="bi bi-person-badge text-primary fs-5"></i>
                                                </button>
                                                <input type="hidden" name="sales_officer_id" id="sales_officer_id" value="{{ $sale->employee_id }}">
                                                <input type="text" id="sales_officer_display" class="form-control border-start-0 ps-0 fw-bold bg-white text-dark" readonly
                                                    value="{{ $sale->employee->full_name ?? 'Select Sales Officer' }}">
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="form-label" style="font-size: 11px;">COMMISSION %</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-percent text-success fs-6"></i>
                                                </span>
                                                <input type="number" step="0.01" min="0" max="100" name="commission_percentage" id="commission_percentage"
                                                    class="form-control border-start-0 ps-2 fw-bold text-dark" placeholder="Enter % (e.g. 1.00)" 
                                                    value="{{ $sale->commission_percentage !== null ? floatval($sale->commission_percentage) : '' }}">
                                            </div>
                                            <small class="text-muted" style="font-size: 10px;">Applied on amount net of GST (18%) & Sale Tax (5%)</small>
                                        </div>

                                        <div class="mt-auto bg-light p-3 rounded border">
                                            <div class="form-check form-switch d-flex align-items-center gap-2 mb-2">
                                                <input class="form-check-input mt-0" type="checkbox" id="gst_invoice" name="is_gst_invoice" checked style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                                <label class="form-check-label fw-bold text-dark compact-lbl" for="gst_invoice">GST INVOICE</label>
                                            </div>
                                            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                                <input class="form-check-input mt-0" type="checkbox" id="enable_hs_code" name="enable_hs_code" checked style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                                <label class="form-check-label fw-bold text-dark compact-lbl" for="enable_hs_code">ENABLE HS CODE</label>
                                            </div>
                                            <div class="text-muted mt-2" style="font-size: 11px; font-weight: 600;">
                                                STATUS: <span class="text-danger">DRAFT / UN-POSTED</span>
                                            </div>
                                        </div>
                                        <input type="hidden" id="warehouse_id_header" value="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MAIN TABLE AREA -->
                    <div class="erp-card mt-2">
                        <div class="erp-card-header d-flex justify-content-between align-items-center" style="padding-bottom: 1rem; border-bottom: none;">
                            <h3 class="erp-card-title"><i class="bi bi-boxes text-primary"></i> Product Line Items</h3>
                            <button type="button" class="btn-erp btn-erp-primary" id="btnAddRow" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                <i class="bi bi-plus-lg"></i> Add New Row
                            </button>
                        </div>
                        <div class="erp-card-body pt-0">
                            <div class="erp-table-wrapper" style="overflow-x: auto;">
                                @php
                                    $allUnits = \App\Models\Unit::pluck('name')->toArray();
                                    $defaultUoms = ['KG', 'Piece', 'Number', 'Pack', 'Box', 'Carton', 'Dozen', 'Meter', 'Liter', 'Pair', 'Set', 'Roll', 'Bag', 'Bottle', 'Vial', 'Kit'];
                                    $uomList = array_unique(array_merge($defaultUoms, $allUnits));
                                @endphp
                                <datalist id="uom_datalist">
                                    @foreach($uomList as $uName)
                                        <option value="{{ $uName }}">
                                    @endforeach
                                </datalist>
                                <table class="erp-table">
                                    <thead>
                                        <tr>
                                            <th style="width:120px">Item Code</th>
                                            <th style="width:145px" class="hs-code-col text-center">HS Code</th>
                                            <th style="min-width:320px">Product</th>
                                            <th style="min-width:150px">Packing</th>
                                            <th style="width:100px" class="text-center">UOM</th>
                                            <th style="width:80px" class="text-center">Packet</th>
                                            <th style="min-width:100px" class="text-end">Box Qty</th>
                                            <th style="min-width:80px" class="text-end">Loose</th>
                                            <th style="min-width:90px" class="text-end">Free Pcs</th>
                                            <th style="min-width:120px" class="text-end">Rate</th>
                                            <th style="min-width:180px" class="text-end">Discount</th>
                                            <th style="min-width:150px" class="text-end">Sub Total</th>
                                            <th style="min-width:130px" class="text-end">GST %</th>
                                            <th style="min-width:130px" class="text-end">IncTax</th>
                                            <th style="min-width:130px" class="text-end">advTax</th>
                                            <th style="min-width:160px" class="text-end">Net Total</th>
                                            @if ($sale->sale_status != 'booked')
                                                <th style="min-width:220px">Batch</th>
                                                <th style="min-width:150px">Lot#</th>
                                            @endif
                                            <th style="width:120px" class="text-end">Cost/PC</th>
                                            <th style="width:60px; text-align:center;">Del</th>
                                        </tr>
                                    </thead>
                                    <tbody id="saleItems">
                                        @foreach($sale->items as $index => $item)
                                            @php
                                                $prod = $item->product;
                                                $ppb = $item->pieces_per_box > 0 ? $item->pieces_per_box : ($prod->pieces_per_box > 0 ? $prod->pieces_per_box : 1);
                                                $sizeMode = $item->size_mode ?? ($prod->size_mode ?? 'by_cartons');
                                                
                                                // Format Qty: Boxes.Pieces
                                                $boxes = floor($item->total_pieces / $ppb);
                                                $remPieces = $item->total_pieces % $ppb;
                                                $qtyDisp = ($remPieces > 0) ? "$boxes.$remPieces" : $boxes;

                                                $freeTotal = $item->free_total_pieces ?? 0;
                                            @endphp
                                            <tr class="item-row">
                                                <td>
                                                    <input type="hidden" name="product_id[]" class="item-id" value="{{ $item->product_id }}">
                                                    <input type="hidden" name="warehouse_id[]" class="item-warehouse row-input" value="{{ $item->warehouse_id }}">
                                                    <input type="text" name="item_code[]" class="form-control bg-transparent border-0 px-0 item-code" readonly value="{{ $prod->item_code }}">
                                                </td>
                                                <td class="hs-code-col">
                                                    <div class="input-group input-group-sm" style="min-width: 130px;">
                                                        <input type="text" name="hs_code[]" class="form-control text-center hs-code item-hs-code" style="font-size:11px; padding:2px 4px; background:#fff;" value="{{ $item->hs_code }}" placeholder="HS Code">
                                                        <button type="button" class="btn btn-outline-primary btn-quick-hs px-2" onclick="window.openQuickHsModalForBtn && window.openQuickHsModalForBtn(this)" title="Quick Update Product HS Code" style="padding: 2px 7px;">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td style="font-weight:600; color:#334155;">
                                                    <input type="hidden" name="item_name[]" class="item-name" value="{{ $prod->item_name }}">
                                                    <button type="button" class="product-select-btn has-value">
                                                        {{ $prod->item_name }} <br>
                                                        <small class="text-muted" style="font-size:0.7rem; font-weight:400;">{{ $prod->item_code }}</small>
                                                        <span class="psm-btn-arrow">&#9660;</span>
                                                    </button>
                                                </td>
                                                <td>
                                                    <select name="pieces_per_box[]" class="form-select packing-select" style="min-width:130px;">
                                                        <option value="{{ $item->uom_id }}" data-factor="{{ $ppb }}" selected>{{ $ppb > 1 ? '1x' . (int)$ppb : $item->uom_name }}</option>
                                                    </select>
                                                    <input type="hidden" name="packing_id[]" class="packing-id" value="{{ $item->uom_id }}">
                                                    <input type="hidden" name="uom_id[]" class="uom-id" value="{{ $item->uom_id }}">
                                                    <input type="hidden" name="item_uom_factor[]" class="item_uom_factor" value="{{ $ppb }}">
                                                    <input type="hidden" name="size_mode[]" class="size-mode" value="{{ $sizeMode }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="item_uom[]" list="uom_datalist" class="form-control text-center uom-input row-input" style="min-width:85px;" value="{{ $item->uom_name ?: ($item->product->unit->name ?? 'Piece') }}" placeholder="UOM">
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" name="pcs_per_box_display[]" class="form-control text-center item-uom" value="{{ $ppb }}" readonly style="width:60px; background:#f8fafc;">
                                                </td>
                                                <td><input type="number" name="qty[]" class="form-control quantity text-end fw-bold row-input" style="background:#eff6ff;" value="{{ $boxes }}"></td>
                                                <td><input type="number" name="loose_pieces[]" class="form-control loose_pieces text-end row-input" value="{{ $remPieces }}"></td>
                                                <td><input type="number" name="free_loose_pieces[]" class="form-control free_loose_pieces text-end row-input" value="{{ $freeTotal }}"></td>
                                                <td><input type="number" step="0.01" name="price[]" class="form-control price text-end row-input" value="{{ $item->price }}"></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="hidden" name="item_disc_type[]" class="item-disc-type" value="percent">
                                                        <input type="number" step="0.01" name="item_disc[]" class="form-control item_disc text-end row-input" value="{{ $item->discount_percent }}">
                                                        <button class="btn btn-outline-info toggle-type" type="button" data-type="percent">%</button>
                                                    </div>
                                                </td>
                                                <td><input type="text" name="sub_total[]" class="form-control row-sub-total text-end" readonly value="{{ $item->total }}"></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" name="gst[]" class="form-control gst text-end row-input" value="{{ $item->gst_percent ?? 0 }}">
                                                        <span class="input-group-text p-1" style="font-size:0.7rem;">%</span>
                                                    </div>
                                                    <input type="hidden" name="gst_amount[]" class="gst-amount-row" value="{{ $item->gst_amount ?? 0 }}">
                                                </td>
                                                <td><input type="number" step="0.01" name="inc_tax[]" class="form-control inc-tax text-end row-input" value="{{ $item->incTax ?? 0 }}"></td>
                                                <td><input type="number" step="0.01" name="adv_tax[]" class="form-control adv-tax text-end row-input" value="{{ $item->advTax ?? 0 }}"></td>
                                                <td><input type="text" name="total[]" class="form-control row-net-total input-highlight text-end" readonly value="{{ $item->total + ($item->gst_amount ?? 0) + ($item->incTax ?? 0) + ($item->advTax ?? 0) }}"></td>
                                                @if ($sale->sale_status != 'booked')
                                                    <td>
                                                        <select name="batch_id[]" class="form-select batch-select2 row-input" style="width:100%">
                                                            <option value="">Auto (FEFO)</option>
                                                            @if($item->batch_id)
                                                                <option value="{{ $item->batch_id }}" selected>{{ $item->batch_relation->batch_number ?? 'B-'.$item->batch_id }}</option>
                                                            @endif
                                                        </select>
                                                    </td>
                                                    <td><input type="text" name="lot_no[]" class="form-control text-center row-input lot-no-display" value="{{ $item->batch_relation->batch_number ?? '' }}" readonly></td>
                                                @endif
                                                <td><input type="text" name="cost_per_pc[]" class="form-control row-cost-pc text-end" readonly></td>
                                                <td style="text-align:center;">
                                                    <button type="button" class="btn-erp-danger-ghost remove-row"><i class="bi bi-x-circle-fill"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- BOTTOM AREA -->
                    <div class="row">
                        <div class="col-xl-8 col-lg-7">
                            <div class="erp-card mb-4">
                                <div class="erp-card-header">
                                    <h3 class="erp-card-title"><i class="bi bi-wallet2 text-primary"></i> Setup & Payments</h3>
                                </div>
                                <div class="erp-card-body">
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label class="form-label">Delivered By / Transport</label>
                                            <input type="text" name="transport_name" class="form-control" value="{{ $sale->transport_name }}" placeholder="Vehicle info">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="form-label">Sale Remarks</label>
                                            <input type="text" name="note" class="form-control" value="{{ $sale->note }}" placeholder="Notes">
                                        </div>
                                    </div>
                                    
                                    @if ($sale->sale_status != 'booked')
                                        <hr class="my-4" style="border-color: #e2e8f0; border-style: dashed;">
                                        <h5 class="fw-bold mb-3" style="font-size:0.95rem;">Payment Assignments</h5>
                                        <div id="paymentWrapper">
                                            @php
                                                $displayPayments = [];
                                                if($sale->payments && $sale->payments->count() > 0) {
                                                    foreach($sale->payments as $p) {
                                                        $displayPayments[] = (object)['account_id' => $p->account_id, 'amount' => $p->amount];
                                                    }
                                                } elseif(!empty($sale->payment_details)) {
                                                    foreach($sale->payment_details as $pd) {
                                                        $displayPayments[] = (object)['account_id' => $pd['account_id'], 'amount' => $pd['amount']];
                                                    }
                                                }
                                            @endphp

                                            @foreach($displayPayments as $payment)
                                                <div class="d-flex gap-2 align-items-center mb-2 payment-row">
                                                    <div class="input-group" style="max-width:350px;">
                                                        <select class="form-select rv-account" name="payment_account_id[]">
                                                            @foreach ($accounts as $acc)
                                                                <option value="{{ $acc->id }}" {{ $payment->account_id == $acc->id ? 'selected' : '' }}>{{ $acc->title }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="input-group" style="max-width:180px;">
                                                        <span class="input-group-text bg-light fw-bold">Rs.</span>
                                                        <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" value="{{ $payment->amount }}">
                                                    </div>
                                                    <button type="button" class="btn btn-erp-danger-ghost remove-payment"><i class="bi bi-trash"></i></button>
                                                </div>
                                            @endforeach
                                            <div class="d-flex gap-2 align-items-center mb-2 payment-row">
                                                <div class="input-group" style="max-width:350px;">
                                                    <select class="form-select rv-account" name="payment_account_id[]">
                                                        <option value="" selected disabled>Select Payment Account</option>
                                                        @foreach ($accounts as $acc)
                                                            <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="input-group" style="max-width:180px;">
                                                    <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" placeholder="0.00">
                                                </div>
                                                <button type="button" class="btn btn-erp-primary rounded px-3" id="btnAddPayment"><i class="bi bi-plus-lg"></i> Add</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-5">
                            <div class="erp-card sticky-top" style="top: 20px;">
                                <div class="erp-card-header bg-primary text-white" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                                    <h3 class="erp-card-title text-white"><i class="bi bi-calculator text-info"></i> Financial Summary</h3>
                                </div>
                                <div class="erp-card-body" style="background: #f8fafc;">
                                    <div class="summary-row">
                                        <span class="summary-label">Total Qty</span>
                                        <span class="fw-bold text-primary" id="summary_total_qty">0</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Gross Total</span>
                                        <input type="text" class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value" id="gross_total" readonly value="{{ number_format($sale->total_bill_amount, 2) }}">
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label text-danger">Line Discounts</span>
                                        <input type="text" class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-danger" id="total_row_disc" readonly value="0.00">
                                    </div>
                                    <div class="summary-row py-2">
                                        <span class="summary-label">Additional Bill Discount</span>
                                        <div class="input-group input-group-sm w-50">
                                            <input type="number" step="0.01" class="form-control text-end" id="sum_discount" name="discount" value="{{ $sale->total_extradiscount }}">
                                            <input type="hidden" name="discount_type" id="sum_discount_type" value="amount">
                                            <button class="btn btn-outline-secondary toggle-type" type="button" data-type="amount">Rs</button>
                                        </div>
                                    </div>
                                    <div class="summary-row bg-white p-2 rounded border mb-2">
                                        <span class="summary-label fw-bold">Post-Disc Sub Total</span>
                                        <input type="text" class="form-control form-control-sm text-end w-50 bg-transparent border-0 fw-bold text-dark" id="summary_sub_total" readonly value="0.00">
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label text-success fw-bold">Total GST <small>(Added ➕)</small></span>
                                        <input type="text" class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-success" id="total_gst" readonly value="0.00">
                                    </div>
                                    <div class="summary-row" style="border-top:1px dashed #e2e8f0; padding-top:6px; margin-top:4px;">
                                        <span class="summary-label fw-bold text-dark">Invoice Total</span>
                                        <input type="text" class="form-control form-control-sm text-end w-50 bg-transparent border-0 fw-bold text-dark" id="invoice_total" readonly value="0.00">
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label text-danger">Income Tax (WHT) <small>(Deducted ➖)</small></span>
                                        <input type="text" class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-danger" id="total_inc_tax" readonly value="0.00">
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label text-success fw-bold">Advance Tax <small>(Added ➕)</small></span>
                                        <input type="text" class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-success fw-bold" id="total_adv_tax" readonly value="0.00">
                                    </div>
                                    <div class="summary-row py-2">
                                        <span class="summary-label">Freight Charges</span>
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end w-50 input_summary" name="freight_charges" id="sum_freight" value="{{ $sale->freight_charges ?? 0 }}">
                                    </div>
                                    <div class="summary-row py-2">
                                        <span class="summary-label">Expenses</span>
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end w-50 input_summary" name="extra_cost" id="sum_expense" value="{{ $sale->extra_cost ?? 0 }}">
                                    </div>
                                    <div class="summary-total-row d-flex justify-content-between align-items-center">
                                        <span class="summary-total-label">NET PAYABLE</span>
                                        <input type="text" name="total_net" id="final_net_total" class="bg-transparent border-0 text-end summary-total-value w-50" readonly value="{{ number_format($sale->total_net, 2) }}">
                                    </div>
                                    
                                    <div class="mt-4 d-flex flex-column gap-2">
                                        <button type="button" id="btnSaveOnly" class="btn-sale-action btn-sale-draft">
                                            <i class="bi bi-save fs-6"></i> Update Draft
                                        </button>
                                        <button type="button" id="btnConfirm" class="btn-sale-action btn-sale-confirm">
                                            <i class="bi bi-check2-circle fs-6"></i> Confirm & Post SIN
                                        </button>
                                        <button type="button" id="btnPostToFbr" 
                                            class="btn-sale-action btn-sale-fbr {{ $sale->fbr_status === 'posted' ? 'is-posted' : '' }}" 
                                            data-sale-id="{{ $sale->id }}"
                                            {{ ($sale->sale_status !== 'post' || $sale->fbr_status === 'posted') ? 'disabled' : '' }}>
                                            @if($sale->fbr_status === 'posted')
                                                <i class="bi bi-check-circle-fill text-success fs-6"></i> FBR Posted ({{ $sale->fbr_invoice_no }})
                                            @else
                                                <i class="bi bi-cloud-arrow-up-fill fs-6"></i> POST TO FBR
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('admin_panel.sale.sale_receipt_note.modals')
    @include('admin_panel.components.product_select_modal')
    @include('admin_panel.sale.components.fbr_post_modal')
    @include('admin_panel.sale.components.quick_hs_modal')

@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Quick HS Code Button Click
            $(document).on('click', '.btn-quick-hs', function(e) {
                e.preventDefault();
                if (window.openQuickHsModalForBtn) {
                    window.openQuickHsModalForBtn(this);
                }
            });

            // Helper functions
            function num(n) { return isNaN(parseFloat(n)) ? 0 : parseFloat(n); }

            function parseBoxPiece($input, pack, sizeMode) {
                let val = String($input.val() || '0');
                let total_pieces = 0, boxes = 0, pieces = 0;

                if (sizeMode === 'by_piece') {
                    total_pieces = parseInt(val) || 0;
                    $input.val(total_pieces);
                    return { total_pieces, boxes: total_pieces, pieces: 0 };
                }

                if (val.includes('.')) {
                    let parts = val.split('.');
                    boxes = parseInt(parts[0]) || 0;
                    pieces = parseInt(parts[1]) || 0;
                    if (pieces >= pack && pack > 0) {
                        boxes += Math.floor(pieces / pack);
                        pieces = pieces % pack;
                        $input.val(pieces > 0 ? `${boxes}.${pieces}` : `${boxes}`);
                    }
                } else {
                    boxes = parseInt(val) || 0;
                }
                total_pieces = (boxes * pack) + pieces;
                return { total_pieces, boxes, pieces };
            }

            function recalcRow($row) {
                const pack = parseFloat($row.find('.item_uom_factor').val()) || 1;
                const sizeMode = $row.find('.size-mode').val() || 'by_cartons';
                const price = num($row.find('.price').val());
                const discVal = num($row.find('.item_disc').val());
                const discType = $row.find('.item-disc-type').val() || 'amount';
                const gstRate = num($row.find('.gst').val());

                let boxes = num($row.find('.quantity').val());
                let loose = num($row.find('.loose_pieces').val());
                let total_pieces = (boxes * pack) + loose;

                let total_free_pieces = num($row.find('.free_loose_pieces').val());

                $row.data('total-pieces', total_pieces);
                $row.data('total-free-pieces', total_free_pieces);

                let billableQty = boxes + (pack > 0 ? (loose / pack) : 0);
                let line_gross = billableQty * price;
                let line_disc = (discType === 'percent') ? (line_gross * discVal / 100) : discVal;
                let subTotal = Math.max(0, line_gross - line_disc);

                const gstAmt = subTotal * (gstRate / 100);   // GST: ADDED
                // inc-tax and adv-tax are now percentages (standardized)
                const incTaxPct = num($row.find('.inc-tax').val());
                const advTaxPct = num($row.find('.adv-tax').val());
                const incTaxAmt = subTotal * (incTaxPct / 100); // WHT: DEDUCTED
                const advTaxAmt = subTotal * (advTaxPct / 100); // Adv: DEDUCTED
                const netTotal = subTotal + gstAmt - incTaxAmt - advTaxAmt;

                                $row.find('.gst-amount-row').val(gstAmt.toFixed(2));
                $row.find('.row-sub-total').val(subTotal.toFixed(2));
                $row.find('.row-net-total').val(netTotal.toFixed(2));
            }

            function recalcSummary() {
                let gross = 0, totalRowDisc = 0, totalGstAmt = 0, sumRowSub = 0;
                let totalIncTax = 0, totalAdvTax = 0;
                let totalPieces = 0;
                let boxCount = 0, pieceCount = 0;

                $('#saleItems tr').each(function() {
                    const $r = $(this);
                    recalcRow($r);
                    
                    const price = num($r.find('.price').val());
                    const pack = parseFloat($r.find('.item_uom_factor').val()) || 1;
                    
                    let boxes = num($r.find('.quantity').val());
                    let loose = num($r.find('.loose_pieces').val());
                    let total_pieces = (boxes * pack) + loose;
                    
                    const discVal = num($r.find('.item_disc').val());
                    const discType = $r.find('.item-disc-type').val() || 'amount';

                    let billableQty = boxes + (pack > 0 ? (loose / pack) : 0);
                    let rowGross = billableQty * price;
                    let rowDisc = (discType === 'percent') ? (rowGross * discVal / 100) : discVal;
                    let sub = num($r.find('.row-sub-total').val());

                    gross += rowGross;
                    totalRowDisc += rowDisc;
                    sumRowSub += sub;
                    totalGstAmt += num($r.find('.gst-amount-row').val());
                    // WHT and Adv are now percentages — compute amounts from row subtotal
                    const rSub = num($r.find('.row-sub-total').val());
                    totalIncTax += rSub * (num($r.find('.inc-tax').val()) / 100);
                    totalAdvTax += rSub * (num($r.find('.adv-tax').val()) / 100);
                    totalPieces += total_pieces;
                    boxCount += boxes;
                    pieceCount += loose;
                });
                
                $('#summary_total_qty').text(totalPieces + ' Pcs');
                $('#gross_total').val(gross.toFixed(2));
                $('#total_row_disc').val(totalRowDisc.toFixed(2));
                $('#total_gst').val(totalGstAmt.toFixed(2));
                $('#total_inc_tax').val(totalIncTax.toFixed(2));
                $('#total_adv_tax').val(totalAdvTax.toFixed(2));

                const discVal = num($('#sum_discount').val());
                const discType = $('#sum_discount').closest('.input-group').find('.toggle-type').data('type') || 'amount';
                
                let billDisc = (discType === 'percent') ? (sumRowSub * discVal / 100) : discVal;
                let summarySub = sumRowSub - billDisc;
                $('#summary_sub_total').val(summarySub.toFixed(2));

                const sumFreight  = num($('#sum_freight').val());
                const sumExpense  = num($('#sum_expense').val());

                // Pakistan Standard:
                // Invoice Total = (summarySub + freight + expense) + GST
                // Net Payable = Invoice Total - WHT + Adv
                const gstBase     = summarySub + sumFreight + sumExpense;
                const invoiceTotal = gstBase + totalGstAmt;
                let finalNet       = invoiceTotal - totalIncTax + totalAdvTax;
                $('#invoice_total').val(invoiceTotal.toFixed(2));
                $('#final_net_total').val(finalNet.toFixed(2));
            }

            function addBlankRow() {
                const isSoMode = "{{ $sale->sale_status == 'booked' }}";
                let batchHtml = !isSoMode ? `
                    <td><select name="batch_id[]" class="form-select batch-select2 row-input" style="width:100%"><option value="">Auto (FEFO)</option></select></td>
                    <td><input type="text" name="lot_no[]" class="form-control text-center row-input lot-no-display" placeholder="Auto" readonly></td>
                ` : '';
                
                const newRow = `
                    <tr class="item-row">
                        <td>
                            <input type="hidden" name="product_id[]" class="item-id">
                            <input type="hidden" name="warehouse_id[]" class="item-warehouse" value="1">
                            <input type="text" name="item_code[]" class="form-control bg-transparent border-0 px-0 item-code" readonly>
                        </td>
                        <td class="hs-code-col">
                            <div class="input-group input-group-sm" style="min-width: 130px;">
                                <input type="text" name="hs_code[]" class="form-control text-center hs-code item-hs-code" style="font-size:11px; padding:2px 4px; background:#fff;" placeholder="HS Code">
                                <button type="button" class="btn btn-outline-primary btn-quick-hs px-2" onclick="window.openQuickHsModalForBtn && window.openQuickHsModalForBtn(this)" title="Quick Update Product HS Code" style="padding: 2px 7px;">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </td>
                        <td style="font-weight:600;">
                            <input type="hidden" name="item_name[]" class="item-name">
                            <button type="button" class="product-select-btn">Select Product <span class="psm-btn-arrow">&#9660;</span></button>
                        </td>
                        <td><select name="pieces_per_box[]" class="form-select packing-select row-input"><option value="">Select</option></select>
                            <input type="hidden" name="packing_id[]" class="packing-id"><input type="hidden" name="uom_id[]" class="uom-id">
                            <input type="hidden" name="item_uom_factor[]" class="item_uom_factor" value="1"><input type="hidden" name="size_mode[]" class="size-mode" value="by_cartons"></td>
                        <td><input type="text" name="item_uom[]" list="uom_datalist" class="form-control text-center uom-input row-input" style="min-width:85px;" value="Piece" placeholder="UOM"></td>
                        <td><input type="number" name="pcs_per_box_display[]" class="form-control text-center item-uom" value="1" readonly style="width:60px;"></td>
                        <td><input type="number" name="qty[]" class="form-control quantity text-end fw-bold row-input" value="1"></td>
                        <td><input type="number" name="loose_pieces[]" class="form-control loose_pieces text-end row-input" value="0"></td>
                        <td><input type="number" name="free_loose_pieces[]" class="form-control free_loose_pieces text-end row-input" value="0"></td>
                        <td><input type="number" step="0.01" name="price[]" class="form-control price text-end row-input" value="0"></td>
                        <td><div class="input-group input-group-sm"><input type="hidden" name="item_disc_type[]" class="item-disc-type" value="percent"><input type="number" step="0.01" name="item_disc[]" class="form-control item_disc text-end row-input" value="0"><button class="btn btn-outline-info toggle-type" type="button" data-type="percent">%</button></div></td>
                        <td><input type="text" name="sub_total[]" class="form-control row-sub-total text-end" readonly></td>
                        <td><div class="input-group input-group-sm"><input type="number" step="0.01" name="gst[]" class="form-control gst text-end row-input" value="0"><span class="input-group-text">%</span></div><input type="hidden" name="gst_amount[]" class="gst-amount-row"></td>
                        <td><input type="number" step="0.01" name="inc_tax[]" class="form-control inc-tax text-end row-input" value="0"></td>
                        <td><input type="number" step="0.01" name="adv_tax[]" class="form-control adv-tax text-end row-input" value="0"></td>
                        <td><input type="text" name="total[]" class="form-control row-net-total input-highlight text-end" readonly></td>
                        ${batchHtml}
                        <td><input type="text" name="cost_per_pc[]" class="form-control row-cost-pc text-end" readonly></td>
                        <td class="text-center"><button type="button" class="btn-erp-danger-ghost remove-row"><i class="bi bi-x-circle-fill"></i></button></td>
                    </tr>`;
                $('#saleItems').append(newRow);
                const $inserted = $('#saleItems tr:last');
                recalcRow($inserted);
                return $inserted;
            }

            // ERP Product Modal Logic
            $(document).on('click', '.product-select-btn', function() {
                var $btn = $(this);
                var $row = $btn.closest('tr');

                ERPProductModal.open({
                    mode: 'single',
                    onSelect: function(products) {
                        products.forEach(function(p, idx) {
                            var $targetRow = (idx === 0) ? $row : addBlankRow();
                            populateRow($targetRow, p);
                        });
                    }
                });
            });

            function populateRow($row, data) {
                $row.find('.item-id').val(data.id);
                $row.find('.item-code').val(data.item_code);
                $row.find('.item-hs-code').val(data.hs_code || '');
                $row.find('.item-name').val(data.item_name);
                $row.find('.price').val(data.price || data.sale_price_per_piece || 0);
                $row.find('.size-mode').val(data.size_mode || 'by_cartons');
                let uomVal = (data.unit && data.unit.name) ? data.unit.name : (data.uom_name || 'Piece');
                $row.find('.uom-input').val(uomVal);

                // Update button UI
                var $btn = $row.find('.product-select-btn');
                var btnText = (data.item_name || 'Select Product') + '<br><small class="text-muted" style="font-size:0.7rem; font-weight:400;">' + (data.item_code || '') + '</small>';
                $btn.html(btnText + ' <span class="psm-btn-arrow">&#9660;</span>');
                $btn.addClass('has-value');

                // Packing dropdown logic
                var $pSelect = $row.find('.packing-select');
                if ($pSelect.length) {
                    $pSelect.empty();
                    let optionsHtml = '';
                    let seenFactors = {};

                    if (data.packings && data.packings.length > 0) {
                        data.packings.forEach(function(pkg) {
                            let f = parseInt(pkg.pieces_per_box) || 1;
                            if (f === 1) f = parseInt(data.uom || data.pieces_per_box) || 1;
                            if (seenFactors[f]) return; 
                            seenFactors[f] = true;
                            optionsHtml += `<option value="${pkg.id}" data-ppb="${f}" data-price="${pkg.sale_price || 0}">${pkg.name}</option>`;
                        });
                    } else {
                        let bPpb = parseInt(data.uom || data.pieces_per_box) || 1;
                        let bName = data.uom_name || 'Pcs';
                        if (!bName || bName.toLowerCase() === 'piece' || bName.toLowerCase() === 'pcs') {
                            bName = '1x' + bPpb;
                        }
                        optionsHtml = `<option value="" data-ppb="${bPpb}" data-price="${data.price || 0}">${bName} (Base)</option>`;
                    }
                    $pSelect.html(optionsHtml);

                    $pSelect.off('change').on('change', function() {
                        var $r = $(this).closest('tr');
                        var $opt = $(this).find('option:selected');
                        var factor = parseFloat($opt.data('ppb')) || 1;
                        var sPrice = parseFloat($opt.data('price')) || 0;
                        var pId    = $(this).val();
                        
                        $r.find('.item_uom_factor').val(factor);
                        $r.find('.item-uom').val(factor);   
                        $r.find('.packing-id').val(pId);
                        $r.find('.uom-id').val(pId);
                        
                        if (sPrice > 0) { $r.find('.price').val(sPrice.toFixed(2)); }
                        recalcRow($r);
                        recalcSummary();
                    });
                    $pSelect.trigger('change');
                }

                setTimeout(() => {
                    if (data.size_mode === 'by_piece') {
                        $row.find('.loose_pieces').focus().select();
                    } else {
                        $row.find('.quantity').focus().select();
                    }
                }, 100);
            }

            // Add Row button
            $('#btnAddRow').click(function() {
                addBlankRow();
            });

            $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); recalcSummary(); });
            $(document).on('input', '.quantity, .loose_pieces, .free_loose_pieces, .price, .item_disc, .gst, .inc-tax, .adv-tax, #sum_discount, #sum_freight, #sum_expense', recalcSummary);

            $(document).on('click', '.toggle-type', function() {
                const $btn = $(this);
                const current = $btn.data('type') || 'amount', next = (current === 'amount' ? 'percent' : 'amount');
                $btn.data('type', next).text(next === 'percent' ? '%' : 'Rs');
                $btn.toggleClass('btn-outline-info', next === 'percent').toggleClass('btn-outline-secondary', next === 'amount');
                const $row = $btn.closest('tr');
                if ($row.length) { $row.find('.item-disc-type').val(next); recalcRow($row); } else { $('#sum_discount_type').val(next); }
                recalcSummary();
            });

            // HS Code Toggle Logic
            function toggleHsCodeFields() {
                const isEnabled = $('#enable_hs_code').is(':checked');
                if (isEnabled) {
                    $('.hs-code-col').show();
                } else {
                    $('.hs-code-col').hide();
                }
            }
            $('#enable_hs_code').on('change', toggleHsCodeFields);
            toggleHsCodeFields(); // Initial call

            $('#btnSaveOnly, #btnConfirm').click(function(e) {
                e.preventDefault();
                const isPost = $(this).attr('id') === 'btnConfirm';
                $('#action').val(isPost ? 'post' : 'save_only');

                if (isPost) {
                    Swal.fire({
                        title: 'Are you sure you want to post this sale?',
                        text: "This action will affect your inventory stock and this change is irreversible.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#059669',
                        confirmButtonText: 'Yes, Post it!'
                    }).then((result) => { if (result.isConfirmed) submitForm(); });
                } else {
                    submitForm();
                }
            });

            function submitForm() {
                const $btn = $('#action').val() === 'post' ? $('#btnConfirm') : $('#btnSaveOnly');
                const ogHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

                $.ajax({
                    url: "{{ route('sales.store') }}",
                    method: "POST",
                    data: $('#saleForm').serialize(),
                    success: function(res) {
                        Swal.fire({ icon: 'success', title: 'Success!', text: 'Transaction updated.', timer: 1500, showConfirmButton: false })
                        .then(() => window.location.href = "{{ route('sale.receipt.index') }}");
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(ogHtml);
                        Swal.fire('Error', xhr.responseJSON?.message || 'Update failed', 'error');
                    }
                });
            }

            $('#btnPostToFbr').click(function(e) {
                e.preventDefault();
                const saleId = $(this).attr('data-sale-id');
                if (window.FbrPostManager && saleId) {
                    window.FbrPostManager.open(saleId);
                }
            });

            $(document).on('fbr:posted', function(e, data) {
                $('#btnPostToFbr')
                    .prop('disabled', true)
                    .addClass('is-posted')
                    .html('<i class="bi bi-check-circle-fill text-success me-1"></i> FBR Posted (' + data.fbrInvoiceNo + ')');
            });

            // --- Import Logic ---
            function importBookedItem(btn) {
                let $btn = $(btn);
                let items = $btn.data('items');
                if (typeof items === 'string') { try { items = JSON.parse(items); } catch (e) { items = []; } }
                if (!items || !items.length) return;

                // Set Header Data
                if ($btn.data('customer-id')) $('#customer_id').val($btn.data('customer-id')).trigger('change');
                if ($btn.data('employee')) $('#sales_officer_id').val($btn.data('employee')).trigger('change');
                
                // Clear existing table and import items
                $('#saleItems').empty();
                items.forEach(function(item) {
                    $('#btnAddRow').click();
                    let $row = $('#saleItems tr:last');
                    $row.find('.item-id').val(item.product_id);
                    $row.find('.item-code').val(item.item_code);
                    $row.find('.item-name').val(item.product_name);
                    
                    let newOption = new Option(item.product_name, item.product_id, true, true);
                    $row.find('.product-select2').append(newOption).trigger('change');
                    
                    let ppb = parseInt(item.ppb) || 1;
                    let totalPieces = parseInt(item.total_pieces) || 0;
                    let boxes = Math.floor(totalPieces / ppb);
                    let pcs = totalPieces % ppb;

                    $row.find('.quantity').val(boxes);
                    $row.find('.loose_pieces').val(pcs);
                    $row.find('.price').val(item.price);
                    recalcRow($row);
                });
                recalcSummary();
            }

            $('.btn-import-single').on('click', function() {
                if (window.ERPImportLoader) window.ERPImportLoader.start();
                importBookedItem(this);
                $('#bookedProductsModal').modal('hide');
                if (window.ERPImportLoader) window.ERPImportLoader.success();
            });

            // Search Logic for Modals
            $('#searchCustomer').on('input', function() {
                let v = $(this).val().toLowerCase();
                $('.customer-item-row').each(function() { $(this).toggle($(this).attr('data-search').includes(v)); });
            });
            $('#searchOfficer').on('input', function() {
                let v = $(this).val().toLowerCase();
                $('.officer-item-row').each(function() { $(this).toggle($(this).attr('data-search').includes(v)); });
            });
            $('#searchBookedProducts').on('input', function() {
                let v = $(this).val().toLowerCase();
                $('.booked-item-row').each(function() { $(this).toggle($(this).attr('data-search').includes(v)); });
            });

            // Initial recalcs
            recalcSummary();
        });
    </script>
@endsection
