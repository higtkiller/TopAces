<?php
require_once "other/configDB.php";

$conn = openConnection();

// Fetch all planes for the dropdowns
$sql = "SELECT id, name, country FROM planes ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->execute();
$planes = $stmt->get_result();

$planesList = [];
while($row = $planes->fetch_assoc()) {
    $planesList[] = $row;
}

// Handle random battle selection
if(isset($_GET['random']) && $_GET['random'] == '1') {
    // Get two random planes
    $randomSql = "SELECT id FROM planes ORDER BY RAND() LIMIT 2";
    $randomStmt = $conn->prepare($randomSql);
    $randomStmt->execute();
    $randomPlanes = $randomStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if(count($randomPlanes) == 2) {
        $_POST['plane1'] = $randomPlanes[0]['id'];
        $_POST['plane2'] = $randomPlanes[1]['id'];
        $_POST['simulate'] = true;
    }
}

// If combat simulation is requested
$showResults = false;
$plane1Data = null;
$plane2Data = null;
$combatResults = null;
$errorMessage = null;

if(isset($_POST['simulate']) && isset($_POST['plane1']) && isset($_POST['plane2'])) {
    $plane1Id = intval($_POST['plane1']);
    $plane2Id = intval($_POST['plane2']);
    
    if($plane1Id > 0 && $plane2Id > 0) {
        if($plane1Id == $plane2Id) {
            $errorMessage = "Please select two different aircraft for combat simulation!";
        } else {
            // Fetch plane 1 data
            $sql = "SELECT * FROM planes WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $plane1Id);
            $stmt->execute();
            $plane1Data = $stmt->get_result()->fetch_assoc();
            
            // Fetch plane 2 data
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $plane2Id);
            $stmt->execute();
            $plane2Data = $stmt->get_result()->fetch_assoc();
            
            if($plane1Data && $plane2Data) {
                $showResults = true;
                $combatResults = calculateCombat($plane1Data, $plane2Data);
                
                // Save simulation results to database
                $userId = $_SESSION["user"]["id"] ?? 1;
                $winner = $combatResults['winner'];
                
                $saveSql = "INSERT INTO combat_history (user_id, plane1_id, plane2_id, plane1_win_chance, plane2_win_chance, winner) VALUES (?, ?, ?, ?, ?, ?)";
                $saveStmt = $conn->prepare($saveSql);
                $saveStmt->bind_param("iiidds", 
                    $userId, 
                    $plane1Id, 
                    $plane2Id, 
                    $combatResults['p1_chance'], 
                    $combatResults['p2_chance'], 
                    $winner
                );
                $saveStmt->execute();
            }
        }
    }
}

