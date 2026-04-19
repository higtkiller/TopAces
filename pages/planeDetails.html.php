<?php
require_once "other/configDB.php";

$conn = openConnection();

// Get plane ID from URL
$planeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($planeId > 0) {
    $sql = "SELECT * FROM planes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $planeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $plane = $result->fetch_assoc();
        
        // Generate image path
        $planeName = strtolower($plane['name']);
        $planeName = preg_replace("/[^a-z0-9-]/", "", $planeName);
        $imagePath = "assets/planeImages/" . $planeName . ".png";
    } else {
        header("Location: index.php?page=hangar");
        exit();
    }
} else {
    header("Location: index.php?page=hangar");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($plane['name']); ?> - Details</title>
    <link rel="stylesheet" href="styles/themes.css">
    <link rel="stylesheet" href="styles/planeDetails.css">
</head>
<body class="<?php echo ($_SESSION['theme'] ?? 'dark') === 'light' ? 'light-theme' : ''; ?>">
    <div class="container">
        <!-- Back Button -->
        <div class="back-nav">
            <a href="index.php?page=hangar" class="back-btn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Hangar
            </a>
        </div>

        <!-- Main Content -->
        <div class="details-wrapper">
            <!-- Left Side - Image and Basic Info -->
            <div class="left-panel">
                <div class="plane-image-container">
                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($plane['name']); ?>">
                </div>
                
                <div class="basic-info">
                    <h1><?php echo htmlspecialchars($plane['name']); ?></h1>
                    <div class="info-badges">
                        <span class="badge country"><?php echo htmlspecialchars($plane['country']); ?></span>
                        <span class="badge generation">Gen <?php echo htmlspecialchars($plane['generation']); ?></span>
                        <span class="badge role"><?php echo htmlspecialchars($plane['role']); ?></span>
                    </div>
                    <div class="power-rating">
                        <div class="rating-label">Power Rating</div>
                        <div class="rating-bar">
                            <div class="rating-fill" style="width: <?php echo $plane['power_rating']; ?>%"></div>
                            <span class="rating-value"><?php echo $plane['power_rating']; ?>/100</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Detailed Specifications -->
            <div class="right-panel">
                <h2>Technical Specifications</h2>
                
                <div class="specs-grid">
                    <!-- Performance -->
                    <div class="spec-category">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                            </svg>
                            Performance
                        </h3>
                        <div class="spec-items">
                            <div class="spec-item">
                                <span class="spec-label">Max Speed</span>
                                <span class="spec-value">Mach <?php echo $plane['max_speed_mach']; ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Rate of Climb</span>
                                <span class="spec-value"><?php echo $plane['rate_of_climb_ms']; ?> m/s</span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Turn Time</span>
                                <span class="spec-value"><?php echo $plane['turn_time_sec']; ?> sec</span>
                            </div>
                        </div>
                    </div>

                    <!-- Range & Altitude -->
                    <div class="spec-category">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 2v20M2 12h20"/>
                            </svg>
                            Range & Altitude
                        </h3>
                        <div class="spec-items">
                            <div class="spec-item">
                                <span class="spec-label">Combat Radius</span>
                                <span class="spec-value"><?php echo number_format($plane['combat_radius_km']); ?> km</span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Service Ceiling</span>
                                <span class="spec-value"><?php echo number_format($plane['service_ceiling_m']); ?> m</span>
                            </div>
                        </div>
                    </div>

                    <!-- Power & Payload -->
                    <div class="spec-category">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="8" width="18" height="12" rx="2"/>
                                <path d="M12 8V4M8 4h8"/>
                            </svg>
                            Power & Payload
                        </h3>
                        <div class="spec-items">
                            <div class="spec-item">
                                <span class="spec-label">Engine Thrust</span>
                                <span class="spec-value"><?php echo number_format($plane['engine_thrust_kn']); ?> kN</span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Max Payload</span>
                                <span class="spec-value"><?php echo number_format($plane['max_payload_kg']); ?> kg</span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Hardpoints</span>
                                <span class="spec-value"><?php echo $plane['hardpoints']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                

                <!-- Stats Comparison Bars -->
                <div class="comparison-section">
                    <h3>Performance Comparison</h3>
                    <div class="stat-bars">
                        <div class="stat-bar-item">
                            <div class="stat-bar-label">
                                <span>Speed</span>
                                <span>Mach <?php echo $plane['max_speed_mach']; ?></span>
                            </div>
                            <div class="stat-bar-track">
                                <div class="stat-bar-fill speed" style="width: <?php echo min(($plane['max_speed_mach'] / 3) * 100, 100); ?>%"></div>
                            </div>
                        </div>

                        <div class="stat-bar-item">
                            <div class="stat-bar-label">
                                <span>Maneuverability</span>
                                <span><?php echo $plane['turn_time_sec']; ?>s</span>
                            </div>
                            <div class="stat-bar-track">
                                <div class="stat-bar-fill maneuver" style="width: <?php echo max(100 - (($plane['turn_time_sec'] - 15) * 4), 20); ?>%"></div>
                            </div>
                        </div>

                        <div class="stat-bar-item">
                            <div class="stat-bar-label">
                                <span>Range</span>
                                <span><?php echo number_format($plane['combat_radius_km']); ?> km</span>
                            </div>
                            <div class="stat-bar-track">
                                <div class="stat-bar-fill range" style="width: <?php echo min(($plane['combat_radius_km'] / 2500) * 100, 100); ?>%"></div>
                            </div>
                        </div>

                        <div class="stat-bar-item">
                            <div class="stat-bar-label">
                                <span>Payload Capacity</span>
                                <span><?php echo number_format($plane['max_payload_kg']); ?> kg</span>
                            </div>
                            <div class="stat-bar-track">
                                <div class="stat-bar-fill payload" style="width: <?php echo min(($plane['max_payload_kg'] / 15000) * 100, 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>