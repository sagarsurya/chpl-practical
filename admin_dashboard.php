<?php
include 'db.php';

if(!isset($_COOKIE["admin_name"])) {
    header("Location: login.php");
    exit();
}
// var_dump($_COOKIE);
if(isset($_POST['logout'])) {
    // Unset all admin cookies
    setcookie("admin_name", "", time() - 3600, "/", "", true, true);
    setcookie("admin_id", "", time() - 3600, "/", "", true, true);
    setcookie("admin_email_id", "", time() - 3600, "/", "", true, true);
    
    header("Location: login.php");
    exit();
}

$invoices_query = $conn->query("SELECT * FROM invoices");
$invoices = $invoices_query->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card mt-5">
                    <div class="card-header">
                        <h2>Admin Dashboard</h2>
                        <form method="post" style="float:right;">
                            <button type="submit" name="logout" class="btn btn-danger btn-sm">Logout</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <h2>Welcome, <?php echo htmlspecialchars($_COOKIE["admin_name"]); ?>!</h2>
                        <div class="card-header mt-3">
                            <h3>Invoices List</h3>
                        </div>
                        <div class="card-body">
                            <a href="invoice.php" class="btn-link">
                                <button type="button" class="btn btn-primary add">Add Invoice</button>
                            </a>
                            <table class="table table-bordered mt-3">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Invoice Date</th>
                                        <th>Company</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="4">No record found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($invoices as $invoice): ?>
                                        <tr>
                                            <td><?php echo $invoice['invoice_no']; ?></td>
                                            <td><?php echo $invoice['invoice_date']; ?></td>
                                            <td><?php echo $invoice['company']; ?></td>
                                            <td><?php echo $invoice['total_amount']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