function calculateCombat($p1, $p2) {
    // Combat scoring weights
    $weights = [
        'speed' => 0.15,           // Speed advantage
        'maneuverability' => 0.25, // Turn performance (lower is better)
        'climb_rate' => 0.15,      // Climb rate
        'power_rating' => 0.20,    // Overall power
        'payload' => 0.10,         // Weapons capacity
        'service_ceiling' => 0.10, // Altitude advantage
        'thrust' => 0.05           // Engine power
    ];
    
    $p1Score = 0;
    $p2Score = 0;
    
    // Speed comparison (higher is better)
    $speedRatio = $p1['max_speed_mach'] / ($p1['max_speed_mach'] + $p2['max_speed_mach']);
    $p1Score += $speedRatio * $weights['speed'] * 100;
    $p2Score += (1 - $speedRatio) * $weights['speed'] * 100;
    
    // Maneuverability (lower turn time is better)
    $maneuverRatio = $p2['turn_time_sec'] / ($p1['turn_time_sec'] + $p2['turn_time_sec']);
    $p1Score += $maneuverRatio * $weights['maneuverability'] * 100;
    $p2Score += (1 - $maneuverRatio) * $weights['maneuverability'] * 100;
    
    // Climb rate (higher is better)
    $climbRatio = $p1['rate_of_climb_ms'] / ($p1['rate_of_climb_ms'] + $p2['rate_of_climb_ms']);
    $p1Score += $climbRatio * $weights['climb_rate'] * 100;
    $p2Score += (1 - $climbRatio) * $weights['climb_rate'] * 100;
    
    // Power rating (higher is better)
    $powerRatio = $p1['power_rating'] / ($p1['power_rating'] + $p2['power_rating']);
    $p1Score += $powerRatio * $weights['power_rating'] * 100;
    $p2Score += (1 - $powerRatio) * $weights['power_rating'] * 100;
    
    // Payload (higher is better)
    $payloadRatio = $p1['max_payload_kg'] / ($p1['max_payload_kg'] + $p2['max_payload_kg']);
    $p1Score += $payloadRatio * $weights['payload'] * 100;
    $p2Score += (1 - $payloadRatio) * $weights['payload'] * 100;
    
    // Service ceiling (higher is better)
    $ceilingRatio = $p1['service_ceiling_m'] / ($p1['service_ceiling_m'] + $p2['service_ceiling_m']);
    $p1Score += $ceilingRatio * $weights['service_ceiling'] * 100;
    $p2Score += (1 - $ceilingRatio) * $weights['service_ceiling'] * 100;
    
    // Thrust (higher is better)
    $thrustRatio = $p1['engine_thrust_kn'] / ($p1['engine_thrust_kn'] + $p2['engine_thrust_kn']);
    $p1Score += $thrustRatio * $weights['thrust'] * 100;
    $p2Score += (1 - $thrustRatio) * $weights['thrust'] * 100;
    
    // Normalize to percentages
    $total = $p1Score + $p2Score;
    $p1WinChance = ($p1Score / $total) * 100;
    $p2WinChance = ($p2Score / $total) * 100;
    
    // Determine advantages
    $advantages = [
        'p1' => [],
        'p2' => []
    ];
    
    if($p1['max_speed_mach'] > $p2['max_speed_mach']) {
        $advantages['p1'][] = 'Speed Advantage';
    } else {
        $advantages['p2'][] = 'Speed Advantage';
    }
    
    if($p1['turn_time_sec'] < $p2['turn_time_sec']) {
        $advantages['p1'][] = 'Superior Maneuverability';
    } else {
        $advantages['p2'][] = 'Superior Maneuverability';
    }
    
    if($p1['rate_of_climb_ms'] > $p2['rate_of_climb_ms']) {
        $advantages['p1'][] = 'Better Climb Rate';
    } else {
        $advantages['p2'][] = 'Better Climb Rate';
    }
    
    if($p1['service_ceiling_m'] > $p2['service_ceiling_m']) {
        $advantages['p1'][] = 'Higher Altitude Capability';
    } else {
        $advantages['p2'][] = 'Higher Altitude Capability';
    }
    
    if($p1['max_payload_kg'] > $p2['max_payload_kg']) {
        $advantages['p1'][] = 'Greater Weapons Capacity';
    } else {
        $advantages['p2'][] = 'Greater Weapons Capacity';
    }
    
    return [
        'p1_chance' => round($p1WinChance, 1),
        'p2_chance' => round($p2WinChance, 1),
        'advantages' => $advantages,
        'winner' => $p1WinChance > $p2WinChance ? 'plane1' : 'plane2'
    ];
}

// Generate image paths
function getPlaneImage($planeName) {
    $name = strtolower($planeName);
    $name = preg_replace("/[^a-z0-9-]/", "", $name);
    return "assets/planeImages/" . $name . ".png";
}

$currentTheme = $_SESSION['theme'] ?? 'dark';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combat Simulator - Top Aces</title>
    <link rel="stylesheet" href="styles/themes.css">
    <link rel="stylesheet" href="styles/simulator.css">
    <script src="scripts/battle-animation.js"></script>
