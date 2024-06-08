<?php
include 'db.php';

if(isset($_COOKIE["admin_name"]) && isset($_COOKIE["admin_id"]) && isset($_COOKIE["admin_email_id"])) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT admin_id, admin_name, admin_email_id, admin_password FROM admin WHERE admin_email_id = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($admin_id, $admin_name, $admin_email_id, $admin_password);
        $stmt->fetch();
        
        if ($password === $admin_password) {

            setcookie("admin_name", $admin_name, time() + (86400 * 30), "/");
            setcookie("admin_id", $admin_id, time() + (86400 * 30), "/");
            setcookie("admin_email_id", $admin_email_id, time() + (86400 * 30), "/");

            echo "Login successful!";

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error-message {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header">
                        <h2>Admin Login</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger error-message"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password:</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </form>
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
