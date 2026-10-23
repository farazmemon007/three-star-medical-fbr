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
            background-color: #f8fafc;
            color: #1e293b;
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
                transform: translateY(10px);
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
            padding: 20px;
            background: var(--glass);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: var(--shadow-subtle);
        }

        .title-group h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.025em;
        }

        .title-group p {
            font-size: 0.85rem;
            color: var(--secondary);
            margin: 0;
            margin-top: 4px;
        }

        /* Modern Card/Box */
        .premium-card {
            background: #ffffff;
            border-radius: var(--radius-xl);
            border: 1px solid #e2e8f0;
            padding: 24px;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: var(--shadow-subtle);
        }

        .card-header-modern {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 15px;
        }

        .icon-box {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 1.1rem;
        }

        .card-header-modern h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Tab System Modern */
        .tab-nav {
            display: flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .tab-btn {
            flex: 1;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            background: transparent;
            color: var(--secondary);
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .tab-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Form Controls Modern */
        .form-group-modern {
            margin-bottom: 18px;
        }

        .label-modern {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .input-modern {
            width: 100%;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #0f172a;
            transition: all 0.2s;
        }

        .input-modern:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .input-modern::placeholder {
            color: #94a3b8;
        }

        .select-modern {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.2em 1.2em;
            padding-right: 2.5rem;
        }

        /* Floating Pill UI Component */
        .floating-action-pill {
            position: fixed;
            bottom: 35px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(15px);
            padding: 12px 24px;
            border-radius: 100px;
            box-shadow: var(--shadow-bold);
            display: flex;
            gap: 15px;
            z-index: 1000;
            border: 1px solid rgba(255, 255, 255, 0.15);
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                transform: translate(-50%, 100px);
            }

            to {
                transform: translate(-50%, 0);
            }
        }

        .pill-btn {
            padding: 10px 28px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.3s;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .pill-btn-save {
            background: var(--primary);
            color: white;
        }

        .pill-btn-save:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .pill-btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .pill-btn-cancel:hover {
            background: rgba(255, 0, 0, 0.2);
        }

        /* Profile Upload Area */
        .profile-drop-area {
            width: 100%;
            aspect-ratio: 1/1;
            border: 2px dashed #cbd5e1;
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #f8fafc;
            overflow: hidden;
            position: relative;
            transition: 0.2s;
        }

        .profile-drop-area:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        #previewImage {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
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
            margin-bottom: 15px;
        }

        .audit-label {
            font-size: 0.7rem;
            color: var(--secondary);
            font-weight: 700;
            text-transform: uppercase;
        }

        .audit-value {
            font-size: 0.85rem;
            color: #0f172a;
            font-weight: 500;
        }

        /* Helpers */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .hidden {
            display: none !important;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .text-primary-gradient {
            background: linear-gradient(135deg, var(--primary), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>

    <div class="premium-container">
        <form action="{{ route('parties.store') }}" method="POST" enctype="multipart/form-data" id="partyForm">
            @csrf

            <div class="premium-header">
                <div class="title-group">
                    <h1><i class="fa fa-user-plus text-primary-gradient"></i> Define New Party</h1>
                    <p>Registration system for Customers and Vendors</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="icon-box" title="Quick Print"><i class="fa fa-print"></i></div>
                    <div class="icon-box" style="background:#fef3c7; color:#d97706;" title="Drafts"><i
                            class="fa fa-sticky-note"></i></div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Column 1: Core & Addresses -->
                <div class="col-lg-8">
                    <div class="row g-4">
                        <!-- Box 1: General Info -->
                        <div class="col-md-6">
                            <div class="premium-card">
                                <div class="card-header-modern">
                                    <div class="icon-box"><i class="fa fa-id-card"></i></div>
                                    <h3>Identity & Classification</h3>
                                </div>

                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Account Code</label>
                                        <input type="text" class="input-modern" name="code" id="systemCode"
                                            value="{{ $type == 'Vendor' ? $vendDraft : $custDraft }}" readonly
                                            style="font-weight: 800; color:var(--primary);">
                                    </div>
                                    <div class="form-group-modern d-flex align-items-center justify-content-end"
                                        style="padding-top:25px;">
                                        <label class="d-flex align-items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="is_active" value="1" checked
                                                style="width:18px; height:18px; border-radius:4px;">
                                            <span style="font-size:0.8rem; font-weight:700;">Account Active</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Row: Type + Abr --}}
                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Party Type <span class="text-danger">*</span></label>
                                        <select class="input-modern select-modern" name="party_type" id="partyType">
                                            <option value="Customer" {{ $type == 'Customer' ? 'selected' : '' }}>CUSTOMER
                                            </option>
                                            <option value="Vendor" {{ $type == 'Vendor' ? 'selected' : '' }}>VENDOR</option>
                                            <option value="Vendor/Customer"
                                                {{ $type == 'Vendor/Customer' ? 'selected' : '' }}>VENDOR / CUSTOMER
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Abbreviation / Abr</label>
                                        <input type="text" class="input-modern" name="abr" placeholder="Br. Code">
                                    </div>
                                </div>

                                {{-- Row: Title (full width) --}}
                                <div class="form-group-modern">
                                    <label class="label-modern">Full Title / Name <span class="text-danger">*</span></label>
                                    <input type="text" class="input-modern @error('title') border-danger @enderror"
                                        name="title" value="{{ old('title') }}" placeholder="Client Title" required>
                                </div>

                                {{-- Row: Business Name (vendor) --}}
                                <div id="vendorBusinessName" class="form-group-modern hidden">
                                    <label class="label-modern">Business Name</label>
                                    <input type="text" class="input-modern" name="business_name"
                                        placeholder="Business / Trade Name">
                                </div>

                                {{-- Row: CNIC + NTN --}}
                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">CNIC # (Optional)</label>
                                        <input type="text" class="input-modern" name="cnic"
                                            placeholder="XXXXX-XXXXXXX-X">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">NTN # (Optional)</label>
                                        <input type="text" class="input-modern" name="ntn_no" placeholder="NTN #">
                                    </div>
                                </div>

                                {{-- Row: Filer Type + GST --}}
                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Filer Type (FBR Status)</label>
                                        <select class="input-modern select-modern" name="filer_type" id="filerType">
                                            <option value="Non-Filer" {{ old('filer_type', 'Non-Filer') == 'Non-Filer' ? 'selected' : '' }}>Non-Filer</option>
                                            <option value="Filer" {{ old('filer_type') == 'Filer' ? 'selected' : '' }}>Filer</option>
                                        </select>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">GST # (Optional)</label>
                                        <input type="text" class="input-modern" name="gst_no"
                                            value="{{ old('gst_no') }}" placeholder="Sales Tax ID">
                                    </div>
                                </div>

                                {{-- Row: FTN + DSL --}}
                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">FTN # (Optional)</label>
                                        <input type="text" class="input-modern" name="ftn_no"
                                            value="{{ old('ftn_no') }}" placeholder="e.g. 1347561-4">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">DSL # (Optional)</label>
                                        <input type="text" class="input-modern" name="dsl_no"
                                            value="{{ old('dsl_no') }}" placeholder="Drug Sale Lic.">
                                    </div>
                                </div>

                                {{-- Row: DRAP --}}
                                <div class="form-group-modern">
                                    <label class="label-modern">DRAP # (Optional)</label>
                                    <input type="text" class="input-modern" name="drap_no"
                                        value="{{ old('drap_no') }}" placeholder="Medical Reg.">
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
                                                    {{ session('super_admin_branch_id') == $b->id ? 'selected' : '' }}>
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

                        <!-- Box 2: Modern Tabbed Addresses -->
                        <div class="col-md-6">
                            <div class="premium-card">
                                <div class="card-header-modern">
                                    <div class="icon-box"><i class="fa fa-map-marked-alt"></i></div>
                                    <h3>Locations & Communication</h3>
                                </div>

                                <div class="tab-nav">
                                    <button type="button" class="tab-btn active" data-tab="mailing">MAILING
                                        ADDR.</button>
                                    <button type="button" class="tab-btn" data-tab="shipping">SHIPPING ADDR.</button>
                                </div>

                                <div id="tab-mailing">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Complete Address</label>
                                        <input type="text" class="input-modern" name="address"
                                            placeholder="Building, Street, Area...">
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">City</label>
                                            <input type="text" class="input-modern" name="city"
                                                placeholder="City">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Country</label>
                                            <input type="text" class="input-modern" name="country" value="Pakistan">
                                        </div>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Email Address (Optional)</label>
                                        <input type="email" class="input-modern" name="email"
                                            placeholder="official@company.com">
                                    </div>
                                </div>

                                <div id="tab-shipping" class="hidden">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Shipping Point Address</label>
                                        <input type="text" class="input-modern" name="shipping_address"
                                            placeholder="Warehouse / Branch Address">
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Shipping City</label>
                                            <input type="text" class="input-modern" name="shipping_city"
                                                placeholder="City">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Phone #</label>
                                            <input type="text" class="input-modern" name="shipping_phone"
                                                placeholder="Contact at Point">
                                        </div>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Shipping Email (Optional)</label>
                                        <input type="text" class="input-modern" name="shipping_email"
                                            placeholder="e.g. logistics@company.com or notes">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Box 3: Commercial Settings -->
                        <div class="col-md-6">
                            <div class="premium-card">
                                <div class="card-header-modern">
                                    <div class="icon-box" style="background:#ecfdf5; color:#059669;"><i
                                            class="fa fa-hand-holding-usd"></i></div>
                                    <h3 id="settingsTitle">Customer Settings</h3>
                                </div>

                                <div class="grid-2">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Credit Terms</label>
                                        <select class="input-modern select-modern" name="credit_terms" id="creditTermsSelect" onchange="toggleCustomCreditTerms()">
                                            <option value="0">Cash / Immediate</option>
                                            <option value="7">7 Days</option>
                                            <option value="15">15 Days</option>
                                            <option value="30">30 Days</option>
                                            <option value="custom">Custom Days</option>
                                        </select>
                                        <input type="number" class="input-modern mt-2" name="custom_credit_terms" id="customCreditTermsInput" 
                                            placeholder="Enter days" style="display: none;" min="1">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Opening Balance</label>
                                        <input type="number" step="0.01" class="input-modern" name="opening_balance"
                                            value="0.00"
                                            style="border-color: #fca5a5; background: #fff1f2; font-weight: 800;">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Payment Mode</label>
                                        <select class="input-modern select-modern" name="payment_mode" id="paymentMode">
                                            <option value="Cash">Cash</option>
                                            <option value="Bank">Bank</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Cheque Details (Dynamic) -->
                                <div id="chequeDetails" class="hidden">
                                    <div style="background:#f0f9ff; padding:15px; border-radius:12px; border:1px solid #bae6fd; margin-bottom:15px;">
                                        <div class="label-modern" style="color:#0369a1; margin-bottom:12px;"><i class="fa fa-university me-2"></i>Cheque / Bank Details</div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Bank Name</label>
                                            <input type="text" class="input-modern" name="bank_name" placeholder="Bank Name">
                                        </div>
                                        <div class="grid-2">
                                            <div class="form-group-modern">
                                                <label class="label-modern">Cheque #</label>
                                                <input type="text" class="input-modern" name="cheque_no" placeholder="Cheque Number">
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="label-modern">Cheque Date</label>
                                                <input type="date" class="input-modern" name="cheque_date">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Specific Fields -->
                                <div id="customerOnlyFields">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Category</label>
                                        <select class="input-modern select-modern" name="category">
                                            <option>(N/A)</option>
                                            <option>A-Class</option>
                                            <option>B-Class</option>
                                        </select>
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Credit Status</label>
                                            <select class="input-modern select-modern" name="credit_status">
                                                <option>DO NOT NOTIFY</option>
                                                <option>NOTIFY OVERDUE</option>
                                                <option>HOLD ACCOUNT</option>
                                            </select>
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Credit Limit</label>
                                            <input type="number" step="0.01" class="input-modern"
                                                name="credit_limit" value="0.00">
                                        </div>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Loyalty Group</label>
                                        <select class="input-modern select-modern" name="loyalty_group">
                                            <option>GENERAL CUSTOMER</option>
                                        </select>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Default Price Group</label>
                                        <select class="input-modern select-modern" name="default_price">
                                            <option>AVG COST PRICE</option>
                                            <option>SALE PRICE</option>
                                        </select>
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">V1 MC %</label>
                                            <input type="number" step="0.01" class="input-modern" name="v1_mc"
                                                value="0">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">V2 MC %</label>
                                            <input type="number" step="0.01" class="input-modern" name="v2_mc"
                                                value="0">
                                        </div>
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">VAN #</label>
                                            <input type="text" class="input-modern" name="van_no"
                                                placeholder="Br. Code">
                                        </div>
                                        <div class="form-group-modern" id="customerCngField">
                                            <label class="label-modern">CNG</label>
                                            <input type="number" class="input-modern" name="cng" value="0">
                                        </div>
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Card Expiry</label>
                                        <input type="date" class="input-modern" name="card_expiry">
                                    </div>
                                </div>

                                <!-- Vendor Specific Fields -->
                                <div id="vendorOnlyFields" class="hidden">
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Commission %</label>
                                            <input type="number" step="0.01" class="input-modern"
                                                name="commission_percent" value="0">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">WH Tax %</label>
                                            <input type="number" step="0.01" class="input-modern" name="wh_tax"
                                                value="0">
                                        </div>
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Margin %</label>
                                            <input type="number" step="0.01" class="input-modern" name="margin_percent"
                                                value="0">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern text-success">Credit Limit</label>
                                            <input type="number" step="0.01" class="input-modern" name="credit_limit"
                                                value="0" style="border-color: #86efac; background: #f0fdf4;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Box 4: Human Relations -->
                        <div class="col-md-6">
                            <div class="premium-card">
                                <div class="card-header-modern">
                                    <div class="icon-box" style="background:#fdf2f8; color:#be185d;"><i
                                            class="fa fa-user-friends"></i></div>
                                    <h3>Contact Person</h3>
                                </div>

                                <div
                                    style="background:#f8fafc; padding:20px; border-radius:15px; margin-bottom:20px; border:1px solid #e2e8f0;">
                                    <div class="label-modern text-primary mb-3" style="font-size:0.9rem;">Contact Person 1
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Name</label>
                                        <input type="text" class="input-modern" name="contact_person"
                                            placeholder="Name">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Designation</label>
                                        <input type="text" class="input-modern" name="contact_person_designation"
                                            placeholder="Designation">
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Cell #</label>
                                            <input type="text" class="input-modern" name="phone"
                                                placeholder="Cell">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Whatsapp</label>
                                            <input type="text" class="input-modern" name="contact_person_whatsapp"
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
                                            placeholder="Name">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="label-modern">Designation</label>
                                        <input type="text" class="input-modern" name="contact_person_2_designation"
                                            placeholder="Designation">
                                    </div>
                                    <div class="grid-2">
                                        <div class="form-group-modern">
                                            <label class="label-modern">Cell #</label>
                                            <input type="text" class="input-modern" name="contact_person_2_mobile"
                                                placeholder="Cell">
                                        </div>
                                        <div class="form-group-modern">
                                            <label class="label-modern">Whatsapp</label>
                                            <input type="text" class="input-modern" name="contact_person_2_whatsapp"
                                                placeholder="Whatsapp">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Media & Metadata (Right Sticky) -->
                <div class="col-lg-4">
                    <div style="position: sticky; top: 20px;">
                        <!-- Media Card -->
                        <div class="premium-card mb-4" style="text-align: center;">
                            <div class="card-header-modern">
                                <div class="icon-box"><i class="fa fa-camera"></i></div>
                                <h3>Brand Identity</h3>
                            </div>

                            <label for="imageUpload" class="profile-drop-area" id="dropArea">
                                <i class="fa fa-cloud-upload-alt" style="font-size: 2rem; color: #cbd5e1;"></i>
                                <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; margin-top: 10px;">DROP
                                    LOGO OR CLICK TO BROWSE</span>
                                <img id="previewImage" src="" style="display:none;">
                            </label>
                            <input type="file" id="imageUpload" name="image" class="hidden" accept="image/*"
                                onchange="previewFile(this)">

                            <div class="mt-3 text-muted" style="font-size:0.7rem;">Supported: JPG, PNG, WEBP (Max 2MB)
                            </div>
                        </div>

                        <!-- System Audit Card -->
                        <div class="premium-card">
                            <div class="card-header-modern">
                                <div class="icon-box" style="background:#f1f5f9; color:#475569;"><i
                                        class="fa fa-shield-alt"></i></div>
                                <h3>System Protocol</h3>
                            </div>

                            <div class="audit-list">
                                <div class="audit-item">
                                    <span class="audit-label">Protocol Status</span>
                                    <span class="audit-value"><i class="fa fa-circle text-success me-1"
                                            style="font-size:0.6rem"></i> Ready for Registration</span>
                                </div>
                                <div class="audit-item">
                                    <span class="audit-label">Authorized Operator</span>
                                    <span class="audit-value">{{ auth()->user()->name }}</span>
                                </div>
                                <div class="audit-item">
                                    <span class="audit-label">Session ID</span>
                                    <span
                                        class="audit-value text-muted">{{ strtoupper(substr(session()->getId(), 0, 8)) }}</span>
                                </div>
                                <div class="audit-item">
                                    <span class="audit-label">Encryption</span>
                                    <span class="audit-value">AES-256 System Validated</span>
                                </div>
                            </div>

                            <hr style="border-style:dashed;">

                            <div style="font-size:0.65rem; color:var(--secondary); font-style:italic; line-height:1.4;">
                                * All data remains confidential under company policy. Changes are logged for audit trailing.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Floating Action Pill -->
            <div class="floating-action-pill">
                <a href="{{ url()->previous() }}" class="pill-btn pill-btn-cancel">Discard Changes</a>
                <button type="submit" class="pill-btn pill-btn-save">
                    <i class="fa fa-check-circle"></i> Complete Registration
                </button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        const custCode = "{{ $custDraft }}";
        const vendCode = "{{ $vendDraft }}";

        document.addEventListener("DOMContentLoaded", function() {
            // Tab switching logic
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

            // Dynamic Code Switching
            const typeSelect = document.getElementById('partyType');
            const codeInput = document.getElementById('systemCode');

            typeSelect.addEventListener('change', function() {
                // Add a quick animation effect
                codeInput.style.transition = "0.2s";
                codeInput.style.opacity = "0";

                setTimeout(() => {
                    const val = this.value;
                    const custFields = document.getElementById('customerOnlyFields');
                    const vendFields = document.getElementById('vendorOnlyFields');
                    const vendBusiness = document.getElementById('vendorBusinessName');
                    const custCng = document.getElementById('customerCngField');
                    const title = document.getElementById('settingsTitle');

                    if (val === 'Vendor') {
                        codeInput.value = vendCode;
                        title.innerText = 'Vendor Settings';
                        custFields.classList.add('hidden');
                        vendFields.classList.remove('hidden');
                        vendBusiness.classList.remove('hidden');
                        custCng.classList.add('hidden');
                    } else if (val === 'Customer') {
                        codeInput.value = custCode;
                        title.innerText = 'Customer Settings';
                        custFields.classList.remove('hidden');
                        vendFields.classList.add('hidden');
                        vendBusiness.classList.add('hidden');
                        custCng.classList.remove('hidden');
                    } else {
                        // For Vendor/Customer (Both)
                        codeInput.value = custCode;
                        title.innerText = 'Combined Settings';
                        custFields.classList.remove('hidden');
                        vendFields.classList.remove('hidden');
                        vendBusiness.classList.remove('hidden');
                        custCng.classList.remove('hidden');
                    }
                    codeInput.style.opacity = "1";
                }, 200);
            });

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

            // Trigger initial state
            typeSelect.dispatchEvent(new Event('change'));
            paymentMode.dispatchEvent(new Event('change'));

            // Success/Error Alerts integration
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: "{{ session('success') }}",
                    timer: 4000,
                    showConfirmButton: false,
                    background: '#f8fafc',
                    iconColor: '#10b981'
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Operation Failed',
                    text: "{{ session('error') }}",
                    showConfirmButton: true,
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
                    const preview = document.getElementById('previewImage');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    document.getElementById('dropArea').style.borderColor = 'var(--primary)';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection
