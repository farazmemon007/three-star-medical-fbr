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
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Invoice - {{ $sale->invoice_no ?? $sale->id }}</title>
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

  .meta-strip { display: flex; justify-content: space-between; align-items: center; font-size: 10.5px; border-top: 1px solid #555; border-bottom: 1px solid #555; padding: 3px 3px; margin-bottom: 0; }

  table.items { width: 100%; border-collapse: collapse; font-size: 10px; }
  table.items th { background: #e0e0e0; border: 0.5px solid #888; padding: 3px 4px; text-align: center; font-weight: bold; }
  table.items th.la { text-align: left; }
  table.items td { border: 0.5px solid #ccc; padding: 2.5px 4px; }
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
  <div class="doc-title">Sales Invoice</div>

  <!-- PARTY + BARCODE -->
  <div class="party-meta">
    <div class="party-block">
      <div class="prow"><span class="plbl">To :</span><span><strong>{{ optional($sale->customer_relation)->customer_name ?? 'WALKING CUSTOMER' }}</strong></span></div>
      <div class="prow"><span class="plbl">Address :</span><span>{{ optional($sale->customer_relation)->address ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">Contact :</span><span>{{ optional($sale->customer_relation)->mobile ?? optional($sale->customer_relation)->phone ?? '-' }}</span></div>
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

      @if($sale->fbr_status === 'posted')
      <div style="margin-top: 5px; border: 1.5px solid #059669; border-radius: 4px; padding: 3px 6px; text-align: center; background: #f0fdf4; width: 145px;">
        <div style="font-size: 7.5px; font-weight: 800; color: #059669; letter-spacing: 0.5px;">✓ FBR DIGITAL INVOICE</div>
        <div style="font-size: 8px; font-family: monospace; font-weight: bold; color: #1e293b; word-break: break-all;">{{ $sale->fbr_invoice_no }}</div>
        @if($sale->fbr_posted_at)
        <div style="font-size: 7px; color: #475569; margin-top: 1px;">{{ date('d-M-Y H:i', strtotime($sale->fbr_posted_at)) }}</div>
        @endif
      </div>
      @endif
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
    $hasGst = ((float)($sale->total_gst ?? 0) > 0);
    $showHsCode = (bool)($sale->enable_hs_code ?? false);
    $grandPieces = 0; $grandGst = 0; $grandDiscount = 0; $grandBox = 0; $grandPackPieces = 0;
    $grandFree = 0;
  @endphp
  <table class="items">
    <thead>
      <tr>
        <th style="width:24px;">SR#</th>
        <th class="la">Item Description</th>
        @if($showHsCode)
        <th style="width:60px;">HS Code</th>
        @endif
        <th style="width:55px;">Packing</th>
        <th style="width:65px;">Expiry</th>
        <th style="width:60px;">Lot #</th>
        <th style="width:44px;">Qty</th>
        <th style="width:40px;">Free</th>
        <th style="width:68px;">Rate/Pc</th>
        <th style="width:56px;">Disc</th>
        @if($hasGst)
        <th style="width:40px;">GST%</th>
        <th style="width:52px;">GST Amt</th>
        @endif
        <th style="width:70px;">Net Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($saleItems as $index => $item)
      @php
        $piecesPerBox = max(1, (int)($item['uom_factor'] ?? $item['pieces_per_box'] ?? 1));
        $totalPcs     = (int)($item['total_pieces'] ?? 0);
        $grandPieces += $totalPcs;
        $grandDiscount += (float)($item['discount_amount'] ?? 0);
        $grandBox += floor($totalPcs / $piecesPerBox);
        $grandPackPieces += ($totalPcs % $piecesPerBox);
        $grandFree   += (int)($item['free_total_pieces'] ?? 0);
        
        // B.P notation
        if ($piecesPerBox > 1) {
            $boxes = floor($totalPcs / $piecesPerBox);
            $loose = $totalPcs % $piecesPerBox;
            $qtyDisp = $loose > 0 ? "$boxes.$loose" : $boxes;
        } else {
            $qtyDisp = $totalPcs;
        }
        // Free B.P
        $freePcs = (int)($item['free_total_pieces'] ?? 0);
        if ($piecesPerBox > 1 && $freePcs > 0) {
            $fb = floor($freePcs / $piecesPerBox); $fl = $freePcs % $piecesPerBox;
            $freeDisp = $fl > 0 ? "$fb.$fl" : $fb;
        } else {
            $freeDisp = $freePcs > 0 ? $freePcs : '-';
        }
        // GST per item — try to read directly; fall back to calculating from total
        $gstPct = $item['gst_percent'] ?? $item['gst'] ?? 0;
        $gstAmt = $item['gst_amount'] ?? 0;
        if ($gstAmt == 0 && $gstPct > 0) {
            $sub = $item['total'] - ($item['discount_amount'] ?? 0);
            $gstAmt = $sub * $gstPct / 100;
        }
      @endphp
        <td class="c">{{ $index + 1 }}</td>
        <td>
          <strong>{{ $item['item_name'] }}</strong>
          @if(!empty($item['item_code'])) <br><small style="color:#666;">Code: {{ $item['item_code'] }}</small> @endif
        </td>
        @if($showHsCode)
        <td class="c">{{ $item['hs_code'] ?? '-' }}</td>
        @endif
        <td class="c">{{ ($item['uom_name'] ?? '') ?: ($piecesPerBox > 1 ? '1X'.$piecesPerBox : (($item['unit'] ?? '') ?: 'Piece')) }}</td>
        <td class="c">{{ $item['exp_date'] ?? '-' }}</td>
        <td class="c">{{ $item['lot_number'] ?? '-' }}</td>
        <td class="c"><strong>{{ $qtyDisp }}</strong></td>
        <td class="c">{{ $freeDisp }}</td>
        <td class="r">{{ number_format($item['price'], 2) }}</td>
        <td class="r">{{ $item['discount_amount'] > 0 ? number_format($item['discount_amount'], 2) : '—' }}</td>
        @if($hasGst)
        <td class="c">{{ $gstPct > 0 ? number_format($gstPct, 1).'%' : '—' }}</td>
        <td class="r">{{ $gstAmt > 0 ? number_format($gstAmt, 2) : '—' }}</td>
        @endif
        <td class="r"><strong>{{ number_format($item['total'], 2) }}</strong></td>
      </tr>
      @endforeach
      <!-- Grand Row -->
      <tr style="background:#f4f4f4; font-weight:bold;">
        <td colspan="{{ $showHsCode ? 6 : 5 }}" class="r" style="font-size:9.5px;color:#555;">Total Items: {{ count($saleItems) }}</td>
        <td class="c">
          {{ $grandPackPieces > 0 ? "$grandBox.$grandPackPieces" : $grandBox }} <small>B.P</small>
        </td>
        <td class="c">{{ $grandFree > 0 ? $grandFree : '—' }}</td>
        @if($hasGst)
        <td colspan="5"></td>
        @else
        <td colspan="3"></td>
        @endif
        <td class="r" style="font-size:11px;">{{ number_format($sale->items->sum('line_total'), 2) }}</td>
      </tr>
    </tbody>
  </table>

  <!-- BOTTOM: REMARKS + TOTALS -->
  @php
    $amountPaid = (float)($sale->cash ?? 0);
    $closingBalance = $previousBalance + (float)$sale->total_net - $amountPaid;
  @endphp
  <div class="bottom-section">
    <div class="left-bottom">
      <div class="remarks-row">
        <span style="color:#d00;">Remarks :</span>
        <span>{{ $sale->return_note ?? $sale->remarks ?? '-' }}</span>
      </div>
      <div class="inwords-row">
        <strong>In Words :</strong>
        <span>{{ !empty($sale->total_amount_Words) ? $sale->total_amount_Words : numberToWords($sale->total_net) }}</span>
      </div>
    </div>
    <div class="right-totals">
      <table class="totals-table">
        <tr>
          <td class="lbl">Sub Total :</td>
          <td class="val">{{ number_format((float)$sale->total_bill_amount + $grandDiscount, 2) }}</td>
          @if($hasGst)
          <td class="lbl" style="padding-left:20px;">Total GST :</td>
          <td class="val">{{ number_format($sale->total_gst ?? 0, 2) }}</td>
          @else
          <td colspan="2"></td>
          @endif
        </tr>
        <tr>
          <td class="lbl">Product Discount :</td>
          <td class="val" style="color:#c0392b;">{{ $grandDiscount > 0 ? '- ' : '' }}{{ number_format($grandDiscount, 2) }}</td>
          <td class="lbl" style="padding-left:20px;">Bill Discount :</td>
          <td class="val" style="color:#c0392b;">{{ (float)($sale->total_extradiscount ?? 0) > 0 ? '- ' : '' }}{{ number_format($sale->total_extradiscount ?? 0, 2) }}</td>
        </tr>
        <tr>
          <td class="lbl">Freight Charges :</td>
          <td class="val">{{ number_format($sale->total_freight ?? 0, 2) }}</td>
          <td class="lbl" style="padding-left:20px;">Bilti Expenses :</td>
          <td class="val">{{ number_format($sale->expense ?? $sale->total_expense ?? 0, 2) }}</td>
        </tr>
        @if((float)($sale->total_inc_tax ?? 0) > 0 || (float)($sale->total_adv_tax ?? 0) > 0)
        <tr>
          <td class="lbl">Inc Tax :</td>
          <td class="val">{{ number_format($sale->total_inc_tax ?? 0, 2) }}</td>
          <td class="lbl" style="padding-left:20px;">Adv Tax :</td>
          <td class="val">{{ number_format($sale->total_adv_tax ?? 0, 2) }}</td>
        </tr>
        @endif
        <tr class="divider">
          <td class="lbl">BF Balance :</td>
          <td class="val">{{ number_format($previousBalance, 2) }}</td>
          <td class="lbl" style="padding-left:20px;">Amount Paid :</td>
          <td class="val" style="color:#27ae60;">{{ number_format($amountPaid, 2) }}</td>
        </tr>
        <tr class="divider">
          <td class="lbl" style="font-size:11px;"><strong>Net Payable :</strong></td>
          <td class="val"><strong>{{ number_format($sale->total_net, 2) }}</strong></td>
          <td class="lbl" style="padding-left:20px; font-size:11px;"><strong>Balance :</strong></td>
          <td class="val" style="font-weight:bold; {{ $closingBalance > 0 ? 'color:#c0392b;' : 'color:#27ae60;' }}">
            {{ number_format($closingBalance, 2) }}
          </td>
        </tr>
      </table>
    </div>
  </div>

  <!-- FOOTER -->
  <div class="footer-dates" style="margin-top:10px;">
    <div>
      <div><strong>Created By :</strong> &nbsp;{{ auth()->user()->name ?? 'Admin' }}</div>
      <div><strong>Date Created :</strong> &nbsp;{{ $sale->created_at->format('d/m/Y H:i') }}</div>
    </div>
    @if($officerName)
    <div>
      <div><strong>Sales Officer :</strong> &nbsp;{{ $officerName }}</div>
    </div>
    @endif
    @if($sale->credit_days)
    <div>
      <div><strong>Credit Days :</strong> &nbsp;{{ $sale->credit_days }}</div>
      <div><strong>Due Date :</strong> &nbsp;{{ \Carbon\Carbon::parse($sale->sale_date ?? $sale->created_at)->addDays($sale->credit_days)->format('d/m/Y') }}</div>
    </div>
    @endif
  </div>

  <!-- APPROVAL ROW -->
  <div class="approval-row">
    <div class="approval-field">
      <span><strong>Approved By :</strong></span>
      <div class="approval-line"></div>
    </div>
    <div class="approval-field">
      <span><strong>Checked & Received By :</strong></span>
      <div class="approval-line"></div>
    </div>
    <div class="approval-field">
      <span><strong>Authorized Signature :</strong></span>
      <div class="approval-line"></div>
    </div>
  </div>

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