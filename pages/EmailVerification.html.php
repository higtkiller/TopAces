<?php 
$error = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if($_SERVER["REQUEST_METHOD"] == "POST"){
    

    require_once "other/configDB.php";
    $conn = openConnection();

    $email = $_SESSION["email"];
    $password = $_SESSION["password"];
    $time = date('Y-m-d H:i:s');
    

    $entered =
    ($_POST['num1'] ?? '') .
    ($_POST['num2'] ?? '') .
    ($_POST['num3'] ?? '') .
    ($_POST['num4'] ?? '') .
    ($_POST['num5'] ?? '') .
    ($_POST['num6'] ?? '');

    if(!isset($_SESSION['verify_code_hash'],$_SESSION['verify_expires'])){
        $error = 'No verification code requested';
    }

    if(time() > $_SESSION['verify_expires']){
        $error = 'Code expired. Please request another code.';
    }

    if (!preg_match('/^\d{6}$/', $entered)) {
        $error = "Enter a valid 6-digit code.";
    }

    if (password_verify($entered, $_SESSION['verify_code_hash'])) {
        unset($_SESSION['verify_code_hash'], $_SESSION['verify_expires'],$_SESSION['email'],$_SESSION["password"]);
        $hashedPassword = password_hash($password,PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email,password,registerTime,last_login) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss",$email,$hashedPassword,$time,$time);
        $stmt->execute();
        $_SESSION['user'] = $email;
        header("Location: index.php?page=home");
        exit;
    
    } else {
        $error =  "Wrong code.";
    }


    
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles/login_page_style.css"> 
</head>
<body>
    <section id="logo_section">
        <img src="assets/logo.png" alt="logo">
    </section>
    <section id="verification_section">
        <form method="post" action="index.php?page=emailVerification">
            <h1>Register</h1>
            <p>A 6-digit verification code has been sent toyour email. Check your inbox and enter the code to verify your account.</p><br>
            <div id="numContainer">
                <div id="inputs">
                    <input type="text" name="num1" id="num1" class="num" maxlength="1" inputmode="numeric" >
                    <input type="text" name="num2" id="num2" class="num" maxlength="1" inputmode="numeric" >
                    <input type="text" name="num3" id="num3" class="num" maxlength="1" inputmode="numeric" >
                    <input type="text" name="num4" id="num4" class="num" maxlength="1" inputmode="numeric" >
                    <input type="text" name="num5" id="num5" class="num" maxlength="1" inputmode="numeric" >
                    <input type="text" name="num6" id="num6" class="num" maxlength="1" inputmode="numeric">
                </div>
                <p class="error"><?php echo $error; ?></p>
                <button type="submit">Register</button>
                <a href="#"><br>Resend code</a>
                </div>
            </div>
            
        </form>
    </section>
</body>
</html>

