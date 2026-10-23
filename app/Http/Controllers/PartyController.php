<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\CustomerLedger;
use App\Models\VendorLedger;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\BranchScoped;
use App\Models\Branch;

class PartyController extends Controller
{
    use BranchScoped;
    public function edit($id, Request $request)
    {
        $type = $request->get('type', 'Customer');
        
        if ($type == 'Customer') {
            $party = Customer::findOrFail($id);
            $party->code = $party->customer_id;
            $party->title = $party->customer_name;
            $party->phone = $party->mobile;
            $party->email = $party->email_address;
            $party->credit_limit = $party->balance_range;
        } else {
            $party = Vendor::findOrFail($id);
            $party->code = $party->vendor_code;
            $party->title = $party->name;
            // email, phone, etc already match
        }

        $branches = $this->isSuperAdmin() ? Branch::all() : [];
        return view('admin_panel.parties.edit', compact('party', 'type', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'party_type' => 'required|in:Customer,Vendor,Vendor/Customer',
        ]);

        $type = $request->get('type', 'Customer');
        $partyType = $request->input('party_type');
        $data = $request->all();
        if (isset($data['credit_terms']) && $data['credit_terms'] === 'custom') {
            $data['credit_terms'] = $data['custom_credit_terms'] ?? null;
        }

        DB::beginTransaction();
        try {
            $mappedData = [
                'is_active' => $request->has('is_active') ? 1 : 0,
                'party_type' => $partyType,
                'title' => $data['title'] ?? null,
                'url' => $data['url'] ?? null,
                'ntn_no' => $data['ntn_no'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'fax' => $data['fax'] ?? null,
                'credit_terms' => $data['credit_terms'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_country' => $data['shipping_country'] ?? null,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_fax' => $data['shipping_fax'] ?? null,
                'shipping_email' => $data['shipping_email'] ?? null,
                'gst_no' => $data['gst_no'] ?? null,
                'dsl_no' => $data['dsl_no'] ?? null,
                'drap_no' => $data['drap_no'] ?? null,
                'ftn_no' => $data['ftn_no'] ?? null,
                'credit_terms' => $data['credit_terms'] ?? null,
                'payment_mode' => $data['payment_mode'] ?? null,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'credit_status' => $data['credit_status'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'branch_id' => $request->input('branch_id') ?? 1,
                'bank_name' => $data['bank_name'] ?? null,
                'cheque_no' => $data['cheque_no'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
            ];

            if ($type === 'Customer') {
                $customer = Customer::findOrFail($id);
                $updateData = array_merge($mappedData, [
                    'customer_name' => $data['title'] ?? null,
                    'business_name' => $data['business_name'] ?? null,
                    'abr' => $data['abr'] ?? null,
                    'cnic' => $data['cnic'] ?? null,
                    'filer_type' => $data['filer_type'] ?? 'Non-Filer',
                    'address' => $data['address'] ?? null,
                    'mobile' => $data['phone'] ?? null,
                    'email_address' => $data['email'] ?? null,
                    'category' => $data['category'] ?? null,
                    'balance_range' => $data['credit_limit'] ?? 0,
                    'loyalty_group' => $data['loyalty_group'] ?? null,
                    'default_price' => $data['default_price'] ?? null,
                    'v1_mc' => $data['v1_mc'] ?? 0,
                    'v2_mc' => $data['v2_mc'] ?? 0,
                    'van_no' => $data['van_no'] ?? null,
                    'cng' => $data['cng'] ?? null,
                    'card_expiry' => $data['card_expiry'] ?? null,
                    'contact_person' => $data['contact_person'] ?? null,
                    'contact_person_designation' => $data['contact_person_designation'] ?? null,
                    'contact_person_whatsapp' => $data['contact_person_whatsapp'] ?? null,
                    'contact_person_2' => $data['contact_person_2'] ?? null,
                    'contact_person_2_designation' => $data['contact_person_2_designation'] ?? null,
                    'contact_person_2_whatsapp' => $data['contact_person_2_whatsapp'] ?? null,
                    'mobile_2' => $data['contact_person_2_mobile'] ?? null,
                ]);
                
                if ($request->hasFile('image')) {
                    $updateData['image'] = $request->file('image')->store('customers', 'public');
                }

                $customer->update($updateData);

                // Sync to Vendor if dual type
                if ($partyType === 'Vendor/Customer') {
                    $code = $customer->customer_id;
                    $vendor = Vendor::where('vendor_code', $code)->first();
                    $vendorUpdateData = [
                        'name' => $data['title'] ?? null,
                        'business_name' => $data['business_name'] ?? null,
                        'cnic' => $data['cnic'] ?? null,
                        'address' => $data['address'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'party_type' => 'Vendor/Customer',
                        'branch_id' => $mappedData['branch_id'],
                        'is_active' => $mappedData['is_active'],
                        'city' => $mappedData['city'] ?? null,
                        'country' => $mappedData['country'] ?? null,
                        'ntn_no' => $mappedData['ntn_no'] ?? null,
                        'gst_no' => $mappedData['gst_no'] ?? null,
                        'dsl_no' => $mappedData['dsl_no'] ?? null,
                        'drap_no' => $mappedData['drap_no'] ?? null,
                        'ftn_no' => $mappedData['ftn_no'] ?? null,
                        'bank_name' => $mappedData['bank_name'] ?? null,
                        'cheque_no' => $mappedData['cheque_no'] ?? null,
                        'cheque_date' => $mappedData['cheque_date'] ?? null,
                        'opening_balance' => $mappedData['opening_balance'] ?? 0,
                        'contact_person' => $data['contact_person'] ?? null,
                        'contact_person_designation' => $data['contact_person_designation'] ?? null,
                        'contact_person_mobile' => $data['phone'] ?? null,
                    ];
                    if ($vendor) {
                        $vendor->update($vendorUpdateData);
                    } else {
                        Vendor::create(array_merge($vendorUpdateData, ['vendor_code' => $code]));
                    }
                }
            } else {
                $vendor = Vendor::findOrFail($id);
                $updateData = array_merge($mappedData, [
                    'name' => $data['title'] ?? null,
                    'business_name' => $data['business_name'] ?? null,
                    'cnic' => $data['cnic'] ?? null,
                    'address' => $data['address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'commission_percent' => $data['commission_percent'] ?? 0,
                    'wh_tax' => $data['wh_tax'] ?? 0,
                    'margin_percent' => $data['margin_percent'] ?? 0,
                    'contact_person' => $data['contact_person'] ?? null,
                    'contact_person_designation' => $data['contact_person_designation'] ?? null,
                    'contact_person_mobile' => $data['phone'] ?? null,
                    'contact_person_whatsapp' => $data['contact_person_whatsapp'] ?? null,
                    'contact_person_2' => $data['contact_person_2'] ?? null,
                    'contact_person_2_designation' => $data['contact_person_2_designation'] ?? null,
                    'contact_person_2_mobile' => $data['contact_person_2_mobile'] ?? null,
                    'contact_person_2_whatsapp' => $data['contact_person_2_whatsapp'] ?? null,
                ]);

                if ($request->hasFile('image')) {
                    $updateData['image'] = $request->file('image')->store('vendors', 'public');
                }

                $vendor->update($updateData);

                // Sync to Customer if dual type
                if ($partyType === 'Vendor/Customer') {
                    $code = $vendor->vendor_code;
                    $customer = Customer::where('customer_id', $code)->first();
                    $customerUpdateData = [
                        'customer_name' => $data['title'] ?? null,
                        'business_name' => $data['business_name'] ?? null,
                        'cnic' => $data['cnic'] ?? null,
                        'address' => $data['address'] ?? null,
                        'mobile' => $data['phone'] ?? null,
                        'email_address' => $data['email'] ?? null,
                        'party_type' => 'Vendor/Customer',
                        'branch_id' => $mappedData['branch_id'],
                        'is_active' => $mappedData['is_active'],
                        'city' => $mappedData['city'] ?? null,
                        'country' => $mappedData['country'] ?? null,
                        'ntn_no' => $mappedData['ntn_no'] ?? null,
                        'gst_no' => $mappedData['gst_no'] ?? null,
                        'dsl_no' => $mappedData['dsl_no'] ?? null,
                        'drap_no' => $mappedData['drap_no'] ?? null,
                        'ftn_no' => $mappedData['ftn_no'] ?? null,
                        'bank_name' => $mappedData['bank_name'] ?? null,
                        'cheque_no' => $mappedData['cheque_no'] ?? null,
                        'cheque_date' => $mappedData['cheque_date'] ?? null,
                        'opening_balance' => $mappedData['opening_balance'] ?? 0,
                        'contact_person' => $data['contact_person'] ?? null,
                        'contact_person_designation' => $data['contact_person_designation'] ?? null,
                        'mobile_2' => $data['contact_person_2_mobile'] ?? null,
                    ];
                    if ($customer) {
                        $customer->update($customerUpdateData);
                    } else {
                        Customer::create(array_merge($customerUpdateData, ['customer_id' => $code]));
                    }
                }
            }

            DB::commit();
            $redirectRoute = ($type === 'Vendor') ? 'vendors.index' : 'customers.index';
            return redirect()->route($redirectRoute)->with('success', "Profile for '{$data['title']}' has been updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Party Update Error: " . $e->getMessage());
            return back()->withInput()->with('error', "Failed to update party profile. System Error: " . $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'Customer'); // Default to Customer
        $custDraft = 'CUST-'.str_pad(\App\Models\Customer::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $vendDraft = 'VEND-'.str_pad(\App\Models\Vendor::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $branches = $this->isSuperAdmin() ? Branch::all() : [];

        return view('admin_panel.parties.create', compact('custDraft', 'vendDraft', 'type', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'party_type' => 'required|in:Customer,Vendor,Vendor/Customer',
            'code' => 'required|string|unique:customers,customer_id|unique:vendors,vendor_code',
        ]);

        $data = $request->all();
        if (isset($data['credit_terms']) && $data['credit_terms'] === 'custom') {
            $data['credit_terms'] = $data['custom_credit_terms'] ?? null;
        }
        $partyType = $request->input('party_type');
        $opening = $request->input('opening_balance', 0);
        $userId = Auth::id();
        $branchId = $request->input('branch_id') ?? $this->getBranchId() ?? 1;

        DB::beginTransaction();
        try {
            $mappedData = [
                'is_active' => $request->has('is_active') ? 1 : 0,
                'party_type' => $partyType,
                'title' => $data['title'] ?? null,
                'url' => $data['url'] ?? null,
                'ntn_no' => $data['ntn_no'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'fax' => $data['fax'] ?? null,
                'opening_balance' => $opening,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_country' => $data['shipping_country'] ?? null,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_fax' => $data['shipping_fax'] ?? null,
                'shipping_email' => $data['shipping_email'] ?? null,
                'gst_no' => $data['gst_no'] ?? null,
                'dsl_no' => $data['dsl_no'] ?? null,
                'drap_no' => $data['drap_no'] ?? null,
                'ftn_no' => $data['ftn_no'] ?? null,
                'credit_terms' => $data['credit_terms'] ?? null,
                'payment_mode' => $data['payment_mode'] ?? null,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'credit_status' => $data['credit_status'] ?? null,
                'branch_id' => $branchId,
                'bank_name' => $data['bank_name'] ?? null,
                'cheque_no' => $data['cheque_no'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
            ];

            $customerData = array_merge($mappedData, [
                'customer_id' => $data['code'] ?? 'CUST-'.rand(100,9999),
                'customer_name' => $data['title'] ?? null,
                'business_name' => $data['business_name'] ?? null,
                'abr' => $data['abr'] ?? null,
                'cnic' => $data['cnic'] ?? null,
                'filer_type' => $data['filer_type'] ?? 'Non-Filer',
                'address' => $data['address'] ?? null,
                'mobile' => $data['phone'] ?? null,
                'email_address' => $data['email'] ?? null,
                'category' => $data['category'] ?? null,
                'credit_status' => $data['credit_status'] ?? null,
                'balance_range' => $data['credit_limit'] ?? 0,
                'loyalty_group' => $data['loyalty_group'] ?? null,
                'default_price' => $data['default_price'] ?? null,
                'v1_mc' => $data['v1_mc'] ?? 0,
                'v2_mc' => $data['v2_mc'] ?? 0,
                'van_no' => $data['van_no'] ?? null,
                'cng' => $data['cng'] ?? null,
                'card_expiry' => $data['card_expiry'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_person_designation' => $data['contact_person_designation'] ?? null,
                'contact_person_whatsapp' => $data['contact_person_whatsapp'] ?? null,
                'contact_person_2' => $data['contact_person_2'] ?? null,
                'contact_person_2_designation' => $data['contact_person_2_designation'] ?? null,
                'contact_person_2_whatsapp' => $data['contact_person_2_whatsapp'] ?? null,
                'mobile_2' => $data['contact_person_2_mobile'] ?? null,
            ]);

            $vendorData = array_merge($mappedData, [
                'name' => $data['title'] ?? null,
                'vendor_code' => $data['code'] ?? 'VEND-'.rand(100,9999),
                'business_name' => $data['business_name'] ?? null,
                'cnic' => $data['cnic'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'commission_percent' => $data['commission_percent'] ?? 0,
                'wh_tax' => $data['wh_tax'] ?? 0,
                'margin_percent' => $data['margin_percent'] ?? 0,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_person_designation' => $data['contact_person_designation'] ?? null,
                'contact_person_mobile' => $data['phone'] ?? null,
                'contact_person_whatsapp' => $data['contact_person_whatsapp'] ?? null,
                'contact_person_2' => $data['contact_person_2'] ?? null,
                'contact_person_2_designation' => $data['contact_person_2_designation'] ?? null,
                'contact_person_2_mobile' => $data['contact_person_2_mobile'] ?? null,
                'contact_person_2_whatsapp' => $data['contact_person_2_whatsapp'] ?? null,
            ]);

            $balanceService = app(\App\Services\BalanceService::class);
            $journalService = app(\App\Services\JournalEntryService::class);

            if ($partyType === 'Customer' || $partyType === 'Vendor/Customer') {
                $customer = Customer::create($customerData);
                if ($opening != 0) {
                    CustomerLedger::create([
                        'customer_id' => $customer->id,
                        'admin_or_user_id' => $userId,
                        'previous_balance' => $opening,
                        'opening_balance' => $opening,
                        'closing_balance' => $opening,
                    ]);
                    $balanceService->ensureDefaultCOA($branchId);
                    
                    // Create Receipt Voucher instead of direct journal entry if requested
                    if ($opening > 0) {
                        $this->createOpeningBalanceVoucher($customer, 'receipt', $opening, $data, $branchId);
                    } else {
                        // Fallback to manual entry for negative opening (advance)
                        $arAccountId = $balanceService->getAccountsReceivableId($branchId);
                        $journalService->recordEntry($customer, $arAccountId, 0, abs($opening), "Opening Balance for Customer: {$customer->customer_name}", now()->toDateString(), $customer);
                    }
                }
            }

            if ($partyType === 'Vendor' || $partyType === 'Vendor/Customer') {
                $vendor = Vendor::create($vendorData);
                if ($opening != 0) {
                    VendorLedger::create([
                        'vendor_id' => $vendor->id,
                        'admin_or_user_id' => $userId,
                        'opening_balance' => $opening,
                        'closing_balance' => $opening,
                        'previous_balance' => $opening,
                    ]);
                    $balanceService->ensureDefaultCOA($branchId);
                    
                    // Create Payment Voucher instead of direct journal entry
                    if ($opening > 0) {
                        $this->createOpeningBalanceVoucher($vendor, 'payment', $opening, $data, $branchId);
                    } else {
                        // Fallback to manual entry for negative opening
                        $apAccountId = $balanceService->getAccountsPayableId($branchId);
                        $journalService->recordEntry($vendor, $apAccountId, abs($opening), 0, "Opening Balance for Vendor: {$vendor->name}", now()->toDateString(), $vendor);
                    }
                }
            }

            DB::commit();
            $redirectRoute = ($partyType === 'Vendor') ? 'vendors.index' : 'customers.index';
            return redirect()->route($redirectRoute)->with('success', "New party '{$data['title']}' has been successfully registered in the system.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Party Store Error: " . $e->getMessage());
            return back()->withInput()->with('error', "Could not create party record. Validation or System failure: " . $e->getMessage());
        }
    }

    private function createOpeningBalanceVoucher($party, $type, $amount, $data, $branchId)
    {
        $balanceService = app(\App\Services\BalanceService::class);
        $voucherService = app(\App\Services\VoucherService::class);
        
        $cashAccountId = $balanceService->getCashAccountId($branchId);
        $partyType = get_class($party);
        
        $lines = [];
        if ($type === 'receipt') {
            // Receipt: Dr Cash (from customer), Cr Receivable (the customer)
            $receivableAccountId = $balanceService->getAccountsReceivableId($branchId);
            
            // If payment_mode is cheque, we use "Cheque in Hand" if exists
            $targetAccountId = $cashAccountId;
            if (strtolower($data['payment_mode'] ?? '') === 'cheque') {
                $chequeInHand = \App\Models\Account::where('title', 'Cheque In Hand')
                    ->where('branch_id', $branchId)->first();
                if ($chequeInHand) $targetAccountId = $chequeInHand->id;
            }

            $lines[] = ['account_id' => $targetAccountId, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance Payment (Initial)'];
            $lines[] = ['account_id' => $receivableAccountId, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance Clearing'];
        } else {
            // Payment: Dr Payable (to vendor), Cr Cash (from us)
            $payableAccountId = $balanceService->getAccountsPayableId($branchId);
            
            $lines[] = ['account_id' => $payableAccountId, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance Payment (Initial)'];
            $lines[] = ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance Clearing'];
        }

        $voucher = $voucherService->createVoucher([
            'voucher_type' => $type,
            'date' => now()->toDateString(),
            'status' => \App\Models\VoucherMaster::STATUS_POSTED,
            'party_type' => $partyType,
            'party_id' => $party->id,
            'remarks' => "Opening Balance Voucher for " . ($data['title'] ?? 'Party'),
            'branch_id' => $branchId
        ], $lines, Auth::id());

        // Handle Cheque Management
        if (strtolower($data['payment_mode'] ?? '') === 'cheque' && !empty($data['cheque_no'])) {
            \App\Models\Cheque::create([
                'voucher_master_id' => $voucher->id,
                'cheque_no' => $data['cheque_no'],
                'cheque_date' => $data['cheque_date'] ?? now()->toDateString(),
                'bank_name' => $data['bank_name'] ?? null,
                'status' => 'pending',
                'amount' => $amount,
                'actual_account_id' => $cashAccountId // Default target bank/clearing account
            ]);
        }
    }
}
