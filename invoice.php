<?php
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_COOKIE["admin_name"]) || !isset($_COOKIE["admin_id"]) || !isset($_COOKIE["admin_email_id"])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['logout'])) {
    // Unset all admin cookies
    setcookie("admin_name", "", time() - 3600, "/", "", true, true);
    setcookie("admin_id", "", time() - 3600, "/", "", true, true);
    setcookie("admin_email_id", "", time() - 3600, "/", "", true, true);
    
    header("Location: login.php");
    exit();
}

// Fetch the latest invoice number from the database
$result = $conn->query("SELECT MAX(invoice_no) AS max_invoice_no FROM invoices");
$row = $result->fetch_assoc();
$max_invoice_no = $row['max_invoice_no'];

if ($max_invoice_no !== null) {
    $numeric_part = intval(substr($max_invoice_no, 3));
    $new_numeric_part = $numeric_part + 1;
    $formatted_invoice_no = 'INV' . str_pad($new_numeric_part, 5, '0', STR_PAD_LEFT);
} else {
    $formatted_invoice_no = 'INV00001';
}

// print_r($formatted_invoice_no);
$response = array();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $invoice_no = $_POST['invoice_no'];
    $invoice_date = $_POST['invoice_date'];
    $company = $_POST['company'];
    $remarks = $_POST['remarks'];
    $products = $_POST['products'];
    $quantities = $_POST['quantities'];
    $rates = $_POST['rates'];
    $amounts = $_POST['amounts'];
    $total_amount = $_POST['total_amount'];

    // Validate required fields
    if (empty($invoice_no) || empty($invoice_date) || empty($company) || empty($products) || empty($quantities) || empty($rates) || empty($amounts)) {
        $response['status'] = 'error';
        $response['message'] = "All fields are required.";
    }

    // Validate product quantities against stock
    foreach ($products as $index => $product_id) {
        $quantity = $quantities[$index];
        $stmt = $conn->prepare("SELECT product_stock FROM product_mst WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($product_stock);
        $stmt->fetch();
        $stmt->close();
    
        if ($product_stock < $quantity) {
            $response['status'] = 'error';
            $response['message'] = "Insufficient stock for product ID $product_id.";
            echo json_encode($response);
            exit;
        }
    }

    if (empty($errorMessages)) {
        // Insert invoice data
        $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, invoice_date, company, remarks, total_amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $invoice_no, $invoice_date, $company, $remarks, $total_amount);
        $stmt->execute();
        $invoice_id = $stmt->insert_id;
        $stmt->close();

        // Insert invoice items and update product stock
        foreach ($products as $index => $product_id) {
            $quantity = $quantities[$index];
            $rate = $rates[$index];
            $amount = $amounts[$index];

            $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $invoice_id, $product_id, $quantity, $rate, $amount);
            $stmt->execute();
            $stmt->close();

            // Update product stock
            $stmt = $conn->prepare("UPDATE product_mst SET product_stock = product_stock - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $quantity, $product_id);
            $stmt->execute();
            $stmt->close();
        }

        $response['status'] = 'success';
        $response['message'] = "Invoice generated successfully!";
    }
    echo json_encode($response);
    exit;
}

// Fetch products from the product_mst table
$products_result = $conn->query("SELECT product_id, product_name, product_des, product_stock FROM product_mst");
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Generation</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card mt-5">
                    <div class="card-header">
                        <h2>New Invoice</h2>
                        <form method="post" style="float:right;">
                            <button type="submit" name="logout" class="btn btn-danger btn-sm">Logout</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <form method="post" id="invoiceForm" action="">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="invoice_no">Invoice No *</label>
                                    <input type="text" class="form-control" id="invoice_no" name="invoice_no" value="<?php echo $formatted_invoice_no; ?>" readonly required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="invoice_date">Invoice Date *</label>
                                    <input type="date" class="form-control" id="invoice_date" name="invoice_date" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="company">Company *</label>
                                    <input type="text" class="form-control" id="company" name="company" required>
                                </div>
                            </div>
                            
                            <h4 class="mt-4">Product Detail</h4>
                            <div id="product-list">
                                <div class="form-row product-item">
                                    <div class="form-group col-md-3">
                                        <label for="product">Choose Product</label>
                                        <select class="form-control" name="products[]">
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?php echo $product['product_id']; ?>">
                                                <?php echo $product['product_id']; ?> - <?php echo $product['product_name']; ?> (Stock: <?php echo $product['product_stock']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="quantity">Quantity</label>
                                        <input type="number" class="form-control" name="quantities[]" required>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="rate">Rate</label>
                                        <input type="text" class="form-control" name="rates[]" required>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="amount">Amount</label>
                                        <input type="text" class="form-control" name="amounts[]" readonly>
                                    </div>
                                    <div class="form-group col-md-2 align-self-end">
                                        <button type="button" class="btn btn-danger remove-product">Remove</button>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-primary mt-3" id="add-product">Add Product</button>
                            
                            <div class="form-row mt-4">
                                <div class="form-group col-md-6">
                                    <label for="remarks">Remarks</label>
                                    <input type="text" class="form-control" id="remarks" name="remarks">
                                </div>
                                <div class="form-group col-md-6 text-right">
                                    <label for="total_amount">Net Amount *</label>
                                    <input type="text" class="form-control" id="total_amount" name="total_amount" readonly>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success">Save</button>
                            <button type="reset" class="btn btn-danger">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#add-product').click(function() {
                var productItem = $('.product-item:first').clone();
                productItem.find('input').val('');
                $('#product-list').append(productItem);
            });

            $(document).on('click', '.remove-product', function() {
                $(this).closest('.product-item').remove();
                calculateTotal();
            });

            $(document).on('input', '[name="quantities[]"], [name="rates[]"]', function() {
                var row = $(this).closest('.product-item');
                var quantity = row.find('[name="quantities[]"]').val();
                var rate = row.find('[name="rates[]"]').val();
                var amount = quantity * rate;
                row.find('[name="amounts[]"]').val(amount.toFixed(2));
                calculateTotal();
            });

            function calculateTotal() {
                var total = 0;
                $('[name="amounts[]"]').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#total_amount').val(total.toFixed(2));
            }

            $('#invoiceForm').submit(function(event) {
                event.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    type: 'POST',
                    url: 'invoice.php',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        // console.log(response);return;
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 3000
                            });
                            $('#invoiceForm')[0].reset();
                            setTimeout(function() {
                                window.location.href = 'admin_dashboard.php';
                            }, 3000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                showConfirmButton: true,
                                confirmButtonText: 'Okay'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to generate invoice. Please try again later.',
                            showConfirmButton: true,
                            confirmButtonText: 'Okay'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
