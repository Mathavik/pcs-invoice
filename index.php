<?php
require_once 'session.php';
ensureSessionStarted();
include('header.php');
$loginError = '';
if (!empty($_POST['email']) && !empty($_POST['pwd'])) {
	require_once 'Invoice.php';
	$invoice = new Invoice();
	$user = $invoice->loginUsers($_POST['email'], $_POST['pwd']);
	if (!empty($user)) {
		$_SESSION['user'] = $user[0]['first_name'] . "" . $user[0]['last_name'];
		$_SESSION['userId'] = $user[0]['id'];
		$_SESSION['email'] = $user[0]['email'];
		$_SESSION['address'] = $user[0]['address'];
		$_SESSION['mobile'] = $user[0]['mobile'];
		header("Location:main.php");
	} else {
		$loginError = "Invalid email or password!";
	}
}
?>

<title>Login page</title>
<script src="js/invoice.js"></script>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #0f1e45 0%, #1e3c72 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-wrapper {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 350px;
    height: 450px;
    padding: 40px 30px;
    margin: 20px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.logo {
    text-align: left;
    margin-bottom: 30px;
}

.logo img {
    max-width: 150px;
}

.login-wrapper h4 {
    font-size: 24px;
    text-align: center;
    margin-bottom: 25px;
    color: #222;
}

.form-control {
    width: 100%;
    height: 45px;
    padding: 0 15px;
    border: 1px solid #d1d3d4;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 15px;
    background: #f9f9f9;
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #8548d0;
    background: #fff;
}

.btn-primary {
    background: linear-gradient(180deg, #29BFFF 0%, #8548D0 100%);
    color: white;
    border: none;
    padding: 12px;
    width: 100%;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-primary:hover {
    opacity: 0.95;
}

.below-actions {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 15px;
    gap: 10px;
    flex-wrap: wrap;
}

.inline-link {
    color: #8548D0;
    text-decoration: none;
    font-weight: 500;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.inline-btn {
    background: none;
    border: none;
    color: limegreen;
    font-weight: 500;
    cursor: pointer;
    padding: 0;
    font-size: 14px;
}

.divider {
    color: #999;
}

.alert {
    background: #ffe0e0;
    color: #a70000;
    border-left: 5px solid #f44336;
    padding: 10px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}

@media (max-width: 480px) {
    .login-wrapper {
        height: auto;
        max-width: 90%;
        padding: 30px 20px;
    }
}
</style>

<div class="login-wrapper">
    <div class="logo">
        <img src="img/PCS-Logo" alt="PCS Logo">
    </div>
    <h4><b>Login Here</b></h4>
    <form method="post" action="">
        <?php if ($loginError) { ?>
        <div class="alert"><?php echo $loginError; ?></div>
        <?php } ?>
        <input name="email" id="email" type="email" class="form-control" placeholder="Email address" required>
        <input type="password" class="form-control" name="pwd" placeholder="Password" required>
        <button type="submit" name="login" class="btn-primary">Login</button>
        <div class="below-actions">
            <a href="forget.php" class="inline-link">Forgotten Password?</a>
            <span class="divider">|</span>
            <button type="button" class="inline-btn" onclick="window.location.href='register.php'">Not a User?</button>
        </div>
    </form>
</div>

<!-- <?php include('footer.php'); ?> -->