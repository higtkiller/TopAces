<?php
$error = '';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    require_once "other/configDB.php";
    
    $conn = openConnection();

    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $repeatPassword = trim($_POST["repeatPassword"] ?? '');
    
    $_SESSION['email'] = $email;


    if ($email === '' || $password === '' || $repeatPassword === '') {
        $error = "Izpolni vsa polja!";
    } elseif ($password !== $repeatPassword) {
        $error = "Gesli se ne ujemata!";
    } elseif(strlen($password) <= 8){
        $error = "Geslo mora biti daljse od 8 znakov";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if($stmt->num_rows > 0){
            $error = "Email že obstaja";
        } else {
            SendVerificationCode($email);
            closeConnection($conn);
            header("Location: index.php?page=emailVerification");
            exit;
        }
        $stmt->close();
    }
    
    closeConnection($conn);
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
    <section id="login_section">
        <form method="post" action="index.php?page=register">
            <h1>Register</h1>
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
            <label for="passowrd">Password</label>
            <input type="password" id="password" name="password">
            <label for="repeatPassword">Repeat password</label>
            <input type="password" id="repeatPassword" name="repeatPassword">
            <p class="error"><?php echo $error; ?></p>
            <button type="submit">Continue</button>
            <p id="register_link" >Alredy have an account ? <a href = "index.php?page=login">Login</a></p>
        </form>
    </section>
</body>
</html>


