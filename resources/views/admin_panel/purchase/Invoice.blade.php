@php
if (!function_exists('numberToWordsPurch')) {
    function numberToWordsPurch($num) {
        if (!$num || $num == 0) return "ZERO RUPEES ONLY";
        $a = ['','ONE ','TWO ','THREE ','FOUR ','FIVE ','SIX ','SEVEN ','EIGHT ','NINE ','TEN ','ELEVEN ','TWELVE ','THIRTEEN ','FOURTEEN ','FIFTEEN ','SIXTEEN ','SEVENTEEN ','EIGHTEEN ','NINETEEN '];
        $b = ['','','TWENTY','THIRTY','FORTY','FIFTY','SIXTY','SEVENTY','EIGHTY','NINETY'];
        $num = (string) round($num);
        if (strlen($num) > 9) return 'OVERFLOW';
        $n = str_pad($num, 9, '0', STR_PAD_LEFT);
        $parts = [substr($n,0,2),substr($n,2,2),substr($n,4,2),substr($n,6,1),substr($n,7,2)];
        $str = '';
        if ((int)$parts[0]>0) $str.=((int)$parts[0]<20?$a[(int)$parts[0]]:$b[(int)$parts[0][0]].' '.$a[(int)$parts[0][1]]).'CRORE ';
        if ((int)$parts[1]>0) $str.=((int)$parts[1]<20?$a[(int)$parts[1]]:$b[(int)$parts[1][0]].' '.$a[(int)$parts[1][1]]).'LAKH ';
        if ((int)$parts[2]>0) $str.=((int)$parts[2]<20?$a[(int)$parts[2]]:$b[(int)$parts[2][0]].' '.$a[(int)$parts[2][1]]).'THOUSAND ';
        if ((int)$parts[3]>0) $str.=$a[(int)$parts[3]].'HUNDRED ';
        if ((int)$parts[4]>0) { if($str!='') $str.='AND '; $str.=((int)$parts[4]<20?$a[(int)$parts[4]]:$b[(int)$parts[4][0]].' '.$a[(int)$parts[4][1]]); }
        return trim($str).' RUPEES ONLY';
    }
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
<title>Purchase Invoice - {{ $purchase->invoice_no }}</title>
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

  .party-meta { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 4px; }
  .party-block { font-size: 10.5px; line-height: 1.85; flex: 1; }
  .prow { display: flex; gap: 4px; }
  .plbl { font-weight: bold; min-width: 75px; }
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
  .right-totals { min-width: 230px; font-size: 10.5px; }
  .totals-table { width: 100%; border-collapse: collapse; }
  .totals-table td { padding: 2px 6px; font-size: 10.5px; }
  .totals-table td.lbl { white-space: nowrap; }
  .totals-table td.val { text-align: right; font-weight: bold; }
  .totals-table .net-row td { font-weight: bold; background: #f5f5f5; }
  .totals-table .divider td { border-top: 0.5px solid #aaa; }

  .footer-dates { font-size: 10px; line-height: 1.8; margin-top: 10px; }
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
  <a href="{{ route('Purchase.home') }}" class="btn btn-back">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    Back
  </a>
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
        <strong>{{ $purchase->branch->name ?? 'Head Office' }} :</strong> {{ $purchase->branch->address ?? 'M17-18 Mezanine Floor Seth Centre 10 Syed Mouj Darya Road (Edward Road) Lahore.' }}<br>
        <strong>Phone :</strong> {{ $purchase->branch->number ?? '0092-42-37353433' }} &nbsp; | &nbsp; <strong>GST Invoice</strong>
      </div>
    </div>
  </div>

  <!-- TITLE -->
  <div class="doc-title">Purchase Invoice (Goods Receipt Note)</div>

  <!-- VENDOR + BARCODE -->
  <div class="party-meta">
    <div class="party-block">
      <div class="prow"><span class="plbl">Vendor :</span><span><strong>{{ $purchase->vendor->name ?? 'WALKING VENDOR' }}</strong></span></div>
      <div class="prow"><span class="plbl">Address :</span><span>{{ $purchase->vendor->address ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">Contact :</span><span>{{ $purchase->vendor->phone ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">NTN :</span><span>{{ $purchase->vendor->ntn ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">Warehouse :</span><span>{{ $purchase->warehouse->warehouse_name ?? '-' }}</span></div>
    </div>
    <div class="party-block" style="text-align:right;">
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">GRN # :</span><span><strong>{{ $purchase->invoice_no ?? $purchase->id }}</strong></span></div>
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Bill Date :</span><span>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d/m/Y') }}</span></div>
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Entry Date :</span><span>{{ $purchase->created_at->format('d/m/Y H:i') }}</span></div>
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Status :</span><span><strong>{{ ucfirst($purchase->status_purchase ?? 'Draft') }}</strong></span></div>
      @if($purchase->po_ref)
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">PO Ref # :</span><span>{{ $purchase->po_ref }}</span></div>
      @endif
    </div>
    <div class="barcode-right">
      <div style="display:flex;justify-content:center;padding:2px 0;">
        {!! DNS1D::getBarcodeHTML($purchase->invoice_no ?? (string)$purchase->id, 'C128', 1, 35) !!}
      </div>
      <div class="barcode-num">{{ $purchase->invoice_no ?? $purchase->id }}</div>
    </div>
  </div>

  <!-- META STRIP -->
  <div class="meta-strip">
    <span><strong>Vendor ID :</strong> {{ $purchase->vendor->id ?? '-' }}</span>
    <span><strong>Branch :</strong> {{ optional(\App\Models\Branch::find($purchase->branch_id))->name ?? 'Head Office' }}</span>
    <span>Page 1 of 1</span>
  </div>

  <!-- ITEMS TABLE -->
  @php 
    $hasGst = ((float)($purchase->gst_total ?? $purchase->total_gst ?? 0) > 0);
    $showHsCode = (bool)($purchase->enable_hs_code ?? false);
    $grandPieces = 0; $grandGst = 0; $grandDiscount = 0; $grandBox = 0; $grandPackPieces = 0;
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
        <th style="width:62px;">Expiry</th>
        <th style="width:60px;">Lot #</th>
        <th style="width:48px;">Qty</th>
        <th style="width:68px;">Rate/Pc</th>
        <th style="width:56px;">Disc</th>
        @if($hasGst)
        <th style="width:40px;">GST%</th>
        <th style="width:52px;">GST Amt</th>
        @endif
        <th style="width:72px;">Net Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($purchase->items as $index => $item)
      @php
        $piecesPerBox = max(1, (int)($item->uom_factor ?? $item->pieces_per_box ?? 1));
        $totalPcs     = (int)($item->total_pieces ?? ($item->qty * $piecesPerBox));
        $grandPieces += $totalPcs;

        // UOM label
        $uomLabel = null;
        if ($item->uom_name && strtolower($item->uom_name) !== 'piece' && $item->uom_name !== '-- Base --') {
            $uomLabel = $item->uom_name;
        } elseif ($item->uom) {
            $uomLabel = $item->uom->name;
        }
        if (!$uomLabel && $piecesPerBox > 1) $uomLabel = '1X'.$piecesPerBox;
        if (!$uomLabel) $uomLabel = $item->product->unit->name ?? 'Piece';

        $lotNo   = $item->batch_no ?? '-';
        $expDate = $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->format('m/Y') : '-';
        $discVal = (float)($item->item_discount ?? 0);
        $discType = $item->item_discount_type ?? 'amount';
        $itemGross = $totalPcs * (float)$item->price;
        $rowDiscAmount = ($discType === 'percent') ? ($itemGross * $discVal / 100) : $discVal;

        $gstPct  = (float)($item->gst_percent ?? 0);
        $gstAmt  = (float)($item->gst_amount ?? 0);
        if ($gstAmt == 0 && $gstPct > 0) {
            $rawLine = $itemGross - $rowDiscAmount;
            $gstAmt = round($rawLine * $gstPct / 100, 2);
        }
        $grandDiscount += $rowDiscAmount;
        $grandBox += floor($totalPcs / $piecesPerBox);
        $grandPackPieces += ($totalPcs % $piecesPerBox);
        $grandGst += $gstAmt;
      @endphp
      <tr>
        <td class="c">{{ $index + 1 }}</td>
        <td>
          <strong>{{ ($item->product->item_name ?? 'N/A') . ' ' . ($item->product->brand->name ?? '') }}</strong>
          @if(!empty($item->product->item_code)) <br><small style="color:#666;">Code: {{ $item->product->item_code }}</small> @endif
        </td>
        @if($showHsCode)
        <td class="c">{{ $item->hs_code ?: ($item->product->hs_code ?? '-') }}</td>
        @endif
        <td class="c">
          {{ $uomLabel }}
        </td>
        <td class="c">{{ $expDate }}</td>
        <td class="c">{{ $lotNo }}</td>
        <td class="c"><strong>{{ number_format($totalPcs, 0) }}</strong></td>
        <td class="r">{{ number_format($item->price, 3) }}</td>
        <td class="r">{{ $rowDiscAmount > 0 ? number_format($rowDiscAmount, 2) : '—' }}</td>
        @if($hasGst)
        <td class="c">{{ $gstPct > 0 ? number_format($gstPct, 1).'%' : '—' }}</td>
        <td class="r">{{ $gstAmt > 0 ? number_format($gstAmt, 2) : '—' }}</td>
        @endif
        <td class="r"><strong>{{ number_format($item->line_total, 2) }}</strong></td>
      </tr>
      @endforeach
      <!-- Grand Row -->
      <tr style="background:#f4f4f4;font-weight:bold;">
        <td colspan="{{ $showHsCode ? 6 : 5 }}" class="r" style="font-size:9.5px;color:#555;">Total Items: {{ $purchase->items->count() }}</td>
        <td class="c">
          <strong>{{ number_format($grandPieces, 0) }}</strong> <small>Pcs</small>
        </td>
        @if($hasGst)
        <td colspan="3"></td>
        <td class="r">{{ $grandGst > 0 ? number_format($grandGst, 2) : '—' }}</td>
        @else
        <td colspan="2"></td>
        @endif
        <td class="r">{{ number_format($purchase->items->sum('line_total'), 2) }}</td>
      </tr>
    </tbody>
  </table>

  <!-- BOTTOM -->
  @php
    $subtotal   = (float)$purchase->subtotal;
    $discount   = (float)$purchase->discount;
    $discType   = $purchase->discount_type ?? 'amount';
    $extraCost  = (float)($purchase->extra_cost ?? 0);
    $freight    = (float)($purchase->freight_charges ?? 0);
    $gstTotal   = (float)($purchase->gst_total ?? $purchase->total_gst ?? $grandGst);
    $netAmount  = (float)$purchase->net_amount;
    $paidAmount = (float)$purchase->paid_amount;
    $balance    = $netAmount - $paidAmount;
  @endphp
  <div class="bottom-section">
    <div class="left-bottom">
      <div class="remarks-row">
        <span style="color:#d00;">Note :</span>
        <span>{{ $purchase->note ?? '-' }}</span>
      </div>
      <div class="inwords-row">
        <strong>In Words :</strong>
        <span>{{ numberToWordsPurch($netAmount) }}</span>
      </div>
    </div>
    <div class="right-totals">
      <table class="totals-table">
        <tr>
          <td class="lbl">Sub Total :</td>
          <td class="val">{{ number_format($subtotal + $grandDiscount, 2) }}</td>
          @if($hasGst)
          <td class="lbl" style="padding-left:20px;">Total GST :</td>
          <td class="val">{{ number_format($gstTotal, 2) }}</td>
          @else
          <td colspan="2"></td>
          @endif
        </tr>
        <tr>
          <td class="lbl">Product Discount :</td>
          <td class="val" style="color:#c0392b;">{{ $grandDiscount > 0 ? '- ' : '' }}{{ number_format($grandDiscount, 2) }}</td>
          <td class="lbl" style="padding-left:20px;">Bill Discount{{ $discType === 'percent' ? ' (%)' : '' }} :</td>
          <td class="val" style="color:#c0392b;">{{ $discount > 0 ? '- ' : '' }}{{ number_format($discount, 2) }}</td>
        </tr>
        <tr>
          <td class="lbl">Bilti Expenses :</td>
          <td class="val">{{ number_format($extraCost, 2) }}</td>
          <td class="lbl" style="padding-left:20px;">Freight Charges :</td>
          <td class="val">{{ number_format($freight, 2) }}</td>
        </tr>
        <tr>
          <td class="lbl">Amount Paid :</td>
          <td class="val" style="color:#27ae60;">{{ number_format($paidAmount, 2) }}</td>
          <td colspan="2"></td>
        </tr>
        <tr class="divider">
          <td class="lbl"><strong>Net Payable :</strong></td>
          <td class="val"><strong>{{ number_format($netAmount, 2) }}</strong></td>
          <td class="lbl" style="padding-left:20px;"><strong>Balance :</strong></td>
          <td class="val" style="font-weight:bold; {{ $balance > 0 ? 'color:#c0392b;' : 'color:#27ae60;' }}">
            {{ number_format($balance, 2) }}
          </td>
        </tr>
      </table>
    </div>
  </div>

  <!-- FOOTER -->
  <div class="footer-dates" style="margin-top:10px;">
    <div><strong>Date Created :</strong> &nbsp;{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d/m/Y') }}</div>
    <div><strong>Created By :</strong> &nbsp;&nbsp;{{ auth()->user()->name ?? 'Admin' }}</div>
  </div>

  <!-- APPROVAL ROW -->
  <div class="approval-row">
    <div class="approval-field">
      <span><strong>Approved By :</strong></span>
      <div class="approval-line"></div>
    </div>
    <div class="approval-field">
      <span><strong>Checked &amp; Received By :</strong></span>
      <div class="approval-line"></div>
    </div>
    <div class="approval-field">
      <span><strong>Store Keeper :</strong></span>
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
  pdf.save('purchase_invoice_{{ $purchase->invoice_no ?? $purchase->id }}.pdf');
}
</script>

</body>
</html>
