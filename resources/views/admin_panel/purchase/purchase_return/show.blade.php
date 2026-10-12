@php
if (!function_exists('numberToWordsReturn')) {
    function numberToWordsReturn($num) {
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
<title>Purchase Return Invoice - {{ $return->return_invoice }}</title>
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

  .a4-wrapper { width: 794px; min-height: 1123px; margin: 20px auto; background: #fff; padding: 26px 30px; box-shadow: 0 2px 14px rgba(0,0,0,0.25); position: relative; overflow: hidden; }

  /* Watermark */
  .watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    font-size: 140px;
    font-weight: 900;
    color: rgba(200, 0, 0, 0.06);
    white-space: nowrap;
    pointer-events: none;
    z-index: 0;
    text-transform: uppercase;
    letter-spacing: 15px;
  }

  .header { display: flex; align-items: center; gap: 18px; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 8px; position: relative; z-index: 1; }
  .logo-box { flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
  .invoice-logo { height: 75px; width: auto; max-width: 130px; object-fit: contain; display: block; }
  .company-info h1 { font-family: 'Palatino Linotype', Palatino, serif; font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #000; line-height: 1.1; }
  .company-info .addr { font-size: 10px; margin-top: 4px; line-height: 1.5; }

  .doc-title { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin: 8px 0 6px; letter-spacing: 1px; position: relative; z-index: 1; color: #c0392b; }

  .party-meta { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 4px; position: relative; z-index: 1; }
  .party-block { font-size: 10.5px; line-height: 1.85; flex: 1; }
  .prow { display: flex; gap: 4px; }
  .plbl { font-weight: bold; min-width: 75px; }
  .barcode-right { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 2px; }
  .barcode-num { font-size: 8.5px; letter-spacing: 0.5px; text-align: center; margin-top: 1px; font-weight: bold; }

  .meta-strip { display: flex; justify-content: space-between; align-items: center; font-size: 10.5px; border-top: 1px solid #555; border-bottom: 1px solid #555; padding: 3px 3px; margin-bottom: 0; position: relative; z-index: 1; }

  table.items { width: 100%; border-collapse: collapse; font-size: 10px; position: relative; z-index: 1; margin-top: 5px; }
  table.items th { background: #fbecec; border: 0.5px solid #888; padding: 4px 4px; text-align: center; font-weight: bold; color: #c0392b; }
  table.items th.la { text-align: left; }
  table.items td { border: 0.5px solid #ccc; padding: 3.5px 4px; }
  table.items td.c { text-align: center; }
  table.items td.r { text-align: right; }

  .bottom-section { display: flex; border: 0.5px solid #ccc; border-top: none; position: relative; z-index: 1; }
  .left-bottom { flex: 1; border-right: 0.5px solid #ccc; }
  .remarks-row { padding: 3px 6px; font-size: 10.5px; font-weight: bold; border-bottom: 0.5px solid #ccc; min-height: 20px; }
  .inwords-row { padding: 3px 6px; font-size: 10px; display: flex; gap: 4px; flex-wrap: wrap; }
  .right-totals { min-width: 230px; font-size: 10.5px; }
  .totals-table { width: 100%; border-collapse: collapse; }
  .totals-table td { padding: 3px 6px; font-size: 10.5px; }
  .totals-table td.lbl { white-space: nowrap; }
  .totals-table td.val { text-align: right; font-weight: bold; }
  .totals-table .net-row td { font-weight: bold; background: #fef2f2; color: #c0392b; }
  .totals-table .divider td { border-top: 0.5px solid #aaa; }

  .footer-dates { font-size: 10px; line-height: 1.8; margin-top: 15px; position: relative; z-index: 1; }
  .approval-row { display: flex; gap: 50px; margin-top: 25px; font-size: 10.5px; position: relative; z-index: 1; }
  .approval-field { display: flex; flex-direction: column; gap: 3px; }
  .approval-line { border-bottom: 1px solid #000; width: 190px; margin-top: 2px; }

  @media print {
    body { background: #fff; }
    .action-bar { display: none !important; }
    .a4-wrapper { box-shadow: none; margin: 0; width: 100%; padding: 10mm 12mm; min-height: auto; }
    @page { size: A4; margin: 0; }
    .watermark { color: rgba(200, 0, 0, 0.08) !important; -webkit-print-color-adjust: exact; }
  }
</style>
</head>
<body>

<div class="action-bar">
  <a href="{{ route('purchase.return.index') }}" class="btn btn-back">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    Back to List
  </a>
  <button class="btn btn-print" onclick="window.print()">
    <svg viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
    Print Invoice
  </button>
  <button class="btn btn-pdf" onclick="downloadPDF()">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9.5 8.5c0 .83-.67 1.5-1.5 1.5H7v2H5.5V9H8c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V9H13c.83 0 1.5.67 1.5 1.5v3zm4-3H17v1h1.5V13H17v2h-1.5V9h3v1.5zM7 10.5h1v1H7v-1zM11 10.5h1v3h-1v-3z"/></svg>
    Download PDF
  </button>
</div>

<div class="a4-wrapper" id="invoice">
  <!-- Watermark -->
  <div class="watermark">RETURNED</div>

  <!-- HEADER -->
  <div class="header">
    <div class="logo-box">
      <img src="{{ $logoSrc }}" alt="Three Stars Medical Supplies" class="invoice-logo">
    </div>
    <div class="company-info">
      <h1>THREE STARS MEDICAL SUPPLIES</h1>
      <div class="addr">
        <strong>Head Office :</strong> M17-18 Mezanine Floor Seth Centre 10 Syed Mouj Darya Road (Edward Road) Lahore.<br>
        <strong>Phone :</strong> 0092-42-37353433 &nbsp; | &nbsp; <strong>Purchase Return Note</strong>
      </div>
    </div>
  </div>

  <!-- TITLE -->
  <div class="doc-title">Purchase Return Invoice (Debit Note)</div>

  <!-- VENDOR + BARCODE -->
  <div class="party-meta">
    <div class="party-block">
      <div class="prow"><span class="plbl">Vendor :</span><span><strong>{{ $return->vendor->name ?? 'N/A' }}</strong></span></div>
      <div class="prow"><span class="plbl">Address :</span><span>{{ $return->vendor->address ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">Contact :</span><span>{{ $return->vendor->phone ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">Warehouse :</span><span>{{ $return->warehouse->warehouse_name ?? '-' }}</span></div>
      <div class="prow"><span class="plbl">Reason :</span><span>{{ $return->return_reason ?? '-' }}</span></div>
    </div>
    <div class="party-block" style="text-align:right;">
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Return # :</span><span><strong>{{ $return->return_invoice }}</strong></span></div>
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Return Date :</span><span>{{ \Carbon\Carbon::parse($return->return_date)->format('d/m/Y') }}</span></div>
      @if($return->purchase)
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Ref GRN # :</span><span>{{ $return->purchase->invoice_no }}</span></div>
      @endif
      <div class="prow" style="justify-content:flex-end;"><span class="plbl">Entry Date :</span><span>{{ $return->created_at->format('d/m/Y H:i') }}</span></div>
    </div>
    <div class="barcode-right">
      <div style="display:flex;justify-content:center;padding:2px 0;">
        {!! DNS1D::getBarcodeHTML($return->return_invoice, 'C128', 1, 35) !!}
      </div>
      <div class="barcode-num">{{ $return->return_invoice }}</div>
    </div>
  </div>

  <!-- META STRIP -->
  <div class="meta-strip">
    <span><strong>Vendor ID :</strong> {{ $return->vendor->id ?? '-' }}</span>
    <span><strong>Branch :</strong> {{ optional(\App\Models\Branch::find($return->branch_id))->name ?? 'Head Office' }}</span>
    <span>Page 1 of 1</span>
  </div>

  <!-- ITEMS TABLE -->
  <table class="items">
    <thead>
      <tr>
        <th style="width:30px;">SR#</th>
        <th class="la">Item Description</th>
        <th style="width:65px;">Batch / Lot</th>
        <th style="width:65px;">Expiry</th>
        <th style="width:60px;">UOM</th>
        <th style="width:55px;">Qty</th>
        <th style="width:75px;">Price/Pc</th>
        <th style="width:70px;">Discount</th>
        <th style="width:85px;">Line Total</th>
      </tr>
    </thead>
    <tbody>
      @php $grandPieces = 0; @endphp
      @foreach($return->items as $index => $item)
      @php
        $grandPieces += (float)$item->qty;
        $expDate = $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->format('m/Y') : '-';
        $itemDisc = (float)($item->item_discount ?? 0);
        $itemDiscType = $item->item_discount_type ?? 'amount';
        
        $rowGross = (float)$item->qty * (float)$item->price;
        $displayDisc = ($itemDiscType === 'percent') ? ($rowGross * $itemDisc / 100) : $itemDisc;
      @endphp
      <tr>
        <td class="c">{{ $index + 1 }}</td>
        <td>
          <strong>{{ $item->product->item_name ?? 'N/A' }}</strong>
          @if(!empty($item->product->item_code)) <br><small style="color:#666;">Code: {{ $item->product->item_code }}</small> @endif
        </td>
        <td class="c">{{ $item->batch_no ?? '-' }}</td>
        <td class="c">{{ $expDate }}</td>
        <td class="c">{{ $item->unit ?? 'pc' }}</td>
        <td class="c"><strong>{{ number_format($item->qty, 0) }}</strong></td>
        <td class="r">{{ number_format($item->price, 2) }}</td>
        <td class="r" style="color:#c0392b;">{{ $displayDisc > 0 ? number_format($displayDisc, 2) : '—' }}</td>
        <td class="r"><strong>{{ number_format($item->line_total, 2) }}</strong></td>
      </tr>
      @endforeach
      <!-- Grand Row -->
      <tr style="background:#f9f9f9;font-weight:bold;">
        <td colspan="5" class="r" style="font-size:9.5px;color:#555;">Total Return Items: {{ $return->items->count() }}</td>
        <td class="c"><strong>{{ number_format($grandPieces, 0) }}</strong> <small>Pcs</small></td>
        <td colspan="2"></td>
        <td class="r">{{ number_format($return->items->sum('line_total'), 2) }}</td>
      </tr>
    </tbody>
  </table>

  <!-- BOTTOM -->
  <div class="bottom-section">
    <div class="left-bottom">
      <div class="remarks-row">
        <span style="color:#c0392b;">Remarks :</span>
        <span>{{ $return->remarks ?? '-' }}</span>
      </div>
      <div class="inwords-row">
        <strong>In Words :</strong>
        <span>{{ numberToWordsReturn($return->net_amount) }}</span>
      </div>
    </div>
    <div class="right-totals">
      <table class="totals-table">
        <tr>
          <td class="lbl">Total Item Value :</td>
          <td class="val">{{ number_format($return->bill_amount, 2) }}</td>
        </tr>
        <tr>
          <td class="lbl">Item Discounts :</td>
          <td class="val" style="color:#c0392b;">{{ (float)$return->item_discount > 0 ? '- ' : '' }}{{ number_format($return->item_discount, 2) }}</td>
        </tr>
        <tr>
          <td class="lbl">Extra Deductions :</td>
          <td class="val" style="color:#c0392b;">{{ (float)$return->extra_discount > 0 ? '- ' : '' }}{{ number_format($return->extra_discount, 2) }}</td>
        </tr>
        <tr class="divider net-row">
          <td class="lbl">Net Refundable :</td>
          <td class="val">{{ number_format($return->net_amount, 2) }}</td>
        </tr>
        <tr>
          <td class="lbl">Paid / Adjusted :</td>
          <td class="val" style="color:#27ae60;">{{ number_format($return->paid, 2) }}</td>
        </tr>
        <tr class="divider">
          <td class="lbl">Remaining Balance :</td>
          <td class="val" style="{{ ($return->net_amount - $return->paid) > 0 ? 'color:#c0392b;' : 'color:#27ae60;' }}">
            {{ number_format($return->net_amount - $return->paid, 2) }}
          </td>
        </tr>
      </table>
    </div>
  </div>

  <!-- APPROVAL ROW -->
  <div class="approval-row">
    <div class="approval-field">
      <span><strong>Return Requested By :</strong></span>
      <div class="approval-line"></div>
    </div>
    <div class="approval-field">
      <span><strong>Approved By :</strong></span>
      <div class="approval-line"></div>
    </div>
    <div class="approval-field">
      <span><strong>Vendor's Acknowledgment :</strong></span>
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
  pdf.save('purchase_return_{{ $return->return_invoice }}.pdf');
}
</script>

</body>
</html>
