@php
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
<title>Delivery Challan - {{ $dc->dc_no }}</title>
<link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;font-size:11px;background:#888;color:#000;}

.action-bar{background:#2c3e50;padding:10px 20px;display:flex;gap:10px;justify-content:flex-end;}
.btn{padding:8px 20px;font-size:13px;font-weight:bold;border:none;border-radius:4px;cursor:pointer;display:flex;align-items:center;gap:6px;color:#fff;}
.btn-print{background:#27ae60;}.btn-print:hover{background:#1e8449;}
.btn-pdf{background:#c0392b;}.btn-pdf:hover{background:#a93226;}
.btn svg{width:15px;height:15px;fill:white;}

.a4{
  width:794px;min-height:1123px;margin:20px auto;
  background:#fff;padding:18px 24px 24px 24px;
  box-shadow:0 2px 14px rgba(0,0,0,0.35);
  display:flex;flex-direction:column;
}

/* HEADER */
.header{display:flex;align-items:center;gap:14px;border-bottom:1.5px solid #000;padding-bottom:10px;margin-bottom:10px;}
.logo-wrap{flex-shrink:0;}
.logo-wrap img{height:75px;width:auto;max-width:130px;object-fit:contain;display:block;}
.co-info{font-size:10.5px;line-height:1.7;}

.doc-title{font-size:18px;font-weight:bold;margin-bottom:8px;}

/* CUSTOMER META */
.meta-block{font-size:10.5px;line-height:1.8;margin-bottom:4px;}
.meta-row{display:flex;gap:6px;}
.meta-label{min-width:70px;font-weight:normal;}
.meta-val{font-weight:bold;}

.doc-nums{display:flex;gap:40px;margin-bottom:2px;font-size:10.5px;}
.dn-col{display:flex;flex-direction:column;gap:1px;}
.dn-row{display:flex;gap:8px;}
.dn-label{font-weight:bold;min-width:40px;}

.loc-page{display:flex;justify-content:space-between;font-size:10.5px;margin-bottom:3px;}
.loc-val{font-weight:bold;}

/* BARCODE */
.barcode-area{text-align:right;margin-bottom:4px;}
.barcode-lines{font-family: 'Libre Barcode 128', cursive; font-size: 42px; line-height: 1; display: inline-block; padding: 0;}
.barcode-num{font-size:8.5px;letter-spacing:0.5px;text-align:center;}

/* TABLE */
table.t{width:100%;border-collapse:collapse;font-size:10.5px;}
table.t thead th{
  background:#e8f4fd;
  border:0.5px solid #7bafd4;
  padding:3px 5px;
  text-align:left;
  font-weight:bold;
  color:#1a56a0;
}
table.t thead th.r{text-align:right;}
table.t tbody td{border:0.5px solid #ccd9e8;padding:2.5px 5px;font-size:10.5px;}
table.t tbody td.r{text-align:right;}

/* REMARKS / NET QTY */
.remarks-net{display:flex;justify-content:space-between;border:0.5px solid #ccd9e8;border-top:none;padding:3px 6px;font-size:10.5px;}

/* ISSUED / CHECKED */
.sig-row{display:flex;gap:60px;margin-top:18px;font-size:10.5px;}
.sig-field{display:flex;flex-direction:column;gap:3px;}
.sig-line{border-bottom:1px solid #000;width:200px;margin-top:2px;}

/* TERMS */
.terms-section{margin-top:auto;padding-top:18px;font-size:9.5px;line-height:1.6;}
.terms-title{font-weight:bold;font-size:10px;margin-bottom:4px;}
.terms-list{padding-left:0;list-style:none;}
.terms-list li{margin-bottom:3px;}
.terms-list li::before{content:"";margin-right:4px;}
.warranty-title{font-weight:bold;font-size:10px;margin-top:8px;margin-bottom:4px;}
.warranty-text{font-size:9.5px;line-height:1.6;text-align:justify;}

.page-footer{display:flex;justify-content:space-between;font-size:9px;color:#444;border-top:0.5px solid #aaa;margin-top:16px;padding-top:4px;}

@media print{
  body{background:#fff;}
  .action-bar{display:none!important;}
  .a4{box-shadow:none;margin:0;width:100%;padding:10mm 14mm;min-height:auto;}
  @page{size:A4;margin:0;}
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
    <div class="logo-wrap">
      <img src="{{ $logoSrc }}" alt="Three Stars Medical Supplies">
    </div>
    <div class="co-info">
      <strong>{{ $dc->branch->name ?? 'Head Office' }} :</strong> {{ $dc->branch->address ?? 'M17-18 Mezanine Floor Seth Centre 10 Syed Mouj Darya Road (Edward Road) Lahore..' }}<br>
      <strong>Phone :</strong> {{ $dc->branch->number ?? '0092-42-37353433' }}
    </div>
  </div>

  <!-- TITLE -->
  <div class="doc-title">Delivery Challan</div>

  <!-- CUSTOMER + BARCODE ROW -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start;">
    <div class="meta-block">
      <div class="meta-row"><span class="meta-label">Customer :</span><span class="meta-val">{{ $dc->customer->customer_name }}</span></div>
      <div class="meta-row"><span class="meta-label">Address :</span><span class="meta-val">{{ $dc->customer->address ?? 'N/A' }}</span></div>
    </div>
    <div class="barcode-area">
        <div style="display:flex;justify-content:center;padding:2px 0;">
            {!! DNS1D::getBarcodeHTML($dc->dc_no ?? (string)$dc->id, 'C128', 1, 35) !!}
        </div>
        <div class="barcode-num">{{ $dc->dc_no }}</div>
    </div>
  </div>

  <!-- DC / ORD -->
  <div style="font-size:10.5px;line-height:1.9;margin-bottom:4px;">
    <div style="display:flex;gap:40px;">
      <span><strong>DC #</strong> &nbsp;{{ $dc->dc_no }} &nbsp;&nbsp;&nbsp; <strong>Dated :</strong> &nbsp;{{ \Carbon\Carbon::parse($dc->delivery_date)->format('d/m/Y') }}</span>
    </div>
    <div>
      <span><strong>ORD #</strong> &nbsp;&nbsp;{{ $dc->sale->invoice_no ?? 'N/A' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Dated :</strong> &nbsp;{{ $dc->sale ? \Carbon\Carbon::parse($dc->sale->created_at)->format('d/m/Y') : 'N/A' }}</span>
    </div>
  </div>

  <!-- LOCATION / PAGE -->
  <div class="loc-page">
    <span>Location : &nbsp;<span class="loc-val">{{ $dc->branch->name ?? 'HEAD OFFICE' }}</span></span>
    <span>Page 1 of 1</span>
  </div>

  <!-- ITEMS TABLE -->
  <table class="t">
    <thead>
      <tr>
        <th style="width:28px;">Sr #</th>
        <th>Item Description</th>
        @if($dc->enable_hs_code)
        <th style="width:75px;">HS Code</th>
        @endif
        <th style="width:52px;">Pack</th>
        <th style="width:76px;">Lot #</th>
        <th style="width:74px;">Expiry Date</th>
        <th class="r" style="width:44px;">Qty</th>
      </tr>
    </thead>
    <tbody>
        @foreach($dc->items as $index => $item)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>
            <div style="font-weight:bold;">{{ $item->product->item_name }} {{ $item->product->brand->name ?? '' }}</div>
            <div style="font-size:9px; color:#444; margin-top:2px;">Code: {{ $item->product->item_code }}</div>
        </td>
        @if($dc->enable_hs_code)
        <td>{{ $item->product->hs_code ?? '-' }}</td>
        @endif
        <td>{{ $item->uom->name ?? ($item->uom_factor > 1 ? '1x' . (int)$item->uom_factor : ($item->product->unit->name ?? 'Piece')) }}</td>
        <td>{{ $item->lot_number }}</td>
        <td>{{ $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->format('d/m/Y') : '-' }}</td>
        <td class="r">{{ number_format($item->total_pieces, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- REMARKS / NET QTY -->
  <div class="remarks-net">
    <span><strong>Remarks :</strong> {{ $dc->remarks ?? '' }}</span>
    <span><strong>Net Quantity :</strong> &nbsp;&nbsp; {{ number_format($dc->items->sum('total_pieces'), 2) }}</span>
  </div>

  <!-- SIGNATURE ROW -->
  <div class="sig-row">
    <div class="sig-field">
      <span><strong>Issued By :</strong></span>
      <div class="sig-line">&nbsp;</div>
    </div>
    <div class="sig-field" style="margin-left:60px;">
      <span><strong>Checked &amp; Received By :</strong></span>
      <div class="sig-line" style="width:240px;">&nbsp;</div>
    </div>
  </div>

  <!-- TERMS & CONDITIONS -->
  <div class="terms-section">
    <div class="terms-title">TERMS &amp; CONDITIONS :</div>
    <ol class="terms-list" style="padding-left:16px;list-style:decimal;">
      <li>Please acknowledge the receipt of goods. Report of shortage etc. must be within 7 days after the receipt of articles, after this no claim will be considered.</li>
      <li>Goods once sold are not returnable. Please do not throw packing material untill the packages are counted and verified as small items are frequently thrown away with packing material.</li>
    </ol>

    <div class="warranty-title">WARRANTY UNDER MEDICAL DEVICES RULES :</div>
    <div class="warranty-text">
      I Sadia Shahbaz being a person, resident in Pakistan, carrying on business holding valid license No ELI-00353 Issued by Drug Regulatory Authority of Pakistan and having authority or being authorized by M/s Three Stars Medical Supplies authorized vide letter dated 01-03-2024 do hereby give this warranty that the medical devices here-under described as sold by me and contained in the bill of sale, invoice, bill of lading or other document describing the medical devices referred to herein do not contravene in any way the provisions of the DRAP Act, 2012 and the rules framed there under.
    </div>
  </div>

  <!-- PAGE FOOTER -->
  <div class="page-footer">
    <span>ProWaves ver.8.0.1.4592 Copyrights &copy; {{ date('Y') }} Cybernetic Technologies. All rights reserved. &nbsp;&nbsp; rptDeliveryChallan</span>
    <span><strong>Print Date :</strong> &nbsp;{{ date('d/m/Y') }}</span>
  </div>

</div>

<script>
async function downloadPDF(){
  const {jsPDF}=window.jspdf;
  const el=document.getElementById('invoice');
  const canvas=await html2canvas(el,{scale:2,useCORS:true,backgroundColor:'#ffffff'});
  const imgData=canvas.toDataURL('image/png');
  const pdf=new jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
  pdf.addImage(imgData,'PNG',0,0,pdf.internal.pageSize.getWidth(),pdf.internal.pageSize.getHeight());
  pdf.save('delivery_challan_{{ $dc->dc_no }}.pdf');
}
</script>
</body>
</html>
