<?php
session_start();

if (!isset($_SESSION['user'])) {
    echo "Unauthorized access";
    exit();
}

$inventoryFile = "../Admin/inventory.json";
$salesFile     = "../Admin/sales_log.csv";
$counterFile   = "../Admin/invoice_counter.txt";

if (!file_exists($inventoryFile)) file_put_contents($inventoryFile, json_encode(["products" => []], JSON_PRETTY_PRINT));
if (!file_exists($salesFile)) {
    file_put_contents($salesFile, "Invoice No,Date,Time,Customer Name,Customer Phone,Product Code,Product Name,Qty,MRP,Discount %,Final Price,Line Total,Payment Mode\n");
}
if (!file_exists($counterFile)) file_put_contents($counterFile, "1000");

$cartInput      = json_decode($_POST['cart_json'] ?? '[]', true);
$customerName   = trim($_POST['customer_name'] ?? '');
$customerPhone  = trim($_POST['customer_phone'] ?? '');
$taxRate        = floatval($_POST['tax_rate'] ?? 0);
$paymentMode    = trim($_POST['payment_mode'] ?? 'COD');
$gstNumber      = "24ABCD167F1Z4";

if (empty($cartInput) || $customerName === '') {
    echo "Invalid invoice request.";
    exit();
}

$inventory = json_decode(file_get_contents($inventoryFile), true);
$products = $inventory['products'] ?? [];

// Build line items using authoritative server-side prices/stock
$lineItems = [];
foreach ($cartInput as $item) {
    $code = $item['code'] ?? '';
    $qty  = max(1, intval($item['qty'] ?? 1));

    foreach ($products as &$p) {
        if ($p['code'] === $code) {
            $lineItems[] = [
                "code"        => $p['code'],
                "name"        => $p['name'],
                "mrp"         => floatval($p['mrp']),
                "discount"    => floatval($p['discount']),
                "final_price" => floatval($p['final_price']),
                "qty"         => $qty,
                "line_total"  => round(floatval($p['final_price']) * $qty, 2)
            ];
            // reduce stock
            $p['quantity'] = max(0, intval($p['quantity']) - $qty);
            break;
        }
    }
    unset($p);
}

if (empty($lineItems)) {
    echo "No valid products found in cart.";
    exit();
}

// Save updated stock
$inventory['products'] = $products;
file_put_contents($inventoryFile, json_encode($inventory, JSON_PRETTY_PRINT));

// Generate invoice number
$invoiceNum = intval(file_get_contents($counterFile)) + 1;
file_put_contents($counterFile, $invoiceNum);
$invoiceNo = "INV-" . $invoiceNum;

$date = date("d-m-Y");
$time = date("h:i:s A");

// Totals
$totalMrp = 0; $totalFinal = 0;
foreach ($lineItems as $li) {
    $totalMrp   += $li['mrp'] * $li['qty'];
    $totalFinal += $li['line_total'];
}
$totalSaved = round($totalMrp - $totalFinal, 2);
$taxAmount  = round($totalFinal * $taxRate / 100, 2);
$grandTotal = round($totalFinal + $taxAmount, 2);

// Append to sales log CSV (Excel-compatible)
$fh = fopen($salesFile, "a");
foreach ($lineItems as $li) {
    fputcsv($fh, [
        $invoiceNo, $date, $time, $customerName, $customerPhone,
        $li['code'], $li['name'], $li['qty'], $li['mrp'], $li['discount'],
        $li['final_price'], $li['line_total'], $paymentMode
    ]);
}
fclose($fh);
?>
<!DOCTYPE html>
<html>
<head>
<title>Invoice <?php echo htmlspecialchars($invoiceNo); ?></title>
<style>
body{font-family:Arial;padding:30px;color:#222;}
.invoice-box{max-width:700px;margin:auto;border:1px solid #ddd;padding:25px;}
h2{text-align:center;margin-bottom:0;}
.store-info{text-align:center;color:#666;font-size:13px;margin-bottom:20px;}
.meta{display:flex;justify-content:space-between;font-size:14px;margin-bottom:15px;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
table,th,td{border:1px solid #ccc;}
th,td{padding:8px;text-align:center;font-size:14px;}
th{background:#f0f0f0;}
.totals{margin-top:15px;width:300px;margin-left:auto;font-size:14px;}
.totals div{display:flex;justify-content:space-between;padding:4px 0;}
.saved{color:#28a745;font-weight:bold;}
.grand{font-size:18px;font-weight:bold;border-top:2px solid #333;padding-top:8px;}
.print-btn{display:block;margin:25px auto 0;padding:12px 25px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;font-size:15px;}
@media print { .print-btn { display:none; } }
</style>
</head>
<body>

<div class="invoice-box">
<h2>PYSH Games Ltd.</h2>
<div class="store-info">GSTIN: <?php echo htmlspecialchars($gstNumber); ?></div>

<div class="meta">
<div><strong>Invoice No:</strong> <?php echo htmlspecialchars($invoiceNo); ?></div>
<div><strong>Date:</strong> <?php echo $date; ?> &nbsp; <strong>Time:</strong> <?php echo $time; ?></div>
</div>

<div class="meta">
<div><strong>Customer:</strong> <?php echo htmlspecialchars($customerName); ?></div>
<div><strong>Phone:</strong> <?php echo htmlspecialchars($customerPhone ?: '-'); ?></div>
</div>

<p><strong>Payment Mode:</strong> <?php echo htmlspecialchars($paymentMode); ?></p>

<table>
<tr><th>Product</th><th>Qty</th><th>MRP</th><th>Discount</th><th>Final Price</th><th>Line Total</th></tr>
<?php foreach ($lineItems as $li): ?>
<tr>
<td><?php echo htmlspecialchars($li['name']); ?></td>
<td><?php echo $li['qty']; ?></td>
<td>₹<?php echo number_format($li['mrp'], 2); ?></td>
<td><?php echo $li['discount']; ?>%</td>
<td>₹<?php echo number_format($li['final_price'], 2); ?></td>
<td>₹<?php echo number_format($li['line_total'], 2); ?></td>
</tr>
<?php endforeach; ?>
</table>

<div class="totals">
<div><span>Total MRP:</span><span>₹<?php echo number_format($totalMrp, 2); ?></span></div>
<div><span>You Saved:</span><span class="saved">₹<?php echo number_format($totalSaved, 2); ?></span></div>
<div><span>Taxable Amount:</span><span>₹<?php echo number_format($totalFinal, 2); ?></span></div>
<div><span>Tax (<?php echo $taxRate; ?>%):</span><span>₹<?php echo number_format($taxAmount, 2); ?></span></div>
<div class="grand"><span>Grand Total:</span><span>₹<?php echo number_format($grandTotal, 2); ?></span></div>
</div>

<p style="text-align:center;margin-top:20px;color:#28a745;font-weight:bold;">
You saved ₹<?php echo number_format($totalSaved, 2); ?> on this bill!
</p>

<button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
</div>

<script>
window.onload = function() {
    window.print();
};
</script>

</body>
</html>