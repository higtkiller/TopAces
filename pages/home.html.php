<?php
require_once "other/configDB.php";

$conn = openConnection();

// Get user ID from session
$userId = $_SESSION["user"]["id"] ?? 1; // Default to 1 for testing

// Get most recently added aircraft (featured)
$featuredSql = "SELECT * FROM planes ORDER BY id DESC LIMIT 1";
$featuredStmt = $conn->prepare($featuredSql);
$featuredStmt->execute();
$featuredPlane = $featuredStmt->get_result()->fetch_assoc();

// Generate featured plane image path
$featuredImageName = strtolower($featuredPlane['name']);
$featuredImageName = preg_replace("/[^a-z0-9-]/", "", $featuredImageName);
$featuredImagePath = "assets/planeImages/" . $featuredImageName . ".png";

// Get top 5 performers by power rating
$topSql = "SELECT name, power_rating FROM planes ORDER BY power_rating DESC, name ASC LIMIT 5";
$topStmt = $conn->prepare($topSql);
$topStmt->execute();
$topPlanes = $topStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get fleet overview by generation
$fleetSql = "
    SELECT 
        generation,
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM planes)), 0) as percentage
    FROM planes 
    GROUP BY generation 
    ORDER BY 
        CASE 
            WHEN generation = '5' THEN 1
            WHEN generation = '4.5' THEN 2
            WHEN generation = '4' THEN 3
            WHEN generation = '3+' THEN 4
            WHEN generation = '3' THEN 5
            WHEN generation = '2' THEN 6
            ELSE 7
        END
";
$fleetStmt = $conn->prepare($fleetSql);
$fleetStmt->execute();
$fleetData = $fleetStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent activity (last 5 simulations)
$activitySql = "
    SELECT 
        ch.*,
        p1.name as plane1_name,
        p2.name as plane2_name,
        TIMESTAMPDIFF(HOUR, ch.simulation_date, NOW()) as hours_ago,
        TIMESTAMPDIFF(DAY, ch.simulation_date, NOW()) as days_ago
    FROM combat_history ch
    JOIN planes p1 ON ch.plane1_id = p1.id
    JOIN planes p2 ON ch.plane2_id = p2.id
    WHERE ch.user_id = ?
    ORDER BY ch.simulation_date DESC
    LIMIT 5
";
$activityStmt = $conn->prepare($activitySql);
$activityStmt->bind_param("i", $userId);
$activityStmt->execute();
$recentActivity = $activityStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total stats
$totalPlanesSql = "SELECT COUNT(*) as total FROM planes";
$totalPlanesResult = $conn->query($totalPlanesSql);
$totalPlanes = $totalPlanesResult->fetch_assoc()['total'];

$totalSimsSql = "SELECT COUNT(*) as total FROM combat_history WHERE user_id = ?";
$totalSimsStmt = $conn->prepare($totalSimsSql);
$totalSimsStmt->bind_param("i", $userId);
$totalSimsStmt->execute();
$totalSims = $totalSimsStmt->get_result()->fetch_assoc()['total'];

// Calculate win rate
$winRateSql = "
    SELECT 
        SUM(CASE WHEN winner = 'plane1' THEN 1 ELSE 0 END) as wins,
        COUNT(*) as total
    FROM combat_history 
    WHERE user_id = ?
";
$winRateStmt = $conn->prepare($winRateSql);
$winRateStmt->bind_param("i", $userId);
$winRateStmt->execute();
$winRateData = $winRateStmt->get_result()->fetch_assoc();
$winRate = $winRateData['total'] > 0 ? round(($winRateData['wins'] / $winRateData['total']) * 100, 1) : 0;

// Calculate total flight hours (simulations * 2.5 hours average)
$flightHours = $totalSims * 2.5;

// Medal emojis for top performers
$medals = ['🥇', '🥈', '🥉', '', ''];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Top Aces</title>
    <link rel="stylesheet" href="styles/themes.css">
    <link rel="stylesheet" href="styles/home_page_style.css">   
