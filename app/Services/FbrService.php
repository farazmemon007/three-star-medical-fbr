<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class FbrService
{
    /**
     * Ensure FBR columns exist on sales table and fbr_invoice_logs table exists.
     * Prevents Unknown column errors if migration hasn't been run yet on production/cPanel.
     */
    public static function ensureSchemaExists(): void
    {
        try {
            if (!Schema::hasColumn('sales', 'fbr_status')) {
                Schema::table('sales', function (Blueprint $table) {
                    if (!Schema::hasColumn('sales', 'fbr_status')) {
                        $table->string('fbr_status', 20)->default('unposted')->after('sale_status');
                    }
                    if (!Schema::hasColumn('sales', 'fbr_invoice_no')) {
                        $table->string('fbr_invoice_no', 100)->nullable()->after('fbr_status');
                    }
                    if (!Schema::hasColumn('sales', 'fbr_scenario_id')) {
                        $table->string('fbr_scenario_id', 20)->nullable()->after('fbr_invoice_no');
                    }
                    if (!Schema::hasColumn('sales', 'fbr_posted_at')) {
                        $table->timestamp('fbr_posted_at')->nullable()->after('fbr_scenario_id');
                    }
                    if (!Schema::hasColumn('sales', 'fbr_environment')) {
                        $table->string('fbr_environment', 20)->default('sandbox')->after('fbr_posted_at');
                    }
                    if (!Schema::hasColumn('sales', 'fbr_qr_code')) {
                        $table->text('fbr_qr_code')->nullable()->after('fbr_environment');
                    }
                    if (!Schema::hasColumn('sales', 'fbr_response')) {
                        $table->longText('fbr_response')->nullable()->after('fbr_qr_code');
                    }
                });
            }

            if (!Schema::hasTable('fbr_invoice_logs')) {
                Schema::create('fbr_invoice_logs', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('sale_id')->nullable()->index();
                    $table->string('environment', 20)->default('sandbox');
                    $table->string('action', 50)->default('post');
                    $table->string('scenario_id', 20)->nullable();
                    $table->longText('request_payload')->nullable();
                    $table->longText('response_payload')->nullable();
                    $table->string('status_code', 50)->nullable();
                    $table->string('status', 50)->nullable();
                    $table->string('fbr_invoice_no', 100)->nullable()->index();
                    $table->text('error_message')->nullable();
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->timestamps();
                });
            }

            if (Schema::hasTable('system_settings')) {
                DB::table('system_settings')->updateOrInsert(
                    ['key' => 'fbr_environment'],
                    ['value' => 'production', 'type' => 'string', 'group' => 'fbr', 'label' => 'FBR Environment', 'created_at' => now(), 'updated_at' => now()]
                );
                DB::table('system_settings')->updateOrInsert(
                    ['key' => 'fbr_production_token'],
                    ['value' => '355f0259-8d4e-355c-8b97-0ce8d0abe39f', 'type' => 'string', 'group' => 'fbr', 'label' => 'FBR Production Security Token', 'created_at' => now(), 'updated_at' => now()]
                );
                DB::table('system_settings')->updateOrInsert(
                    ['key' => 'fbr_production_url'],
                    ['value' => 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata', 'type' => 'string', 'group' => 'fbr', 'label' => 'FBR Production Post URL', 'created_at' => now(), 'updated_at' => now()]
                );
                DB::table('system_settings')->updateOrInsert(
                    ['key' => 'fbr_production_validate_url'],
                    ['value' => 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata', 'type' => 'string', 'group' => 'fbr', 'label' => 'FBR Production Validate URL', 'created_at' => now(), 'updated_at' => now()]
                );
                DB::table('system_settings')->updateOrInsert(
                    ['key' => 'fbr_enabled'],
                    ['value' => '1', 'type' => 'boolean', 'group' => 'fbr', 'label' => 'Enable FBR Integration', 'created_at' => now(), 'updated_at' => now()]
                );
                \Illuminate\Support\Facades\Cache::forget('setting_fbr_environment');
                \Illuminate\Support\Facades\Cache::forget('setting_fbr_production_token');
                \Illuminate\Support\Facades\Cache::forget('setting_fbr_production_url');
                \Illuminate\Support\Facades\Cache::forget('setting_fbr_production_validate_url');
                \Illuminate\Support\Facades\Cache::forget('setting_fbr_enabled');
            }
        } catch (\Throwable $e) {
            Log::error('FBR ensureSchemaExists Error: ' . $e->getMessage());
        }
    }
    /**
     * Get active FBR environment ('sandbox' or 'production')
     */
    public static function getEnvironment(): string
    {
        return SystemSetting::get('fbr_environment', 'production');
    }

    /**
     * Check if FBR integration is enabled
     */
    public static function isEnabled(): bool
    {
        return (bool) SystemSetting::get('fbr_enabled', true);
    }

    /**
     * Get active API endpoint for posting invoice
     */
    public static function getPostUrl(): string
    {
        $env = self::getEnvironment();
        if ($env === 'production') {
            return SystemSetting::get('fbr_production_url', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata');
        }
        return SystemSetting::get('fbr_sandbox_url', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb');
    }

    /**
     * Get active API endpoint for validating invoice
     */
    public static function getValidateUrl(): string
    {
        $env = self::getEnvironment();
        if ($env === 'production') {
            return SystemSetting::get('fbr_production_validate_url', 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata');
        }
        return SystemSetting::get('fbr_sandbox_validate_url', 'https://gw.fbr.gov.pk/di_data/v1/di/validateinvoicedata_sb');
    }

    /**
     * Get active Bearer Token
     */
    public static function getToken(): string
    {
        $env = self::getEnvironment();
        if ($env === 'production') {
            return SystemSetting::get('fbr_production_token', '355f0259-8d4e-355c-8b97-0ce8d0abe39f');
        }
        return SystemSetting::get('fbr_sandbox_token', 'c2bb2ccd-c57d-3f97-9b8b-0fbc84d598d8');
    }

    /**
     * Get seller details from settings or branch
     */
    public static function getSellerDetails(): array
    {
        return [
            'sellerNTNCNIC'      => preg_replace('/[^0-9]/', '', SystemSetting::get('fbr_seller_ntn', '3520224331243')),
            'sellerBusinessName' => SystemSetting::get('fbr_seller_name', 'THREE STARS MEDICAL SUPPLIES'),
            'sellerProvince'     => SystemSetting::get('fbr_seller_province', 'Punjab'),
            'sellerAddress'      => SystemSetting::get('fbr_seller_address', 'M17-M18 SAITH CENTRE SYED MOJ DARYA ROAD LAHORE'),
        ];
    }

    /**
     * Sanitize buyer NTN / CNIC according to FBR standards
     * - Pakistani NTN: 7 digits (if 8 digits, strip check-digit)
     * - Pakistani CNIC: 13 digits
     */
    public static function formatBuyerNtn(?string $rawNtn): string
    {
        if (empty($rawNtn)) return '';
        $clean = preg_replace('/[^0-9]/', '', $rawNtn);
        if (strlen($clean) === 8) {
            return substr($clean, 0, 7);
        }
        if (strlen($clean) === 7) {
            return $clean;
        }
        if (strlen($clean) >= 13) {
            return substr($clean, 0, 13);
        }
        return $clean;
    }

    /**
     * Build the standard FBR Digital Invoicing payload from a Sale model
     */
    public static function buildPayload(Sale $sale, ?string $overrideScenario = null): array
    {
        $sale->loadMissing(['customer_relation', 'items.product.brand']);
        $customer = $sale->customer_relation;
        $seller = self::getSellerDetails();

        // 1. Determine Buyer Information
        $rawNtn = $customer ? ($customer->ntn_no ?? $customer->cnic ?? '') : '';
        $cleanNtn = self::formatBuyerNtn($rawNtn);

        // Buyer Registration Type
        // If buyer has 7-digit NTN and is registered filer:
        $isRegistered = false;
        if (strlen($cleanNtn) === 7 && ($customer?->filer_type === 'Filer' || !empty($customer?->gst_no))) {
            $isRegistered = true;
        }

        $buyerRegType = $isRegistered ? 'Registered' : 'Unregistered';

        // For Unregistered, if no valid NTN/CNIC, use 7-digit NTN if available or CNIC or '0000000000000'
        $buyerNtnCnic = $cleanNtn;
        if (empty($buyerNtnCnic)) {
            $buyerNtnCnic = '0000000000000';
        }

        $buyerName = $customer ? ($customer->business_name ?: $customer->customer_name ?: 'Walk-in Customer') : 'Walk-in Customer';
        $buyerAddress = $customer && !empty($customer->address) ? trim($customer->address) : 'Lahore, Pakistan';
        $buyerProvince = $customer && !empty($customer->city) ? self::mapCityToProvince($customer->city) : $seller['sellerProvince'];

        // 2. Intelligent Scenario Determination & Line Item Mapping
        // Determine whether buyer is end-consumer / retail or standard distributor
        $isRetailCustomer = (!$isRegistered && (empty($rawNtn) || $buyerNtnCnic === '0000000000000'));

        // 3. Invoice Header
        $invoiceDate = $sale->sale_date 
            ? date('Y-m-d', strtotime($sale->sale_date)) 
            : date('Y-m-d', strtotime($sale->created_at ?? now()));

        $defaultHsCode = SystemSetting::get('fbr_default_hs_code', '9018.9090');
        $defaultUom = SystemSetting::get('fbr_default_uom', 'Numbers, pieces, units');

        // 4. Transform Line Items & Detect Scenario Profile
        $items = [];
        $descCounts = [];
        $detectedScenario = null;

        foreach ($sale->items as $index => $item) {
            $product = $item->product;

            // Format HS Code as XXXX.XXXX (e.g. 0101.2100 or 9018.9090)
            $rawHs = !empty($item->hs_code) ? $item->hs_code : (!empty($product?->hs_code) ? $product->hs_code : $defaultHsCode);
            $cleanHs = self::formatHsCode($rawHs, $defaultHsCode);

            $qty = (float)($item->total_pieces > 0 ? $item->total_pieces : ($item->qty > 0 ? $item->qty : 1));
            $unitPrice = (float)($item->price > 0 ? $item->price : 0);
            $lineSubtotal = round($qty * $unitPrice, 2);
            $disc = (float)($item->discount_amount ?? 0);
            $valExclSt = max(0, round($lineSubtotal - $disc, 2));

            $gstRateNum = (float)($item->gst_percent ?? 0);
            $gstAmt = (float)($item->gst_amount ?? round($valExclSt * ($gstRateNum / 100), 2));
            $totalVal = round($valExclSt + $gstAmt, 2);

            // Determine line saleType, rate, SRO schedule and serial
            $itemSaleType = $item->sale_type ?? ($product?->sale_type ?? '');
            $sroSchedule = $item->sro_schedule ?? ($product?->sro_schedule ?? '');
            $sroSerial = $item->sro_item_serial ?? ($product?->sro_item_serial ?? '');
            $retailPrice = (float)($item->retail_price ?? ($product?->retail_price ?? 0.0));
            $rawUom = !empty($item->uom_name) ? trim($item->uom_name) : (trim($product?->unit?->name ?? ''));
            $uomUpper = strtoupper($rawUom);
            if (empty($rawUom)) {
                $uom = $defaultUom;
            } elseif (in_array($uomUpper, ['KG', 'KGS', 'KILOGRAM', 'KILOGRAMS'])) {
                $uom = 'KG';
            } elseif (in_array($uomUpper, ['NO', 'NOS', 'PC', 'PCS', 'PIECE', 'PIECES', 'UNIT', 'UNITS', 'NUMBER', 'NUMBERS'])) {
                $uom = 'Numbers, pieces, units';
            } elseif (in_array($uomUpper, ['L', 'LTR', 'LITRE', 'LITRES', 'LITER', 'LITERS'])) {
                $uom = 'Litres';
            } elseif (in_array($uomUpper, ['M', 'MTR', 'METER', 'METERS', 'METRE', 'METRES'])) {
                $uom = 'Meters';
            } elseif (in_array($uomUpper, ['PACK', 'PACKS', 'PKT', 'PACKET', 'PACKETS'])) {
                $uom = 'Packs';
            } elseif (in_array($uomUpper, ['BOX', 'BOXES'])) {
                $uom = 'Cartons';
            } else {
                // Send exactly what user typed in the UOM field
                $uom = $rawUom;
            }

            if (str_contains(strtolower($itemSaleType), '3rd schedule') || (!empty($retailPrice) && $retailPrice > 0 && $gstRateNum == 18)) {
                // 3rd Schedule Goods
                $saleType = ' 3rd Schedule Goods ';
                $rateStr = '18%';
                $retailPrice = ($retailPrice > 0) ? $retailPrice : $valExclSt;
                $sroSchedule = '';
                $sroSerial = '';
                $lineScenario = $isRetailCustomer ? 'SN027' : 'SN008';
            } elseif ($gstRateNum == 0 && (str_contains(strtolower($itemSaleType), 'zero') || str_contains(strtolower($sroSchedule), 'fifth'))) {
                // Zero-Rated Goods (Fifth Schedule)
                $saleType = 'Goods at zero-rate';
                $rateStr = '0%';
                $sroSchedule = 'FIFTH SCHEDULE';
                $sroSerial = !empty($sroSerial) ? $sroSerial : '10';
                $lineScenario = 'SN007';
            } elseif ($gstRateNum == 0 && (str_contains(strtolower($itemSaleType), 'exempt') || str_contains(strtolower($rateStr ?? ''), 'exempt') || empty($itemSaleType))) {
                // Exempt Goods (Sixth Schedule)
                $saleType = 'Exempt goods';
                $rateStr = 'Exempt';
                $sroSchedule = '6th Schd Table I';
                $sroSerial = !empty($sroSerial) ? $sroSerial : '100';
                $lineScenario = 'SN006';
            } elseif ($gstRateNum > 0 && $gstRateNum < 18) {
                // Reduced Rate Goods (Eighth Schedule)
                $saleType = 'Goods at Reduced Rate';
                $rateStr = rtrim(rtrim(number_format($gstRateNum, 2), '0'), '.') . '%';
                $sroSchedule = 'EIGHTH SCHEDULE Table 1';
                $sroSerial = !empty($sroSerial) ? $sroSerial : '70';
                $lineScenario = $isRetailCustomer ? 'SN028' : 'SN005';
            } elseif ($gstRateNum == 25 || str_contains(strtolower($itemSaleType), '297')) {
                // SRO 297(I)/2023
                $saleType = 'Goods as per SRO.297(|)/2023';
                $rateStr = '25%';
                $sroSchedule = '297(I)/2023-Table-I';
                $sroSerial = !empty($sroSerial) ? $sroSerial : '12';
                $lineScenario = 'SN024';
            } elseif (str_contains(strtolower($itemSaleType), 'service')) {
                // Services
                $saleType = ' Services ';
                $rateStr = '16%';
                $sroSchedule = '';
                $sroSerial = '';
                $lineScenario = 'SN019';
            } elseif (str_contains(strtolower($itemSaleType), 'processing')) {
                // Processing/Conversion
                $saleType = 'Processing/Conversion of Goods';
                $rateStr = '18%';
                $sroSchedule = '';
                $sroSerial = '';
                $lineScenario = 'SN016';
            } else {
                // Standard Rate Goods (18%)
                $saleType = 'Goods at standard rate (default)';
                $rateStr = '18%';
                $sroSchedule = '';
                $sroSerial = '';
                if ($isRegistered) {
                    $lineScenario = 'SN001';
                } else {
                    $lineScenario = $isRetailCustomer ? 'SN026' : 'SN002';
                }
            }

            if (!$detectedScenario) {
                $detectedScenario = $lineScenario;
            }

            $baseDesc = trim(($item->product_name ?: $product?->item_name ?: 'Medical Item') . ' ' . ($product?->brand?->name ?? ''));
            // Ensure unique description per line to avoid FBR "DUPLICATE INVOICE EXISTS" error on multi-line same items
            $lineNum = $index + 1;
            $descKey = $cleanHs . '_' . $baseDesc;
            if (!isset($descCounts[$descKey])) {
                $descCounts[$descKey] = 1;
                $finalDesc = $baseDesc;
            } else {
                $descCounts[$descKey]++;
                $finalDesc = $baseDesc . ' - Line ' . $descCounts[$descKey];
            }

            $items[] = [
                'hsCode'                          => $cleanHs,
                'productDescription'             => $finalDesc,
                'rate'                            => $rateStr,
                'uoM'                             => $uom,
                'quantity'                        => $qty,
                'totalValues'                     => $totalVal,
                'valueSalesExcludingST'           => $valExclSt,
                'fixedNotifiedValueOrRetailPrice' => $retailPrice,
                'salesTaxApplicable'              => $gstAmt,
                'salesTaxWithheldAtSource'        => 0.0,
                'extraTax'                        => '',
                'furtherTax'                      => 0.0,
                'sroScheduleNo'                   => $sroSchedule,
                'fedPayable'                      => 0.0,
                'discount'                        => $disc,
                'saleType'                        => $saleType,
                'sroItemSerialNo'                 => $sroSerial
            ];
        }

        // Final Scenario selection
        $scenarioId = !empty($overrideScenario) ? $overrideScenario : ($detectedScenario ?? ($isRegistered ? 'SN001' : 'SN002'));


        // Fallback item if invoice has no lines
        if (empty($items)) {
            $items[] = [
                'hsCode'                          => $defaultHsCode,
                'productDescription'             => 'Invoice Items',
                'rate'                            => '18%',
                'uoM'                             => $defaultUom,
                'quantity'                        => 1,
                'totalValues'                     => (float)$sale->total_net,
                'valueSalesExcludingST'           => (float)$sale->total_bill_amount,
                'fixedNotifiedValueOrRetailPrice' => 0.0,
                'salesTaxApplicable'              => (float)$sale->total_gst,
                'salesTaxWithheldAtSource'        => 0.0,
                'extraTax'                        => '',
                'furtherTax'                      => 0.0,
                'sroScheduleNo'                   => '',
                'fedPayable'                      => 0.0,
                'discount'                        => (float)$sale->total_extradiscount,
                'saleType'                        => 'Goods at standard rate (default)',
                'sroItemSerialNo'                 => ''
            ];
        }

        return [
            'invoiceType'           => 'Sale Invoice',
            'invoiceDate'           => $invoiceDate,
            'sellerNTNCNIC'         => $seller['sellerNTNCNIC'],
            'sellerBusinessName'    => $seller['sellerBusinessName'],
            'sellerProvince'        => $seller['sellerProvince'],
            'sellerAddress'         => $seller['sellerAddress'],
            'buyerNTNCNIC'          => $buyerNtnCnic,
            'buyerBusinessName'     => $buyerName,
            'buyerProvince'         => $buyerProvince,
            'buyerAddress'          => $buyerAddress,
            'buyerRegistrationType' => $buyerRegType,
            'invoiceRefNo'          => '',
            'scenarioId'            => $scenarioId,
            'items'                 => $items
        ];
    }

    /**
     * Validate Invoice data with FBR without final posting
     */
    public static function validateInvoice(Sale $sale, ?string $overrideScenario = null): array
    {
        $payload = self::buildPayload($sale, $overrideScenario);
        $url = self::getValidateUrl();
        $token = self::getToken();

        return self::executeRequest($url, $token, $payload, 'validate', $sale->id, $payload['scenarioId']);
    }

    /**
     * Submit/Post invoice directly to FBR Digital Invoicing API
     */
    public static function postInvoice(Sale $sale, ?string $overrideScenario = null): array
    {
        self::ensureSchemaExists();

        // 1. Check if sale is in posted status in ERP
        if ($sale->sale_status !== 'post') {
            return [
                'success' => false,
                'message' => 'Sale invoice must be POSTED in the ERP before sending to FBR.'
            ];
        }

        // 2. Check if already posted to FBR
        if (!empty($sale->fbr_status) && $sale->fbr_status === 'posted' && !empty($sale->fbr_invoice_no)) {
            return [
                'success'        => true,
                'already_posted' => true,
                'fbr_invoice_no' => $sale->fbr_invoice_no,
                'message'        => 'This invoice is already posted to FBR: ' . $sale->fbr_invoice_no
            ];
        }

        $payload = self::buildPayload($sale, $overrideScenario);
        $url = self::getPostUrl();
        $token = self::getToken();

        $result = self::executeRequest($url, $token, $payload, 'post', $sale->id, $payload['scenarioId']);

        if ($result['success']) {
            $fbrInvoiceNo = $result['fbr_invoice_no'] ?? ($result['response_data']['invoiceNumber'] ?? null);

            $updateData = [
                'fbr_status'      => 'posted',
                'fbr_invoice_no'  => $fbrInvoiceNo,
                'fbr_scenario_id' => $payload['scenarioId'],
                'fbr_posted_at'   => now(),
                'fbr_environment' => self::getEnvironment(),
                'fbr_qr_code'     => $fbrInvoiceNo,
                'fbr_response'    => json_encode($result['response_data'] ?? [])
            ];

            try {
                $sale->update($updateData);
            } catch (\Throwable $ex) {
                Log::warning('FBR update failed, running ensureSchemaExists and retrying: ' . $ex->getMessage());
                self::ensureSchemaExists();
                try {
                    DB::table('sales')->where('id', $sale->id)->update($updateData);
                } catch (\Throwable $retryEx) {
                    Log::error('FBR update retry failed: ' . $retryEx->getMessage());
                }
            }
        } else {
            try {
                $sale->update([
                    'fbr_status'   => 'failed',
                    'fbr_response' => json_encode($result['response_data'] ?? ['error' => $result['message']])
                ]);
            } catch (\Throwable $ex) {
                self::ensureSchemaExists();
                try {
                    DB::table('sales')->where('id', $sale->id)->update([
                        'fbr_status'   => 'failed',
                        'fbr_response' => json_encode($result['response_data'] ?? ['error' => $result['message']])
                    ]);
                } catch (\Throwable $retryEx) {}
            }
        }

        return $result;
    }

    /**
     * Helper to execute cURL to FBR
     */
    private static function executeRequest(string $url, string $token, array $payload, string $action, ?int $saleId = null, ?string $scenarioId = null): array
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $environment = self::getEnvironment();

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($responseRaw, true) ?? [];

        // Log to database
        $statusCode = null;
        $statusStr = null;
        $fbrInvoiceNo = null;
        $errorMessage = null;

        if (!empty($responseData['validationResponse'])) {
            $statusCode = $responseData['validationResponse']['statusCode'] ?? null;
            $statusStr = $responseData['validationResponse']['status'] ?? null;
            $errorMessage = $responseData['validationResponse']['error'] ?? null;
        }

        if (!empty($responseData['invoiceNumber'])) {
            $fbrInvoiceNo = $responseData['invoiceNumber'];
        }

        if (!empty($curlError)) {
            $errorMessage = 'Network Error: ' . $curlError;
            $statusStr = 'Network Error';
        }

        // Check if successful
        $isSuccess = ($httpCode === 200 && ($statusStr === 'Valid' || $statusCode === '00' || !empty($fbrInvoiceNo)));

        try {
            DB::table('fbr_invoice_logs')->insert([
                'sale_id'          => $saleId,
                'environment'      => $environment,
                'action'           => $action,
                'scenario_id'      => $scenarioId,
                'request_payload'  => $jsonPayload,
                'response_payload' => $responseRaw,
                'status_code'      => $statusCode ?? (string)$httpCode,
                'status'           => $statusStr ?? ($isSuccess ? 'Valid' : 'Invalid'),
                'fbr_invoice_no'   => $fbrInvoiceNo,
                'error_message'    => $errorMessage,
                'created_by'       => auth()->id() ?? 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('FBR Log Insert Failed: ' . $e->getMessage());
        }

        if ($isSuccess) {
            return [
                'success'        => true,
                'message'        => 'Successfully ' . ($action === 'post' ? 'posted to' : 'validated with') . ' FBR Digital Invoicing Gateway.',
                'fbr_invoice_no' => $fbrInvoiceNo,
                'dated'          => $responseData['dated'] ?? now()->toDateTimeString(),
                'response_data'  => $responseData,
                'payload'        => $payload
            ];
        }

        // Format clean error message
        $finalError = $errorMessage;
        if (empty($finalError)) {
            if (!empty($responseData['validationResponse']['invoiceStatuses'][0]['error'])) {
                $finalError = $responseData['validationResponse']['invoiceStatuses'][0]['error'];
            } elseif ($httpCode === 401) {
                $finalError = 'Unauthorized: Token or Seller NTN mismatch with FBR.';
            } else {
                $finalError = 'FBR Rejected: ' . ($responseRaw ?: 'HTTP Error ' . $httpCode);
            }
        }

        return [
            'success'        => false,
            'message'        => $finalError,
            'http_code'      => $httpCode,
            'response_data'  => $responseData,
            'payload'        => $payload
        ];
    }

    /**
     * Format HS code to 8 digits with dot XXXX.XXXX
     */
    private static function formatHsCode(?string $code, string $default = '9018.9090'): string
    {
        if (empty($code)) return $default;
        $clean = preg_replace('/[^0-9]/', '', $code);
        if (strlen($clean) >= 8) {
            return substr($clean, 0, 4) . '.' . substr($clean, 4, 4);
        }
        if (str_contains($code, '.')) {
            return $code;
        }
        return $default;
    }

    /**
     * Map Pakistani city to province
     */
    private static function mapCityToProvince(?string $city): string
    {
        if (empty($city)) return 'Punjab';
        $city = strtolower(trim($city));
        
        $sindh = ['karachi', 'hyderabad', 'sukkur', 'larkana', 'mirpurkhas', 'nawabshah'];
        $kpk = ['peshawar', 'mardan', 'abbottabad', 'swat', 'bannu', 'kohat', 'dera ismail khan'];
        $balochistan = ['quetta', 'gwadar', 'turbat', 'khuzdar', 'sibi'];
        $isb = ['islamabad'];

        if (in_array($city, $sindh)) return 'Sindh';
        if (in_array($city, $kpk)) return 'Khyber Pakhtunkhwa';
        if (in_array($city, $balochistan)) return 'Balochistan';
        if (in_array($city, $isb)) return 'Islamabad Capital Territory';

        return 'Punjab'; // Default
    }

    /**
     * Get definitions of all 14 FBR Sandbox Scenarios required for production authorization
     */
    public static function getAllSandboxScenarios(): array
    {
        return [
            'SN001' => [
                'title'     => 'Standard Rate Goods to Registered Buyers',
                'saleType'  => 'Goods at standard rate (default)',
                'rate'      => '18%',
                'val'       => 1000.0,
                'tax'       => 180.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '2046004',
                'buyerName' => 'FERTILIZER MANUFACTURERS NEW',
                'buyerReg'  => 'Registered',
            ],
            'SN002' => [
                'title'     => 'Standard Rate Goods to Unregistered Buyers',
                'saleType'  => 'Goods at standard rate (default)',
                'rate'      => '18%',
                'val'       => 1000.0,
                'tax'       => 180.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN005' => [
                'title'     => 'Reduced Rate Goods (Eighth Schedule)',
                'saleType'  => 'Goods at Reduced Rate',
                'rate'      => '1%',
                'val'       => 1000.0,
                'tax'       => 10.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => 'EIGHTH SCHEDULE Table 1',
                'serial'    => '70',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN006' => [
                'title'     => 'Exempt Goods (Sixth Schedule)',
                'saleType'  => 'Exempt goods',
                'rate'      => 'Exempt',
                'val'       => 1000.0,
                'tax'       => 0.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => '6th Schd Table I',
                'serial'    => '100',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN007' => [
                'title'     => 'Zero-Rated Goods (Fifth Schedule)',
                'saleType'  => 'Goods at zero-rate',
                'rate'      => '0%',
                'val'       => 1000.0,
                'tax'       => 0.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => 'FIFTH SCHEDULE',
                'serial'    => '10',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN008' => [
                'title'     => 'Sale of 3rd Schedule Goods',
                'saleType'  => ' 3rd Schedule Goods ',
                'rate'      => '18%',
                'val'       => 1000.0,
                'tax'       => 180.0,
                'retail'    => 1000.0,
                'fed'       => 0.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN016' => [
                'title'     => 'Processing / Conversion of Goods',
                'saleType'  => 'Processing/Conversion of Goods',
                'rate'      => '18%',
                'val'       => 1000.0,
                'tax'       => 180.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN017' => [
                'title'     => 'Goods Subject to FED in ST Mode',
                'saleType'  => 'Goods (FED in ST Mode)',
                'rate'      => '17%',
                'val'       => 1000.0,
                'tax'       => 170.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '2710.1240',
                'uom'       => 'KG',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
                'invoiceDate' => '2025-06-15',
            ],
            'SN018' => [
                'title'     => 'Services Where FED Is Charged in ST Mode',
                'saleType'  => ' Services (FED in ST Mode) ',
                'rate'      => '16%',
                'val'       => 1000.0,
                'tax'       => 160.0,
                'retail'    => 0.0,
                'fed'       => 50.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN019' => [
                'title'     => 'Services (as per ICT Ordinance)',
                'saleType'  => ' Services ',
                'rate'      => '16%',
                'val'       => 1000.0,
                'tax'       => 160.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN024' => [
                'title'     => 'Goods Listed in SRO 297(I)/2023',
                'saleType'  => 'Goods as per SRO.297(|)/2023',
                'rate'      => '25%',
                'val'       => 1000.0,
                'tax'       => 250.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => '297(I)/2023-Table-I',
                'serial'    => '12',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN025' => [
                'title'     => 'Drugs Sold at Fixed ST Rate (Serial 81)',
                'saleType'  => 'Non-Adjustable Supplies',
                'rate'      => '0%',
                'val'       => 1000.0,
                'tax'       => 0.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => 'Eighth Schedule Table 1',
                'serial'    => '81',
                'hs'        => '3004.9099',
                'uom'       => 'KG',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN026' => [
                'title'     => 'Standard Rate Goods to End Consumers by Retailers',
                'saleType'  => 'Goods at standard rate (default)',
                'rate'      => '18%',
                'val'       => 1000.0,
                'tax'       => 180.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN027' => [
                'title'     => '3rd Schedule Goods to End Consumers by Retailers',
                'saleType'  => ' 3rd Schedule Goods ',
                'rate'      => '18%',
                'val'       => 1000.0,
                'tax'       => 180.0,
                'retail'    => 1000.0,
                'fed'       => 0.0,
                'sro'       => '',
                'serial'    => '',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
            'SN028' => [
                'title'     => 'Reduced Rate Goods to End Consumers by Retailers',
                'saleType'  => 'Goods at Reduced Rate',
                'rate'      => '1%',
                'val'       => 1000.0,
                'tax'       => 10.0,
                'retail'    => 0.0,
                'fed'       => 0.0,
                'sro'       => 'EIGHTH SCHEDULE Table 1',
                'serial'    => '70',
                'hs'        => '9018.9090',
                'uom'       => 'Numbers, pieces, units',
                'buyerNtn'  => '0000000000000',
                'buyerName' => 'Walk-in Consumer',
                'buyerReg'  => 'Unregistered',
            ],
        ];
    }

    /**
     * Run all 14 Sandbox Scenarios against FBR gateway (Validate or Post)
     */
    public static function runAllSandboxScenarios(bool $validateOnly = false): array
    {
        self::ensureSchemaExists();

        $scenarios = self::getAllSandboxScenarios();
        $seller = self::getSellerDetails();
        $token = self::getToken();
        $url = $validateOnly ? self::getValidateUrl() : self::getPostUrl();

        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($scenarios as $scId => $item) {
            $payload = [
                'invoiceType'           => 'Sale Invoice',
                'invoiceDate'           => $item['invoiceDate'] ?? date('Y-m-d'),
                'sellerNTNCNIC'         => $seller['sellerNTNCNIC'],
                'sellerBusinessName'    => $seller['sellerBusinessName'],
                'sellerProvince'        => $seller['sellerProvince'],
                'sellerAddress'         => $seller['sellerAddress'],
                'buyerNTNCNIC'          => $item['buyerNtn'],
                'buyerBusinessName'     => $item['buyerName'],
                'buyerProvince'         => $seller['sellerProvince'],
                'buyerAddress'          => 'Lahore, Pakistan',
                'buyerRegistrationType' => $item['buyerReg'],
                'invoiceRefNo'          => '',
                'scenarioId'            => $scId,
                'items'                 => [
                    [
                        'hsCode'                          => $item['hs'],
                        'productDescription'             => 'Sample - ' . $scId . ' (' . $item['title'] . ')',
                        'rate'                            => $item['rate'],
                        'uoM'                             => $item['uom'],
                        'quantity'                        => 1,
                        'totalValues'                     => $item['val'] + $item['tax'] + $item['fed'],
                        'valueSalesExcludingST'           => $item['val'],
                        'fixedNotifiedValueOrRetailPrice' => $item['retail'],
                        'salesTaxApplicable'              => $item['tax'],
                        'salesTaxWithheldAtSource'        => 0.0,
                        'extraTax'                        => '',
                        'furtherTax'                      => 0.0,
                        'sroScheduleNo'                   => $item['sro'],
                        'fedPayable'                      => $item['fed'],
                        'discount'                        => 0.0,
                        'saleType'                        => $item['saleType'],
                        'sroItemSerialNo'                 => $item['serial']
                    ]
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $json = json_decode($resp, true);
            $status = $json['validationResponse']['status'] ?? 'HTTP ' . $httpCode;
            $fbrInv = $json['invoiceNumber'] ?? ($json['validationResponse']['invoiceStatuses'][0]['invoiceNo'] ?? null);
            $err = $json['validationResponse']['error'] ?? '';
            if (!$err && !empty($json['validationResponse']['invoiceStatuses'][0]['error'])) {
                $err = $json['validationResponse']['invoiceStatuses'][0]['error'];
            }

            $isSuccess = ($status === 'Valid');
            if ($isSuccess) {
                $successful++;
            } else {
                $failed++;
            }

            // Log attempt
            try {
                \Illuminate\Support\Facades\DB::table('fbr_invoice_logs')->insert([
                    'sale_id'          => 1,
                    'action'           => $validateOnly ? 'validate' : 'post',
                    'environment'      => 'sandbox',
                    'scenario_id'      => $scId,
                    'request_payload'  => json_encode($payload),
                    'response_payload' => $resp,
                    'http_code'        => $httpCode,
                    'status_code'      => $json['validationResponse']['statusCode'] ?? (string)$httpCode,
                    'status'           => $status,
                    'fbr_invoice_no'   => $fbrInv,
                    'error_message'    => $err,
                    'created_by'       => auth()->id() ?? 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            } catch (\Throwable $e) {
                // ignore log error
            }

            $results[$scId] = [
                'scenario_id'    => $scId,
                'title'          => $item['title'],
                'sale_type'      => $item['saleType'],
                'rate'           => $item['rate'],
                'sro'            => $item['sro'],
                'serial'         => $item['serial'],
                'status'         => $status,
                'is_success'     => $isSuccess,
                'fbr_invoice_no' => $fbrInv,
                'error'          => $err,
            ];

            usleep(250000);
        }

        return [
            'total'       => count($scenarios),
            'successful'  => $successful,
            'failed'      => $failed,
            'is_all_pass' => ($successful === count($scenarios)),
            'scenarios'   => $results
        ];
    }

    /**
     * Get list of all FBR Digital Invoicing scenarios for dropdowns
     */
    public static function getScenarios(): array
    {
        return [
            'SN001' => 'SN001: Sale of Standard Rate Goods to Registered Buyers',
            'SN002' => 'SN002: Sale of Standard Rate Goods to Unregistered Buyers',
            'SN005' => 'SN005: Sale of Reduced Rate Goods (Eighth Schedule)',
            'SN006' => 'SN006: Sale of Exempt Goods (Sixth Schedule)',
            'SN007' => 'SN007: Sale of Zero-Rated Goods (Fifth Schedule)',
            'SN008' => 'SN008: Sale of 3rd Schedule Goods',
            'SN016' => 'SN016: Processing / Conversion of Goods',
            'SN018' => 'SN018: Services Where FED Is Charged in ST Mode',
            'SN019' => 'SN019: Services (as per ICT Ordinance)',
            'SN024' => 'SN024: Sale Of Goods Listed in SRO 297(I)/2023',
            'SN025' => 'SN025: Drugs Sold at Fixed ST Rate Under Serial 81 Of Eighth Schedule Table 1',
            'SN026' => 'SN026: Sale Of Goods at Standard Rate to End Consumers by Retailers',
            'SN027' => 'SN027: Sale Of 3rd Schedule Goods to End Consumers by Retailers',
            'SN028' => 'SN028: Sale Of Goods at Reduced Rate to End Consumers by Retailers',
        ];
    }
}

