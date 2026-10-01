<?php
session_start();

if (!isset($_SESSION['user'])) {
    echo "Unauthorized access";
    exit();
}

$inventoryFile = "../Admin/inventory.json";
if (!file_exists($inventoryFile)) file_put_contents($inventoryFile, json_encode(["products" => []], JSON_PRETTY_PRINT));
$inventory = json_decode(file_get_contents($inventoryFile), true);
$products = $inventory['products'] ?? [];

// Fix photo paths to be relative from client/ folder
foreach ($products as &$p) {
    $p['photo'] = !empty($p['photo']) ? "../" . $p['photo'] : "";
}
unset($p);
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard - Billing</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
html,body{height:100%;overflow:hidden;}
body{margin:0;font-family:Arial;background:#f4f4f4; background:url("../img/bg3.png") center center / cover no-repeat fixed;}
.header{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    z-index:1000;

    background:#00B040;
    color:#fff;
    padding:30px 60px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    box-sizing:border-box;
}

.header h2{
    margin:0;
    font-size:28px;
}

.header-right{
    display:flex;
    align-items:center;
    gap:20px;
}

.user-info{
    text-align:right;
}

.user-info h3{
    margin:0;
    font-size:18px;
    color:#fff;
    border:none;
    padding:0;
}

.user-info p{
    margin:5px 0 0;
    font-size:14px;
    color:#e8ffe8;
}

.logout{
    text-decoration:none;
    color:#fff;
    background:#d32f2f;
    padding:10px 18px;
    border-radius:6px;
    font-weight:bold;
    transition:.2s;
}

.logout:hover{
    background:#b71c1c;
}
.container{
    max-width:1100px;
    height:100vh;
    margin:0 auto;
    padding:115px 20px 10px;
    box-sizing:border-box;
    display:flex;
    flex-direction:column;
    overflow:hidden;
}

.card{background:#fff;padding:12px 16px;border-radius:8px;margin-bottom:10px;box-shadow:0 0 8px rgba(0,0,0,.1);box-sizing:border-box;}
h3{margin:0 0 8px;border-bottom:2px solid black;padding-bottom:6px;font-size:16px;}
input,select{width:100%;padding:7px;margin-top:6px;box-sizing:border-box;border:2px solid black;border-radius:3px;font-size:13px;}
button{padding:8px 16px;background:#007bff;color:white;border:none;cursor:pointer;border-radius:4px;margin-top:8px;font-size:13px;}
button:hover{background:#0056b3;}
.search-results{max-height:150px;overflow-y:auto;border:1px solid #ddd;margin-top:6px;border-radius:4px;}
.search-item{display:flex;align-items:center;gap:10px;padding:6px;border-bottom:1px solid #eee;cursor:pointer;}
.search-item:hover{background:#f0f8ff;}
.search-item img{width:36px;height:36px;object-fit:cover;border-radius:4px;background:#eee;}
.search-item .info{flex:1;}
.search-item .price{font-weight:bold;color:black;}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13px;}
table,th,td{border:1px solid #ccc;}
th,td{padding:5px;text-align:center;}
th{background:#f0f0f0;}
.qty-input{width:55px;padding:4px;}
.remove-btn{background:#dc3545;padding:4px 8px;}
.remove-btn:hover{background:#a71d2a;}
.totals{margin-top:8px;font-size:13px;}
.totals div{display:flex;justify-content:space-between;padding:3px 0;}
.totals .grand{font-size:16px;font-weight:bold;border-top:2px solid #333;padding-top:6px;}
.saved{color:#28a745;font-weight:bold;}
.payment-options{display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;}
.payment-options label{display:flex;align-items:center;gap:5px;background:#f0f0f0;padding:6px 12px;border-radius:5px;cursor:pointer;font-size:13px;}
.payment-options input{width:auto;margin:0;}
.gst-note{color:#555;font-size:12px;margin-top:4px;}
.generate-btn{background:#28a745;font-size:15px;padding:10px;width:100%;}
.generate-btn:hover{background:#1e7e34;}

.generate-btnn{background:#28a745;
font-size:15px;
padding:10px;
width:70%;
 flex:3;}
.generate-btnn:hover{background:#1e7e34;}
.pre{background:#28a745;
font-size:15px;
padding:10px;
width:30%;
flex:1;}
.pre:hover{background:#1e7e34;}

.button-row{
    display:flex;
    gap:10px;
    margin-top:12px;
}


#qrSection{display:none;text-align:center;margin-top:8px;padding:8px;background:#f8f9fa;border-radius:8px;}
#qrcode{display:inline-block;background:#fff;padding:6px;border-radius:8px;line-height:0;}
#step1,
#step2{
    animation:fade .25s;
    overflow-y:auto;
    flex:1 1 auto;
    min-height:0;
}

@keyframes fade{
    from{
        opacity:0;
    }
    to{
        opacity:1;
    }
}

/* keyboard focus highlight */
.kb-focus{
    outline:3px solid #ff9800 !important;
    outline-offset:2px;
    box-shadow:0 0 0 4px rgba(255,152,0,.35) !important;
}
</style>
</head>
<body>

<div class="header">

    <h2>Billing And Inventory Management System</h2>

    <div class="header-right">
        <div class="user-info">
            <h3>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></h3>
            <p>
                <strong>Date:</strong> <?php echo date("d-m-Y"); ?>
                &nbsp; | &nbsp;
                <strong>Time:</strong> <?php echo date("h:i:s A"); ?>
            </p>
        </div>

        <a class="logout" href="logout.php">Logout</a>
    </div>

</div>

<div class="container">



<div class="card">
<h3>Search Product</h3>
<input type="text" id="searchBox" >
<div class="search-results" id="searchResults" style="display:none;"></div>
</div>

<div class="card" style="flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden;">
<h3>Cart</h3>
<div style="overflow-y:auto;max-height:26vh;flex:0 0 auto;">
<table id="cartTable">
<thead>
<tr><th>Product</th><th>MRP</th><th>Discount</th><th>Final Price</th><th>Qty</th><th>Line Total</th><th>Remove</th></tr>
</thead>
<tbody id="cartBody">
<tr id="emptyCartRow"><td colspan="7">Cart is empty</td></tr>
</tbody>
</table>
</div>

<div id="step1">
<div class="totals">
<div><span>Total MRP:</span><span id="totalMrp">₹0.00</span></div>
<div><span>You Saved (Discount):</span><span class="saved" id="totalSaved">₹0.00</span></div>
<div><span>Taxable Amount:</span><span id="taxable">₹0.00</span></div>
<div><span>Tax:</span><span id="taxAmount">₹0.00</span></div>
<div class="grand"><span>Grand Total:</span><span id="grandTotal">₹0.00</span></div>
 <button onclick="nextStep()" class="generate-btn">
            Next
        </button>
</div>
</div>
<div id="step2" style="display:none;">

<div class="card">

<h3>Customer & Payment Details</h3>

<input type="text" id="customerName" placeholder="Customer Name" required>
<input type="text" id="customerPhone" placeholder="Customer Phone (optional)">

<p class="gst-note">
GST No:
<b>24ABCD167F1Z4</b>
</p>

<div class="payment-options">

<label><input type="radio" name="paymentMode" value="COD" checked> Cash</label>

<label><input type="radio" name="paymentMode" value="Online"> Online</label>

<label><input type="radio" name="paymentMode" value="Credit"> Credit</label>

<label><input type="radio" name="paymentMode" value="Debit"> Debit</label>

</div>

<div id="qrSection">

<p style="margin:0 0 10px;font-weight:bold;">
Scan to Pay via UPI
</p>

<div id="qrcode"></div>

<p id="qrAmount"
style="margin-top:10px;font-weight:bold;color:#007bff;font-size:16px;">
</p>

</div>

<div class="step-buttons">


<div class="button-row">
    <button class="pre" onclick="previousStep()">Previous</button>
    <button class="generate-btnn" onclick="generateInvoice()">Generate Invoice</button>
</div>

</div>

</div>

</div>
</div>


<script>
const PRODUCTS = <?php echo json_encode($products); ?>;
let cart = {}; // key: code -> {code,name,mrp,discount,final_price,qty,photo}

const UPI_ID = "vasani.samir3@oksbi";
const MERCHANT_NAME = "Store"; // change to your shop name if you like
let qrCodeInstance = null;

const searchBox = document.getElementById('searchBox');
const searchResults = document.getElementById('searchResults');

searchBox.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (q === "") {
        searchResults.style.display = 'none';
        searchResults.innerHTML = '';
        return;
    }
    const matches = PRODUCTS.filter(p =>
        p.name.toLowerCase().includes(q) ||
        p.code.toLowerCase().includes(q) ||
        (p.barcode && p.barcode.toLowerCase().includes(q))
    );

    if (matches.length === 0) {
        searchResults.innerHTML = '<div class="search-item">No products found</div>';
    } else {
        searchResults.innerHTML = matches.map(p => `
            <div class="search-item" tabindex="0" onclick="addToCart('${p.code}')">
                <img src="${p.photo || ''}" onerror="this.style.visibility='hidden'">
                <div class="info">
                    <div><strong>${escapeHtml(p.name)}</strong> (${escapeHtml(p.code)})</div>
                    <div style="font-size:12px;color:#777;">Stock: ${p.quantity}</div>
                </div>
                <div class="price">₹${Number(p.final_price).toFixed(2)}</div>
            </div>
        `).join('');
    }
    searchResults.style.display = 'block';
    refreshFocusables();
});
function nextStep(){

    if(Object.keys(cart).length===0){
        alert("Please add at least one product.");
        return;
    }

    document.getElementById("step1").style.display="none";
    document.getElementById("step2").style.display="block";

    window.scrollTo({
        top:0,
        behavior:"smooth"
    });

    updateUPIQR();
    refreshFocusables();
    setFocusIndex(0);
}

function previousStep(){

    document.getElementById("step2").style.display="none";
    document.getElementById("step1").style.display="block";

    window.scrollTo({
        top:0,
        behavior:"smooth"
    });
    refreshFocusables();
    setFocusIndex(0);
}
function escapeHtml(str) {
    const div = document.createElement('div');
    div.innerText = str;
    return div.innerHTML;
}

function addToCart(code) {
    const product = PRODUCTS.find(p => p.code === code);
    if (!product) return;

    if (cart[code]) {
        cart[code].qty += 1;
    } else {
        cart[code] = {
            code: product.code,
            name: product.name,
            mrp: parseFloat(product.mrp),
            discount: parseFloat(product.discount),
            final_price: parseFloat(product.final_price),
            qty: 1
        };
    }
    searchBox.value = '';
    searchResults.style.display = 'none';
    renderCart();
}

function updateQty(code, qty) {
    qty = parseInt(qty);
    if (qty <= 0) {
        delete cart[code];
    } else if (cart[code]) {
        cart[code].qty = qty;
    }
    renderCart();
}

function removeFromCart(code) {
    delete cart[code];
    renderCart();
}

function renderCart() {
    const body = document.getElementById('cartBody');
    const codes = Object.keys(cart);

    if (codes.length === 0) {
        body.innerHTML = '<tr id="emptyCartRow"><td colspan="7">Cart is empty</td></tr>';
    } else {
        body.innerHTML = codes.map(code => {
            const item = cart[code];
            const lineTotal = item.final_price * item.qty;
            return `<tr>
                <td>${escapeHtml(item.name)}</td>
                <td>₹${item.mrp.toFixed(2)}</td>
                <td>${item.discount}%</td>
                <td>₹${item.final_price.toFixed(2)}</td>
                <td><input class="qty-input" type="number" min="1" value="${item.qty}" onchange="updateQty('${code}', this.value)"></td>
                <td>₹${lineTotal.toFixed(2)}</td>
                <td><button class="remove-btn" onclick="removeFromCart('${code}')">X</button></td>
            </tr>`;
        }).join('');
    }
    calculateTotals();
    refreshFocusables();
}

function calculateTotals() {
    let totalMrp = 0, totalFinal = 0;
    Object.values(cart).forEach(item => {
        totalMrp += item.mrp * item.qty;
        totalFinal += item.final_price * item.qty;
    });

    const saved = totalMrp - totalFinal;
    const taxRate = 18;
    const tax = totalFinal * taxRate / 100;
    const grandTotal = totalFinal + tax;

    document.getElementById('totalMrp').innerText = '₹' + totalMrp.toFixed(2);
    document.getElementById('totalSaved').innerText = '₹' + saved.toFixed(2);
    document.getElementById('taxable').innerText = '₹' + totalFinal.toFixed(2);
    document.getElementById('taxAmount').innerText = '₹' + tax.toFixed(2);
    document.getElementById('grandTotal').innerText = '₹' + grandTotal.toFixed(2);
    updateUPIQR(); // keep QR amount in sync as cart changes
}



function updateUPIQR() {
    const qrSection = document.getElementById('qrSection');
    const selectedMode = document.querySelector('input[name="paymentMode"]:checked').value;

    if (selectedMode !== 'Online') {
        qrSection.style.display = 'none';
        return;
    }

    const grandTotalText = document.getElementById('grandTotal').innerText.replace('₹', '').trim();
    const amount = parseFloat(grandTotalText) || 0;

    if (amount <= 0) {
        qrSection.style.display = 'none';
        return;
    }

    const invoiceNote = "Bill-" + Date.now();
    const upiLink = `upi://pay?pa=${encodeURIComponent(UPI_ID)}&pn=${encodeURIComponent(MERCHANT_NAME)}&am=${amount.toFixed(2)}&cu=INR&tn=${encodeURIComponent(invoiceNote)}`;

    const qrDiv = document.getElementById('qrcode');
    qrDiv.innerHTML = ''; // clear previous QR before drawing new one

    qrCodeInstance = new QRCode(qrDiv, {
        text: upiLink,
        width: 130,
        height: 130,
        colorDark: "#000000",
        colorLight: "#ffffff"
    });

    document.getElementById('qrAmount').innerText = 'Amount: ₹' + amount.toFixed(2);
    qrSection.style.display = 'block';
}

// Trigger QR generation whenever payment mode changes
document.querySelectorAll('input[name="paymentMode"]').forEach(radio => {
    radio.addEventListener('change', updateUPIQR);
});

function generateInvoice() {
    const codes = Object.keys(cart);
    if (codes.length === 0) {
        alert('Cart is empty. Add at least one product.');
        return;
    }

    const customerName = document.getElementById('customerName').value.trim();
    if (customerName === '') {
        alert('Please enter customer name.');
        return;
    }

    const customerPhone = document.getElementById('customerPhone').value.trim();
   
    const paymentMode = document.querySelector('input[name="paymentMode"]:checked').value;

    const cartArray = codes.map(code => ({ code: cart[code].code, qty: cart[code].qty }));

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'generate_invoice.php';
    form.target = '_blank';

    const fields = {
        cart_json: JSON.stringify(cartArray),
        customer_name: customerName,
        customer_phone: customerPhone,
        tax_rate: 18,
        payment_mode: paymentMode
    };

    for (const key in fields) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// hide search results when clicking elsewhere
document.addEventListener('click', function(e) {
    if (!e.target.closest('#searchBox') && !e.target.closest('#searchResults')) {
        searchResults.style.display = 'none';
    }
});

/* ============================================================
   ARROW-KEY / SPACEBAR NAVIGATION
   Arrow keys move focus between visible focusable elements
   (in reading order). Spacebar "clicks" whatever is focused.
   Typing in a text input still works normally; arrow keys are
   only intercepted for navigation when it makes sense (e.g.
   Up/Down always move focus, Left/Right move focus except
   while inside a text/number input where cursor movement is
   expected).
   ============================================================ */

let focusables = [];
let focusIndex = -1;

function isVisible(el){
    if(!el) return false;
    if(el.disabled) return false;
    const style = window.getComputedStyle(el);
    if(style.display === 'none' || style.visibility === 'hidden') return false;
    // element or an ancestor with display:none check via offsetParent (fails for position:fixed, acceptable here)
    if(el.offsetParent === null && style.position !== 'fixed') return false;
    return true;
}

function refreshFocusables(){
    const selector = [
        '#searchBox',
        '#searchResults .search-item',
        '.qty-input',
        '.remove-btn',
        '#step1 .generate-btn',
        '#customerName',
        '#customerPhone',
        '.payment-options input[type="radio"]',
        '.button-row .pre',
        '.button-row .generate-btnn',
        '.logout'
    ].join(',');

    const nodeList = Array.from(document.querySelectorAll(selector));
    focusables = nodeList.filter(isVisible);

    // keep focus on same element if it still exists
    const current = document.activeElement;
    const idx = focusables.indexOf(current);
    focusIndex = idx !== -1 ? idx : (focusables.length ? 0 : -1);
}

function clearFocusStyles(){
    document.querySelectorAll('.kb-focus').forEach(el => el.classList.remove('kb-focus'));
}

function setFocusIndex(i){
    if(!focusables.length) return;
    if(i < 0) i = 0;
    if(i >= focusables.length) i = focusables.length - 1;
    focusIndex = i;
    clearFocusStyles();
    const el = focusables[focusIndex];
    el.focus();
    el.classList.add('kb-focus');
    if(el.scrollIntoView) el.scrollIntoView({block:'nearest'});
}

function moveFocus(step){
    refreshFocusables();
    if(!focusables.length) return;
    let next = focusIndex + step;
    if(next < 0) next = focusables.length - 1;
    if(next >= focusables.length) next = 0;
    setFocusIndex(next);
}

document.addEventListener('keydown', function(e){
    const active = document.activeElement;
    const tag = active ? active.tagName.toLowerCase() : '';
    const isTextEntry = (tag === 'input' && (active.type === 'text' || active.type === 'number' || active.type === 'tel')) ;

    if(e.key === 'ArrowDown'){
        e.preventDefault();
        moveFocus(1);
    } else if(e.key === 'ArrowUp'){
        e.preventDefault();
        moveFocus(-1);
    } else if(e.key === 'ArrowRight'){
        if(!isTextEntry){
            e.preventDefault();
            moveFocus(1);
        }
    } else if(e.key === 'ArrowLeft'){
        if(!isTextEntry){
            e.preventDefault();
            moveFocus(-1);
        }
    } else if(e.code === 'Space' || e.key === ' '){
        // Let spacebar type normally inside text inputs
        if(isTextEntry) return;
        e.preventDefault();
        if(active){
            if(active.tagName.toLowerCase() === 'input' && active.type === 'radio'){
                active.checked = true;
                active.dispatchEvent(new Event('change', {bubbles:true}));
            } else {
                active.click();
            }
        }
    }
});

// initialise focus list on load and whenever DOM changes relevant to it
window.addEventListener('load', function(){
    refreshFocusables();
    if(focusables.length) setFocusIndex(0);
});
</script>

</body>
</html>