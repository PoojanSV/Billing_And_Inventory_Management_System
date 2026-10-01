<?php
session_start();

$usersFile      = "../Admin/users.json";
$inventoryFile  = "../Admin/inventory.json";
$salesFile      = "../Admin/sales_log.csv";

if (!file_exists($usersFile)) file_put_contents($usersFile, "[]");
if (!file_exists($inventoryFile)) file_put_contents($inventoryFile, json_encode(["products" => []], JSON_PRETTY_PRINT));
if (!file_exists($salesFile)) {
    file_put_contents($salesFile, "Invoice No,Date,Time,Customer Name,Customer Phone,Product Code,Product Name,Qty,MRP,Discount %,Final Price,Line Total,Payment Mode\n");
}

$users = json_decode(file_get_contents($usersFile), true);
if (!$users) $users = [];

$inventory = json_decode(file_get_contents($inventoryFile), true);
if (!$inventory || !isset($inventory['products'])) $inventory = ["products" => []];
$products = $inventory['products'];

$success = $error = $productSuccess = $productError = "";

/* ================= ADD USER ================= */
if (isset($_POST['add'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username != "" && $password != "") {
        $exists = false;
        foreach ($users as $u) {
            if ($u['username'] == $username) { $exists = true; break; }
        }
        if (!$exists) {
            $users[] = [
                "username" => $username,
                "password" => password_hash($password, PASSWORD_DEFAULT)
            ];
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
            $success = "User added successfully.";
        } else {
            $error = "Username already exists.";
        }
    } else {
        $error = "Fill all fields.";
    }
}

/* ================= DELETE USER ================= */
if (isset($_GET['delete'])) {
    $delete = $_GET['delete'];
    foreach ($users as $key => $user) {
        if ($user['username'] == $delete) unset($users[$key]);
    }
    $users = array_values($users);
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit();
}

/* ================= ADD PRODUCT ================= */
if (isset($_POST['add_product'])) {
    $code     = trim($_POST['code']);
    $barcode  = trim($_POST['barcode']);
    $name     = trim($_POST['name']);
    $mrp      = floatval($_POST['mrp']);
    $discount = floatval($_POST['discount']);
    $quantity = intval($_POST['quantity']);
    $photoPath = "img/products/default.jpg";

    if ($code != "" && $name != "" && $mrp > 0) {
        $exists = false;
        foreach ($products as $p) {
            if ($p['code'] == $code) { $exists = true; break; }
        }

        if (!$exists) {
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $destDir = "../img/products/";
                if (!is_dir($destDir)) mkdir($destDir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $code) . "." . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $destDir . $safeName)) {
                    $photoPath = "img/products/" . $safeName;
                }
            }

            $finalPrice = $mrp - ($mrp * $discount / 100);

            $products[] = [
                "code"        => $code,
                "barcode"     => $barcode,
                "name"        => $name,
                "mrp"         => $mrp,
                "discount"    => $discount,
                "final_price" => round($finalPrice, 2),
                "quantity"    => $quantity,
                "photo"       => $photoPath
            ];

            $inventory['products'] = $products;
            file_put_contents($inventoryFile, json_encode($inventory, JSON_PRETTY_PRINT));
            $productSuccess = "Product added successfully.";
        } else {
            $productError = "Product code already exists.";
        }
    } else {
        $productError = "Fill required fields (Code, Name, MRP).";
    }
}

