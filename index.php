<?php
session_start();
$page = $_GET['page'] ?? 'welcome';



switch($page) {
    case 'home':
        include "pages/home.html.php";
        break;

    case 'welcome':
        include "pages/welcome.html";
        break;

    case 'login':
        include "pages\login.html.php";
        break;

    case 'register':
        include 'pages\register.html.php';
        break;
    
    case 'hangar':
        include "pages/hangar.html.php";
        break;
    
    case 'simulator':
        include "pages/simulator.html.php";
        break;
    
    case 'planeDetails':
        include "pages/planeDetails.html.php";
        break;
    
    case 'combatHistory':
        include "pages/combatHistory.html.php";
        break;
    
    case 'account':
        include "pages/account.html.php";
        break;
     case 'emailVerification':
        include "pages\EmailVerification.html.php";
        break;
    
    case 'admin':
    
    if(!isset($_SESSION["user"]) || strtolower(trim($_SESSION["user"])) !== "tijan.contala@gmail.com") {
        
        header("Location: index.php?page=home");
        exit();
    }
    
    include "pages/admin.html.php";
    break;
    
    default:
        include "pages/home.html.php";
        break;
}
?>
