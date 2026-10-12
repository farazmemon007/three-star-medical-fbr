@php
    $typeMap = [
        'bpv' => 'BANK PAYMENT VOUCHER',
        'brv' => 'BANK RECEIVING VOUCHER',
        'crv' => 'CASH RECEIVING VOUCHER',
        'cpv' => 'CASH PAYMENT VOUCHER',
        'jv'  => 'JOURNAL VOUCHER',
        'payment' => 'PAYMENT VOUCHER',
        'receipt' => 'RECEIPT VOUCHER',
        'expense' => 'EXPENSE VOUCHER',
        'journal' => 'JOURNAL VOUCHER',
    ];

    $vTypeKey = strtolower($voucher->voucher_type ?? 'jv');
    $title = $typeMap[$vTypeKey] ?? strtoupper($vTypeKey) . ' VOUCHER';

    $voucherNo = $voucher->voucher_no ?? '-';
    $vDate = $voucher->date ? \Carbon\Carbon::parse($voucher->date)->format('d/m/Y h:i:sa') : ($voucher->created_at ? $voucher->created_at->format('d/m/Y h:i:sa') : now()->format('d/m/Y h:i:sa'));
    $printedOn = now()->format('d/m/Y');
    
    $chequeNo = $voucher->cheque_no ?? '(N/A)';
    $chequeDate = $voucher->cheque_date ? \Carbon\Carbon::parse($voucher->cheque_date)->format('d/m/Y h:i:sa') : $vDate;

    $location = $voucher->branch->name ?? $voucher->location ?? 'HEAD OFFICE';

    // Users Audit Trail
    $createdByName = $voucher->createdBy->name ?? 'Admin';
    $createdDate = $voucher->created_at ? $voucher->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');
    
    $modifiedByName = $voucher->modifiedBy->name ?? $createdByName;
    $modifiedDate = $voucher->updated_at ? $voucher->updated_at->format('d/m/Y H:i') : $createdDate;

    $verifiedByName = $voucher->verifiedBy->name ?? $createdByName;
    $verifiedDate = $voucher->verified_at ? \Carbon\Carbon::parse($voucher->verified_at)->format('d/m/Y H:i') : $vDate;

    $postedByName = $createdByName;
    $postedDate = $voucher->posted_at ? \Carbon\Carbon::parse($voucher->posted_at)->format('d/m/Y H:i') : $vDate;

    // Number to words helper
    function getAmountInWords($number) {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $hundred = null;
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            0 => '', 1 => 'One', 2 => 'Two',
            3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
            70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        );
        $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
        while( $i < $digits_length ) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
            } else $str[] = null;
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal > 0) ? " and " . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paisa' : '';
        return ($Rupees ? 'Rupees ' . trim($Rupees) : 'Zero Rupees') . $paise . ' Only';
    }

    $totalDebit = 0;
    $totalCredit = 0;
    foreach($rows as $r) {
        $totalDebit += (float)($r['debit'] ?? 0);
        $totalCredit += (float)($r['credit'] ?? 0);
    }
    if ($totalDebit == 0 && $voucher->total_amount > 0) $totalDebit = (float)$voucher->total_amount;
    if ($totalCredit == 0 && $voucher->total_amount > 0) $totalCredit = (float)$voucher->total_amount;
    $amountWords = getAmountInWords(max($totalDebit, $totalCredit));

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
<title>{{ $title }} - {{ $voucherNo }}</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size:11.5px; background:#e4e8ec; color:#111; }

  .action-bar { background:#1e293b; padding:10px 20px; display:flex; gap:10px; justify-content:flex-end; }
  .btn { padding:8px 18px; font-size:13px; font-weight:bold; border:none; border-radius:4px; cursor:pointer; display:flex; align-items:center; gap:6px; color:#fff; }
  .btn-print { background:#10b981; } .btn-print:hover { background:#059669; }
  .btn-pdf { background:#ef4444; } .btn-pdf:hover { background:#dc2626; }
  .btn-back { background:#64748b; text-decoration:none; } .btn-back:hover { background:#475569; }

  .a4 {
    width:820px; min-height:1120px; margin:20px auto;
    background:#fff; padding:28px 32px;
    box-shadow:0 4px 20px rgba(0,0,0,0.15);
    position: relative;
  }

  /* HEADER */
  .header { display:flex; align-items:center; gap:16px; margin-bottom:6px; }
  .logo { height:66px; width:auto; max-width:110px; position:relative; flex-shrink:0; display:flex; align-items:center; }
  .logo img { height:66px; width:auto; max-width:110px; object-fit:contain; display:block; }
  .voucher-title { font-size:23px; font-weight:900; letter-spacing:0.5px; border-bottom:2px solid #111; padding-bottom:3px; flex:1; }

  /* TOP META */
  .meta-grid { display:flex; justify-content:space-between; align-items:flex-start; margin-top:10px; }
  .meta-left { font-size:11px; line-height:1.9; }
  .meta-left .row-item { display:flex; gap:8px; }
  .meta-left .lbl { font-weight:bold; min-width:90px; color:#991b1b; }
  .meta-left .val { font-weight:bold; color:#000; }

  .meta-right { text-align:right; font-size:11px; }
  .stamps-box { display:flex; gap:6px; justify-content:flex-end; margin-bottom:8px; }
  .stamp { border:2px solid #000; padding:4px 18px; font-weight:900; font-size:13px; letter-spacing:0.5px; box-shadow: 2px 2px 0px #000; }

  .printed-on { font-size:11px; margin-top:4px; font-weight:600; }
  .page-num { font-size:11px; margin-top:2px; color:#555; }

  /* TABLE */
  table.items-table { width:100%; border-collapse:collapse; margin-top:14px; font-size:11px; }
  table.items-table thead th {
    border-top:1px dotted #666;
    border-bottom:1px dotted #666;
    padding:6px 4px;
    text-align:left;
    font-weight:bold;
    color:#991b1b;
    font-size:11.5px;
  }
  table.items-table thead th.r { text-align:right; }

  .item-row td {
    padding:6px 4px 2px 4px;
    font-weight:bold;
    color:#000;
  }
  .item-row td.r { text-align:right; }

  .narration-row td {
    padding:1px 4px 8px 4px;
    font-size:10.5px;
    font-weight:normal;
    color:#333;
    border-bottom:1px dotted #ccc;
  }
  .narration-row td.r { text-align:right; }

  /* TOTAL & IN WORDS */
  .total-bar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#e2e8f0;
    border:1px solid #cbd5e1;
    font-weight:bold;
    padding:7px 10px;
    margin-top:10px;
    font-size:11.5px;
  }
  .total-bar .net-nums { display:flex; gap:35px; }

  /* AUDIT TRAIL */
  .audit-box {
    margin-top:14px;
    padding:8px 0;
    border-top:1px dotted #888;
    border-bottom:1px dotted #888;
    display:flex;
    justify-content:space-between;
    font-size:10.5px;
  }
  .audit-col { line-height:1.6; }
  .audit-col .lbl { font-weight:bold; color:#000; }
  .audit-col .val { color:#333; }

  /* SIGNATURES */
  .signatures-box {
    display:flex;
    justify-content:space-between;
    margin-top:35px;
    font-size:11px;
    font-weight:bold;
  }
  .sig-line { width:180px; border-bottom:1px solid #000; margin-left:8px; display:inline-block; vertical-align:middle; }

  /* REFERENCES */
  .ref-box {
    margin-top:35px;
    line-height:2.2;
    font-size:14px;
    font-weight:900;
  }
  .ref-line { width:160px; border-bottom:1px solid #000; margin-left:8px; display:inline-block; vertical-align:middle; }

  @media print {
    body { background:#fff; }
    .action-bar { display:none; }
    .a4 { box-shadow:none; margin:0; width:100%; padding:15px; }
  }
</style>
</head>
<body>

<div class="action-bar">
  <a href="javascript:history.back()" class="btn btn-back">Back</a>
  <button class="btn btn-print" onclick="window.print()">Print Voucher</button>
  <button class="btn btn-pdf" onclick="generatePDF()">Download PDF</button>
</div>

<div class="a4" id="voucherContainer">
  
  <!-- HEADER -->
  <div class="header">
    <div class="logo">
      <img src="{{ $logoSrc }}" alt="Three Stars Medical Supplies">
    </div>
    <div class="voucher-title">{{ $title }}</div>
  </div>

  <!-- TOP META -->
  <div class="meta-grid">
    <div class="meta-left">
      <div class="row-item"><span class="lbl">Location :</span> <span class="val">{{ $location }}</span></div>
      <div class="row-item"><span class="lbl">Voucher No :</span> <span class="val">{{ $voucherNo }}</span></div>
      <div class="row-item"><span class="lbl">Dated :</span> <span class="val">{{ $vDate }}</span></div>
      @if(in_array($vTypeKey, ['bpv', 'brv', 'payment', 'receipt']) && $chequeNo !== '(N/A)')
      <div class="row-item"><span class="lbl">Cheque No :</span> <span class="val">{{ $chequeNo }}</span> &nbsp;&nbsp;&nbsp;&nbsp; <span class="lbl">Cheque Date :</span> <span class="val">{{ $chequeDate }}</span></div>
      @endif
    </div>

    <div class="meta-right">
      <div class="stamps-box">
        <div class="stamp">VERIFIED</div>
        <div class="stamp">POSTED</div>
      </div>
      <div class="printed-on">Printed On : {{ $printedOn }}</div>
      <div class="page-num">Page 1 of 1</div>
    </div>
  </div>

  <!-- TABLE -->
  <table class="items-table">
    <thead>
      <tr>
        <th style="width:5%">SR #</th>
        <th style="width:18%">A/C Code</th>
        <th style="width:47%">A/C Title / Description</th>
        <th style="width:15%" class="r">Debit</th>
        <th style="width:15%" class="r">Credit</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $index => $row)
      <tr class="item-row">
        <td>{{ $index + 1 }}</td>
        <td>{{ $row['account_code'] ?? '-' }}</td>
        <td>{{ $row['account_name'] ?? '-' }}</td>
        <td class="r">{{ number_format((float)($row['debit'] ?? 0), 2) }}</td>
        <td class="r">{{ number_format((float)($row['credit'] ?? 0), 2) }}</td>
      </tr>
      <tr class="narration-row">
        <td></td>
        <td></td>
        <td>{{ $row['narration'] ?? '-' }}</td>
        <td class="r"></td>
        <td class="r"></td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- TOTAL BAR -->
  <div class="total-bar">
    <div>In Words : &nbsp; <span style="font-weight:bold;">{{ $amountWords }}</span></div>
    <div class="net-nums">
      <span>Net Total :</span>
      <span style="min-width:70px; text-align:right;">{{ number_format($totalDebit, 2) }}</span>
      <span style="min-width:70px; text-align:right;">{{ number_format($totalCredit, 2) }}</span>
    </div>
  </div>

  <!-- AUDIT TRAIL -->
  <div class="audit-box">
    <div class="audit-col">
      <div><span class="lbl">Created By :</span> <span class="val">{{ $createdByName }}</span></div>
      <div><span class="lbl">Dated :</span> <span class="val">{{ $createdDate }}</span></div>
    </div>
    <div class="audit-col">
      <div><span class="lbl">Modified By :</span> <span class="val">{{ $modifiedByName }}</span></div>
      <div><span class="lbl">Dated :</span> <span class="val">{{ $modifiedDate }}</span></div>
    </div>
    <div class="audit-col">
      <div><span class="lbl">Verified By :</span> <span class="val">{{ $verifiedByName }}</span></div>
      <div><span class="lbl">Dated :</span> <span class="val">{{ $verifiedDate }}</span></div>
    </div>
    <div class="audit-col">
      <div><span class="lbl">Posted By :</span> <span class="val">{{ $postedByName }}</span></div>
      <div><span class="lbl">Dated :</span> <span class="val">{{ $postedDate }}</span></div>
    </div>
  </div>

  <!-- SIGNATURES -->
  <div class="signatures-box">
    <div>Approved By : <span class="sig-line"></span></div>
    <div>Checked By : <span class="sig-line"></span></div>
    <div>Received By : <span class="sig-line"></span></div>
  </div>

  <!-- REFERENCES -->
  <div class="ref-box">
    <div>LP # : <span class="ref-line"></span></div>
    <div>PO # : <span class="ref-line"></span></div>
    <div>GRN # : <span class="ref-line"></span></div>
  </div>

</div>

<script>
function generatePDF() {
    const { jsPDF } = window.jspdf;
    const element = document.getElementById('voucherContainer');
    
    html2canvas(element, { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgWidth = 210;
        const imgHeight = canvas.height * imgWidth / canvas.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
        pdf.save('{{ $title }}-{{ $voucherNo }}.pdf');
    });
}
</script>
</body>
</html>