</head>
<body class="<?php echo $currentTheme === 'light' ? 'light-theme' : ''; ?>">
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

        <div class="header">
            <h1>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                Air Combat Simulator
            </h1>
            <p>Select two aircraft to simulate an air-to-air engagement</p>
        </div>

        <?php if($errorMessage): ?>
        <div class="error-message">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="simulator-form">
            <?php if($showResults): ?>
            <div class="success-message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Simulation saved to combat history!
            </div>
            <?php endif; ?>

            <div class="selection-grid">
                <!-- Plane 1 Selection -->
                <div class="plane-selector">
                    <label>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        </svg>
                        Aircraft 1
                    </label>
                    <select name="plane1" required id="plane1Select">
                        <option value="">Select Aircraft...</option>
                        <?php foreach($planesList as $plane): ?>
                            <option value="<?php echo $plane['id']; ?>" 
                                <?php echo (isset($_POST['plane1']) && $_POST['plane1'] == $plane['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plane['name']); ?> (<?php echo htmlspecialchars($plane['country']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="vs-divider">
                    <span>VS</span>
                </div>

                <!-- Plane 2 Selection -->
                <div class="plane-selector">
                    <label>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        </svg>
                        Aircraft 2
                    </label>
                    <select name="plane2" required id="plane2Select">
                        <option value="">Select Aircraft...</option>
                        <?php foreach($planesList as $plane): ?>
                            <option value="<?php echo $plane['id']; ?>"
                                <?php echo (isset($_POST['plane2']) && $_POST['plane2'] == $plane['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plane['name']); ?> (<?php echo htmlspecialchars($plane['country']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" name="simulate" class="simulate-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
                Run Simulation
            </button>
        </form>

        <?php if($showResults && $plane1Data && $plane2Data): ?>
        
        <!-- Battle Animation -->
        <div class="battle-animation-3d">
            <canvas id="battleCanvas" width="1200" height="600"></canvas>
            <div class="battle-commentary">
                <p class="commentary-text" id="commentaryText">Engaging...</p>
            </div>
        </div>

        <div class="results-container" style="display: none;">
            <h2 class="results-title">Simulation Results</h2>
            
            <!-- Combat Matchup -->
            <div class="matchup-display">
                <div class="plane-card <?php echo $combatResults['winner'] == 'plane1' ? 'winner' : ''; ?>">
                    <div class="plane-image">
                        <img src="<?php echo getPlaneImage($plane1Data['name']); ?>" alt="<?php echo htmlspecialchars($plane1Data['name']); ?>">
                    </div>
                    <h3><?php echo htmlspecialchars($plane1Data['name']); ?></h3>
                    <p class="country"><?php echo htmlspecialchars($plane1Data['country']); ?></p>
                    <div class="win-chance">
                        <div class="chance-label">Win Probability</div>
                        <div class="chance-value"><?php echo $combatResults['p1_chance']; ?>%</div>
                        <div class="chance-bar">
                            <div class="chance-fill" style="width: <?php echo $combatResults['p1_chance']; ?>%"></div>
                        </div>
                    </div>
                    <?php if(count($combatResults['advantages']['p1']) > 0): ?>
                    <div class="advantages">
                        <h4>Advantages:</h4>
                        <ul>
                            <?php foreach($combatResults['advantages']['p1'] as $advantage): ?>
                                <li><?php echo $advantage; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="battle-indicator">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </div>

                <div class="plane-card <?php echo $combatResults['winner'] == 'plane2' ? 'winner' : ''; ?>">
                    <div class="plane-image">
                        <img src="<?php echo getPlaneImage($plane2Data['name']); ?>" alt="<?php echo htmlspecialchars($plane2Data['name']); ?>">
                    </div>
                    <h3><?php echo htmlspecialchars($plane2Data['name']); ?></h3>
                    <p class="country"><?php echo htmlspecialchars($plane2Data['country']); ?></p>
                    <div class="win-chance">
                        <div class="chance-label">Win Probability</div>
                        <div class="chance-value"><?php echo $combatResults['p2_chance']; ?>%</div>
                        <div class="chance-bar">
                            <div class="chance-fill" style="width: <?php echo $combatResults['p2_chance']; ?>%"></div>
                        </div>
                    </div>
                    <?php if(count($combatResults['advantages']['p2']) > 0): ?>
                    <div class="advantages">
                        <h4>Advantages:</h4>
                        <ul>
                            <?php foreach($combatResults['advantages']['p2'] as $advantage): ?>
                                <li><?php echo $advantage; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detailed Stats Comparison -->
            <div class="stats-comparison">
                <h3>Performance Comparison</h3>
                <div class="comparison-grid">
                    <div class="stat-comparison">
                        <div class="stat-name">Max Speed</div>
                        <div class="stat-bars-container">
                            <div class="stat-bar left">
                                <span class="stat-value">Mach <?php echo $plane1Data['max_speed_mach']; ?></span>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo min(($plane1Data['max_speed_mach'] / 3) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-bar right">
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo min(($plane2Data['max_speed_mach'] / 3) * 100, 100); ?>%"></div>
                                </div>
                                <span class="stat-value">Mach <?php echo $plane2Data['max_speed_mach']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-comparison">
                        <div class="stat-name">Turn Time</div>
                        <div class="stat-bars-container">
                            <div class="stat-bar left">
                                <span class="stat-value"><?php echo $plane1Data['turn_time_sec']; ?>s</span>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo max(100 - (($plane1Data['turn_time_sec'] - 15) * 4), 20); ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-bar right">
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo max(100 - (($plane2Data['turn_time_sec'] - 15) * 4), 20); ?>%"></div>
                                </div>
                                <span class="stat-value"><?php echo $plane2Data['turn_time_sec']; ?>s</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-comparison">
                        <div class="stat-name">Climb Rate</div>
                        <div class="stat-bars-container">
                            <div class="stat-bar left">
                                <span class="stat-value"><?php echo $plane1Data['rate_of_climb_ms']; ?> m/s</span>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo min(($plane1Data['rate_of_climb_ms'] / 350) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-bar right">
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo min(($plane2Data['rate_of_climb_ms'] / 350) * 100, 100); ?>%"></div>
                                </div>
                                <span class="stat-value"><?php echo $plane2Data['rate_of_climb_ms']; ?> m/s</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-comparison">
                        <div class="stat-name">Service Ceiling</div>
                        <div class="stat-bars-container">
                            <div class="stat-bar left">
                                <span class="stat-value"><?php echo number_format($plane1Data['service_ceiling_m']); ?> m</span>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo min(($plane1Data['service_ceiling_m'] / 25000) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-bar right">
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo min(($plane2Data['service_ceiling_m'] / 25000) * 100, 100); ?>%"></div>
                                </div>
                                <span class="stat-value"><?php echo number_format($plane2Data['service_ceiling_m']); ?> m</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-comparison">
                        <div class="stat-name">Max Payload</div>
                        <div class="stat-bars-container">
                            <div class="stat-bar left">
                                <span class="stat-value"><?php echo number_format($plane1Data['max_payload_kg']); ?> kg</span>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo min(($plane1Data['max_payload_kg'] / 15000) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-bar right">
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo min(($plane2Data['max_payload_kg'] / 15000) * 100, 100); ?>%"></div>
                                </div>
                                <span class="stat-value"><?php echo number_format($plane2Data['max_payload_kg']); ?> kg</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-comparison">
                        <div class="stat-name">Power Rating</div>
                        <div class="stat-bars-container">
                            <div class="stat-bar left">
                                <span class="stat-value"><?php echo $plane1Data['power_rating']; ?>/100</span>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo $plane1Data['power_rating']; ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-bar right">
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo $plane2Data['power_rating']; ?>%"></div>
                                </div>
                                <span class="stat-value"><?php echo $plane2Data['power_rating']; ?>/100</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-button" onclick="location.href='index.php?page=simulator'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    New Battle
                </button>
                <button class="action-button secondary" onclick="location.href='index.php?page=simulator&random=1'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                    Random Battle
                </button>
                <button class="action-button secondary" onclick="location.href='index.php?page=home'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Dashboard
                </button>
            </div>
        </div>

        <script>
        // Initialize advanced battle animation
        const battleAnim = new BattleAnimation(
            {
                name: '<?php echo addslashes($plane1Data['name']); ?>',
                image: '<?php echo getPlaneImage($plane1Data['name']); ?>',
                climb_rate: <?php echo $plane1Data['rate_of_climb_ms']; ?>,
                turn_time: <?php echo $plane1Data['turn_time_sec']; ?>
            },
            {
                name: '<?php echo addslashes($plane2Data['name']); ?>',
                image: '<?php echo getPlaneImage($plane2Data['name']); ?>',
                climb_rate: <?php echo $plane2Data['rate_of_climb_ms']; ?>,
                turn_time: <?php echo $plane2Data['turn_time_sec']; ?>
            },
            '<?php echo $combatResults['winner']; ?>',
            <?php echo json_encode($combatResults['advantages']); ?>
        );
        </script>
        <?php endif; ?>
    </div>
</body>
</html>