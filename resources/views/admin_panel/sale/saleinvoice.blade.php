@php
if (!function_exists('numberToWords')) {
    function numberToWords($num) {
        if (!$num || $num == 0) return "ZERO RUPEES ONLY";
        $a = array('', 'ONE ', 'TWO ', 'THREE ', 'FOUR ', 'FIVE ', 'SIX ', 'SEVEN ', 'EIGHT ', 'NINE ', 'TEN ', 'ELEVEN ', 'TWELVE ', 'THIRTEEN ', 'FOURTEEN ', 'FIFTEEN ', 'SIXTEEN ', 'SEVENTEEN ', 'EIGHTEEN ', 'NINETEEN ');
        $b = array('', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY');
        $num = (string) round($num);
        $len = strlen($num);
        if ($len > 9) return 'OVERFLOW';
        $n = str_pad($num, 9, '0', STR_PAD_LEFT);
        $parts = array(
            substr($n, 0, 2), substr($n, 2, 2), substr($n, 4, 2), substr($n, 6, 1), substr($n, 7, 2),
        );
        $str = '';
        if ((int)$parts[0] > 0) $str .= ((int)$parts[0] < 20 ? $a[(int)$parts[0]] : $b[(int)$parts[0][0]] . ' ' . $a[(int)$parts[0][1]]) . 'CRORE ';
        if ((int)$parts[1] > 0) $str .= ((int)$parts[1] < 20 ? $a[(int)$parts[1]] : $b[(int)$parts[1][0]] . ' ' . $a[(int)$parts[1][1]]) . 'LAKH ';
        if ((int)$parts[2] > 0) $str .= ((int)$parts[2] < 20 ? $a[(int)$parts[2]] : $b[(int)$parts[2][0]] . ' ' . $a[(int)$parts[2][1]]) . 'THOUSAND ';
        if ((int)$parts[3] > 0) $str .= $a[(int)$parts[3]] . 'HUNDRED ';
        if ((int)$parts[4] > 0) { if ($str != '') $str .= 'AND '; $str .= ((int)$parts[4] < 20 ? $a[(int)$parts[4]] : $b[(int)$parts[4][0]] . ' ' . $a[(int)$parts[4][1]]); }
        return trim($str) . ' RUPEES ONLY';
    }
}

$officer = optional($sale->customer_relation)->salesOfficer ?? optional($sale->employee_relation);
$officerName = $officer->name ?? $officer->first_name ?? null;
if (!$officerName && $sale->employee_id) {
    $emp = \App\Models\Hr\Employee::find($sale->employee_id);
    $officerName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : null;
}
$logoPath = public_path('assets/images/three_star_logo.jpg');
$logoSrc = file_exists($logoPath)
    ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
    : asset('assets/images/three_star_logo.jpg');

$fbrLogoPath = public_path('assets/images/fbr_logo.png');
$fbrLogoSrc = file_exists($fbrLogoPath)
    ? 'data:image/png;base64,' . base64_encode(file_get_contents($fbrLogoPath))
    : asset('assets/images/fbr_logo.png');

$fbrInvoiceNumber = !empty($sale->fbr_invoice_no)
    ? trim($sale->fbr_invoice_no)
    : (!empty($sale->fbr_qr_code) ? trim($sale->fbr_qr_code) : null);

$fbrQrBase64 = null;
if (!empty($fbrInvoiceNumber)) {
    try {
        $dns2d = new \Milon\Barcode\DNS2D();
        $dns2d->setStorPath(storage_path('framework/cache'));
        $fbrQrBase64 = 'data:image/png;base64,' . $dns2d->getBarcodePNG($fbrInvoiceNumber, 'QRCODE', 4, 4);
    } catch (\Throwable $e) {
        $fbrQrBase64 = null;
    }
}
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Tax Invoice - {{ $sale->invoice_no ?? $sale->id }}</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; background: #ccc; font-size: 11px; color: #000; }

  .action-bar { background: #2c3e50; padding: 10px 20px; display: flex; gap: 10px; justify-content: flex-end; }
  .btn { padding: 8px 20px; font-size: 13px; font-weight: bold; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 6px; color: #fff; text-decoration: none; }
  .btn-print { background: #27ae60; } .btn-print:hover { background: #1e8449; }
  .btn-pdf { background: #c0392b; } .btn-pdf:hover { background: #a93226; }
  .btn-back { background: #7f8c8d; } .btn-back:hover { background: #95a5a6; }
  .btn svg { width: 15px; height: 15px; fill: white; }

  .a4-wrapper { width: 794px; min-height: 1123px; margin: 20px auto; background: #fff; padding: 26px 30px; box-shadow: 0 2px 14px rgba(0,0,0,0.25); position: relative; }

  .header { display: flex; align-items: center; gap: 18px; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 8px; }
  .logo-box { flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
  .invoice-logo { height: 75px; width: auto; max-width: 130px; object-fit: contain; display: block; }
  .company-info h1 { font-family: 'Palatino Linotype', Palatino, serif; font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #000; line-height: 1.1; }
  .company-info .addr { font-size: 10px; margin-top: 4px; line-height: 1.5; }

  .doc-title { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin: 8px 0 6px; letter-spacing: 1px; }

  /* Party + Meta row */
  .party-meta { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 4px; }
  .party-block { font-size: 10.5px; line-height: 1.85; flex: 1; }
  .prow { display: flex; gap: 4px; }
  .plbl { font-weight: bold; min-width: 68px; }
  .barcode-right { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 2px; }
  .barcode-num { font-size: 8.5px; letter-spacing: 0.5px; text-align: center; margin-top: 1px; font-weight: bold; }
  .fbr-footer-wrap { margin-top: 18px; display: flex; justify-content: center; width: 100%; }
  .fbr-box-wrapper { text-align: center; display: flex; flex-direction: column; align-items: center; }
  .fbr-box-title { font-size: 9.5px; font-weight: bold; color: #000; margin-bottom: 6px; white-space: nowrap; letter-spacing: 0.2px; text-align: center; }
  .fbr-box-num { font-size: 9.5px; font-weight: bold; font-family: Arial, sans-serif; margin-left: 6px; }
  .fbr-box-badges { display: flex; align-items: center; justify-content: center; gap: 10px; }
  .fbr-qr-img { width: 72px; height: 72px; display: block; image-rendering: pixelated; }
  .fbr-badge-img { height: 72px; width: auto; max-width: 90px; object-fit: contain; display: block; }

  .meta-strip { display: flex; justify-content: space-between; align-items: center; font-size: 10.5px; border-top: 1px solid #555; border-bottom: 1px solid #555; padding: 3px 3px; margin-bottom: 0; }

  table.items { width: 100%; border-collapse: collapse; font-size: 10px; border: 1px solid #000; margin-top: 4px; }
  table.items th { background: #b8b8b8; border: 1px solid #000; padding: 5px 3px; text-align: center; font-weight: bold; color: #000; font-size: 10px; line-height: 1.2; }
  table.items th.la { text-align: left; }
  table.items th.ra { text-align: right; }
  table.items td { border: 1px solid #000; padding: 4px 4px; color: #000; vertical-align: middle; }
  table.items td.c { text-align: center; }
  table.items td.r { text-align: right; }

  .bottom-section { display: flex; border: 0.5px solid #ccc; border-top: none; }
  .left-bottom { flex: 1; border-right: 0.5px solid #ccc; }
  .remarks-row { padding: 3px 6px; font-size: 10.5px; font-weight: bold; border-bottom: 0.5px solid #ccc; min-height: 20px; }
  .inwords-row { padding: 3px 6px; font-size: 10px; display: flex; gap: 4px; flex-wrap: wrap; }
  .right-totals { min-width: 220px; font-size: 10.5px; }
  .totals-table { width: 100%; border-collapse: collapse; }
  .totals-table td { padding: 2px 6px; font-size: 10.5px; }
  .totals-table td.lbl { white-space: nowrap; }
  .totals-table td.val { text-align: right; font-weight: bold; }
  .totals-table .net-row td { font-weight: bold; background: #f5f5f5; }
  .totals-table .divider td { border-top: 0.5px solid #aaa; }

  .footer-dates { font-size: 10px; line-height: 1.8; margin-top: 10px; display: flex; gap: 30px; }
  .approval-row { display: flex; gap: 50px; margin-top: 18px; font-size: 10.5px; }
  .approval-field { display: flex; flex-direction: column; gap: 3px; }
  .approval-line { border-bottom: 1px solid #000; width: 190px; margin-top: 2px; }

  @media print {
    body { background: #fff; }
    .action-bar { display: none !important; }
    .a4-wrapper { box-shadow: none; margin: 0; width: 100%; padding: 12mm 14mm; min-height: auto; }
    @page { size: A4; margin: 0; }
  }
</style>
</head>
<body>

<div class="action-bar">
  <a href="{{ route('sale.index') }}" class="btn btn-back">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    Back
  </a>

  @if($sale->sale_status === 'post')
    @if($sale->fbr_status === 'posted')
      <span class="btn" style="background:#059669; cursor:default;" title="FBR Verified: {{ $sale->fbr_invoice_no }}">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
        FBR Posted: {{ $sale->fbr_invoice_no }}
      </span>
    @else
      <button type="button" class="btn fbr-post-btn" data-id="{{ $sale->id }}" style="background:#d97706; cursor:pointer;" title="Post this invoice to FBR Digital Invoicing">
        <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
        Post to FBR
      </button>
    @endif
  @endif

  <button class="btn btn-print" onclick="window.print()">
    <svg viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
    Print
  </button>
  <button class="btn btn-pdf" onclick="downloadPDF()">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9.5 8.5c0 .83-.67 1.5-1.5 1.5H7v2H5.5V9H8c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V9H13c.83 0 1.5.67 1.5 1.5v3zm4-3H17v1h1.5V13H17v2h-1.5V9h3v1.5zM7 10.5h1v1H7v-1zM11 10.5h1v3h-1v-3z"/></svg>
    Download PDF
  </button>
</div>

<div class="a4-wrapper" id="invoice">

  <!-- HEADER -->
  <div class="header">
    <div class="logo-box">
      <img src="{{ $logoSrc }}" alt="Three Stars Medical Supplies" class="invoice-logo">
    </div>
    <div class="company-info">
      <h1>THREE STARS MEDICAL SUPPLIES</h1>
      <div class="addr">
        <strong>{{ $sale->branch->name ?? 'Head Office' }} :</strong> {{ $sale->branch->address ?? 'M17-18 Mezanine Floor Seth Centre 10 Syed Mouj Darya Road (Edward Road) Lahore.' }}<br>
        <strong>Phone :</strong> {{ $sale->branch->number ?? '0092-42-37353433' }} &nbsp; | &nbsp; <strong>GST Invoice</strong>
      </div>
    </div>
  </div>

  <!-- TITLE -->
  <div class="doc-title">Sales Tax Invoice</div>

  <!-- PARTY + BARCODE -->
  <div class="party-meta">
    <div class="party-block">
      <div class="prow"><span class="plbl">To :</span><span><strong>{{ optional($sale->customer_relation)->customer_name ?? 'WALKING CUSTOMER' }}</strong></span></div>
      <div class="prow"><span class="plbl">Address :</span><span>{{ optional($sale->customer_relation)->address ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">Contact :</span><span>{{ optional($sale->customer_relation)->mobile ?? optional($sale->customer_relation)->phone ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">NTN No :</span><span>{{ trim(optional($sale->customer_relation)->ntn_no ?? '') !== '' ? optional($sale->customer_relation)->ntn_no : '-' }}</span></div>
      @if($officerName)
      <div class="prow"><span class="plbl">Sales Officer :</span><span>{{ $officerName }}</span></div>
      @endif
    </div>
    <div class="party-block" style="text-align:right;">
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Invoice # :</span><span><strong>{{ $sale->invoice_no ?? $sale->id }}</strong></span></div>
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Sale Date :</span><span>{{ $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') : $sale->created_at->format('d/m/Y') }}</span></div>
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Posted :</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
      @if($sale->reference || $sale->sale_order_no)
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Ref / SO # :</span><span>{{ $sale->reference ?? $sale->sale_order_no }}</span></div>
      @endif
      @if($sale->order_no)
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Order # :</span><span>{{ $sale->order_no }}</span></div>
      @endif
    </div>
    <div class="barcode-right">
      <div style="display:flex;justify-content:center;padding:2px 0;">
        {!! DNS1D::getBarcodeHTML($sale->invoice_no ?? (string)$sale->id, 'C128', 1, 35) !!}
      </div>
      <div class="barcode-num">{{ $sale->invoice_no ?? $sale->id }}</div>
    </div>
  </div>

  <!-- META STRIP -->
  <div class="meta-strip">
    <span><strong>Status :</strong> {{ strtoupper($sale->sale_status ?? 'Draft') }}</span>
    <span><strong>Customer ID :</strong> {{ optional($sale->customer_relation)->customer_id ?? '-' }}</span>
    <span>Page 1 of 1</span>
  </div>

  <!-- ITEMS TABLE -->
  @php 
    $grandQty = 0;
    $grandValExcl = 0;
    $grandSalesTax = 0;
    $grandFTax = 0;
    $grandValIncl = 0;
    $grandDiscount = 0;
  @endphp
  <table class="items">
    <thead>
      <tr>
        <th style="width:4%;">Sr#</th>
        <th class="la" style="width:20%;">Description</th>
        <th style="width:9%;">HS Code</th>
        <th style="width:6%;">Uom</th>
        <th class="ra" style="width:6%;">Qty</th>
        <th class="ra" style="width:7%;">Rate</th>
        <th class="ra" style="width:11%;">Value Excluding</th>
        <th style="width:7%;">GST Rate</th>
        <th class="ra" style="width:9%;">Sales Tax</th>
        <th style="width:6%;">F.Tax Rate</th>
        <th class="ra" style="width:6%;">F.Tax Value</th>
        <th class="ra" style="width:9%;">Value Including</th>
      </tr>
    </thead>
    <tbody>
      @foreach($saleItems as $index => $item)
      @php
        $qty = (float)($item['total_pieces'] > 0 ? $item['total_pieces'] : ($item['qty'] > 0 ? $item['qty'] : 1));
        $rate = (float)($item['price'] ?? 0);
        $lineSubtotal = round($qty * $rate, 2);
        $disc = (float)($item['discount_amount'] ?? 0);
        $valExcl = max(0, round($lineSubtotal - $disc, 2));

        $gstPct = (float)($item['gst_percent'] ?? $item['gst'] ?? 0);
        $gstAmt = (float)($item['gst_amount'] ?? 0);
        if ($gstAmt == 0 && $gstPct > 0) {
            $gstAmt = round($valExcl * ($gstPct / 100), 2);
        }
        // Fallback if item level GST wasn't stored but sale has total GST
        if ($gstPct == 0 && $gstAmt == 0 && (float)($sale->total_gst ?? 0) > 0 && (float)($sale->total_bill_amount ?? 0) > 0) {
            $effectiveRate = round(((float)$sale->total_gst / (float)$sale->total_bill_amount) * 100, 1);
            $gstPct = $effectiveRate;
            $gstAmt = round($valExcl * ($gstPct / 100), 2);
        }

        $fTaxPct = (float)($item['further_tax_rate'] ?? 0);
        $fTaxAmt = (float)($item['further_tax_amount'] ?? 0);
        if ($fTaxAmt == 0 && $fTaxPct > 0) {
            $fTaxAmt = round($valExcl * ($fTaxPct / 100), 2);
        }

        $valIncl = round($valExcl + $gstAmt + $fTaxAmt, 2);

        $grandQty += $qty;
        $grandValExcl += $valExcl;
        $grandSalesTax += $gstAmt;
        $grandFTax += $fTaxAmt;
        $grandValIncl += $valIncl;
        $grandDiscount += $disc;

        $uomDisp = (!empty($item['uom_name']) && $item['uom_name'] !== '-') 
            ? $item['uom_name'] 
            : (($item['unit'] ?? '') ?: 'Piece');
      @endphp
      <tr>
        <td class="c">{{ $index + 1 }}</td>
        <td>
          <strong>{{ !empty($item['category_name']) ? $item['category_name'] : $item['item_name'] }}</strong>
        </td>
        <td class="c">{{ !empty($item['hs_code']) ? $item['hs_code'] : '-' }}</td>
        <td class="c">{{ $uomDisp }}</td>
        <td class="r"><strong>{{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }}</strong></td>
        <td class="r">{{ number_format($rate, 2) }}</td>
        <td class="r"><strong>{{ number_format($valExcl, 2) }}</strong></td>
        <td class="c">{{ $gstPct > 0 ? rtrim(rtrim(number_format($gstPct, 2), '0'), '.') . '%' : '0%' }}</td>
        <td class="r">{{ $gstAmt > 0 ? number_format($gstAmt, 2) : '0.00' }}</td>
        <td class="c">{{ $fTaxPct > 0 ? rtrim(rtrim(number_format($fTaxPct, 2), '0'), '.') . '%' : '' }}</td>
        <td class="r">{{ $fTaxAmt > 0 ? number_format($fTaxAmt, 2) : '' }}</td>
        <td class="r"><strong>{{ number_format($valIncl, 2) }}</strong></td>
      </tr>
      @endforeach
      <!-- Grand Total Row -->
      <tr style="background:#e8e8e8; font-weight:bold; border-top:1.5px solid #000;">
        <td colspan="4" class="r" style="font-size:10px; padding:5px 6px;">Total Items: {{ count($saleItems) }}</td>
        <td class="r" style="font-weight:bold; font-size:10.5px;">{{ rtrim(rtrim(number_format($grandQty, 2), '0'), '.') }}</td>
        <td></td>
        <td class="r" style="font-weight:bold; font-size:10.5px;">{{ number_format($grandValExcl, 2) }}</td>
        <td></td>
        <td class="r" style="font-weight:bold; font-size:10.5px;">{{ number_format($grandSalesTax, 2) }}</td>
        <td></td>
        <td class="r" style="font-weight:bold; font-size:10.5px;">{{ $grandFTax > 0 ? number_format($grandFTax, 2) : '' }}</td>
        <td class="r" style="font-weight:bold; font-size:11px;">{{ number_format($grandValIncl, 2) }}</td>
      </tr>
    </tbody>
  </table>

  <!-- BOTTOM: IN WORDS -->
  <div class="bottom-section" style="border: 1px solid #000; border-top: none;">
    <div class="left-bottom" style="width: 100%; border-right: none;">
      <div class="inwords-row" style="padding: 6px 8px; font-size: 10.5px; display: flex; gap: 4px; flex-wrap: wrap;">
        <strong>In Words :</strong>
        <span>{{ !empty($sale->total_amount_Words) ? $sale->total_amount_Words : numberToWords($sale->total_net) }}</span>
      </div>
    </div>
  </div>

  <!-- SYSTEM GENERATED NOTE -->
  <div style="margin-top: 10px; font-size: 10px; font-style: italic; color: #333;">
    This is system generated invoice
  </div>

  <!-- FBR DIGITAL INVOICING FOOTER -->
  @if(!empty($fbrInvoiceNumber))
  <div class="fbr-footer-wrap">
    <div class="fbr-box-wrapper">
      <div class="fbr-box-title">
        FBR Invoice No : <span class="fbr-box-num">{{ $fbrInvoiceNumber }}</span>
      </div>
      <div class="fbr-box-badges">
        @if(!empty($fbrQrBase64))
          <img src="{{ $fbrQrBase64 }}" alt="FBR QR Code" class="fbr-qr-img">
        @endif
        @if(!empty($fbrLogoSrc))
          <img src="{{ $fbrLogoSrc }}" alt="FBR Digital Invoicing System" class="fbr-badge-img">
        @endif
      </div>
    </div>
  </div>
  @endif

</div>

<script>
async function downloadPDF() {
  const { jsPDF } = window.jspdf;
  const invoice = document.getElementById('invoice');
  const canvas = await html2canvas(invoice, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
  const imgData = canvas.toDataURL('image/png');
  const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
  const pdfW = pdf.internal.pageSize.getWidth();
  const imgH = canvas.height * pdfW / canvas.width;
  const pageH = pdf.internal.pageSize.getHeight();
  let y = 0;
  while (y < imgH) {
    if (y > 0) pdf.addPage();
    pdf.addImage(imgData, 'PNG', 0, -y, pdfW, imgH);
    y += pageH;
  }
  pdf.save('invoice_{{ $sale->invoice_no ?? $sale->id }}.pdf');
}

// Reload invoice on successful FBR submission
$(document).on('fbr:posted', function(e, data) {
    setTimeout(function() {
        window.location.reload();
    }, 1200);
});
</script>

@include('admin_panel.sale.components.fbr_post_modal')

</body>
</html>