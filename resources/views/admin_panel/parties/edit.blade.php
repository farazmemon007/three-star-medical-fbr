@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --glass: rgba(255, 255, 255, 0.85);
            --radius-xl: 16px;
            --radius-lg: 12px;
            --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-bold: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body {
            background-color: #f1f5f9;
            color: #334155;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }

        .premium-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 120px;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modern Header */
        .premium-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 25px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-bold);
            color: white;
        }

        .title-group h1 {
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.025em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .title-group p {
            font-size: 0.9rem;
            color: #94a3b8;
            margin: 0;
            margin-top: 6px;
        }

        /* Modern Card/Box */
        .premium-card {
            background: #ffffff;
            border-radius: var(--radius-xl);
            border: 1px solid #e2e8f0;
            padding: 28px;
            height: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-subtle);
        }

        .card-header-modern {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            border-bottom: 2px solid #f8fafc;
            padding-bottom: 15px;
        }

        .icon-box {
            width: 44px;
            height: 44px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.2rem;
        }

        .card-header-modern h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Tab System Modern */
        .tab-nav {
            display: flex;
            background: #f8fafc;
            padding: 5px;
            border-radius: 12px;
            margin-bottom: 22px;
            border: 1px solid #e2e8f0;
        }

        .tab-btn {
            flex: 1;
            padding: 10px 15px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            background: transparent;
            color: var(--secondary);
            border-radius: 10px;
            cursor: pointer;
            transition: 0.25s;
        }

        .tab-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: var(--shadow-subtle);
        }

        /* Form Controls Modern */
        .form-group-modern {
            margin-bottom: 20px;
        }

        .label-modern {
            display: block;
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .input-modern {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            font-size: 0.95rem;
            color: #1e293b;
            transition: all 0.2s;
        }

        .input-modern:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
            background: #ffffff;
        }

        .select-modern {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.85rem center;
            background-repeat: no-repeat;
            background-size: 1.25em;
            padding-right: 2.5rem;
        }

        /* Floating Pill UI Component */
        .floating-action-pill {
            position: fixed;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            padding: 15px 30px;
            border-radius: 100px;
            box-shadow: var(--shadow-bold);
            display: flex;
            gap: 20px;
            z-index: 1000;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from {
                transform: translate(-50%, 150px);
                opacity: 0;
            }

            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        .pill-btn {
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.3s;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .pill-btn-save {
            background: var(--primary);
            color: white;
        }

        .pill-btn-save:hover {
            background: #4338ca;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 15px rgba(79, 70, 229, 0.4);
        }

        .pill-btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
        }

        .pill-btn-cancel:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fecaca;
        }

        /* Profile Upload Area */
        .profile-drop-area {
            width: 100%;
            aspect-ratio: 1/1;
            border: 2px dashed #e2e8f0;
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #f8fafc;
            overflow: hidden;
            position: relative;
            transition: all 0.3s;
        }

        .profile-drop-area:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }

        #previewImage {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        /* Sidebar Audit */
        .audit-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .audit-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
            border-left: 3px solid #e2e8f0;
            padding-left: 15px;
        }

        .audit-label {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
        }

        .audit-value {
            font-size: 0.9rem;
            color: #1e293b;
            font-weight: 600;
        }

        /* Helpers */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .hidden {
            display: none !important;
        }
    </style>

    <div class="premium-container">
        <form action="{{ route('parties.update', $party->id) }}?type={{ $type }}" method="POST"
            enctype="multipart/form-data" id="partyForm">
            @csrf

            <div class="premium-header">
                <div class="title-group">
                    <h1><i class="fa fa-user-edit"></i> Edit Profile: {{ $party->title }}</h1>
                    <p>Modify and synchronize account records across the system</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span
                        style="background: rgba(255,255,255,0.1); padding: 8px 16px; border-radius: 8px; font-weight: 800; border: 1px solid rgba(255,255,255,0.2);">
                        CODE: {{ $party->code }}
                    </span>
                </div>
            </div>

            <div class="row g-4">
                <!-- Column 1: Core Content -->
                <div class="col-lg-8">
                    <div class="row g-4">
                        <!-- Box 1: Core Info -->
                        <div class="col-md-6">
                            <div class="premium-card">
                                <div class="card-header-modern">
                                    <div class="icon-box"><i class="fa fa-info-circle"></i></div>
                                    <h3>Identification</h3>
                                </div>

                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Party Type <span class="text-danger">*</span></label>
                                        <select class="input-modern select-modern" name="party_type" id="partyType">
                                            <option value="Customer"
                                                {{ $party->party_type == 'Customer' ? 'selected' : '' }}>CUSTOMER</option>
                                            <option value="Vendor" {{ $party->party_type == 'Vendor' ? 'selected' : '' }}>
                                                VENDOR</option>
                                            <option value="Vendor/Customer"
                                                {{ $party->party_type == 'Vendor/Customer' ? 'selected' : '' }}>VENDOR /
                                                CUSTOMER</option>
                                        </select>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Abbreviation / Abr</label>
                                        <input type="text" class="input-modern" name="abr"
                                            value="{{ $party->abr ?? '' }}" placeholder="Br. Code">
                                    </div>
                                </div>

                                <div class="form-group-modern">
                                    <label class="d-flex align-items-center gap-3 cursor-pointer">
                                        <input type="checkbox" name="is_active" value="1"
                                            {{ $party->is_active ? 'checked' : '' }} style="width:22px; height:22px;">
                                        <span style="font-size:0.85rem; font-weight:700;">Account Active</span>
                                    </label>
                                </div>

                                {{-- Row: Title (full width) --}}
                                <div class="form-group-modern">
                                    <label class="label-modern">Full Title / Name <span class="text-danger">*</span></label>
                                    <input type="text" class="input-modern" name="title" value="{{ $party->title }}"
                                        placeholder="Client Title" required>
                                </div>

                                {{-- Row: Business Name (vendor) --}}
                                <div id="vendorBusinessName"
                                    class="form-group-modern {{ $party->party_type == 'Customer' ? 'hidden' : '' }}">
                                    <label class="label-modern">Business Name</label>
                                    <input type="text" class="input-modern" name="business_name"
                                        value="{{ $party->business_name ?? '' }}" placeholder="Business / Trade Name">
                                </div>

                                {{-- Row: CNIC + NTN --}}
                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">CNIC # (Optional)</label>
                                        <input type="text" class="input-modern" name="cnic"
                                            value="{{ $party->cnic ?? '' }}" placeholder="XXXXX-XXXXXXX-X">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">NTN # (Optional)</label>
                                        <input type="text" class="input-modern" name="ntn_no"
                                            value="{{ $party->ntn_no ?? '' }}" placeholder="NTN #">
                                    </div>
                                </div>

                                {{-- Row: Filer Type + GST --}}
                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Filer Type (FBR Status)</label>
                                        <select class="input-modern select-modern" name="filer_type" id="filerType">
                                            <option value="Non-Filer" {{ ($party->filer_type ?? '') == 'Non-Filer' || empty($party->filer_type) ? 'selected' : '' }}>Non-Filer</option>
                                            <option value="Filer" {{ ($party->filer_type ?? '') == 'Filer' ? 'selected' : '' }}>Filer</option>
                                        </select>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">GST # (Optional)</label>
                                        <input type="text" class="input-modern" name="gst_no"
                                            value="{{ $party->gst_no ?? '' }}" placeholder="Sales Tax ID">
                                    </div>
                                </div>

                                {{-- Row: FTN + DSL --}}
                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">FTN # (Optional)</label>
                                        <input type="text" class="input-modern" name="ftn_no"
                                            value="{{ $party->ftn_no ?? '' }}" placeholder="e.g. 1347561-4">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">DSL # (Optional)</label>
                                        <input type="text" class="input-modern" name="dsl_no"
                                            value="{{ $party->dsl_no ?? '' }}" placeholder="Drug Sale Lic.">
                                    </div>
                                </div>

                                {{-- Row: DRAP --}}
                                <div class="form-group-modern">
                                    <label class="label-modern">DRAP # (Optional)</label>
                                    <input type="text" class="input-modern" name="drap_no"
                                        value="{{ $party->drap_no ?? '' }}" placeholder="Medical Reg.">
                                </div>

                                {{-- Branch Selection (Super Admin Only) --}}
                                @if (auth()->user()->isSuperAdmin())
                                    <div class="form-group-modern mt-3">
                                        <label class="label-modern" style="color:var(--primary);">Target Branch <span
                                                class="text-danger">*</span></label>
                                        <select class="input-modern select-modern" name="branch_id" required>
                                            <option value="">Select Branch</option>
                                            @foreach ($branches as $b)
                                                <option value="{{ $b->id }}"
                                                    {{ ($party->branch_id ?? '') == $b->id ? 'selected' : '' }}>
                                                    {{ $b->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted" style="font-size:0.65rem;">Super Admin: Assign this
                                            party to a specific branch.</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Box 2: Communication Nodes -->
                        <div class="col-md-6">
                            <div class="premium-card">
                                <div class="card-header-modern">
                                    <div class="icon-box"><i class="fa fa-satellite-dish"></i></div>
                                    <h3>Communication Nodes</h3>
                                </div>

                                <div class="tab-nav">
                                    <button type="button" class="tab-btn active" data-tab="mailing">PRIMARY
                                        ADDR</button>
                                    <button type="button" class="tab-btn" data-tab="shipping">SHIPPING ADDR</button>
                                </div>

                                <div id="tab-mailing">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Mailing Address</label>
                                        <input type="text" class="input-modern" name="address"
                                            value="{{ $party->address ?? '' }}">
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">City</label>
                                            <input type="text" class="input-modern" name="city"
                                                value="{{ $party->city ?? '' }}">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Country</label>
                                            <input type="text" class="input-modern" name="country"
                                                value="{{ $party->country ?? 'Pakistan' }}">
                                        </div>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Email Address (Optional)</label>
                                        <input type="email" class="input-modern" name="email"
                                            value="{{ $party->email ?? '' }}">
                                    </div>
                                </div>

                                <div id="tab-shipping" class="hidden">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Shipping Address</label>
                                        <input type="text" class="input-modern" name="shipping_address"
                                            value="{{ $party->shipping_address ?? '' }}">
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Phone at Point</label>
                                            <input type="text" class="input-modern" name="shipping_phone"
                                                value="{{ $party->shipping_phone ?? '' }}">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Fax / Alternate</label>
                                            <input type="text" class="input-modern" name="shipping_fax"
                                                value="{{ $party->shipping_fax ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Shipping Email (Optional)</label>
                                        <input type="text" class="input-modern" name="shipping_email"
                                            value="{{ $party->shipping_email ?? '' }}"
                                            placeholder="Notes for delivery team">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Box 3: Logistics & Credits -->
                        <div class="col-md-6">
                            <div class="premium-card">
                                <div class="card-header-modern">
                                    <div class="icon-box" style="background:#fefce8; color:#a16207;"><i
                                            class="fa fa-wallet"></i></div>
                                    <h3 id="settingsTitle">
                                        {{ $party->party_type == 'Vendor' ? 'Vendor Settings' : 'Customer Settings' }}</h3>
                                </div>

                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Payment Terms</label>
                                        @php
                                            $cTerms = $party->credit_terms;
                                            $isCustom = !in_array($cTerms, [0, 7, 15, 30]) && !is_null($cTerms);
                                        @endphp
                                        <select class="input-modern select-modern" name="credit_terms" id="creditTermsSelect" onchange="toggleCustomCreditTerms()">
                                            <option value="0" {{ $cTerms == 0 ? 'selected' : '' }}>Cash / Immediate</option>
                                            <option value="7" {{ $cTerms == 7 ? 'selected' : '' }}>7 Days</option>
                                            <option value="15" {{ $cTerms == 15 ? 'selected' : '' }}>15 Days</option>
                                            <option value="30" {{ $cTerms == 30 ? 'selected' : '' }}>30 Days</option>
                                            <option value="custom" {{ $isCustom ? 'selected' : '' }}>Custom Days</option>
                                        </select>
                                        <input type="number" class="input-modern mt-2" name="custom_credit_terms" id="customCreditTermsInput" 
                                            placeholder="Enter days" style="display: {{ $isCustom ? 'block' : 'none' }};" min="1" value="{{ $isCustom ? $cTerms : '' }}">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Operation Mode</label>
                                        <select class="input-modern select-modern" name="payment_mode" id="paymentMode">
                                            <option value="Cash" {{ ($party->payment_mode ?? '') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="Bank" {{ ($party->payment_mode ?? '') == 'Bank' ? 'selected' : '' }}>Bank</option>
                                            <option value="Cheque" {{ ($party->payment_mode ?? '') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Cheque Details (Dynamic) -->
                                <div id="chequeDetails" class="{{ ($party->payment_mode ?? '') == 'Cheque' ? '' : 'hidden' }}">
                                    <div style="background:#f0f9ff; padding:15px; border-radius:12px; border:1px solid #bae6fd; margin-bottom:15px;">
                                        <div class="label-modern" style="color:#0369a1; margin-bottom:12px;"><i class="fa fa-university me-2"></i>Cheque / Bank Details</div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Bank Name</label>
                                            <input type="text" class="input-modern" name="bank_name" value="{{ $party->bank_name ?? '' }}" placeholder="Bank Name">
                                        </div>
                                        <div class="grid-2">
                                            <div class="form-group-modern">
                                                <label class="label-modern">Cheque #</label>
                                                <input type="text" class="input-modern" name="cheque_no" value="{{ $party->cheque_no ?? '' }}" placeholder="Cheque Number">
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="label-modern">Cheque Date</label>
                                                <input type="date" class="input-modern" name="cheque_date" value="{{ $party->cheque_date ? \Carbon\Carbon::parse($party->cheque_date)->format('Y-m-d') : '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Credit Limit</label>
                                        <input type="number" step="0.01" class="input-modern" name="credit_limit"
                                            value="{{ $party->credit_limit ?? 0 }}">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Opening Balance</label>
                                        <input type="number" step="0.01" class="input-modern" name="opening_balance"
                                            value="{{ $party->opening_balance ?? 0 }}"
                                            style="border-color: #fca5a5; background: #fff1f2; font-weight: 800;">
                                    </div>
                                </div>

                                <!-- Customer Specific Fields -->
                                <div id="customerOnlyFields"
                                    class="{{ $party->party_type == 'Vendor' ? 'hidden' : '' }}">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Category</label>
                                        <select class="input-modern select-modern" name="category">
                                            <option value="(N/A)"
                                                {{ ($party->category ?? '') == '(N/A)' ? 'selected' : '' }}>
                                                (N/A)
                                            </option>
                                            <option value="A-Class"
                                                {{ ($party->category ?? '') == 'A-Class' ? 'selected' : '' }}>
                                                A-Class
                                            </option>
                                            <option value="B-Class"
                                                {{ ($party->category ?? '') == 'B-Class' ? 'selected' : '' }}>
                                                B-Class
                                            </option>
                                        </select>
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Credit Status</label>
                                            <select class="input-modern select-modern" name="credit_status">
                                                <option value="DO NOT NOTIFY"
                                                    {{ ($party->credit_status ?? '') == 'DO NOT NOTIFY' ? 'selected' : '' }}>
                                                    DO NOT NOTIFY</option>
                                                <option value="NOTIFY OVERDUE"
                                                    {{ ($party->credit_status ?? '') == 'NOTIFY OVERDUE' ? 'selected' : '' }}>
                                                    NOTIFY OVERDUE</option>
                                                <option value="HOLD ACCOUNT"
                                                    {{ ($party->credit_status ?? '') == 'HOLD ACCOUNT' ? 'selected' : '' }}>
                                                    HOLD ACCOUNT</option>
                                            </select>
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Credit Limit</label>
                                            <input type="number" step="0.01" class="input-modern"
                                                name="credit_limit" value="{{ $party->credit_limit ?? 0 }}">
                                        </div>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Loyalty Group</label>
                                        <select class="input-modern select-modern" name="loyalty_group">
                                            <option value="GENERAL CUSTOMER"
                                                {{ ($party->loyalty_group ?? '') == 'GENERAL CUSTOMER' ? 'selected' : '' }}>
                                                GENERAL CUSTOMER</option>
                                        </select>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Default Price Group</label>
                                        <select class="input-modern select-modern" name="default_price">
                                            <option value="AVG COST PRICE"
                                                {{ ($party->default_price ?? '') == 'AVG COST PRICE' ? 'selected' : '' }}>
                                                AVG COST PRICE</option>
                                            <option value="SALE PRICE"
                                                {{ ($party->default_price ?? '') == 'SALE PRICE' ? 'selected' : '' }}>
                                                SALE PRICE</option>
                                        </select>
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">V1 MC %</label>
                                            <input type="number" step="0.01" class="input-modern" name="v1_mc"
                                                value="{{ $party->v1_mc ?? 0 }}">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">V2 MC %</label>
                                            <input type="number" step="0.01" class="input-modern" name="v2_mc"
                                                value="{{ $party->v2_mc ?? 0 }}">
                                        </div>
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">VAN #</label>
                                            <input type="text" class="input-modern" name="van_no"
                                                value="{{ $party->van_no ?? '' }}" placeholder="Br. Code">
                                        </div>
                                        <div class="form-group-modern" id="customerCngField">
                                            <label class="label-modern">CNG</label>
                                            <input type="number" class="input-modern" name="cng"
                                                value="{{ $party->cng ?? 0 }}">
                                        </div>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Card Expiry</label>
                                        <input type="date" class="input-modern" name="card_expiry"
                                            value="{{ $party->card_expiry ? \Carbon\Carbon::parse($party->card_expiry)->format('Y-m-d') : '' }}">
                                    </div>
                                </div>

                                <!-- Vendor Specific Fields -->
                                <div id="vendorOnlyFields"
                                    class="{{ $party->party_type == 'Vendor' ? '' : ($party->party_type == 'Vendor/Customer' ? '' : 'hidden') }}">
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Commission %</label>
                                            <input type="number" step="0.01" class="input-modern"
                                                name="commission_percent" value="{{ $party->commission_percent ?? 0 }}">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">WH Tax %</label>
                                            <input type="number" step="0.01" class="input-modern" name="wh_tax"
                                                value="{{ $party->wh_tax ?? 0 }}">
                                        </div>
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Margin %</label>
                                            <input type="number" step="0.01" class="input-modern" name="margin_percent"
                                                value="{{ $party->margin_percent ?? 0 }}">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern text-success">Credit Limit</label>
                                            <input type="number" step="0.01" class="input-modern" name="credit_limit"
                                                value="{{ $party->credit_limit ?? 0 }}" style="border-color: #86efac; background: #f0fdf4;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Box 4: Human Points -->
                        <div class="col-md-6">
                            <div class="premium-card">
                                <div class="card-header-modern">
                                    <div class="icon-box" style="background:#fdf2f8; color:#be185d;"><i
                                            class="fa fa-user-friends"></i></div>
                                    <h3>Contact Person</h3>
                                </div>

                                <div
                                    style="background:#f8fafc; padding:20px; border-radius:15px; margin-bottom:20px; border:1px solid #e2e8f0;">
                                    <div class="label-modern text-primary mb-3" style="font-size:0.9rem;">Contact
                                        Person 1</div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Name</label>
                                        <input type="text" class="input-modern" name="contact_person"
                                            value="{{ $party->contact_person ?? '' }}" placeholder="Name">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Designation</label>
                                        <input type="text" class="input-modern" name="contact_person_designation"
                                            value="{{ $party->contact_person_designation ?? '' }}"
                                            placeholder="Designation">
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Cell #</label>
                                            <input type="text" class="input-modern" name="phone"
                                                value="{{ $party->phone ?? ($party->mobile ?? '') }}" placeholder="Cell">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Whatsapp</label>
                                            <input type="text" class="input-modern" name="contact_person_whatsapp"
                                                value="{{ $party->contact_person_whatsapp ?? '' }}"
                                                placeholder="Whatsapp">
                                        </div>
                                    </div>
                                </div>

                                <div
                                    style="background:#f8fafc; padding:20px; border-radius:15px; border:1px solid #e2e8f0;">
                                    <div class="label-modern mb-3" style="font-size:0.9rem;">Contact Person 2</div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Name</label>
                                        <input type="text" class="input-modern" name="contact_person_2"
                                            value="{{ $party->contact_person_2 ?? '' }}" placeholder="Name">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Designation</label>
                                        <input type="text" class="input-modern" name="contact_person_2_designation"
                                            value="{{ $party->contact_person_2_designation ?? '' }}"
                                            placeholder="Designation">
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Cell #</label>
                                            <input type="text" class="input-modern" name="contact_person_2_mobile"
                                                value="{{ $party->contact_person_2_mobile ?? ($party->mobile_2 ?? '') }}"
                                                placeholder="Cell">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Whatsapp</label>
                                            <input type="text" class="input-modern" name="contact_person_2_whatsapp"
                                                value="{{ $party->contact_person_2_whatsapp ?? '' }}"
                                                placeholder="Whatsapp">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Audit & Media -->
                <div class="col-lg-4">
                    <div style="position: sticky; top: 20px;">
                        <div class="premium-card mb-4" style="text-align: center;">
                            <div class="card-header-modern">
                                <div class="icon-box"><i class="fa fa-image"></i></div>
                                <h3>Profile Identity</h3>
                            </div>

                            <label for="imageUpload" class="profile-drop-area">
                                @if ($party->image)
                                    <img id="previewImage" src="{{ asset('storage/' . $party->image) }}">
                                @else
                                    <i class="fa fa-user-circle" style="font-size: 3rem; color: #e2e8f0;"></i>
                                    <span style="font-size: 0.8rem; margin-top: 15px; font-weight:700;">UPDATE
                                        IMAGE</span>
                                @endif
                                <img id="newPreview"
                                    style="display:none; width:100%; height:100%; object-fit:cover; position:absolute; z-index:5;">
                            </label>
                            <input type="file" id="imageUpload" name="image" class="hidden" accept="image/*"
                                onchange="previewFile(this)">
                        </div>

                        <div class="premium-card">
                            <div class="card-header-modern">
                                <div class="icon-box" style="background:#f8fafc;"><i class="fa fa-history"></i></div>
                                <h3>Audit Registry</h3>
                            </div>

                            <div class="audit-list">
                                <div class="audit-item">
                                    <span class="audit-label">First Integration</span>
                                    <span class="audit-value">{{ $party->created_at->format('d M, Y - H:i') }}</span>
                                </div>
                                <div class="audit-item">
                                    <span class="audit-label">Latest Modification</span>
                                    <span class="audit-value">{{ $party->updated_at->diffForHumans() }}</span>
                                </div>
                                <div class="audit-item">
                                    <span class="audit-label">Author Identity</span>
                                    <span class="audit-value">{{ auth()->user()->name }} (Operator)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="floating-action-pill">
                <a href="{{ url()->previous() }}" class="pill-btn pill-btn-cancel">Abort Changes</a>
                <button type="submit" class="pill-btn pill-btn-save">
                    <i class="fa fa-sync-alt"></i> Synchronize Record
                </button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Tab switching
            const tabs = document.querySelectorAll('.tab-btn');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    const target = this.getAttribute('data-tab');
                    document.getElementById('tab-mailing').classList.add('hidden');
                    document.getElementById('tab-shipping').classList.add('hidden');
                    document.getElementById('tab-' + target).classList.remove('hidden');
                });
            });

            // Handle Dynamic Field Switching
            const typeSelect = document.getElementById('partyType');
            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    const val = this.value;
                    const custFields = document.getElementById('customerOnlyFields');
                    const vendFields = document.getElementById('vendorOnlyFields');
                    const vendBusiness = document.getElementById('vendorBusinessName');
                    const custCng = document.getElementById('customerCngField');
                    const title = document.getElementById('settingsTitle');

                    if (val === 'Vendor') {
                        title.innerText = 'Vendor Settings';
                        custFields?.classList.add('hidden');
                        vendFields?.classList.remove('hidden');
                        vendBusiness?.classList.remove('hidden');
                        custCng?.classList.add('hidden');
                    } else if (val === 'Customer') {
                        title.innerText = 'Customer Settings';
                        custFields?.classList.remove('hidden');
                        vendFields?.classList.add('hidden');
                        vendBusiness?.classList.add('hidden');
                        custCng?.classList.remove('hidden');
                    } else {
                        title.innerText = 'Combined Settings';
                        custFields?.classList.remove('hidden');
                        vendFields?.classList.remove('hidden');
                        vendBusiness?.classList.remove('hidden');
                        custCng?.classList.remove('hidden');
                    }
                });

                // Trigger initial state
                typeSelect.dispatchEvent(new Event('change'));
            }

            // Payment Mode Logic (Cheque Fields)
            const paymentMode = document.getElementById('paymentMode');
            const chequeDetails = document.getElementById('chequeDetails');

            paymentMode.addEventListener('change', function() {
                if (this.value === 'Cheque') {
                    chequeDetails.classList.remove('hidden');
                } else {
                    chequeDetails.classList.add('hidden');
                }
            });

            if (paymentMode) {
                paymentMode.dispatchEvent(new Event('change'));
            }

            // Notifications
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Data Synchronized',
                    text: "{{ session('success') }}",
                    showConfirmButton: false,
                    timer: 3000,
                    background: '#ffffff'
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Registry Error',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#4f46e5'
                });
            @endif
        });

        window.toggleCustomCreditTerms = function() {
            var select = document.getElementById('creditTermsSelect');
            var customInput = document.getElementById('customCreditTermsInput');
            if (select.value === 'custom') {
                customInput.style.display = 'block';
                customInput.setAttribute('required', 'required');
            } else {
                customInput.style.display = 'none';
                customInput.removeAttribute('required');
                customInput.value = '';
            }
        };

        function previewFile(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    const prev = document.getElementById('newPreview');
                    prev.src = e.target.result;
                    prev.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection
