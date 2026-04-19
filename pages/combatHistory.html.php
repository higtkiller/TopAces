<?php
require_once "other/configDB.php";

$conn = openConnection();

// Get user ID from session
$userId = $_SESSION["user"]["id"] ?? 1;

// Pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$countSql = "SELECT COUNT(*) as total FROM combat_history WHERE user_id = ?";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get combat history with pagination
$historySql = "
    SELECT 
        ch.*,
        p1.name as plane1_name,
        p1.country as plane1_country,
        p2.name as plane2_name,
        p2.country as plane2_country,
        TIMESTAMPDIFF(HOUR, ch.simulation_date, NOW()) as hours_ago,
        TIMESTAMPDIFF(DAY, ch.simulation_date, NOW()) as days_ago,
        DATE_FORMAT(ch.simulation_date, '%b %d, %Y at %h:%i %p') as formatted_date
    FROM combat_history ch
    JOIN planes p1 ON ch.plane1_id = p1.id
    JOIN planes p2 ON ch.plane2_id = p2.id
    WHERE ch.user_id = ?
    ORDER BY ch.simulation_date DESC
    LIMIT ? OFFSET ?
";
$historyStmt = $conn->prepare($historySql);
$historyStmt->bind_param("iii", $userId, $perPage, $offset);
$historyStmt->execute();
$combatHistory = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get stats
$statsSql = "
    SELECT 
        COUNT(*) as total_battles,
        SUM(CASE WHEN winner = 'plane1' THEN 1 ELSE 0 END) as plane1_wins,
        SUM(CASE WHEN winner = 'plane2' THEN 1 ELSE 0 END) as plane2_wins
    FROM combat_history 
    WHERE user_id = ?
";
$statsStmt = $conn->prepare($statsSql);
$statsStmt->bind_param("i", $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combat History - Top Aces</title>
    <link rel="stylesheet" href="styles/themes.css">
    <link rel="stylesheet" href="styles/combatHistory.css">
</head>
<body class="<?php echo ($_SESSION['theme'] ?? 'dark') === 'light' ? 'light-theme' : ''; ?>">
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
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                Combat History
            </h1>
            <p>Complete record of all your air-to-air simulations</p>
        </div>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-box">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_battles']; ?></h3>
                    <p>Total Simulations</p>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon blue">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['plane1_wins']; ?></h3>
                    <p>Aircraft 1 Victories</p>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon purple">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['plane2_wins']; ?></h3>
                    <p>Aircraft 2 Victories</p>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="history-container">
            <?php if(count($combatHistory) > 0): ?>
                <div class="history-list">
                    <?php foreach($combatHistory as $battle): 
                        $winnerName = $battle['winner'] == 'plane1' ? $battle['plane1_name'] : $battle['plane2_name'];
                        $loserName = $battle['winner'] == 'plane1' ? $battle['plane2_name'] : $battle['plane1_name'];
                        $winChance = $battle['winner'] == 'plane1' ? $battle['plane1_win_chance'] : $battle['plane2_win_chance'];
                        
                        // Format time ago
                        if($battle['days_ago'] > 7) {
                            $timeAgo = $battle['formatted_date'];
                        } else if($battle['days_ago'] > 0) {
                            $timeAgo = $battle['days_ago'] == 1 ? '1 day ago' : $battle['days_ago'] . ' days ago';
                        } else if($battle['hours_ago'] > 0) {
                            $timeAgo = $battle['hours_ago'] == 1 ? '1 hour ago' : $battle['hours_ago'] . ' hours ago';
                        } else {
                            $timeAgo = 'Just now';
                        }
                    ?>
                    <div class="history-item">
                        <div class="battle-info">
                            <div class="battle-header">
                                <div class="battle-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                    </svg>
                                </div>
                                <div class="battle-time"><?php echo $timeAgo; ?></div>
                            </div>
                            
                            <div class="battle-matchup">
                                <div class="plane-info <?php echo $battle['winner'] == 'plane1' ? 'winner' : 'loser'; ?>">
                                    <h4><?php echo htmlspecialchars($battle['plane1_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($battle['plane1_country']); ?></p>
                                    <div class="chance"><?php echo round($battle['plane1_win_chance']); ?>%</div>
                                </div>
                                
                                <div class="vs-badge">VS</div>
                                
                                <div class="plane-info <?php echo $battle['winner'] == 'plane2' ? 'winner' : 'loser'; ?>">
                                    <h4><?php echo htmlspecialchars($battle['plane2_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($battle['plane2_country']); ?></p>
                                    <div class="chance"><?php echo round($battle['plane2_win_chance']); ?>%</div>
                                </div>
                            </div>
                            
                            <div class="battle-result">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <strong><?php echo htmlspecialchars($winnerName); ?></strong> emerged victorious
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=combatHistory&p=<?php echo $page - 1; ?>" class="page-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="page-info">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </div>
                    
                    <?php if($page < $totalPages): ?>
                        <a href="?page=combatHistory&p=<?php echo $page + 1; ?>" class="page-btn">
                            Next
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                    <h3>No Combat History Yet</h3>
                    <p>Start running simulations to build your combat history!</p>
                    <a href="index.php?page=simulator" class="start-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        Start Your First Simulation
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>