</head>
<body class="<?php echo ($_SESSION['theme'] ?? 'dark') === 'light' ? 'light-theme' : ''; ?>">
    <!-- Left Navigation -->
    <nav>
        <img src="assets/logo.png" alt="Top Aces Logo" class="logo">
        <div id="sidebar">
            <a href="index.php?page=home" class="active">
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
            <a href="index.php?page=account">
                <img src="assets/account.png" alt="account">
                <p>Account</p>
            </a>
            <?php if(isset($_SESSION["user"]) && $_SESSION["user"]=== "tijan.contala@gmail.com"): ?>
                <a href="index.php?page=admin" class="admin-nav-link">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <p>Admin Panel</p>
                    <span class="admin-badge-nav">ADMIN</span>
                </a>
            <?php endif; ?>
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

    <!-- Main Dashboard Content -->
    <div id="dashboard">
        <div class="dashboard-header">
            <div>
                <h1>Welcome back, Pilot</h1>
                <p class="subtitle">Your mission control center</p>
            </div>
            <div class="header-actions">
                <button class="action-btn primary" onclick="location.href='index.php?page=simulator'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Start Simulation
                </button>
                <button class="action-btn secondary" onclick="location.href='index.php?page=hangar'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                    View Hangar
                </button>
            </div>
        </div>

        <!-- Stats Cards Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalPlanes; ?></h3>
                    <p>Total Aircraft</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalSims; ?></h3>
                    <p>Simulations Run</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $winRate; ?>%</h3>
                    <p>Win Rate</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($flightHours); ?></h3>
                    <p>Flight Hours</p>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Featured Aircraft -->
            <div class="dashboard-card featured">
                <div class="card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        Featured Aircraft
                    </h2>
                    <span class="featured-tag">Latest Addition</span>
                </div>
                <div class="featured-content">
                    <div class="featured-image">
                        <img src="<?php echo $featuredImagePath; ?>" alt="<?php echo htmlspecialchars($featuredPlane['name']); ?>">
                    </div>
                    <div class="featured-info">
                        <h3><?php echo htmlspecialchars($featuredPlane['name']); ?></h3>
                        <p class="featured-country"><?php echo htmlspecialchars($featuredPlane['country']); ?></p>
                        <div class="featured-badges">
                            <span class="badge"><?php echo htmlspecialchars($featuredPlane['generation']); ?> Generation</span>
                            <span class="badge"><?php echo htmlspecialchars($featuredPlane['role']); ?></span>
                        </div>
                        <div class="featured-stats">
                            <div class="mini-stat">
                                <span class="mini-label">Speed</span>
                                <span class="mini-value">Mach <?php echo $featuredPlane['max_speed_mach']; ?></span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-label">Power</span>
                                <span class="mini-value"><?php echo $featuredPlane['power_rating']; ?>/100</span>
                            </div>
                        </div>
                        <button class="featured-btn" onclick="location.href='index.php?page=planeDetails&id=<?php echo $featuredPlane['id']; ?>'">
                            View Details
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                        Top Performers
                    </h2>
                </div>
                <div class="top-list">
                    <?php foreach($topPlanes as $index => $plane): ?>
                    <div class="top-item">
                        <div class="rank <?php echo $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')); ?>">
                            <?php echo $index + 1; ?>
                        </div>
                        <div class="top-info">
                            <p class="top-name"><?php echo htmlspecialchars($plane['name']); ?></p>
                            <p class="top-spec">Power: <?php echo $plane['power_rating']; ?>/100</p>
                        </div>
                        <?php if($index < 3): ?>
                            <div class="top-badge"><?php echo $medals[$index]; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fleet Overview -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3h18v18H3z"/>
                            <path d="M3 9h18M9 21V9"/>
                        </svg>
                        Fleet Overview
                    </h2>
                </div>
                <div class="fleet-stats">
                    <?php foreach($fleetData as $fleet): ?>
                    <div class="fleet-item">
                        <div class="fleet-label"><?php echo htmlspecialchars($fleet['generation']); ?> Generation</div>
                        <div class="fleet-bar">
                            <div class="fleet-fill <?php 
                                echo $fleet['generation'] == '4.5' ? 'gen45' : 
                                     ($fleet['generation'] == '4' ? 'gen4' : 
                                     ($fleet['generation'] == '3' || $fleet['generation'] == '3+' ? 'gen3' : 
                                     ($fleet['generation'] == '2' ? 'gen2' : ''))); 
                            ?>" style="width: <?php echo $fleet['percentage']; ?>%"></div>
                        </div>
                        <div class="fleet-count"><?php echo $fleet['count']; ?> Aircraft</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        Quick Actions
                    </h2>
                </div>
                <div class="quick-actions">
                    <button class="quick-action-btn" onclick="location.href='index.php?page=hangar&sort=power_rating&order=DESC'">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                        <span>Most Powerful</span>
                    </button>
                    <button class="quick-action-btn" onclick="location.href='index.php?page=hangar&sort=max_speed_mach&order=DESC'">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        <span>Fastest Aircraft</span>
                    </button>
                    <button class="quick-action-btn" onclick="location.href='index.php?page=hangar&sort=turn_time_sec&order=ASC'">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="8 12 12 16 16 12"/>
                        </svg>
                        <span>Most Agile</span>
                    </button>
                    <button class="quick-action-btn" onclick="location.href='index.php?page=simulator&random=1'">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                        <span>Random Battle</span>
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="dashboard-card wide">
                <div class="card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                        Recent Simulations
                    </h2>
                    <a href="index.php?page=combatHistory" class="view-all">View All</a>
                </div>
                <div class="activity-list">
                    <?php if(count($recentActivity) > 0): ?>
                        <?php foreach($recentActivity as $activity): 
                            $winnerName = $activity['winner'] == 'plane1' ? $activity['plane1_name'] : $activity['plane2_name'];
                            $loserName = $activity['winner'] == 'plane1' ? $activity['plane2_name'] : $activity['plane1_name'];
                            $winChance = $activity['winner'] == 'plane1' ? $activity['plane1_win_chance'] : $activity['plane2_win_chance'];
                            
                            // Format time ago
                            if($activity['days_ago'] > 0) {
                                $timeAgo = $activity['days_ago'] == 1 ? '1 day ago' : $activity['days_ago'] . ' days ago';
                            } else if($activity['hours_ago'] > 0) {
                                $timeAgo = $activity['hours_ago'] == 1 ? '1 hour ago' : $activity['hours_ago'] . ' hours ago';
                            } else {
                                $timeAgo = 'Just now';
                            }
                        ?>
                        <div class="activity-item">
                            <div class="activity-icon battle">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                </svg>
                            </div>
                            <div class="activity-info">
                                <p class="activity-title">
                                    <strong><?php echo htmlspecialchars($activity['plane1_name']); ?></strong> 
                                    vs 
                                    <strong><?php echo htmlspecialchars($activity['plane2_name']); ?></strong>
                                </p>
                                <p class="activity-time"><?php echo $timeAgo; ?></p>
                            </div>
                            <div class="activity-result winner-badge">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <?php echo htmlspecialchars($winnerName); ?> (<?php echo round($winChance); ?>%)
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 16v-4M12 8h.01"/>
                            </svg>
                            <p>No recent simulations</p>
                            <p class="empty-subtitle">Run some simulations to see your combat history here!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>