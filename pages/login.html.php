<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "other/configDB.php";
$error = "";
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $conn = openConnection();
    $email = $_POST['email'];
    $password = $_POST['password'];
    $time = date('Y-m-d H:i:s');

    if(empty($email) || empty($password)){
        $error = 'Enter your email and password!';    
    }
    else if(strlen($password) < 8){
        $error = 'Password is at least 8 characters long';
    }
    else{
        $sql = 'SELECT password FROM users WHERE email = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($hashedPassword);
        if($stmt->fetch()){
            $stmt->close();
            if(password_verify($password, $hashedPassword)){
                $sql = 'UPDATE users SET last_login = ? WHERE email = ?';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss",$time,$email);
                $stmt->execute();
                $stmt->close();
                header("Location: index.php?page=home");
                $_SESSION["user"] = $email;
                exit;
            }
            else{
               $error = 'Wrong password!'; 
            }
        }
        else{
            $error = 'This email doesnt exist';
        }
       
        
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
    <section id="login_section">
        <form method="post">
            <h1>Login</h1>
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
            <label for="passowrd">Password</label>
            <input type="password" id="password" name="password">
            <p class="error"><?php echo $error ?></p>
            <a href="#"><p>Forgot password?</p></a>
            <button type="submit">Login</button>
            <p id="register_link" >Don't have an account ? <a href = "index.php?page=register">Register</a></p>
        </form>
    </section>
</body>
</html>
