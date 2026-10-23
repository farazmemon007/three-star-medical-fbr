@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #059669;
            /* Emerald 600 */
            --primary-dark: #047857;
            --primary-light: #ecfdf5;
            --secondary: #64748b;
            --accent: #10b981;
            --white: #ffffff;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }

        body {
            background-color: #f4f7fa;
            font-family: 'Outfit', sans-serif;
            color: var(--slate-800);
        }

        .main-content {
            padding: 1.5rem;
        }

        /* Hero Header Section */
        .hero-header {
            background: linear-gradient(135deg, #065f46 0%, #064e3b 100%);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-text h4 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
            color: white;
        }

        .hero-text p {
            font-size: 1rem;
            opacity: 0.8;
            margin: 0;
            font-weight: 400;
        }

        /* KPI Dashboard */
        .stat-card {
            background: var(--white);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--slate-200);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.emerald {
            background: #d1fae5;
            color: #059669;
        }

        .stat-icon.amber {
            background: #fef3c7;
            color: #d97706;
        }

        .stat-icon.rose {
            background: #ffe4e6;
            color: #e11d48;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--slate-900);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--secondary);
            font-weight: 600;
            margin-top: 0.25rem;
        }

        /* Verified Table Styling */
        .premium-table-container {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid var(--slate-200);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        #grn-table {
            width: 100% !important;
        }

        #grn-table thead th {
            padding: 1.25rem 1rem;
            font-weight: 700;
            color: var(--slate-800);
            background: var(--slate-50);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--slate-200);
        }

        #grn-table tbody tr {
            border-bottom: 1px solid var(--slate-100);
            transition: background 0.2s;
        }

        #grn-table tbody tr:hover {
            background: var(--slate-50);
        }

        #grn-table td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Branding Elements */
        .grn-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.8rem;
            background: var(--primary-light);
            color: var(--primary-dark);
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.75rem;
            border: 1px solid #a7f3d0;
        }

        .vendor-box {
            display: flex;
            flex-direction: column;
        }

        .vendor-name {
            font-weight: 700;
            color: var(--slate-900);
        }

        .business-tag {
            font-size: 0.75rem;
            color: var(--secondary);
            font-weight: 500;
            margin-top: 2px;
        }

        /* Batch Information Grid */
        .batch-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
            max-width: 200px;
        }

        .batch-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .lot-badge {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .mfg-badge {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #d1fae5;
        }

        .exp-badge {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        /* Action Menus */
        .btn-dropdown {
            background: var(--slate-100);
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-dropdown:hover {
            background: var(--slate-200);
        }

        .dropdown-menu {
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--slate-200);
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-item:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Financials Column */
        .amt-verified {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            color: var(--primary-dark);
            text-align: right;
        }

        .amt-status {
            font-size: 0.7rem;
            text-align: right;
            padding-top: 2px;
        }

        /* DataTables Premium Look */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 14px;
            padding: 0.75rem 1.25rem;
            background-color: var(--slate-50);
            border: 1px solid var(--slate-200);
            width: 300px;
            transition: all 0.2s;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            background-color: white;
        }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            <!-- Hero Header -->
            <div class="hero-header">
                <div class="hero-text">
                    <h4>Sale Invoice Notes</h4>
                    <p>Verified sales and finalized outward inventory entries for Three Star Medical.</p>
                </div>
                <div>
                    @can('sales.create')
                        <a class="btn btn-light btn-lg px-5 shadow-lg fw-800 rounded-pill text-emerald" style="color: #065f46"
                            href="{{ route('sale.add', ['mode' => 'sin']) }}">
                            <i class="fas fa-file-signature me-2"></i> CREATE NEW INVOICE NOTE
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="row mb-5">
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon emerald"><i class="fas fa-check-double"></i></div>
                        <div class="stat-value">{{ number_format($sales->count()) }}</div>
                        <div class="stat-label">Verified Sales</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon amber"><i class="fas fa-money-check-alt"></i></div>
                        <div class="stat-value">Rs. {{ number_format($sales->sum('total_net'), 0) }}</div>
                        <div class="stat-label">Total Sale Value</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon rose"><i class="fas fa-hand-holding-usd"></i></div>
                        <div class="stat-value">Rs. {{ number_format($sales->sum('total_net'), 0) }}</div>
                        <div class="stat-label">Total Dispatched Value</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon emerald"><i class="fas fa-box-open"></i></div>
                        <div class="stat-value">Stocked</div>
                        <div class="stat-label">System Updated</div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="premium-table-container">
                <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                    <h5 class="fw-800 m-0 text-slate-800"><i class="fas fa-stream me-2 text-primary"></i>Verified Registry
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i
                                class="fas fa-download me-2"></i>Excel</button>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i
                                class="fas fa-print me-2"></i>Print Registry</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="grn-table" class="table align-middle datanew">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Verification Date</th>
                                <th>Sale Ref #</th>
                                <th>Customer / Institution</th>
                                <th>Product Details</th>
                                <th class="text-end">Billing Detail</th>
                                <th class="text-center">FBR Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $sale)
                                <tr>
                                    <td class="fw-bold text-slate-400">#{{ $sale->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="text-emerald" style="font-size: 1.1rem;"><i
                                                    class="fas fa-calendar-check"></i></div>
                                            <span
                                                class="fw-bold">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="grn-pill text-nowrap">{{ $sale->invoice_no }}</span>
                                            @if($sale->sale_status === 'un-post')
                                                <span class="badge badge-warning text-dark uppercase fw-800 animate__animated animate__pulse animate__infinite" style="font-size: 0.65rem; border: 1px solid #d97706; padding: 4px 8px; border-radius: 6px;">UN-POSTED</span>
                                            @endif
                                        </div>
                                        @if($sale->createdBy)
                                            <div class="mt-1">
                                                <span class="badge badge-light text-muted fw-bold" style="font-size: 0.65rem; border: 1px dashed var(--slate-200); padding: 2px 6px; border-radius: 4px;">
                                                    <i class="fas fa-user me-1"></i>{{ $sale->createdBy->name }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="vendor-box">
                                            <span
                                                class="vendor-name">{{ $sale->customer_relation->customer_name ?? 'System Customer' }}</span>
                                            @if($sale->sale_status === 'un-post')
                                                <span class="text-warning small fw-bold d-block mb-1" style="font-size: 0.65rem;"><i class="fas fa-clock"></i> PENDING VERIFICATION</span>
                                            @endif
                                            <span class="business-tag"><i
                                                    class="fas fa-user me-1"></i>{{ $sale->customer_relation->business_name ?? 'Individual' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="batch-grid">
                                            @foreach ($sale->items as $item)
                                                @php
                                                    $prod = $item->product;
                                                    $qtyString = $item->total_pieces . " Pcs";
                                                @endphp
                                                <span class="detail-tag">{{ ($prod->brand->name ?? '') . ' ' . ($prod->item_name ?? 'Item') }}
                                                    ({{ $qtyString }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $hasItems = $sale->items && $sale->items->isNotEmpty();
                                            if ($hasItems) {
                                                $calcGross = 0;
                                                $calcGst = 0;
                                                $calcNet = 0;
                                                foreach ($sale->items as $it) {
                                                    $iqty = (float)(!empty($it->qty) && $it->qty > 0 ? $it->qty : ($it->total_pieces ?? 1));
                                                    $iprice = (float)($it->price ?? 0);
                                                    $ilineSub = round($iqty * $iprice, 2);
                                                    $idisc = (float)($it->discount_amount ?? 0);
                                                    $ivalExcl = max(0, round($ilineSub - $idisc, 2));
                                                    $igstPct = (float)($it->gst_percent ?? $it->gst ?? 0);
                                                    $igstAmt = (float)($it->gst_amount ?? 0);
                                                    if ($igstAmt == 0 && $igstPct > 0) {
                                                        $igstAmt = round($ivalExcl * ($igstPct / 100), 2);
                                                    }
                                                    $ifTaxAmt = (float)($it->further_tax_amount ?? 0);
                                                    $ivalIncl = round($ivalExcl + $igstAmt + $ifTaxAmt, 2);

                                                    $calcGross += $ivalExcl;
                                                    $calcGst += $igstAmt;
                                                    $calcNet += $ivalIncl;
                                                }
                                                $dispGross = $calcGross;
                                                $dispGst = $calcGst;
                                                $dispNet = round($calcNet - (float)($sale->total_extradiscount ?? 0) + (float)($sale->total_freight ?? 0) + (float)($sale->total_expense ?? 0), 2);
                                            } else {
                                                $dispGross = (float)$sale->total_bill_amount;
                                                $dispGst = (float)$sale->total_gst;
                                                $dispNet = (float)$sale->total_net;
                                            }
                                            $dispUpdatedNet = max(0, round($dispNet - (float)($sale->total_returned ?? 0), 2));
                                        @endphp
                                        <div class="text-muted mb-1 font-monospace" style="font-size: 0.65rem; text-transform: uppercase; line-height: 1.2;">
                                            Gross: <span class="fw-bold text-dark">{{ number_format($dispGross, 2) }}</span><br>
                                            GST: <span class="fw-bold text-dark">{{ number_format($dispGst, 2) }}</span><br>
                                            @if(($sale->total_inc_tax ?? 0) > 0) Inc. Tax: <span class="fw-bold text-dark">{{ number_format($sale->total_inc_tax, 2) }}</span><br> @endif
                                            @if(($sale->total_adv_tax ?? 0) > 0) Adv. Tax: <span class="fw-bold text-dark">{{ number_format($sale->total_adv_tax, 2) }}</span><br> @endif
                                            Disc: <span class="fw-bold text-danger">{{ number_format($sale->total_extradiscount, 2) }}</span>
                                        </div>
                                        <div class="amt-verified">
                                            @if (($sale->total_returned ?? 0) > 0)
                                                <div class="text-decoration-line-through text-muted small"
                                                    style="font-size: 0.7rem;">
                                                    {{ number_format($dispNet, 2) }}</div>
                                                <span>{{ number_format($dispUpdatedNet, 2) }}</span>
                                            @else
                                                {{ number_format($dispNet, 2) }}
                                            @endif
                                        </div>
                                        <div class="amt-status">
                                            @if($sale->sale_status === 'post')
                                                <span class="text-success fw-800"><i
                                                        class="fas fa-check-circle me-1"></i>Posted</span>
                                            @else
                                                <span class="text-warning fw-800"><i
                                                        class="fas fa-file-signature me-1"></i>Un-posted</span>
                                             @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($sale->fbr_status === 'posted')
                                            <span class="badge badge-success px-2 py-1 text-wrap d-inline-block" style="font-size: 0.72rem; line-height: 1.3;" title="FBR Invoice: {{ $sale->fbr_invoice_no }}">
                                                <i class="fas fa-check-circle me-1"></i> FBR POSTED<br>
                                                <small class="font-monospace text-light" style="font-size: 0.65rem;">{{ Str::limit($sale->fbr_invoice_no, 15) }}</small>
                                            </span>
                                        @elseif($sale->sale_status === 'post')
                                            <button type="button" class="btn btn-sm btn-outline-warning fbr-post-btn text-nowrap py-1 px-2" data-id="{{ $sale->id }}" title="Post this invoice to FBR">
                                                <i class="fas fa-cloud-upload-alt me-1"></i> Post to FBR
                                            </button>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1" style="font-size: 0.72rem;">
                                                <i class="fas fa-minus me-1"></i> Draft
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-dropdown" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="fas fa-ellipsis-h me-1"></i> Manage
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right shadow-lg">
                                                @can('sales.view')
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('sales.invoice', $sale->id) }}">
                                                            <i class="fas fa-file-invoice text-emerald"></i> View Invoice</a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="{{ route('sales.dc', $sale->id) }}">
                                                            <i class="fas fa-shipping-fast text-amber"></i> Dispatch Note</a>
                                                    </li>
                                                @endcan
                                                @if($sale->sale_status === 'post')
                                                    <div class="dropdown-divider"></div>
                                                    @if($sale->fbr_status === 'posted')
                                                        <li><a class="dropdown-item text-success fbr-post-btn" href="javascript:void(0);" data-id="{{ $sale->id }}">
                                                            <i class="fas fa-shield-alt text-success me-1"></i> FBR Info (Posted)</a></li>
                                                    @else
                                                        <li><a class="dropdown-item text-warning fbr-post-btn fw-bold" href="javascript:void(0);" data-id="{{ $sale->id }}">
                                                            <i class="fas fa-cloud-upload-alt text-warning me-1"></i> Post to FBR</a></li>
                                                    @endif
                                                @endif
                                                @if (!$sale->is_fully_returned)
                                                    @if($sale->sale_status === 'post')
                                                        @can('sales.create')
                                                            <div class="dropdown-divider"></div>
                                                            <li><a class="dropdown-item text-danger"
                                                                    href="{{ route('sale.return.show', $sale->id) }}">
                                                                    <i class="fas fa-undo-alt"></i> Process Return</a></li>
                                                        @endcan
                                                        @can('sales.unpost')
                                                            <li><a class="dropdown-item text-warning btn-unpost-srn"
                                                                   href="javascript:void(0);" 
                                                                   data-id="{{ $sale->id }}" 
                                                                   data-invoice="{{ $sale->invoice_no }}">
                                                                   <i class="fas fa-history"></i> Un-post SIN</a></li>
                                                        @endcan
                                                    @elseif($sale->sale_status === 'un-post')
                                                        @can('sales.edit')
                                                            <div class="dropdown-divider"></div>
                                                            <li><a class="dropdown-item text-primary"
                                                                   href="{{ route('sales.edit', $sale->id) }}">
                                                                   <i class="fas fa-edit"></i> Edit Draft SIN</a></li>
                                                        @endcan
                                                    @endif
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('.datanew').DataTable({
                "pageLength": 10,
                "aaSorting": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search and filter records..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });

            $(document).on('click', '.btn-unpost-srn', function() {
                let id = $(this).data('id');
                let invoice = $(this).data('invoice');
                
                Swal.fire({
                    title: 'Un-post SIN?',
                    text: "Reverting SIN " + invoice + " to DRAFT will PERMANENTLY DELETE accounting/payment records. Continue?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Un-post Now',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ url('sales') }}/" + id + "/unpost";
                    }
                });
            });

            // Reload page on successful FBR post to reflect badge & invoice number
            $(document).on('fbr:posted', function(e, data) {
                setTimeout(function() {
                    window.location.reload();
                }, 1200);
            });
        });
    </script>
    @include('admin_panel.sale.components.fbr_post_modal')
@endsection
