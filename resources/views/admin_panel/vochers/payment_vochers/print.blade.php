@php
    $typeLabel = "CASH PAYMENT VOUCHER";
    $vNo = $voucher->pvid ?? $voucher->voucher_no ?? '-';
    $vDate = \Carbon\Carbon::parse($voucher->receipt_date ?? $voucher->date)->format('d/m/Y h:i:s a');
    $printedAt = now()->format('d/m/Y');
    
    // Determine totals for summary
    $totalDebit = 0;
    $totalCredit = 0;
    foreach($rows as $r) {
        $totalDebit += (isset($r['type']) && $r['type'] == 'debit') ? $r['amount'] : (isset($r['debit']) ? $r['debit'] : 0);
        $totalCredit += (isset($r['type']) && $r['type'] == 'credit') ? $r['amount'] : (isset($r['credit']) ? $r['credit'] : 0);
        
        // If it's a simple mapping with no explicit debit/credit (legacy), adjust
        if (!isset($r['debit']) && !isset($r['credit'])) {
             // In Payment Voucher, rows are usually DEBIT to Expense/Party and CREDIT to Cash/Bank
             $totalDebit += $r['amount'];
        }
    }
    
    // Add the counter-party row if not present
    // For Payment, Total Amount is CREDITED to Cash/Bank (leaving)
    $needsContraCredit = ($totalCredit == 0 && $voucher->total_amount > 0);
    if ($totalDebit == 0 && $voucher->total_amount > 0) {
        $totalDebit = $voucher->total_amount;
    }
    if ($needsContraCredit) {
        $totalCredit = $voucher->total_amount;
    }

    $createdBy = $voucher->createdBy->name ?? 'Admin';
    $createdAt = \Carbon\Carbon::parse($voucher->created_at)->format('d/m/Y H:i');
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
<title>{{ $typeLabel }} - {{ $vNo }}</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size:11px; background:#ccc; color:#000; }

  .action-bar { background:#2c3e50; padding:10px 20px; display:flex; gap:10px; justify-content:flex-end; }
  .btn { padding:8px 20px; font-size:13px; font-weight:bold; border:none; border-radius:4px; cursor:pointer; display:flex; align-items:center; gap:6px; color:#fff; }
  .btn-print { background:#27ae60; } .btn-print:hover { background:#1e8449; }
  .btn-pdf { background:#c0392b; } .btn-pdf:hover { background:#a93226; }
  .btn svg { width:15px; height:15px; fill:white; }

  .a4 {
    width:794px; min-height:1123px; margin:20px auto;
    background:#fff; padding:22px 28px 28px 28px;
    box-shadow:0 2px 14px rgba(0,0,0,0.25);
    position: relative;
  }

  /* HEADER */
  .header { display:flex; align-items:flex-end; gap:10px; margin-bottom:2px; }
  .logo { height:68px; width:auto; max-width:110px; position:relative; flex-shrink:0; display:flex; align-items:center; }
  .logo img { height:68px; width:auto; max-width:110px; object-fit:contain; display:block; }
  .voucher-title { font-size:22px; font-weight:bold; border-bottom:2px solid #000; padding-bottom:3px; flex:1; }

  /* TOP META */
  .top-meta { display:flex; justify-content:space-between; align-items:flex-start; margin-top:8px; }
  .meta-left { font-size:11px; line-height:2; }
  .meta-left .row { display:flex; gap:6px; }
  .meta-left .lbl { font-weight:bold; min-width:80px; color:#c00; }
  .meta-left .val { font-weight:bold; }
  .verified-posted { display:flex; gap:4px; }
  .stamp-box { border:1.5px solid #000; padding:4px 18px; font-weight:bold; font-size:12px; }

  .printed-on { font-size:11px; margin-top:6px; text-align:right; }
  .page-num { text-align:right; font-size:10.5px; margin:3px 0 4px; }

  /* TABLE */
  table.items { width:100%; border-collapse:collapse; font-size:10.5px; }
  table.items thead th {
    border-bottom:1px dotted #000;
    padding:3px 5px;
    text-align:left;
    font-weight:bold;
    color:#c00;
  }
  table.items thead th.r { text-align:right; }

  .item-main td {
    padding:6px 5px 0 5px;
    font-weight:bold;
    border-top:1px dotted #ccc;
  }
  .item-sub td {
    padding:0 5px 6px 5px;
    font-size:10.5px;
    font-weight:normal;
    border-bottom:1px dotted #ddd;
  }
  .item-sub td.r { text-align:right; }
  .item-main td.r { text-align:right; }

  /* IN WORDS / NET TOTAL */
  .inwords-row {
    display:flex;
    justify-content:space-between;
    align-items:center;
    border:1px solid #aaa;
    padding:4px 6px;
    margin-top:10px;
    font-size:10.5px;
  }
  .inwords-row .left { display:flex; gap:6px; }
  .inwords-row .right { display:flex; gap:20px; align-items:center; }
  .net-label { font-weight:bold; }
  .net-vals { display:flex; gap:40px; font-weight:bold; }

  /* AUDIT TRAIL */
  .audit-row {
    display:grid;
    grid-template-columns:1fr 1fr 1fr 1fr;
    font-size:10px;
    border-top:1px dotted #aaa;
    border-bottom:1px dotted #aaa;
    padding:4px 2px;
    margin-top:10px;
    gap:4px;
  }
  .audit-cell { line-height:1.7; }
  .audit-cell .al { font-weight:bold; }

  /* APPROVAL */
  .approval-row {
    display:flex;
    gap:40px;
    margin-top:25px;
    font-size:11px;
  }
  .apf { display:flex; flex-direction:column; gap:4px; }
  .apf .aline { border-bottom:1px solid #000; width:180px; }

  /* LP PO GRN VOU */
  .ref-section { margin-top:22px; font-size:14px; line-height:2.4; }
  .ref-row { display:flex; gap:10px; align-items:baseline; }
  .ref-label { font-weight:bold; min-width:80px; }
  .ref-line { border-bottom:1.5px solid #000; width:170px; display:inline-block; }
  .vou-val { font-weight:bold; font-size:15px; }

  @media print {
    body { background:#fff; }
    .action-bar { display:none !important; }
    .a4 { box-shadow:none; margin:0; width:100%; padding:10mm; min-height:auto; }
    @page { size:A4; margin:0; }
  }
</style>
</head>
<body>

<div class="action-bar">
  <button class="btn btn-print" onclick="window.print()">
    <svg viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
    Print
  </button>
  <button class="btn btn-pdf" onclick="downloadPDF()">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9.5 8.5c0 .83-.67 1.5-1.5 1.5H7v2H5.5V9H8c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V9H13c.83 0 1.5.67 1.5 1.5v3zm4-3H17v1h1.5V13H17v2h-1.5V9h3v1.5zM7 10.5h1v1H7v-1zM11 10.5h1v3h-1v-3z"/></svg>
    Download PDF
  </button>
</div>

<div class="a4" id="invoice">

  <!-- HEADER -->
  <div class="header">
    <div class="logo">
      <img src="{{ $logoSrc }}" alt="Three Stars Medical Supplies">
    </div>
    <div class="voucher-title">{{ $typeLabel }}</div>
  </div>

  <!-- TOP META -->
  <div class="top-meta">
    <div class="meta-left">
      <div class="row"><span class="lbl">Location :</span><span class="val">{{ $voucher->branch->name ?? 'HEAD OFFICE' }}</span></div>
      <div class="row"><span class="lbl">Voucher No :</span><span class="val">{{ $vNo }}</span></div>
      <div class="row"><span class="lbl">Dated :</span><span class="val">{{ $vDate }}</span></div>
    </div>
    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
      <div class="verified-posted">
        <div class="stamp-box">VERIFIED</div>
        <div class="stamp-box">POSTED</div>
      </div>
      <div class="printed-on"><strong>Printed On :</strong> &nbsp;{{ $printedAt }}</div>
    </div>
  </div>

  <div class="page-num">Page 1 of 1</div>

  <!-- ITEMS TABLE -->
  <table class="items">
    <thead>
      <tr>
        <th style="width:28px;">SR #</th>
        <th style="width:100px;">A/C Code</th>
        <th style="width:120px;">Ref # / Invoice #</th>
        <th>A/C Title / Description</th>
        <th class="r" style="width:90px;">Debit</th>
        <th class="r" style="width:90px;">Credit</th>
      </tr>
    </thead>
    <tbody>
      @php $sr = 1; @endphp
      @foreach($rows as $row)
      <tr class="item-main">
        <td>{{ $sr++ }}</td>
        <td>{{ $row['account_code'] ?? '-' }}</td>
        <td>{{ $row['reference'] ?? '-' }}</td>
        <td><strong>{{ $row['account_name'] ?? ($row['account_head'] ?? '-') }}</strong></td>
        <td class="r"></td>
        <td class="r"></td>
      </tr>
      <tr class="item-sub">
        <td></td><td></td><td></td>
        <td>{{ $row['narration'] ?? '-' }}</td>
        <td class="r">
            @if(isset($row['debit']) && $row['debit'] > 0)
                {{ number_format($row['debit'], 2) }}
            @elseif(isset($row['type']) && $row['type'] == 'debit')
                {{ number_format($row['amount'], 2) }}
            @elseif(!isset($row['credit']) && !isset($row['type']))
                {{ number_format($row['amount'], 2) }}
            @else
                0.00
            @endif
        </td>
        <td class="r">
            @if(isset($row['credit']) && $row['credit'] > 0)
                {{ number_format($row['credit'], 2) }}
            @elseif(isset($row['type']) && $row['type'] == 'credit')
                {{ number_format($row['amount'], 2) }}
            @else
                0.00
            @endif
        </td>
      </tr>
      @endforeach

      {{-- Add the Counter-entry row (Cash/Bank) for visualization only if legacy --}}
      @if($needsContraCredit)
      <tr class="item-main">
        <td>{{ $sr }}</td>
        <td>-</td>
        <td>-</td>
        <td><strong>CASH / BANK ACCOUNT</strong></td>
        <td class="r"></td><td class="r"></td>
      </tr>
      <tr class="item-sub">
        <td></td><td></td><td></td>
        <td>Contra Entry</td>
        <td class="r">0.00</td>
        <td class="r">{{ number_format($voucher->total_amount, 2) }}</td>
      </tr>
      @endif
    </tbody>
  </table>

  <!-- IN WORDS / NET TOTAL -->
  <div class="inwords-row">
    <div class="left">
      <strong>In Words :</strong>
      <span id="amountWords">...</span>
    </div>
    <div class="right">
      <span class="net-label">Net Total :</span>
      <div class="net-vals">
        <span>{{ number_format($totalDebit, 2) }}</span>
        <span>{{ number_format($totalCredit, 2) }}</span>
      </div>
    </div>
  </div>

  <!-- AUDIT TRAIL -->
  <div class="audit-row">
    <div class="audit-cell">
      <div><span class="al">Created By :</span> &nbsp;{{ $createdBy }}</div>
      <div><span class="al">Dated :</span> &nbsp;{{ $createdAt }}</div>
    </div>
    <div class="audit-cell">
      <div><span class="al">Modified By :</span> &nbsp;{{ $createdBy }}</div>
      <div><span class="al">Dated :</span> &nbsp;{{ $createdAt }}</div>
    </div>
    <div class="audit-cell">
      <div><span class="al">Verified By :</span> &nbsp;{{ $createdBy }}</div>
      <div><span class="al">Dated :</span> &nbsp;{{ $createdAt }}</div>
    </div>
    <div class="audit-cell">
      <div><span class="al">Posted By :</span> &nbsp;{{ $createdBy }}</div>
      <div><span class="al">Dated :</span> &nbsp;{{ $createdAt }}</div>
    </div>
  </div>

  <!-- APPROVAL -->
  <div class="approval-row">
    <div class="apf">
      <span><strong>Approved By :</strong></span>
      <div class="aline">&nbsp;</div>
    </div>
    <div class="apf">
      <span><strong>Checked By :</strong></span>
      <div class="aline">&nbsp;</div>
    </div>
    <div class="apf">
      <span><strong>Received By :</strong></span>
      <div class="aline">&nbsp;</div>
    </div>
  </div>

  <!-- LP / PO / GRN / VOU -->
  <div class="ref-section">
    <div class="ref-row">
      <span class="ref-label">LP # :</span>
      <span class="ref-line">&nbsp;</span>
    </div>
    <div class="ref-row">
      <span class="ref-label">PO # :</span>
      <span class="ref-line">&nbsp;</span>
    </div>
    <div class="ref-row">
      <span class="ref-label">GRN # :</span>
      <span class="ref-line">&nbsp;</span>
    </div>
    <div class="ref-row" style="margin-top:6px;">
      <span class="ref-label">VOU # :</span>
      <span class="vou-val">{{ $vNo }}</span>
    </div>
  </div>

</div>

<script>
function numberToWords(num) {
    const a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    if ((num = num.toString()).length > 9) return 'Overflow';
    let n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
    if (!n) return;
    let str = '';
    str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + ' Crore ' : '';
    str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + ' Lakh ' : '';
    str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + ' Thousand ' : '';
    str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + ' Hundred ' : '';
    str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) + ' ' : '';
    return str.trim() + ' Rupees Only';
}

document.addEventListener('DOMContentLoaded', function() {
    const total = parseFloat("{{ $voucher->total_amount }}");
    document.getElementById('amountWords').innerText = numberToWords(Math.floor(total));
});

async function downloadPDF() {
  const { jsPDF } = window.jspdf;
  const el = document.getElementById('invoice');
  const canvas = await html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
  const imgData = canvas.toDataURL('image/png');
  const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
  const w = pdf.internal.pageSize.getWidth();
  const h = pdf.internal.pageSize.getHeight();
  pdf.addImage(imgData, 'PNG', 0, 0, w, h);
  pdf.save('{{ $vNo }}.pdf');
}
</script>

</body>
</html>
