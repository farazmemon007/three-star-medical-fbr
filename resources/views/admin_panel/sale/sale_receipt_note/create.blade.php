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
            /* box-shadow: inset 0 0 0 1px #3b82f6; */
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
            font-weight: 700;
            padding: 0 0.4rem;
            transition: all 0.2s;
            min-width: 32px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .erp-table .input-group .btn:hover {
            background-color: #f1f5f9;
            color: #3b82f6;
            border-color: #cbd5e1 !important;
        }

        /* Remove arrows from number inputs */
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

        /* Highlight inputs (Net Totals etc) */
        .input-highlight {
            font-weight: 700 !important;
            color: #059669 !important;
            /* Emerald 600 */
            font-size: 0.95rem;
        }

        /* Product Search */
        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            padding-left: 2.75rem !important;
            border-color: #cbd5e1;
            background-color: #fff;
            height: 42px;
            border-radius: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .searchResults {
            position: absolute;
            z-index: 9999;
            width: 100%;
            max-height: 250px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            margin-top: 5px;
            padding: 0.5rem;
        }

        .search-result-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: none;
            margin-bottom: 2px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-result-item:hover,
        .search-result-item.active {
            background-color: #eff6ff;
            color: #1e40af;
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

        .btn-erp-success:hover {
            background: linear-gradient(to bottom, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(5, 150, 105, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .btn-erp-warning {
            background: linear-gradient(to bottom, #f59e0b, #d97706);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(217, 119, 6, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .btn-erp-warning:hover:not(:disabled) {
            background: linear-gradient(to bottom, #d97706, #b45309);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(217, 119, 6, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .btn-erp:disabled {
            opacity: 0.55;
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: none !important;
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

        .btn-erp-secondary {
            background-color: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
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

        .btn-erp-danger-ghost:hover {
            background: #fee2e2;
            color: #dc2626;
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

        /* Scrollbars */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Product Select Button Style */
        .product-select-btn {
            background: #fff;
            color: #333;
            border: 1px solid #000;
            padding: 4px 10px;
            border-radius: 1px;
            font-weight: 500;
            width: 100%;
            text-align: left;
            position: relative;
            cursor: pointer;
            font-size: 13px;
        }

        .product-select-btn:hover {
            background: #f8f9fa;
        }

        .product-select-btn.has-value {
            background: #fff;
            color: #000;
            border-color: #000;
            font-weight: 700;
        }

        .psm-btn-arrow {
            float: right;
            font-size: 0.8em;
            margin-top: 3px;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

                <form id="saleForm" action="{{ route('sales.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" id="action" value="post">
                    <input type="hidden" name="draft_id" id="draft_id_input" value="">
                    <input type="hidden" name="dc_id" id="dc_id_input" value="">
                    <input type="hidden" name="mode" value="{{ request()->query('mode') }}">

                    <!-- Page Header Top -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="page-title"><i class="bi bi-box-seam me-2 text-primary"></i>
                                {{ request()->query('mode') == 'so' ? 'Sale Order' : 'Sale Invoice Note' }}
                            </h1>
                            <p class="page-subtitle mb-0">Record outward stock to customers efficiently.
                            </p>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn-erp btn-erp-success" id="btnDeliveryOrderModal" disabled>
                                <i class="bi bi-file-earmark-arrow-down"></i> Import Delivery Note
                            </button>
                            <a href="{{ route('sale.receipt.index') }}" class="btn-erp btn-erp-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>

                    <div class="erp-card mb-3">
                        <div class="erp-card-header">
                            <h3 class="erp-card-title"><i class="bi bi-info-square text-primary"></i>
                                {{ request()->query('mode') == 'so' ? 'Sale Order Information' : 'Order Information' }}
                            </h3>
                            <span class="erp-badge"><i class="bi bi-record-circle-fill me-1 text-info"></i>Draft Mode</span>
                        </div>
                        <div class="erp-card-body p-3 bg-light">
                            <div class="row gx-3 gy-3">
                                <!-- Left Section (Matches Screenshot) -->
                                <div class="col-lg-8">
                                    <div class="bg-white border rounded p-3 shadow-sm h-100">
                                        <table class="table table-borderless table-sm mb-0">
                                            <style>
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

                                                .compact-input:focus {
                                                    border-color: #3b82f6;
                                                    outline: none;
                                                    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
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
                                            <tbody>
                                                @php
                                                    $currentBranchId = auth()->user()->getBranchId();
                                                    $currentBranchName = $currentBranchId
                                                        ? \App\Models\Branch::find($currentBranchId)?->name
                                                        : 'All Branches (Super Admin)';
                                                @endphp
                                                <tr>
                                                    <td class="compact-lbl compact-td" style="width: 15%;">BRANCH :</td>
                                                    <td colspan="3" class="compact-td">
                                                        <input type="text"
                                                            class="compact-input shadow-sm text-secondary bg-light"
                                                            style="max-width:300px; font-weight:600;"
                                                            value="{{ strtoupper($currentBranchName) }}" readonly>
                                                        <input type="hidden" name="branch_id" id="branch_id"
                                                            value="{{ $currentBranchId }}">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">SALE INVC #</td>
                                                    <td class="text-secondary fw-bold compact-td" style="width: 35%;">
                                                        <input type="hidden"
                                                            value="{{ $nextInvoice ?? 'S-001017' }}">
                                                        <input type="text" name="sale_no" readonly
                                                            value="{{ $nextInvoice ?? 'S-001017' }}"
                                                            class="bg-transparent border-0 text-secondary p-0 w-100 fw-bold"
                                                            style="outline: none;">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td" style="width: 25%;">CUST REF / DC #</td>
                                                    <td style="width: 25%;" class="compact-td"><input type="text"
                                                            name="vendor_bill_no" class="compact-input shadow-sm"></td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">INVOICE DATE :</td>
                                                    <td class="compact-td"><input type="date" id="purchase_date_input"
                                                            name="purchase_date" value="{{ date('Y-m-d') }}"
                                                            class="compact-input shadow-sm text-secondary"
                                                            style="max-width: 200px;"></td>
                                                    <td class="compact-lbl text-end compact-td">DC DATE :</td>
                                                    <td class="compact-td"><input type="date" id="dc_date_input"
                                                            name="dc_date" class="compact-input shadow-sm text-secondary"></td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">SO #</td>
                                                    <td class="text-secondary compact-td">
                                                        <!-- Note: the name was requested as purchase_order_no in the form -->
                                                        <input type="text" name="sale_order_no"
                                                            class="bg-transparent border-0 text-secondary p-0 w-100"
                                                            style="outline: none;" value="000000">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td">SO DATE :</td>
                                                    <td class="compact-td"><input type="date" id="so_date_input" name="so_date"
                                                            value="{{ date('Y-m-d') }}"
                                                            class="compact-input shadow-sm text-secondary"></td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">ORDER #</td>
                                                    <td class="compact-td"><input type="text"
                                                            name="order_no" class="compact-input shadow-sm"></td>
                                                    <td class="compact-lbl text-end compact-td">JOB # / REF</td>
                                                    <td class="compact-td"><input type="text" name="reference"
                                                            class="compact-input shadow-sm"></td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">PROJECT :</td>
                                                    <td class="compact-td" colspan="3">
                                                        <select name="project"
                                                            class="compact-select text-secondary shadow-sm" style="max-width: 200px;">
                                                            <option value="">(N/A)</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Right Section (Vendor & Settings) -->
                                <div class="col-lg-4">
                                    <div class="bg-white border rounded p-3 shadow-sm h-100 d-flex flex-column">
                                        <div class="form-group mb-3">
                                            <label class="form-label" style="font-size: 11px;">CUSTOMER ACCOUNT</label>
                                            <div class="input-group shadow-sm">
                                                <button type="button" class="btn input-group-text bg-light border-end-0" data-toggle="modal" data-target="#customerModal" title="Select Customer">
                                                    <i class="bi bi-person text-primary fs-5"></i>
                                                </button>
                                                <input type="hidden" name="customer" id="customer_id" value="">
                                                <input type="text" id="customer_name_display"
                                                    class="form-control border-start-0 ps-0 fw-bold bg-white text-dark" readonly
                                                    placeholder="WALKING CUSTOMER" style="cursor: pointer;" data-toggle="modal" data-target="#customerModal" value="WALKING CUSTOMER">
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="form-label" style="font-size: 11px;">SALES OFFICER</label>
                                            <div class="input-group shadow-sm">
                                                <button type="button" class="btn input-group-text bg-light border-end-0" data-toggle="modal" data-target="#officerModal" title="Select Sales Officer">
                                                    <i class="bi bi-person-badge text-primary fs-5"></i>
                                                </button>
                                                <input type="hidden" name="sales_officer_id" id="sales_officer_id" value="">
                                                <input type="text" id="sales_officer_display"
                                                    class="form-control border-start-0 ps-0 fw-bold bg-white text-dark" readonly
                                                    placeholder="Select Sales Officer" style="cursor: pointer;" data-toggle="modal" data-target="#officerModal" value="Select Sales Officer">
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="form-label" style="font-size: 11px;">COMMISSION %</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-percent text-success fs-6"></i>
                                                </span>
                                                <input type="number" step="0.01" min="0" max="100" name="commission_percentage" id="commission_percentage"
                                                    class="form-control border-start-0 ps-2 fw-bold text-dark" placeholder="Enter % (e.g. 1.00)" value="">
                                            </div>
                                            <small class="text-muted" style="font-size: 10px;">Applied on amount net of GST (18%) & Sale Tax (5%)</small>
                                        </div>

                                        <div class="mt-auto bg-light p-3 rounded border">
                                            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                                <input class="form-check-input mt-0" type="checkbox" id="gst_invoice"
                                                    name="is_gst_invoice" checked
                                                    style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                                <label class="form-check-label fw-bold text-dark compact-lbl"
                                                    for="gst_invoice" style="cursor:pointer; padding-top:2px;">GST
                                                    INVOICE</label>
                                            </div>
                                            <div class="form-check form-switch d-flex align-items-center gap-2 mt-2 mb-0">
                                                <input class="form-check-input mt-0" type="checkbox" id="enable_hs_code"
                                                    name="enable_hs_code" value="1" checked
                                                    style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                                <label class="form-check-label fw-bold text-dark compact-lbl"
                                                    for="enable_hs_code" style="cursor:pointer; padding-top:2px;">ENABLE HS CODE</label>
                                            </div>
                                            <div class="text-muted mt-2" style="font-size: 11px; font-weight: 600;">
                                                STATUS: <span class="text-primary">UN-POSTED</span></div>
                                            @php
                                                $defaultWhId = isset($Warehouse) && $Warehouse->first() ? $Warehouse->first()->id : 1;
                                            @endphp
                                            <input type="hidden" id="warehouse_id_header" value="{{ $defaultWhId }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MAIN TABLE AREA -->
                    <div class="erp-card mt-2">
                        <div class="erp-card-header d-flex justify-content-between align-items-center"
                            style="padding-bottom: 1rem; border-bottom: none;">
                            <h3 class="erp-card-title"><i class="bi bi-boxes text-primary"></i> Product Line Items</h3>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn-erp btn-erp-primary" id="btnAddRow"
                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                    <i class="bi bi-plus-lg"></i> Add New Row
                                </button>
                            </div>
                        </div>

                        <div class="erp-card-body pt-0">
                            <!-- Table -->
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
                                            <th style="width:300px">Product</th>
                                            <th style="width:145px" class="hs-code-col text-center">HS Code</th>
                                            <th style="width:140px">Packing</th>
                                            <th style="width:100px" class="text-center">UOM</th>
                                            <th style="width:80px" class="text-center">Packet</th>
                                            <th style="min-width:100px" class="text-end">Box Qty</th>
                                            <th style="min-width:80px" class="text-end">Loose</th>
                                            <th style="min-width:90px" class="text-end">Free Pcs</th>
                                            <th style="min-width:120px" class="text-end">Rate/PC</th>
                                            <th style="min-width:180px" class="text-end">Discount</th>
                                            <th style="min-width:150px" class="text-end">Sub Total</th>
                                            <th style="min-width:130px" class="text-end">GST %</th>
                                            <th style="min-width:130px" class="text-end">IncTax</th>
                                            <th style="min-width:130px" class="text-end">advTax</th>
                                            <th style="min-width:160px" class="text-end">Net Total</th>
                                            @if (request()->query('mode') != 'so')
                                                <th style="width:180px">Batch Selection</th>
                                                <th style="width:140px">Lot / Batch#</th>
                                                <th style="width:120px" class="text-end">Cost/PC</th>
                                            @else
                                                <th style="width:120px" class="text-end">Cost/PC</th>
                                            @endif
                                            <th style="width:60px; text-align:center;">Del</th>
                                        </tr>
                                    </thead>
                                    <tbody id="saleItems">
                                        <!-- Embedded Rows Go Here -->
                                    </tbody>
                                </table>
                                <!-- Empty State Background -->
                                <div id="emptyTableState" class="text-center py-5 text-muted"
                                    style="background: #f8fafc; border-top: 1px dashed #cbd5e1;">
                                    <i class="bi bi-cart-x fs-1 mb-2"></i>
                                    <p class="mb-0">No items added yet. Search products above to begin.</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- BOTTOM AREA -->
                    <div class="row">
                        <!-- Setup & Payments -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="erp-card mb-4">
                                <div class="erp-card-header">
                                    <h3 class="erp-card-title"><i class="bi bi-wallet2 text-primary"></i> {{ request()->query('mode') == 'so' ? 'Setup Details' : 'Setup & Payments' }}
                                    </h3>
                                </div>
                                <div class="erp-card-body">
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label class="form-label">Delivered By / Transport</label>
                                            <input type="text" name="transport_name" class="form-control"
                                                placeholder="Name or vehicle details">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="form-label">Sale Remarks</label>
                                            <input type="text" name="note" class="form-control"
                                                placeholder="Any internal notes">
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="printPreview"
                                                    name="print_preview" value="1" checked>
                                                <label class="form-check-label fw-bold text-dark" for="printPreview">
                                                    Auto Print Receipt
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    @if (request()->query('mode') != 'so')
                                        <hr class="my-4" style="border-color: #e2e8f0; border-style: dashed;">

                                        <h5 class="fw-bold mb-3" style="font-size:0.95rem; color:#1e293b;">Payment Voucher
                                            Assignments</h5>
                                        <div id="paymentWrapper">
                                            <!-- Payment rows dynamically added here -->
                                            <div class="d-flex gap-2 align-items-center mb-2 payment-row">
                                                <div class="input-group" style="max-width:350px;">
                                                    <span class="input-group-text bg-light text-secondary"><i
                                                            class="bi bi-bank"></i></span>
                                                    <select class="form-select rv-account" name="payment_account_id[]">
                                                        <option value="" selected disabled>Select Payment Account
                                                        </option>
                                                        @if (isset($accounts))
                                                            @foreach ($accounts as $acc)
                                                                <option value="{{ $acc->id }}">{{ $acc->title }}
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="input-group" style="max-width:180px;">
                                                    <span class="input-group-text bg-light fw-bold">Rs.</span>
                                                    <input type="number" class="form-control text-end payment-amount"
                                                        name="payment_amount[]" placeholder="0.00">
                                                </div>
                                                <button type="button" class="btn btn-erp-primary rounded px-3 shadow-sm"
                                                    id="btnAddPayment" style="padding: 0.5rem 0.75rem;"><i
                                                        class="bi bi-plus-lg"></i> Add</button>
                                            </div>
                                        </div>
                                        <div class="mt-3 p-3 bg-light rounded-3 d-inline-block border">
                                            <span class="text-muted fw-bold me-3">Total Applied Payment:</span>
                                            <span class="fw-bold text-success fs-5" id="totalPaid">Rs. 0.00</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="erp-card sticky-top" style="top: 20px;">
                                <div class="erp-card-header bg-primary text-white"
                                    style="border-radius: 12px 12px 0 0; background: linear-gradient(135deg, #1e293b, #0f172a);">
                                    <h3 class="erp-card-title text-white"><i class="bi bi-calculator text-info"></i>
                                        Financial Summary</h3>
                                </div>
                                <div class="erp-card-body" style="background: #f8fafc; border-radius: 0 0 12px 12px;">

                                    <div class="summary-row" id="summary-qty-row">
                                        <span class="summary-label">Total Qty</span>
                                        <span class="fw-bold text-primary small w-50 text-end" id="summary_total_qty">0 Pcs</span>
                                    </div>

                                    <div class="summary-row" id="summary-free-qty-row">
                                        <span class="summary-label">Total Free Qty</span>
                                        <span class="fw-bold text-info small w-50 text-end" id="summary_total_free_qty">—</span>
                                    </div>

                                    <div class="summary-row">
                                        <span class="summary-label">Gross Total</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value"
                                            id="gross_total" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row">
                                        <span class="summary-label text-danger">Product Discounts</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-danger"
                                            id="total_row_disc" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row align-items-center py-2">
                                        <span class="summary-label">Bill Discount</span>
                                        <div class="d-flex align-items-center gap-1 w-50">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="sum_discount" name="discount" class="form-control text-end input_summary" value="0.00">
                                                <input type="hidden" name="discount_type" id="sum_discount_type" value="amount">
                                                <button class="btn btn-outline-secondary toggle-type" type="button" data-type="amount" style="min-width:40px; font-size:0.75rem;">Rs</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="summary-row">
                                        <span class="summary-label">Sub Total</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value"
                                            name="subtotal" id="summary_sub_total" readonly tabindex="-1"
                                            value="0.00">
                                    </div>

                                    <div class="summary-row py-2">
                                        <span class="summary-label">Freight Charges</span>
                                        <input type="number"
                                            class="form-control form-control-sm text-end w-50 input_summary"
                                            name="freight_charges" id="sum_freight" value="0.00">
                                    </div>

                                    <div class="summary-row py-2">
                                        <div class="d-flex flex-column text-start">
                                            <span class="summary-label">Bilti Expenses</span>
                                        </div>
                                        <input type="number"
                                            class="form-control form-control-sm text-end w-50 input_summary"
                                            name="extra_cost" id="sum_expense" value="0.00">
                                    </div>

                                    <div class="summary-row text-success mt-1">
                                        <span class="summary-label text-success fw-bold">Total GST <small>(Added ➕)</small></span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-success"
                                            id="total_gst" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row" style="border-top:1px dashed #e2e8f0; padding-top:6px; margin-top:4px;">
                                        <span class="summary-label fw-bold text-dark">Invoice Total</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 fw-bold text-dark"
                                            id="invoice_total" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row text-primary mt-1">
                                        <span class="summary-label text-danger">Inc Tax (WHT) <small>(Deducted ➖)</small></span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-danger"
                                            id="total_inc_tax" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row text-success mt-1">
                                        <span class="summary-label text-success fw-bold">Adv Tax <small>(Added ➕)</small></span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-success fw-bold"
                                            id="total_adv_tax" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <!-- FINAL NET TOTAL -->
                                    <div
                                        class="summary-total-row d-flex justify-content-between align-items-center mt-3 shadow-sm">
                                        <div class="summary-total-label">Net Payable</div>
                                        <div class="text-end">
                                            <div class="text-muted small fw-bold">PKR</div>
                                            <input type="text"
                                                class="form-control form-control-lg text-end bg-transparent border-0 summary-total-value p-0"
                                                id="final_net_total" name="net_amount" readonly tabindex="-1"
                                                value="0.00">
                                        </div>
                                    </div>

                                    <div class="summary-row py-2 mt-2">
                                        <span class="summary-label">Amount in Words</span>
                                        <input type="text" name="total_amount_Words" id="amountInWords" 
                                            class="form-control form-control-sm text-end w-75 bg-transparent border-0 fw-bold" 
                                            readonly tabindex="-1" value="Zero Rupees">
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="d-flex flex-column gap-2 mt-4">
                                        {{-- CONFIRM & POST button: visible in sin mode, hidden in so mode (JS can un-hide it) --}}
                                        <button type="button"
                                            class="btn-sale-action btn-sale-confirm"
                                            id="btnConfirm" style="{{ request()->query('mode') == 'so' ? 'display:none;' : '' }}">
                                            <i class="bi bi-check-circle-fill fs-6"></i> CONFIRM &amp; POST SALE
                                        </button>
                                        {{-- Save Draft button: visible in so mode, hidden in sin mode (JS can un-hide it) --}}
                                        <button type="button" class="btn-sale-action btn-sale-draft"
                                            id="btnSaveOnly" style="{{ request()->query('mode') != 'so' ? 'display:none;' : '' }}">
                                            <i class="bi bi-save2 fs-6"></i> Save Sale Order (Draft)
                                        </button>
                                        {{-- POST TO FBR button: disabled by default, enabled after sale is posted --}}
                                        <button type="button"
                                            class="btn-sale-action btn-sale-fbr"
                                            id="btnPostToFbr"
                                            disabled
                                            title="Post this sale invoice to FBR Digital Invoicing system"
                                            style="{{ request()->query('mode') == 'so' ? 'display:none;' : '' }}">
                                            <i class="bi bi-cloud-arrow-up-fill fs-6"></i> POST TO FBR
                                        </button>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="printPreview" checked>
                                            <label class="form-check-label text-muted small" for="printPreview">Auto-print
                                                receipt</label>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
    <!-- Booked Products Modal -->
    <div class="modal fade" id="bookedProductsModal" tabindex="-1" aria-labelledby="bookedProductsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom border-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2"
                        id="bookedProductsModalLabel">
                        <i class="bi bi-card-checklist text-primary fs-4"></i>
                        {{ request()->query('mode') == 'so' ? 'Import Sales / Delivery Notes' : 'Import Delivery Notes' }}
                    </h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <div class="search-wrapper">
                                <i class="bi bi-search search-icon text-muted"></i>
                                <input type="text" id="searchBookedProducts"
                                    class="form-control form-control-sm search-input"
                                    placeholder="Search by vendor, component, code, sys ID...">
                            </div>
                        </div>
                    </div>

                    <div class="erp-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="erp-table table-hover align-middle mb-0" id="bookedProductsTable">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th style="width: 5%;"></th>
                                    <th>SYS ID / Ref #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th class="text-end">Total Items</th>
                                    <th class="text-end">Net Amount</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bookedProductsTableBody">
                                @php
                                    $hasDraftItems = false;
                                    $isSoMode = request()->query('mode') == 'so';
                                    $advanceDcNotes = $dcNotes->filter(fn($dc) => $dc->is_advance);
                                @endphp

                                {{-- Draft Sale Orders section (mode=so only) --}}
                                @if ($isSoMode)
                                    @php
                                        $draftSales = $sales->where('sale_status', 'draft');
                                    @endphp
                                    @foreach ($draftSales as $draft)
                                        @php $hasDraftItems = true; @endphp
                                        <tr class="booked-item-row"
                                            data-customer-id="{{ $draft->customer_id }}"
                                            data-search="{{ strtolower($draft->invoice_no . ' ' . ($draft->customer_relation->customer_name ?? '')) }}">
                                            <td>
                                                <i class="bi bi-file-earmark-text text-primary"></i>
                                            </td>
                                            <td><span class="badge bg-light text-dark border">{{ $draft->invoice_no }}</span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($draft->sale_date)->format('d M, Y') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="vendor-initial bg-primary-subtle text-primary fw-bold rounded-3 p-2"
                                                        style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                        {{ strtoupper(substr($draft->customer_relation->customer_name ?? 'C', 0, 1)) }}
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold text-dark">{{ $draft->customer_relation->customer_name ?? 'N/A' }}</span>
                                                        <span
                                                            class="text-muted small">{{ $draft->customer_relation->business_name ?? 'Individual' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold">{{ $draft->items->count() }}</td>
                                            <td class="text-end fw-bold text-primary">
                                                {{ number_format($draft->total_net, 2) }}</td>
                                            <td class="text-end">
                                                <button type="button"
                                                    class="btn btn-primary btn-sm rounded-pill px-4 btn-import-single"
                                                    title="Import Sale Order"
                                                    data-type="draft"
                                                    data-customer-id="{{ $draft->customer_id }}"
                                                    data-id="{{ $draft->id }}">
                                                    <i class="bi bi-arrow-down-square me-1"></i> Import
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Visual separator for advance DCs in SO mode --}}
                                    @if ($advanceDcNotes->count() > 0)
                                    <tr>
                                        <td colspan="7" style="background:#fffbeb; color:#92400e; font-weight:700; padding:6px 14px; font-size:12px; border-top:2px solid #fbbf24;">
                                            <i class="bi bi-truck me-1"></i> Advance Delivery Notes &mdash; Pending Sale Invoice
                                        </td>
                                    </tr>
                                    @endif
                                @endif

                                {{-- Advance DCs (always shown in SO mode); All DC notes in SIN mode --}}
                                @foreach ($isSoMode ? $advanceDcNotes : $dcNotes as $dc)
                                    @php $hasDraftItems = true; @endphp
                                    <tr class="booked-item-row"
                                        data-customer-id="{{ $dc->customer_id }}"
                                        data-search="{{ strtolower($dc->dc_no . ' ' . ($dc->customer->customer_name ?? '')) }}">
                                        <td>
                                            <i class="bi bi-file-earmark-arrow-down text-warning"></i>
                                        </td>
                                        <td><span class="badge bg-warning text-dark border">{{ $dc->dc_no }}</span>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($dc->delivery_date)->format('d M, Y') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="vendor-initial bg-primary-subtle text-primary fw-bold rounded-3 p-2"
                                                    style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                    {{ strtoupper(substr($dc->customer->customer_name ?? 'C', 0, 1)) }}
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span
                                                        class="fw-bold text-dark">{{ $dc->customer->customer_name ?? 'N/A' }}</span>
                                                    <span
                                                        class="text-muted small">{{ $dc->customer->business_name ?? 'Individual' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold">{{ $dc->items->count() }}</td>
                                        <td class="text-end fw-bold text-primary">
                                            {{ number_format($dc->net_amount, 2) }}</td>
                                        <td class="text-end">
                                            <button type="button"
                                                class="btn btn-warning btn-sm rounded-pill px-4 btn-import-single"
                                                title="Import DC Note as Sale Invoice"
                                                data-type="dc"
                                                data-customer-id="{{ $dc->customer_id }}"
                                                data-id="{{ $dc->id }}"
                                                data-is-advance="{{ $dc->is_advance ? '1' : '0' }}">
                                                <i class="bi bi-arrow-down-square me-1"></i> Import Invoice
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach

                                @if (!$hasDraftItems)
                                    <tr id="emptyBookedRow">
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No booked/draft products found.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top border-light px-4 py-3">
                    <button type="button" class="btn-erp btn-erp-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom border-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="customerModalLabel">
                        <i class="bi bi-people text-primary fs-4"></i>
                        Select Customer
                    </h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="search-wrapper position-relative">
                                <i class="bi bi-search text-muted position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                                <input type="text" id="searchCustomer" class="form-control form-control-sm ps-5 py-2 rounded-pill" placeholder="Search by name, code, or business...">
                            </div>
                        </div>
                    </div>
                    <div class="erp-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="erp-table table-hover align-middle mb-0" id="customerTable">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Business</th>
                                    <th>Contact Info</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="customerTableBody">
                                <tr class="customer-item-row" data-search="walking customer">
                                    <td>-</td>
                                    <td><strong>WALKING CUSTOMER</strong></td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 btn-select-customer" data-id="" data-name="WALKING CUSTOMER">
                                            Select
                                        </button>
                                    </td>
                                </tr>
                                @foreach ($Customer as $c)
                                    <tr class="customer-item-row" data-search="{{ strtolower(($c->customer_id ?? '') . ' ' . ($c->customer_name ?? '') . ' ' . ($c->business_name ?? '')) }}">
                                        <td><span class="badge bg-light text-dark border">{{ $c->customer_id ?? 'N/A' }}</span></td>
                                        <td><strong>{{ $c->customer_name }}</strong></td>
                                        <td>{{ $c->business_name ?? '-' }}</td>
                                        <td>
                                            @if($c->phone)<i class="bi bi-telephone text-muted"></i> <small>{{ $c->phone }}</small><br>@endif
                                            @if($c->email)<small class="text-muted"><i class="bi bi-envelope"></i> {{ $c->email }}</small><br>@endif
                                            @if($c->address)<small class="text-muted"><i class="bi bi-geo-alt"></i> {{ \Illuminate\Support\Str::limit($c->address, 30) }}</small>@endif
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 btn-select-customer" 
                                                data-id="{{ $c->id }}" 
                                                data-name="{{ $c->customer_id ? '['.$c->customer_id.'] ' : '' }}{{ $c->customer_name }} {{ $c->business_name ? '(' . $c->business_name . ')' : '' }}">
                                                Select
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top border-light px-4 py-3">
                    <button type="button" class="btn-erp btn-erp-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Officer Modal -->
    <div class="modal fade" id="officerModal" tabindex="-1" aria-labelledby="officerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom border-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="officerModalLabel">
                        <i class="bi bi-person-badge text-primary fs-4"></i>
                        Select Sales Officer
                    </h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="search-wrapper position-relative">
                                <i class="bi bi-search text-muted position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                                <input type="text" id="searchOfficer" class="form-control form-control-sm ps-5 py-2 rounded-pill" placeholder="Search by name...">
                            </div>
                        </div>
                    </div>
                    <div class="erp-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="erp-table table-hover align-middle mb-0" id="officerTable">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Contact Details</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="officerTableBody">
                                <tr class="officer-item-row" data-search="select sales officer">
                                    <td><strong>N/A (None)</strong></td>
                                    <td>-</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 btn-select-officer" data-id="" data-name="Select Sales Officer">
                                            Select
                                        </button>
                                    </td>
                                </tr>
                                @foreach ($employees as $emp)
                                    <tr class="officer-item-row" data-search="{{ strtolower(($emp->employee_id ?? '') . ' ' . ($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) }}">
                                        <td><span class="badge bg-light text-dark border">{{ $emp->employee_id ?? 'N/A' }}</span></td>
                                        <td><strong>{{ $emp->first_name }} {{ $emp->last_name }}</strong></td>
                                        <td>
                                            @if($emp->phone) <i class="bi bi-telephone ms-1 text-muted"></i> <small>{{ $emp->phone }}</small><br> @endif
                                            @if($emp->email) <small class="text-muted"><i class="bi bi-envelope ms-1"></i> {{ $emp->email }}</small> @endif
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 btn-select-officer" 
                                                data-id="{{ $emp->id }}" 
                                                data-name="{{ $emp->employee_id ? '['.$emp->employee_id.'] ' : '' }}{{ $emp->first_name }} {{ $emp->last_name }}">
                                                Select
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top border-light px-4 py-3">
                    <button type="button" class="btn-erp btn-erp-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @include('admin_panel.sale.components.quick_hs_modal')

@endsection

{{-- Advanced Product Selection Modal --}}
@include('admin_panel.components.product_select_modal')
@include('admin_panel.sale.components.fbr_post_modal')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Custom Select2 Overrides for Modern UI */
    .select2-container--default .select2-selection--single {
        border: 1px solid transparent;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 4px;
        height: 38px;
        display: flex;
        align-items: center;
        background-color: transparent;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-bottom: 1px solid #3b82f6;
        box-shadow: inset 0 -1px 0 0 #3b82f6;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155;
        font-weight: 600;
    }
</style>

<script>
    $(document).ready(function() {
        function toggleGstFields() {
            let isGst = $('#gst_invoice').is(':checked');
            if (isGst) {
                $('.gst, .inc-tax, .adv-tax').prop('readonly', false).css('background', '');
            } else {
                $('.gst, .inc-tax, .adv-tax').val(0).prop('readonly', true).css('background', '#e9ecef');
            }
            // Trigger recalculation on all rows
            $('#saleItems tr').each(function() {
                let $row = $(this);
                if (typeof recalcRow === "function" && ($row.find('.item-id').val() || $row.find('.product-select2').val())) {
                    recalcRow($row);
                }
            });
            if (typeof recalcSummary === "function") recalcSummary();
        }

        $('#gst_invoice').on('change', function() {
            toggleGstFields();
        });
        
        setTimeout(toggleGstFields, 500);

        // Customer Modal Logic
        $('#searchCustomer').on('input', function() {
            let val = $(this).val().toLowerCase();
            $('.customer-item-row').each(function() {
                let searchData = $(this).attr('data-search');
                if (searchData.includes(val)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $('.btn-select-customer').on('click', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            $('#customer_id').val(id);
            $('#customer_name_display').val(name);
            $('#customerModal').modal('hide');

            $('#btnDeliveryOrderModal').prop('disabled', false);

            $('.booked-item-row').each(function() {
                let rowCustId = $(this).attr('data-customer-id') || $(this).find('.btn-import-single').attr('data-customer-id') || $(this).find('.btn-import-single').data('customer-id');
                if (!id || rowCustId == id) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            $('#searchBookedProducts').val('');
        });

        $('#btnDeliveryOrderModal').on('click', function() {
            let currentCustId = $('#customer_id').val();
            if (currentCustId) {
                $('.booked-item-row').each(function() {
                    let rowCustId = $(this).attr('data-customer-id') || $(this).find('.btn-import-single').attr('data-customer-id');
                    if (rowCustId == currentCustId) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                $('.booked-item-row').show();
            }
            $('#searchBookedProducts').val('');
            $('#bookedProductsModal').modal('show');
        });

        // Officer Modal Logic
        $('#searchOfficer').on('input', function() {
            let val = $(this).val().toLowerCase();
            $('.officer-item-row').each(function() {
                let searchData = $(this).attr('data-search');
                if (searchData.includes(val)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $('.btn-select-officer').on('click', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            $('#sales_officer_id').val(id);
            $('#sales_officer_display').val(name);
            $('#officerModal').modal('hide');
        });

        // Payment Voucher Add/Remove Logic
        $('#btnAddPayment').click(function() {
            const html = `
                <div class="d-flex gap-2 align-items-center mb-2 payment-row">
                    <div class="input-group" style="max-width:350px;">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-bank"></i></span>
                        <select class="form-select rv-account" name="payment_account_id[]">
                            <option value="" selected disabled>Select Payment Account</option>
                            @if (isset($accounts))
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="input-group" style="max-width:180px;">
                        <span class="input-group-text bg-light fw-bold">Rs.</span>
                        <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" placeholder="0.00">
                    </div>
                    <button type="button" class="btn btn-erp-danger-ghost border-0 remove-payment" style="padding: 0.5rem 0.75rem;"><i class="bi bi-trash fs-5"></i></button>
                </div>`;
            $('#paymentWrapper').append(html);
        });

        $(document).on('click', '.remove-payment', function() {
            $(this).closest('.payment-row').remove();
            calcTotalPaid();
        });

        $(document).on('focus', '.quantity, .free_qty, .price, .item_disc, .gst, .payment-amount, .input_summary', function() {
            $(this).select();
        });

        $(document).on('input', '.payment-amount', function() {
            calcTotalPaid();
        });

        function calcTotalPaid() {
            let total = 0;
            $('.payment-amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#totalPaid').text('Rs. ' + total.toFixed(2));
        }

        // Prevent natural form submission (e.g. on Enter key)
        $('#saleForm').on('submit', function(e) {
            e.preventDefault();
        });

        // AJAX Form Submission
        $('#btnSaveOnly').click(function(e) {
            e.preventDefault();

            /* 
            // Check for duplicate lots
            let duplicateLots = getDuplicateLots();
            if (duplicateLots.length > 0) {
                let dupeList = duplicateLots.map(d => `<li><strong>${d.name}</strong>: ${d.lot}</li>`)
                    .join('');
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Lot Numbers!',
                    html: `<p>The following products have the same Lot Number in your list. Each Lot Number must be unique per product:</p><ul class="text-start">${dupeList}</ul>`,
                });
                return;
            }
            */

            let $btn = $(this);
            let ogHtml = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
            $('#action').val('save_only');

            $.ajax({
                url: "{{ route('sales.store') }}",
                method: "POST",
                data: $('#saleForm').serialize(),
                success: function(response) {
                    if (response.invoice_url && response.print_preview && $('#action')
                        .val() !== 'save_only') {
                        window.open(response.invoice_url, '_blank');
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Draft Saved!',
                        text: 'Sale order saved as draft successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        let redirectUrl = "{{ route('sale.order.index') }}";
                        window.location.href = redirectUrl;
                    });
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(ogHtml);
                    let msg = 'Something went wrong.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON
                        .message;
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg += '\n' + Object.values(xhr.responseJSON.errors).flat().join(
                            '\n');
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        $('#btnConfirm').click(function(e) {
            e.preventDefault();

            /*
            // Check for duplicate lots
            let duplicateLots = getDuplicateLots();
            if (duplicateLots.length > 0) {
                let dupeList = duplicateLots.map(d => `<li><strong>${d.name}</strong>: ${d.lot}</li>`)
                    .join('');
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Lot Numbers!',
                    html: `<p>The following products have the same Lot Number in your list. Each Lot Number must be unique per product:</p><ul class="text-start">${dupeList}</ul>`,
                });
                return;
            }
            */



            let warningText =
                "This action will affect your inventory stock and this change is irreversible.";
            let warningTitle = "Are you sure you want to post this sale?";
            let warningIcon = "warning";
            let confirmBtnColor = '#059669';
            let confirmBtnText = 'Yes, Post it!';

            Swal.fire({
                title: warningTitle,
                html: warningText,
                icon: warningIcon,
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: confirmBtnText,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    let $btn = $('#btnConfirm');
                    let ogHtml = $btn.html();
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'
                    );
                    $('#action').val('post');
                    $.ajax({
                        url: "{{ route('sales.store') }}",
                        method: "POST",
                        data: $('#saleForm').serialize(),
                        success: function(response) {
                            if (response.invoice_url && response.print_preview) {
                                window.open(response.invoice_url, '_blank');
                            }

                            // Capture created sale ID
                            const createdSaleId = response.booking_id;

                            // Update UI: Disable Confirm button and Enable POST TO FBR button
                            if (createdSaleId) {
                                $('#btnConfirm').prop('disabled', true).html('<i class="bi bi-check2-all me-1"></i> Sale Already Posted');
                                $('#btnPostToFbr')
                                    .prop('disabled', false)
                                    .attr('data-sale-id', createdSaleId)
                                    .css('cursor', 'pointer');
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Sale Posted Successfully!',
                                html: `
                                    <div class="py-2">
                                        <p class="text-muted mb-3">Sale processed, stock updated, and ledger recorded.</p>
                                        <div class="alert alert-warning border text-start p-2 mb-0" style="font-size: 0.88rem;">
                                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> <strong>Do you want to post it to FBR?</strong>
                                            <div class="text-muted small mt-1">You can transmit to FBR right now, or click the enabled <b>POST TO FBR</b> button at any time.</div>
                                        </div>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonColor: '#f59e0b',
                                cancelButtonColor: '#64748b',
                                confirmButtonText: '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Yes, Post to FBR',
                                cancelButtonText: 'View Sales List'
                            }).then((popupRes) => {
                                if (popupRes.isConfirmed) {
                                    if (window.FbrPostManager && createdSaleId) {
                                        window.FbrPostManager.open(createdSaleId);
                                    }
                                } else if (popupRes.dismiss === Swal.DismissReason.cancel) {
                                    window.location.href = response.redirect_url || "{{ route('sale.receipt.index') }}";
                                }
                            });
                        },
                        error: function(xhr) {
                            $btn.prop('disabled', false).html(ogHtml);
                            let msg = 'Something went wrong.';
                            if (xhr.responseJSON && xhr.responseJSON.message) msg =
                                xhr.responseJSON.message;
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg += '\n' + Object.values(xhr.responseJSON.errors)
                                    .flat().join('\n');
                            }
                            Swal.fire('Error', msg, 'error');
                        }
                    });
                }
            });
        });

        // POST TO FBR Button Click Handler
        $('#btnPostToFbr').click(function(e) {
            e.preventDefault();
            const saleId = $(this).attr('data-sale-id');
            if (!saleId) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sale Not Yet Posted',
                    text: 'Please confirm and post the sale first. Once posted, you can transmit this invoice to FBR.'
                });
                return;
            }
            if (window.FbrPostManager) {
                window.FbrPostManager.open(saleId);
            }
        });

        // Listen for FBR Posted Event
        $(document).on('fbr:posted', function(e, data) {
            $('#btnPostToFbr')
                .prop('disabled', true)
                .css('cursor', 'default')
                .addClass('is-posted')
                .html('<i class="bi bi-check-circle-fill text-success me-1"></i> FBR Posted: ' + data.fbrInvoiceNo);
        });

        // Discount Type Toggle (Amt per pc vs Percent)
        $(document).on('click', '.toggle-type', function() {
            let $btn = $(this);
            let current = $btn.data('type') || 'amount';
            let next = (current === 'amount') ? 'percent' : 'amount';
            let nextText = (next === 'percent') ? '%' : 'Rs';
            
            $btn.data('type', next).text(nextText);
            
            if (next === 'percent') {
                $btn.removeClass('btn-outline-secondary').addClass('btn-outline-info');
            } else {
                $btn.removeClass('btn-outline-info').addClass('btn-outline-secondary');
            }

            // Sync hidden input
            let $row = $btn.closest('tr');
            if ($row.length) {
                $row.find('.item-disc-type').val(next);
                recalcRow($row);
                recalcSummary();
            } else {
                // Bill discount toggle
                $('#sum_discount_type').val(next);
                recalcSummary();
            }
        });

        // Toggle Advance Tax Type (% vs Rs)
        $(document).on('click', '.toggle-adv-tax-type', function () {
            let $btn = $(this);
            let current = $btn.attr('data-type') || 'percent';
            let next = (current === 'percent') ? 'amount' : 'percent';

            $btn.attr('data-type', next).text(next === 'percent' ? '%' : 'Rs');
            
            if (next === 'percent') {
                $btn.removeClass('btn-outline-secondary').addClass('btn-outline-info');
            } else {
                $btn.removeClass('btn-outline-info').addClass('btn-outline-secondary');
            }

            let $row = $btn.closest('tr');
            if ($row.length) {
                $row.find('.adv-tax-type').val(next);
                recalcRow($row);
                recalcSummary();
            }
        });

        // Standardized Product Row Population
        function populateProductRow($row, p) {
            const $btn = $row.find('.product-select-btn');
            $btn.text((p.item_name || '') + (p.item_code ? ' (' + p.item_code + ')' : '') + ' ');
            $btn.append('<span class="psm-btn-arrow">&#9660;</span>');
            $btn.addClass('has-value');

            $row.find('.item-id').val(p.id);
            $row.find('.item-code').val(p.item_code || '');
            $row.find('.item-name').val(p.item_name || '');
            $row.find('.hs-code').val(p.hs_code || '');
            $row.find('.size-mode').val(p.size_mode || 'by_cartons');

            // Auto set UOM from product unit or uom_name
            let uomVal = (p.unit && p.unit.name) ? p.unit.name : (p.uom_name || 'Piece');
            $row.find('.uom-input').val(uomVal);
            console.log(p);
            
            // Set price from product data if not already set or if it's a new product
            if (p.sale_price_per_piece) {
                $row.find('.price').val(p.sale_price_per_piece);
            }

            // Standardize UOM/Packing dropdown
            const $pkgSelect = $row.find('.packing-select');
            if ($pkgSelect.length) {
                let optionsHtml = '';
                let seenFactors = {};

                // 1. Packings from database (ProductUom table) - Prioritize these names
                if (p.packings && p.packings.length > 0) {
                    p.packings.forEach(pkg => {
                        let f = parseInt(pkg.pieces_per_box) || 1;
                        if (f <= 1) {
                            let match = pkg.name ? pkg.name.match(/1[xX](\d+)/) : null;
                            if (!match && p.item_name) match = p.item_name.match(/1[xX](\d+)/);
                            if (match && parseInt(match[1]) > 0) {
                                f = parseInt(match[1]);
                            } else {
                                f = parseInt(p.uom || p.pieces_per_box) || 1;
                            }
                        }
                        if (seenFactors[f]) return;
                        seenFactors[f] = true;
                        optionsHtml += `<option value="${pkg.id}" data-ppb="${f}" data-price="${pkg.sale_price || 0}" data-mode="${p.size_mode || 'by_cartons'}">${pkg.name}</option>`;
                    });
                } else {
                    // 2. Base Unit (Only add if NO specialized packings exist)
                    let bPpb = parseInt(p.uom || p.pieces_per_box) || 1;
                    if (bPpb <= 1 && p.item_name) {
                        let match = p.item_name.match(/1[xX](\d+)/);
                        if (match && parseInt(match[1]) > 0) {
                            bPpb = parseInt(match[1]);
                        }
                    }
                    let bName = p.uom_name || (p.unit ? p.unit.name : 'Pcs');
                    if (!bName || bName.toLowerCase() === 'piece' || bName.toLowerCase() === 'pcs') {
                        bName = '1x' + bPpb;
                    }
                    
                    optionsHtml = `<option value="" data-ppb="${bPpb}" data-price="${p.sale_price_per_piece || 0}" data-mode="${p.size_mode || 'by_cartons'}">${bName} (Base)</option>`;
                }

                $pkgSelect.html(optionsHtml);
                
                // 3. Auto-select first available packing if any exists
                let $extras = $pkgSelect.find('option[value!=""]');
                if ($extras.length > 0) {
                    $pkgSelect.val($extras.first().val());
                }
                
                // Explicitly update Packet (item-uom) and trigger change
                const $selectedOpt = $pkgSelect.find('option:selected');
                if ($selectedOpt.length) {
                    const initialFactor = parseInt($selectedOpt.attr('data-ppb') || $selectedOpt.data('ppb')) || 1;
                    const $uomInput = $row.find('.item-uom');
                    $uomInput.val(initialFactor);
                    if ($uomInput[0]) $uomInput[0].value = initialFactor; // Direct assignment for reliability
                    $row.find('.item_uom_factor').val(initialFactor);
                }

                $pkgSelect.trigger('change');
            }

            // Load warehouses and batches
            const $whSelect = $row.find('.item-warehouse');
            if ($whSelect.length) {
                const isSoMode = "{{ request()->query('mode') }}" === "so";
                let whParams = {};
                if (isSoMode) whParams.include_empty = 1;

                $.getJSON('/product/' + p.id + '/warehouses', whParams, function(warehouses) {
                    $whSelect.html('<option value="">-- Select --</option>');
                    if (warehouses.length > 0) {
                        warehouses.forEach(function(pw) {
                            var stock = pw.total_pieces || 0;
                            var displayStock = pw.stock_display || stock;
                            $whSelect.append(`<option value="${pw.id}" data-stock="${stock}" data-stock-display="${displayStock}" data-ppb="${pw.ppb || 1}" data-size-mode="${pw.size_mode || 'std'}">${pw.name} (Stock: ${displayStock})</option>`);
                        });
                        const hWh = $('#warehouse_id_header').val() || '{{ $defaultWhId }}';
                        const defaultWh = warehouses.find(w => w.id == hWh) || warehouses[0];
                        $whSelect.val(defaultWh.id).trigger('change');
                    }
                });
            }

            loadBatches($row, p.id);
            recalcRow($row);
            recalcSummary();
        }

        // Product Table & Search Logic
        let rowCount = 0;

        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function checkEmptyState() {
            if ($('#saleItems tr').length > 0) {
                $('#emptyTableState').hide();
            } else {
                $('#emptyTableState').show();
            }
        }

        // ── parseBoxPiece: identical to GRN logic ──────────────────────────
        function parseBoxPieceNew($boxInput, $looseInput, pack) {
            let boxes = parseFloat($boxInput.val()) || 0;
            let loose = parseFloat($looseInput.val()) || 0;
            let total = (boxes * pack) + loose;
            return { total_pieces: total, boxes: boxes, pieces: loose };
        }


        function recalcRow($row) {
            const pack     = parseFloat($row.find('.item-uom').val()) || 1;
            const sizeMode = $row.find('.size-mode').val() || 'by_cartons';
            const price    = num($row.find('.price').val());
            const discVal  = num($row.find('.item_disc').val());
            const discType = $row.find('.item-disc-type').val() || 'amount';
            const gstRate  = num($row.find('.gst').val());

            let paid = parseBoxPieceNew($row.find('.quantity'), $row.find('.loose_pieces'), pack);
            let total_free_pieces = num($row.find('.free_loose_pieces').val());

            $row.data('total-pieces', paid.total_pieces);
            $row.data('total-free-pieces', total_free_pieces);

            let line_gross = paid.total_pieces * price;
            let line_disc  = (discType === 'percent') ? (line_gross * discVal / 100) : discVal;

            let subTotal = Math.max(0, line_gross - line_disc);

            const gstAmt   = subTotal * (gstRate / 100);   // GST: ADDED
            $row.find('.gst-amount-row').val(gstAmt.toFixed(2));

            const incTaxPct  = num($row.find('.inc-tax').val());
            const advTaxVal  = num($row.find('.adv-tax').val());
            const advTaxType = $row.find('.adv-tax-type').val() || 'percent';

            const incTaxAmt = subTotal * (incTaxPct / 100); // WHT: DEDUCTED
            const advTaxAmt = (advTaxType === 'percent') ? (subTotal * (advTaxVal / 100)) : advTaxVal; // Adv: ADDED
            // Line net: sub + GST - WHT + Adv
            const netTotal  = subTotal + gstAmt - incTaxAmt + advTaxAmt;

            $row.find('.row-sub-total').val(subTotal.toFixed(2));
            $row.find('.row-net-total').val(netTotal.toFixed(2));

            const totalQty = paid.total_pieces + total_free_pieces;
            $row.find('.row-cost-pc').val(totalQty > 0 ? (netTotal / totalQty).toFixed(2) : '0.00');
        }

        function recalcSummary() {
            let gross       = 0;
            let totalGstAmt = 0;
            let totalIncTax = 0;
            let totalAdvTax = 0;
            let totalPieces = 0;
            let totalFreePieces = 0;
            let totalRowDisc = 0;
            let sumRowSub = 0;
            let boxCount = 0;
            let pieceCount = 0;

            $('#saleItems tr').each(function() {
                const $r      = $(this);
                recalcRow($r);
                const pack    = parseFloat($r.find('.item-uom').val()) || 1;
                const sizeMode = $r.find('.size-mode').val() || 'by_cartons';
                const price   = num($r.find('.price').val());
                const fpcs    = $r.data('total-free-pieces') || 0;
                
                const discVal  = num($r.find('.item_disc').val());
                const discType = $r.find('.item-disc-type').val() || 'amount';

                let paid = parseBoxPieceNew($r.find('.quantity'), $r.find('.loose_pieces'), pack);

                let rowGross  = paid.total_pieces * price;
                let rowDisc   = (discType === 'percent') ? (rowGross * discVal / 100) : discVal;
                
                let rowGst    = num($r.find('.gst-amount-row').val());
                let rowSub    = num($r.find('.row-sub-total').val());
                let rowIncTax = rowSub * (num($r.find('.inc-tax').val()) / 100);

                let rowAdvTaxVal  = num($r.find('.adv-tax').val());
                let rowAdvTaxType = $r.find('.adv-tax-type').val() || 'percent';
                let rowAdvTax     = (rowAdvTaxType === 'percent') ? (rowSub * (rowAdvTaxVal / 100)) : rowAdvTaxVal;

                let sub       = num($r.find('.row-sub-total').val());

                gross          += rowGross;
                totalRowDisc   += rowDisc;
                sumRowSub      += sub;
                totalGstAmt    += rowGst;
                totalIncTax    += rowIncTax;
                totalAdvTax    += rowAdvTax;
                totalPieces    += paid.total_pieces;
                totalFreePieces += fpcs;
                
                boxCount       += paid.boxes;
                pieceCount     += paid.pieces;
            });

            // Display total pieces only
            $('#summary_total_qty').text(totalPieces + ' Pcs');
            $('#summary_total_free_qty').text(totalFreePieces + ' Total Pcs');

            $('#gross_total').val(gross.toFixed(2));
            $('#total_row_disc').val(totalRowDisc.toFixed(2));
            $('#total_gst').val(totalGstAmt.toFixed(2));
            $('#total_inc_tax').val(totalIncTax.toFixed(2));
            $('#total_adv_tax').val(totalAdvTax.toFixed(2));

            const discVal = num($('#sum_discount').val());
            const discType = $('#sum_discount').closest('.input-group').find('.toggle-type').data('type');
            
            let billDisc = 0;
            if (discType === 'percent') {
                billDisc = (sumRowSub) * (discVal / 100);
            } else {
                billDisc = discVal;
            }

            let summarySub = sumRowSub - billDisc;
            $('#summary_sub_total').val(summarySub.toFixed(2));

            const sumApplyGst = num($('#sum_apply_gst').val());
            const sumFreight  = num($('#sum_freight').val());
            const sumExpense  = parseFloat($('#sum_expense').val() || 0);

            // Pakistan Standard:
            // Invoice Total = (summarySub + freight + expense + fixed gst add-on) + GST
            // Net Payable = Invoice Total - WHT - Adv
            const gstBase     = summarySub + sumFreight + sumExpense + sumApplyGst;
            const invoiceTotal = gstBase + totalGstAmt;
            let finalNet       = invoiceTotal - totalIncTax + totalAdvTax;
            $('#invoice_total').val(invoiceTotal.toFixed(2));
            $('#final_net_total').val(finalNet.toFixed(2));

            // Update Amount in Words
            if (typeof numberToWords === 'function') {
                $('#amountInWords').val(numberToWords(Math.round(finalNet)));
            }
        }

        function numberToWords(num) {
            var a = ['', 'one ', 'two ', 'three ', 'four ', 'five ', 'six ', 'seven ', 'eight ', 'nine ', 'ten ', 'eleven ', 'twelve ', 'thirteen ', 'fourteen ', 'fifteen ', 'sixteen ', 'seventeen ', 'eighteen ', 'nineteen '];
            var b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
            if ((num = num.toString()).length > 9) return 'overflow';
            n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
            if (!n) return;
            var str = '';
            str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + 'crore ' : '';
            str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + 'lakh ' : '';
            str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + 'thousand ' : '';
            str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + 'hundred ' : '';
            str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) : '';
            return str.trim().toUpperCase() + ' RUPEES ONLY';
        }


        function getDuplicateLots() {
            let lots = {};
            let duplicates = [];
            $('#saleItems tr').each(function() {
                let pid = $(this).find('.item-id').val();
                let lot = $(this).find('input[name="lot_no[]"]').val();
                let name = $(this).find('.item-name').val();
                if (pid && lot && lot.trim() !== '') {
                    lot = lot.trim().toLowerCase();
                    let key = pid + '_' + lot;
                    if (lots[key]) {
                        duplicates.push({
                            name: name,
                            lot: lot
                        });
                    } else {
                        lots[key] = true;
                    }
                }
            });
            return duplicates;
        }

        function loadBatches($row, productId, selectedBatchId = null) {
            const warehouseId = $row.find('.row-warehouse-id').val() || $('#warehouse_id_header').val();
            const $batchSelect = $row.find('.batch-select2');
            const isSoMode = "{{ request()->query('mode') }}" === "so";

            // Clear existing except first option
            $batchSelect.find('option:not(:first)').remove();

            if (!productId) return;

            let params = { warehouse_id: warehouseId };
            if (isSoMode) params.include_empty = 1;

            $.get("{{ route('batches.for.product', ':id') }}".replace(':id', productId), params, function(batches) {
                batches.forEach(function(batch) {
                    $batchSelect.append(`<option value="${batch.id}"
                        data-lot="${batch.batch_number}"
                        data-exp="${batch.exp_date}"
                        data-qty="${batch.qty_remaining}">
                        ${batch.label}
                    </option>`);
                });
                
                if (selectedBatchId) {
                    $batchSelect.val(selectedBatchId).trigger('change');
                }
            });
        }

        function addBlankRow() {
            rowCount++;
            const isSoMode = "{{ request()->query('mode') }}" === "so";
            let batchHtml = '';
            if (!isSoMode) {
                batchHtml = `
                <td>
                    <select name="batch_id[]" class="form-select batch-select2 row-input" style="width:100%">
                        <option value="">Auto (FEFO)</option>
                    </select>
                </td>
                <td><input type="text" name="lot_no[]" class="form-control text-center row-input lot-no-display" placeholder="Auto"></td>
                `;
            }

            const newRow = `
              <tr class="item-row">
                <td>
                    <input type="hidden" name="product_id[]" class="item-id">
                    <input type="hidden" name="warehouse_id[]" class="item-warehouse row-warehouse-id" value="{{ $defaultWhId }}">
                    <input type="text" name="item_code[]" class="form-control bg-transparent border-0 px-0 item-code" readonly>
                </td>
                <td style="font-weight:600; color:#334155;">
                    <input type="hidden" name="item_name[]" class="item-name">
                    <button type="button" class="product-select-btn">Select Product <span class="psm-btn-arrow">&#9660;</span></button>
                </td>
                <td class="hs-code-col">
                    <div class="input-group input-group-sm" style="min-width: 130px;">
                        <input type="text" name="hs_code[]" class="form-control text-center hs-code item-hs-code" style="font-size:11px; padding:2px 4px; background:#fff;" placeholder="HS Code">
                        <button type="button" class="btn btn-outline-primary btn-quick-hs px-2" onclick="window.openQuickHsModalForBtn && window.openQuickHsModalForBtn(this)" title="Quick Update Product HS Code" style="padding: 2px 7px;">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </td>
                <td>
                    <select name="pieces_per_box[]" class="form-select packing-select" style="min-width:130px;"></select>
                    <input type="hidden" name="packing_id[]" class="packing-id">
                    <input type="hidden" name="uom_id[]" class="uom-id">
                    <input type="hidden" name="item_uom_factor[]" class="item_uom_factor" value="1">
                    <input type="hidden" name="size_mode[]" class="size-mode" value="by_cartons">
                </td>
                <td>
                    <input type="text" name="item_uom[]" list="uom_datalist" class="form-control text-center uom-input row-input" style="min-width:85px;" placeholder="e.g. KG, Piece" value="Piece">
                </td>
                <td class="text-center">
                    <input type="number" name="pcs_per_box_display[]" class="form-control text-center item-uom" value="1" readonly style="width:60px; background:#f8fafc;"
                           title="Pieces per Box">
                </td>
                <td><input type="number" name="qty[]" class="form-control quantity text-end fw-bold row-input" style="background:#eff6ff; min-width: 80px;" value="1" placeholder="Box"></td>
                <td><input type="number" name="loose_pieces[]" class="form-control loose_pieces text-end fw-bold row-input" style="background:#eff6ff; min-width: 80px;" value="0" placeholder="Pcs"></td>
                <td><input type="number" name="free_loose_pieces[]" class="form-control free_loose_pieces text-end row-input" value="0" placeholder="Free Pcs"></td>
                <td><input type="number" step="0.01" name="price[]" class="form-control price text-end row-input" value="0" ></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="item_disc_type[]" class="item-disc-type" value="amount">
                        <input type="number" step="0.01" name="item_disc[]" class="form-control item_disc text-end row-input" value="0">
                        <button class="btn btn-outline-secondary toggle-type" type="button" data-type="amount">Rs</button>
                    </div>
                </td>
                <td><input type="text" name="sub_total[]" class="form-control row-sub-total text-end" readonly></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" name="gst[]" class="form-control gst text-end row-input" value="0">
                        <span class="input-group-text p-1" style="font-size:0.7rem;">%</span>
                    </div>
                    <input type="hidden" name="gst_amount[]" class="gst-amount-row" value="0">
                </td>
                <td><input type="number" step="0.01" name="inc_tax[]" class="form-control inc-tax text-end row-input" value="0"></td>
                <td>
                    <div class="input-group input-group-sm" style="min-width: 90px;">
                        <input type="hidden" name="adv_tax_type[]" class="adv-tax-type" value="percent">
                        <input type="number" step="0.01" name="adv_tax[]" class="form-control adv-tax text-end row-input" value="0">
                        <button class="btn btn-outline-info toggle-adv-tax-type" type="button" data-type="percent" style="padding: 2px 5px; font-size: 0.72rem;">%</button>
                    </div>
                </td>
                <td><input type="text" name="total[]" class="form-control row-net-total input-highlight text-end" readonly></td>
                ${batchHtml}
                <td><input type="text" name="cost_per_pc[]" class="form-control row-cost-pc text-end" readonly></td>
                <td style="text-align:center;">
                    <button type="button" class="btn-erp-danger-ghost remove-row" title="Remove Item"><i class="bi bi-x-circle-fill"></i></button>
                </td>
              </tr>`;

            $('#saleItems').append(newRow);
            const $inserted = $('#saleItems tr:last');

            recalcRow($inserted);
            toggleGstFields();
            recalcSummary();
            checkEmptyState();
            return $inserted;
        }

        /* ── Quick HS Code Button Click ── */
        $(document).on('click', '.btn-quick-hs', function(e) {
            e.preventDefault();
            if (window.openQuickHsModalForBtn) {
                window.openQuickHsModalForBtn(this);
            }
        });

        /* ── Row Product Button → Modal (multi-select) ── */
        $(document).on('click', '.product-select-btn', function() {
            var $triggerBtn = $(this);
            var $triggerRow = $triggerBtn.closest('tr');
            var triggerRowEmpty = !$triggerRow.find('.item-id').val();
            
            ERPProductModal.open({
                priceField: 'sale',
                selectedIds: [],
                onSelect: function(products) {
                    products.forEach(function(p, idx) {
                        // First product replaces the triggering row, subsequent products get new rows
                        var $row = (idx === 0) ? $triggerRow : addBlankRow();
                        populateProductRow($row, p);
                    });
                    checkEmptyState();
                    setTimeout(function() { $('#saleItems tr:last').find('.quantity').focus().select(); }, 150);
                }
            });
        });

        function initSelect2($el) {  /* kept for legacy */
            $el.select2({
                placeholder: 'Search product...',
                allowClear: true,
                ajax: {
                    url: "{{ route('search-products') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        var items = Array.isArray(data) ? data : (data.results || []);
                        return {
                            results: items.map(function(item) {
                                return {
                                    id: item.id,
                                    text: (item.item_code ? item.item_code + ' - ' : '') + item.item_name,
                                    item_name: item.item_name,
                                    item_code: item.item_code,
                                    hs_code: item.hs_code || '',
                                    uom: item.pieces_per_box || 1,
                                    size_mode: item.size_mode || 'by_cartons',
                                    packings: item.packings || [],
                                    price: item.sale_price_per_piece || 0,
                                    uom_name: item.uom_name || ''
                                };
                            }),
                            pagination: data.pagination || { more: false }
                        };
                    },
                    cache: true
                }
            });

            $el.on('select2:select', function(e) {
                var data = e.params.data;
                var $row = $(this).closest('tr');
                $row.find('.item-code').val(data.item_code);
                $row.find('.item-id').val(data.id);
                $row.find('.item-name').val(data.item_name);
                $row.find('.hs-code').val(data.hs_code || '');
                $row.find('.size-mode').val(data.size_mode || 'by_cartons');
                console.log(data);
                
                // Initialize Packing Select — DB packings first, base only if not covered
                var $pSelect = $row.find('.packing-select');
                $pSelect.empty();
                
                if ($pSelect.length) {
                    let optionsHtml = '';
                    let seenFactors = {};

                    // 1. DB packings first (use their exact names from product_uoms)
                    if (data.packings && data.packings.length > 0) {
                        data.packings.forEach(function(pkg) {
                            let f = parseInt(pkg.pieces_per_box) || 1;
                            if (f === 1) f = parseInt(data.uom || data.pieces_per_box) || 1;
                            if (seenFactors[f]) return; // deduplicate by factor
                            seenFactors[f] = true;
                            optionsHtml += `<option value="${pkg.id}" data-ppb="${f}" data-price="${pkg.sale_price || 0}">${pkg.name}</option>`;
                        });
                    } else {
                        // 2. Base unit — only add if NO DB packings exist
                        let bPpb = parseInt(data.uom) || 1;
                        let bName = data.uom_name;
                        if (!bName || bName.toLowerCase() === 'piece' || bName.toLowerCase() === 'pcs') {
                            bName = '1x' + bPpb;
                        }
                        optionsHtml = `<option value="" data-ppb="${bPpb}" data-price="${data.price || 0}">${bName}</option>`;
                    }

                    $pSelect.html(optionsHtml);
                    
                    // Auto-select first option (which is now the first DB packing if any)
                    $pSelect.prop('selectedIndex', 0);
                    
                    // Update Packet column and trigger change
                    const $selectedOpt = $pSelect.find('option:selected');
                    if ($selectedOpt.length) {
                        const initialFactor = parseInt($selectedOpt.attr('data-ppb') || $selectedOpt.data('ppb')) || 1;
                        const $uomInput = $row.find('.item-uom');
                        $uomInput.val(initialFactor);
                        if ($uomInput[0]) $uomInput[0].value = initialFactor;
                        $row.find('.item_uom_factor').val(initialFactor);
                    }

                    $pSelect.trigger('change');
                }

                if (!$pSelect.val()) {
                    $row.find('.price').val(data.price);
                }

                var ppb = parseInt(data.uom) || 1;
                $row.find('.quantity').attr('placeholder', 'Box').val('1');
                $row.find('.loose_pieces').attr('placeholder', 'Pcs').val('0');
                $row.find('.free_loose_pieces').attr('placeholder', 'Free Pcs').val('0');

                $row.data('ppb', data.uom || 1);
                $row.data('size_mode', data.size_mode || 'by_cartons');

                // Load warehouses for this product
                var $whSelect = $row.find('.item-warehouse');
                $whSelect.html('<option value="">-- Select --</option>');
                
                const isSoMode = "{{ request()->query('mode') }}" === "so";
                let whParams = {};
                if (isSoMode) whParams.include_empty = 1;

                $.getJSON('/product/' + data.id + '/warehouses', whParams, function(warehouses) {
                    $whSelect.html('<option value="">-- Select --</option>');
                    if (warehouses.length > 0) {
                        warehouses.forEach(function(pw) {
                            var stock = pw.total_pieces || 0;
                            var displayStock = pw.stock_display || stock;
                            
                            $whSelect.append('<option value="' + pw.id +
                                '" data-stock="' + stock + '" data-stock-display="' + displayStock + '" data-ppb="' + (pw.ppb || 1) + '" data-size-mode="' + (pw.size_mode || 'std') + '">' + pw
                                .name + ' (Stock: ' + displayStock + ')</option>'
                            );
                        });
                        if (warehouses.length > 0) {
                            var hWh = $('#warehouse_id_header').val() || '{{ $defaultWhId }}';
                            var defaultWh = warehouses.find(w => w.id == hWh) || warehouses[0];
                            $whSelect.val(defaultWh.id).trigger('change');
                        }
                    }
                });

                // FEFO: Load batches
                loadBatches($row, data.id);

                recalcRow($row);
                recalcSummary();
                // move focus to qty
                setTimeout(() => $row.find('.quantity').focus().select(), 100);
            });
            $el.on('select2:unselect', function(e) {
                var $row = $(this).closest('tr');
                $row.find('.item-code').val('');
                $row.find('.item-id').val('');
                $row.find('.item-name').val('');
                $row.find('.hs-code').val('');
                $row.find('.item-uom').val('');
                $row.find('.price').val(0);

                // Clear batches
                $row.find('.batch-select2').find('option:not(:first)').remove();
                $row.find('.lot-no-display').val('').prop('readonly', false);

                recalcRow($row);
                recalcSummary();
            });
        }

        // Listener for Batch Change
        $('#saleItems').on('change', '.batch-select2', function() {
            const $row = $(this).closest('tr');
            const $opt = $(this).find('option:selected');
            const batchId = $(this).val();

            if (batchId) {
                const lot = $opt.data('lot');
                $row.find('.lot-no-display').val(lot).prop('readonly', true);
            } else {
                // Auto FEFO
                $row.find('.lot-no-display').val('').prop('readonly', false).attr('placeholder',
                    'Auto');
            }
        });

        // Listener for Warehouse Change in row
        $('#saleItems').on('change', '.item-warehouse', function() {
            const whId = $(this).val();
            const $row = $(this).closest('tr');
            const productId = $row.find('.item-id').val();
            if (productId) {
                loadBatches($row, productId);
            }
        });

        // Generate Blank Row by default
        addBlankRow();

        // HS Code Toggle Logic
        $('#enable_hs_code').on('change', function() {
            if($(this).is(':checked')) {
                $('.hs-code-col').show();
            } else {
                $('.hs-code-col').hide();
            }
        }).trigger('change');

        // Add row via button
        $('#btnAddRow').click(function() {
            addBlankRow();
        });

        // Add row via Enter Key on any row-input
        $('#saleItems').on('keydown', '.row-input', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Check if this is the last row in the table
                const isLastRow = $(this).closest('tr').is(':last-child');
                if (isLastRow) {
                    addBlankRow();
                    setTimeout(function() {
                        $('#saleItems tr:last').find('.product-select-btn').focus();
                    }, 100);
                } else {
                    // Just move to the next row's quantity or select2
                    $(this).closest('tr').next().find('.quantity').focus().select();
                }
            }
        });

        // Calculations & Removals
        $('#saleItems').on('change', '.packing-select', function() {
            var $row = $(this).closest('tr');
            var $opt = $(this).find('option:selected');
            
            var factor = parseFloat($opt.attr('data-ppb') || $opt.data('ppb')) || 1;
            if (factor <= 1) {
                var optText = $opt.text();
                if (optText && optText.match(/1[xX](\d+)/)) {
                    factor = parseInt(optText.match(/1[xX](\d+)/)[1]);
                }
            }
            var price  = $opt.data('price');
            var pkgId  = $(this).val();

            $row.find('.item_uom_factor').val(factor);
            $row.find('.item-uom').val(factor);
            $row.find('.uom-id').val(pkgId);
            
            if (price && parseFloat(price) > 0) {
                $row.find('.price').val(parseFloat(price).toFixed(2));
            }
            
            recalcRow($row);
            recalcSummary();
        });

        $('#saleItems').on('input', '.quantity, .loose_pieces, .free_loose_pieces, .price, .item_disc, .gst, .inc-tax, .adv-tax', function() {
            recalcRow($(this).closest('tr'));
            recalcSummary();
        });

        $('.input_summary, #sum_freight, #sum_expense').on('input', recalcSummary);

        $('#saleItems').on('change', 'input[name="lot_no[]"]', function() {
            let $row = $(this).closest('tr');
            let pid = $row.find('.item-id').val();
            let lot = $(this).val();

            if (pid && lot && lot.trim() !== '') {
                $.get("{{ route('purchase.check_lot') }}", {
                    product_id: pid,
                    lot_no: lot
                }, function(res) {
                    if (res.exists) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Batch Number Already Exists',
                            text: `This batch/lot number (${lot}) was already purchased for this product in the past. Please verify if this is a repeat purchase or a typing error.`
                        });
                    }
                });
            }
        });

        $('#saleItems').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            recalcSummary();
            checkEmptyState();
        });


        // Init Vendor Search Select2
        $('#vendor_select').select2({
            placeholder: 'Select Vendor A/C...',
            allowClear: true
        });

        recalcSummary();
        checkEmptyState();

        // --- Booked Products Logic ---
        $('#searchBookedProducts').on('input', function() {
            let val = $(this).val().toLowerCase();
            $('.booked-item-row').each(function() {
                let searchable = $(this).attr('data-search') || '';
                $(this).toggle(searchable.includes(val));
            });
        });

        // Helper to reliably set date input values (supporting Native Inputs & Flatpickr instances)
        function setFormDate(selector, dateStr) {
            if (!dateStr) return;
            // Strict YYYY-MM-DD extraction
            if (typeof dateStr === 'string' && dateStr.length >= 10) {
                dateStr = dateStr.substring(0, 10);
            }
            var el = document.querySelector(selector);
            if (!el) return;

            // 1. Direct DOM & jQuery assignment
            el.value = dateStr;
            $(el).val(dateStr);

            // 2. Flatpickr Instance Update (Crucial: Flatpickr hides the original input and uses a cloned visible input)
            if (el._flatpickr) {
                el._flatpickr.setDate(dateStr, true, 'Y-m-d');
            }

            // 3. Dispatch change events
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Helper to populate imported items into the sale items table
        function populateImportedItems(items, defaultWhId) {
            $('#saleItems').empty();
            rowCount = 0;

            if (!items || !items.length) {
                addBlankRow();
                recalcSummary();
                checkEmptyState();
                return;
            }

            items.forEach(function(item) {
                addBlankRow();
                let $lastRow = $('#saleItems tr:last');
                let prodId = item.product_id;

                if (!prodId) return;

                $.getJSON("{{ route('product.details', ':id') }}".replace(':id', prodId), function(p) {
                    populateProductRow($lastRow, p);

                    let ppb = parseInt(p.pieces_per_box) || parseInt(item.ppb) || 1;
                    let totalPieces = parseInt(item.total_pieces) || 0;
                    if (totalPieces === 0 && item.qty) {
                        totalPieces = Math.round((parseFloat(item.qty) || 0) * ppb);
                    }
                    let boxes = Math.floor(totalPieces / ppb);
                    let pcs = totalPieces % ppb;

                    $lastRow.find('.quantity').val(boxes);
                    $lastRow.find('.loose_pieces').val(pcs);

                    if (item.price && parseFloat(item.price) > 0) {
                        $lastRow.find('.price').val(parseFloat(item.price).toFixed(2));
                    }

                    if (item.discount_amount !== undefined && parseFloat(item.discount_amount) > 0) {
                        $lastRow.find('.item_disc').val(parseFloat(item.discount_amount));
                    } else if (item.discount_percent !== undefined && parseFloat(item.discount_percent) > 0) {
                        $lastRow.find('.item_disc').val(parseFloat(item.discount_percent));
                    }

                    if (item.lot_number || item.lot_no || item.batch_no) {
                        $lastRow.find('.lot-no-display').val(item.lot_number || item.lot_no || item.batch_no);
                    }

                    if (item.uom_name) {
                        $lastRow.find('.uom-input').val(item.uom_name);
                    }

                    let wh_id = item.warehouse_id || defaultWhId;
                    if (wh_id) {
                        let $whSelect = $lastRow.find('.item-warehouse');
                        let checkInterval = setInterval(function() {
                            if ($whSelect.find('option[value="' + wh_id + '"]').length > 0) {
                                $whSelect.val(wh_id).trigger('change');
                                clearInterval(checkInterval);
                            }
                        }, 100);
                        setTimeout(() => clearInterval(checkInterval), 3000);
                    }

                    if (item.batch_id) {
                        loadBatches($lastRow, prodId, item.batch_id);
                    }

                    recalcRow($lastRow);
                    recalcSummary();
                    checkEmptyState();
                });
            });
        }

        // Consolidated AJAX-driven Import Handler
        function importBookedItem(btn) {
            let $btn = $(btn);
            let importType = $btn.data('type') || 'dc';
            let id = $btn.data('id');
            let isAdvance = $btn.data('is-advance') == '1';
            let currentMode = $('input[name="mode"]').val();

            if (!id) return;

            if (isAdvance && currentMode === 'so') {
                $('input[name="mode"]').val('sin');
                $('h1.page-title').html('<i class="bi bi-box-seam me-2 text-primary"></i> Sale Invoice Note');
                $('p.page-subtitle').text('Record outward stock to customers efficiently.');
                $('#btnSaveOnly').hide();
                $('#btnConfirm').show();
            }

            if (window.ERPImportLoader) window.ERPImportLoader.start();

            let endpoint = (importType === 'draft') 
                ? '{{ url("sales/draft-details") }}/' + id 
                : '{{ url("sales/dc-details") }}/' + id;

            $.getJSON(endpoint, function(data) {
                if (importType === 'dc') {
                    // Delivery Note (DC) Import
                    if (data.sale_id) $('#draft_id_input').val(data.sale_id);
                    $('#dc_id_input').val(data.id);

                    if (data.customer_id) {
                        $('#customer_id').val(data.customer_id);
                        $('#customer_name_display').val(data.customer_name || (data.customer ? data.customer.customer_name : ''));
                    }

                    if (data.employee_id) {
                        $('#sales_officer_id').val(data.employee_id);
                        $('#sales_officer_display').val(data.employee_name || '');
                    }

                    if (data.commission_percentage !== undefined && data.commission_percentage !== null) {
                        $('#commission_percentage').val(data.commission_percentage);
                    }

                    let headerWh = data.warehouse_id || (data.items && data.items.length ? data.items[0].warehouse_id : '');
                    if (headerWh) {
                        $('#warehouse_id_header').val(headerWh).trigger('change');
                    }

                    $('input[name="vendor_bill_no"]').val(data.grouped_dc_no || data.dc_no || '');

                    if (data.sale) {
                        $('input[name="sale_order_no"]').val(data.sale.invoice_no || '');
                        $('input[name="order_no"]').val(data.sale.order_no || '');
                        $('input[name="note"]').val(data.sale.note || '');
                        $('input[name="reference"]').val(data.sale.reference || '');
                        $('input[name="transport_name"]').val(data.sale.transport_name || '');
                        if (data.sale.discount !== undefined) $('#sum_discount').val(data.sale.discount);
                        if (data.sale.freight_charges !== undefined) $('#sum_freight').val(data.sale.freight_charges);
                        if (data.sale.extra_cost !== undefined) $('#sum_expense').val(data.sale.extra_cost);
                        if (data.sale.is_gst_invoice !== undefined) {
                            $('#gst_invoice').prop('checked', data.sale.is_gst_invoice == 1);
                        }
                    }

                    // Strict Date Population (Flatpickr aware)
                    if (data.delivery_date_formatted) {
                        setFormDate('#dc_date_input', data.delivery_date_formatted);
                        setFormDate('input[name="dc_date"]', data.delivery_date_formatted);
                    }
                    if (data.so_date_formatted) {
                        setFormDate('#so_date_input', data.so_date_formatted);
                        setFormDate('input[name="so_date"]', data.so_date_formatted);
                    }
                    if (data.sale_date_formatted) {
                        setFormDate('#purchase_date_input', data.sale_date_formatted);
                        setFormDate('input[name="purchase_date"]', data.sale_date_formatted);
                    }

                    // Populate Table Rows
                    populateImportedItems(data.items || [], headerWh);

                } else {
                    // Draft Sale Import
                    $('#draft_id_input').val(data.id);

                    if (data.customer_id) {
                        $('#customer_id').val(data.customer_id);
                        $('#customer_name_display').val(data.customer_name || (data.customer_relation ? data.customer_relation.customer_name : ''));
                    }

                    if (data.warehouse_id) {
                        $('#warehouse_id_header').val(data.warehouse_id).trigger('change');
                    }

                    if (data.employee_id) {
                        $('#sales_officer_id').val(data.employee_id);
                        $('#sales_officer_display').val(data.employee_name || '');
                    }

                    if (data.commission_percentage !== undefined && data.commission_percentage !== null) {
                        $('#commission_percentage').val(data.commission_percentage);
                    }

                    $('input[name="vendor_bill_no"]').val(data.vendor_bill_no || '');
                    $('input[name="order_no"]').val(data.order_no || '');
                    $('input[name="sale_order_no"]').val(data.invoice_no || '');
                    $('input[name="note"]').val(data.note || '');
                    $('input[name="reference"]').val(data.reference || '');
                    $('input[name="transport_name"]').val(data.transport_name || '');

                    if (data.discount !== undefined) $('#sum_discount').val(data.discount);
                    if (data.freight_charges !== undefined) $('#sum_freight').val(data.freight_charges);
                    if (data.extra_cost !== undefined) $('#sum_expense').val(data.extra_cost);
                    if (data.is_gst_invoice !== undefined) {
                        $('#gst_invoice').prop('checked', data.is_gst_invoice == 1);
                    }

                    // Strict Date Population (Flatpickr aware)
                    if (data.so_date_formatted) {
                        setFormDate('#so_date_input', data.so_date_formatted);
                        setFormDate('input[name="so_date"]', data.so_date_formatted);
                    }
                    if (data.sale_date_formatted) {
                        setFormDate('#purchase_date_input', data.sale_date_formatted);
                        setFormDate('input[name="purchase_date"]', data.sale_date_formatted);
                    }

                    // Populate Table Rows
                    populateImportedItems(data.items || [], data.warehouse_id);
                }

                $('#bookedProductsModal').modal('hide');
                if (window.ERPImportLoader) window.ERPImportLoader.success();
            }).fail(function(err) {
                console.error("Import Error:", err);
                if (window.ERPImportLoader) window.ERPImportLoader.stop();
                Swal.fire({
                    icon: 'error',
                    title: 'Import Failed',
                    text: 'Unable to load invoice details from server. Please try again.'
                });
            });
        }

        // Import Button Click Handler
        $('.btn-import-single').on('click', function() {
            importBookedItem(this);
        });
    });
</script>