/* ================= UPDATE PRODUCT (MRP / Discount / Qty) ================= */
if (isset($_POST['update_product'])) {
    $code     = trim($_POST['edit_code']);
    $mrp      = floatval($_POST['edit_mrp']);
    $discount = floatval($_POST['edit_discount']);
    $quantity = intval($_POST['edit_quantity']);

    foreach ($products as $key => $p) {
        if ($p['code'] == $code) {
            $products[$key]['mrp']         = $mrp;
            $products[$key]['discount']    = $discount;
            $products[$key]['quantity']    = $quantity;
            $products[$key]['final_price'] = round($mrp - ($mrp * $discount / 100), 2);
        }
    }

    $inventory['products'] = $products;
    file_put_contents($inventoryFile, json_encode($inventory, JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit();
}

/* ================= DELETE PRODUCT ================= */
if (isset($_GET['delete_product'])) {
    $code = $_GET['delete_product'];
    foreach ($products as $key => $p) {
        if ($p['code'] == $code) unset($products[$key]);
    }
    $products = array_values($products);
    $inventory['products'] = $products;
    file_put_contents($inventoryFile, json_encode($inventory, JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit();
}

/* ================= LOAD SALES LOG (last 50) ================= */
$salesRows = [];
if (($handle = fopen($salesFile, "r")) !== false) {
    $header = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        $salesRows[] = $row;
    }
    fclose($handle);
}
$salesRows = array_slice($salesRows, -50);
$salesRows = array_reverse($salesRows);
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>
<style>
body{margin:0;background:#f4f4f4;font-family:Arial;}
.container{width:900px;margin:40px auto;background:white;padding:25px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,.2);}
h2{text-align:center;}
h3{margin-top:35px;border-bottom:2px solid #007bff;padding-bottom:8px;}
input,select{width:100%;padding:10px;margin-top:10px;box-sizing:border-box;}
button{margin-top:15px;padding:10px 20px;background:#007bff;color:white;border:none;cursor:pointer;border-radius:4px;}
button:hover{background:#0056b3;}
table{width:100%;border-collapse:collapse;margin-top:20px;font-size:14px;}
table,th,td{border:1px solid #ccc;}
th,td{padding:8px;text-align:center;}
th{background:#f0f0f0;}
.delete{color:red;text-decoration:none;font-weight:bold;}
.edit{color:#007bff;text-decoration:none;font-weight:bold;margin-right:10px;}
.msg{color:green;}
.err{color:red;}
.form-row{display:flex;gap:10px;}
.form-row > div{flex:1;}
.download-link{display:inline-block;margin-top:10px;padding:8px 15px;background:#28a745;color:white;text-decoration:none;border-radius:4px;}
.download-link:hover{background:#1e7e34;}
.thumb{width:40px;height:40px;object-fit:cover;border-radius:4px;}
</style>
</head>
<body>
<div class="container">

<h2>Admin Panel</h2>

<!-- ================= USERS ================= -->
<h3>Cashier Users</h3>

<?php if($success) echo "<p class='msg'>$success</p>"; ?>
<?php if($error) echo "<p class='err'>$error</p>"; ?>

<form method="post">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="add">Add User</button>
</form>

<table>
<tr><th>No.</th><th>Username</th><th>Delete</th></tr>
<?php
if (count($users) == 0) {
    echo "<tr><td colspan='3'>No Users Found</td></tr>";
} else {
    $i = 1;
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $i++ . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td><a class='delete' href='index.php?delete=" . urlencode($user['username']) . "' onclick=\"return confirm('Delete this user?');\">Delete</a></td>";
        echo "</tr>";
    }
}
?>
</table>

<!-- ================= ADD / EDIT PRODUCT ================= -->
<h3 id="product-form-title">Add Product</h3>

<?php if($productSuccess) echo "<p class='msg'>$productSuccess</p>"; ?>
<?php if($productError) echo "<p class='err'>$productError</p>"; ?>

<form method="post" enctype="multipart/form-data" id="productForm">
<input type="hidden" name="edit_code_hidden" id="edit_code_hidden" value="">

<div class="form-row">
<div><input type="text" name="code" id="p_code" placeholder="Product Code (e.g. P003)" required></div>
<div><input type="text" name="barcode" id="p_barcode" placeholder="Barcode (optional)"></div>
</div>

<input type="text" name="name" id="p_name" placeholder="Product Name" required>

<div class="form-row">
<div><input type="number" step="0.01" name="mrp" id="p_mrp" placeholder="MRP (₹)" required></div>
<div><input type="number" step="0.01" name="discount" id="p_discount" placeholder="Discount %" value="0"></div>
<div><input type="number" name="quantity" id="p_quantity" placeholder="Quantity in stock" required></div>
</div>

<input type="file" name="photo" accept="image/*">

<button type="submit" name="add_product" id="addProductBtn">Add Product</button>
<button type="submit" name="update_product" id="updateProductBtn" style="display:none;background:#28a745;">Save Changes</button>
<button type="button" onclick="resetProductForm()" id="cancelEditBtn" style="display:none;background:#6c757d;">Cancel Edit</button>
</form>

<h3>Product Inventory</h3>
<table>
<tr><th>Photo</th><th>Code</th><th>Name</th><th>MRP</th><th>Discount %</th><th>Final Price</th><th>Stock</th><th>Actions</th></tr>
<?php
if (count($products) == 0) {
    echo "<tr><td colspan='8'>No Products Found</td></tr>";
} else {
    foreach ($products as $p) {
        $photo = !empty($p['photo']) ? "../" . $p['photo'] : "";
        echo "<tr>";
        echo "<td>" . ($photo ? "<img class='thumb' src='" . htmlspecialchars($photo) . "'>" : "-") . "</td>";
        echo "<td>" . htmlspecialchars($p['code']) . "</td>";
        echo "<td>" . htmlspecialchars($p['name']) . "</td>";
        echo "<td>₹" . number_format($p['mrp'], 2) . "</td>";
        echo "<td>" . $p['discount'] . "%</td>";
        echo "<td>₹" . number_format($p['final_price'], 2) . "</td>";
        echo "<td>" . $p['quantity'] . "</td>";
        echo "<td>
            <a class='edit' href='javascript:void(0)' onclick=\"editProduct('" . htmlspecialchars($p['code'], ENT_QUOTES) . "'," . floatval($p['mrp']) . "," . floatval($p['discount']) . "," . intval($p['quantity']) . ")\">Edit</a>
            <a class='delete' href='index.php?delete_product=" . urlencode($p['code']) . "' onclick=\"return confirm('Delete this product?');\">Delete</a>
        </td>";
        echo "</tr>";
    }
}
?>
</table>

<!-- ================= SALES LOG ================= -->
<h3>Sales Log (Excel / CSV)</h3>
<a class="download-link" href="../Admin/sales_log.csv" download>Download Full Sales Log (CSV)</a>

<table>
<tr>
<th>Invoice No</th><th>Date</th><th>Time</th><th>Customer</th><th>Phone</th>
<th>Code</th><th>Product</th><th>Qty</th><th>MRP</th><th>Disc %</th><th>Final Price</th><th>Line Total</th><th>Payment</th>
</tr>
<?php
if (count($salesRows) == 0) {
    echo "<tr><td colspan='13'>No Sales Yet</td></tr>";
} else {
    foreach ($salesRows as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
}
?>
</table>

</div>

<script>
function editProduct(code, mrp, discount, qty) {
    document.getElementById('product-form-title').innerText = 'Edit Product: ' + code;
    document.getElementById('p_code').value = code;
    document.getElementById('p_code').readOnly = true;
    document.getElementById('p_mrp').value = mrp;
    document.getElementById('p_discount').value = discount;
    document.getElementById('p_quantity').value = qty;
    document.getElementById('p_name').removeAttribute('required');
    document.getElementById('edit_code_hidden').name = 'edit_code';
    document.getElementById('edit_code_hidden').value = code;
    document.getElementById('p_mrp').name = 'edit_mrp';
    document.getElementById('p_discount').name = 'edit_discount';
    document.getElementById('p_quantity').name = 'edit_quantity';
    document.getElementById('addProductBtn').style.display = 'none';
    document.getElementById('updateProductBtn').style.display = 'inline-block';
    document.getElementById('cancelEditBtn').style.display = 'inline-block';
    window.scrollTo({top: document.getElementById('productForm').offsetTop - 20, behavior: 'smooth'});
}

function resetProductForm() {
    document.getElementById('productForm').reset();
    document.getElementById('product-form-title').innerText = 'Add Product';
    document.getElementById('p_code').readOnly = false;
    document.getElementById('p_mrp').name = 'mrp';
    document.getElementById('p_discount').name = 'discount';
    document.getElementById('p_quantity').name = 'quantity';
    document.getElementById('addProductBtn').style.display = 'inline-block';
    document.getElementById('updateProductBtn').style.display = 'none';
    document.getElementById('cancelEditBtn').style.display = 'none';
}
</script>

</body>
</html>