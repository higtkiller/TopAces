<?php
require_once "other/configDB.php";

$conn = openConnection();
$userId = $_SESSION["user"]["id"] ?? 1;

// Get user data
$userSql = "SELECT email, registerTime, last_login FROM users WHERE id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

// Get current theme
$currentTheme = $_SESSION['theme'] ?? 'dark';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Top Aces</title>
    <link rel="stylesheet" href="styles/themes.css">
    <link rel="stylesheet" href="styles/account.css">
</head>
<body class="<?php echo $currentTheme === 'light' ? 'light-theme' : ''; ?>">
    <div class="page-container">
        <!-- Left Navigation -->
        <nav>
            <img src="assets/logo.png" alt="Top Aces Logo" class="logo">
            <div id="sidebar">
                <a href="index.php?page=home">
                    <img src="assets/dashboard.png" alt="dashboard">
                    <p>Dashboard</p>
                </a>
                <a href="index.php?page=hangar">
                    <img src="assets/hangar.png" alt="hangar">
                    <p>Hangar</p>
                </a>
                <a href="index.php?page=simulator">
                    <img src="assets/simulator.png" alt="simulator">
                    <p>Simulator</p>
                </a>
                <a href="index.php?page=account" class="active">
                    <img src="assets/account.png" alt="account">
                    <p>Account</p>
                </a>
            </div>
            
            <div class="nav-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="user-details">
                        <p class="user-name">Pilot</p>
                        <p class="user-rank">Ace</p>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container">
                <!-- Back Button -->
                <div class="back-nav">
                    <a href="index.php?page=home" class="back-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>

                <!-- Header -->
                <div class="header">
                    <h1>
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m5.657-13.657l-4.243 4.243m0 4.243l4.243 4.243M23 12h-6m-6 0H5m13.657 5.657l-4.243-4.243m0-4.243l-4.243-4.243"/>
                        </svg>
                        Account Settings
                    </h1>
                    <p>Manage your profile and preferences</p>
                </div>

                <!-- Settings Grid -->
                <div class="settings-grid">
                    <!-- Account Info Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h2>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                Account Information
                            </h2>
                        </div>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($userData['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Member Since</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($userData['registerTime'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Last Login</span>
                                <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($userData['last_login'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>