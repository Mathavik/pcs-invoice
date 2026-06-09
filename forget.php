<?php
include('header.php');
$conn = mysqli_connect("localhost", "root", "", "pcs_invoice");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Forgot Password page</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Overpass&display=swap" rel="stylesheet">
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #0f1e45 0%, #1e3c72 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }

    .reset-box {
        background: #fff;
        padding: 30px 20px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 350px;
        height: 400px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .logo {
        text-align: left;
        margin-bottom: 20px;
    }

    .logo img {
        max-width: 140px;
    }

    h4 {
        font-size: 24px;
        text-align: center;
        margin-bottom: 25px;
        color: #222;
    }

    .form-control {
        height: 40px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
        background: #f8f9fa;
        border: 1px solid #ccc;
        padding: 10px 15px;
    }

    .btn {
        height: 40px;
        font-weight: bold;
        background: linear-gradient(180deg, #29BFFF 0%, #8548D0 100%);
        border: none;
        color: white;
        border-radius: 6px;
        font-size: 15px;
        width: 100%;
    }

    .btn:hover {
        opacity: 0.95;
    }
    </style>
</head>

<body>
    <div class="reset-box">
        <div class="logo">
            <img src="img/PCS-Logo" alt="PCS Logo">
        </div>
        <h4><b>Reset Password</b></h4>
        <form method="post">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
            <input type="password" name="password" class="form-control" placeholder="New Password" required>
            <button type="submit" name="invoice_user" class="btn">Submit</button>
        </form>
    </div>
</body>

</html>

<?php
if (isset($_POST['invoice_user'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $password = md5($pass);
    $q = mysqli_query($conn, "UPDATE `invoice_user` SET `password` = '" . $password . "' WHERE email = '" . $email . "'");
    if ($q) {
        echo "<script>alert('Password Successfully Changed !!')</script>";
        echo "<script>window.location= 'index.php'</script>";
    } else {
        echo "<script>alert('Unable to Change Password !')</script>";
        echo "<meta http-equiv='refresh' content='0'>";
    }
}
?>