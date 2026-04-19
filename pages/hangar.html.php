<?php
require_once "other/configDB.php";

$conn = openConnection();

// Get sorting parameter
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort parameter to prevent SQL injection
$allowedSorts = [
    'name', 'country', 'generation', 'role', 'max_speed_mach', 
    'combat_radius_km', 'service_ceiling_m', 'rate_of_climb_ms', 
    'turn_time_sec', 'engine_thrust_kn', 'max_payload_kg', 
    'hardpoints', 'power_rating'
];

if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'name';
}

if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'ASC';
}

$sql = "SELECT id, name, country, generation, role, max_speed_mach, power_rating FROM planes ORDER BY $sortBy $sortOrder";
$stmt = $conn->prepare($sql);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hangar - Top Aces</title>
<link rel="stylesheet" href="styles/themes.css">
<link rel="stylesheet" href="styles/hangar.css">
</head>

<body class="<?php echo ($_SESSION['theme'] ?? 'dark') === 'light' ? 'light-theme' : ''; ?>">
    <div class="top-bar">
        <a href="index.php?page=home" class="dashboard-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>
        
        <h1 class="hangar-title">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
            Aircraft Hangar
        </h1>
        
        <div class="spacer"></div>
    </div>

    <div class="filter-section">
        <div class="filter-container">
            <div class="filter-label">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
                Sort By:
            </div>
            
            <div class="filter-grid">
                <!-- Name -->
                <a href="?page=hangar&sort=name&order=<?php echo ($sortBy == 'name' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>" 
                   class="filter-btn <?php echo $sortBy == 'name' ? 'active' : ''; ?>">
                    Name
                    <?php if($sortBy == 'name'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Country -->
                <a href="?page=hangar&sort=country&order=<?php echo ($sortBy == 'country' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>" 
                   class="filter-btn <?php echo $sortBy == 'country' ? 'active' : ''; ?>">
                    Country
                    <?php if($sortBy == 'country'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Generation -->
                <a href="?page=hangar&sort=generation&order=<?php echo ($sortBy == 'generation' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>" 
                   class="filter-btn <?php echo $sortBy == 'generation' ? 'active' : ''; ?>">
                    Generation
                    <?php if($sortBy == 'generation'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Role -->
                <a href="?page=hangar&sort=role&order=<?php echo ($sortBy == 'role' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>" 
                   class="filter-btn <?php echo $sortBy == 'role' ? 'active' : ''; ?>">
                    Role
                    <?php if($sortBy == 'role'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Max Speed -->
                <a href="?page=hangar&sort=max_speed_mach&order=DESC" 
                   class="filter-btn <?php echo $sortBy == 'max_speed_mach' ? 'active' : ''; ?>">
                    Max Speed
                    <?php if($sortBy == 'max_speed_mach'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Combat Radius -->
                <a href="?page=hangar&sort=combat_radius_km&order=DESC" 
                   class="filter-btn <?php echo $sortBy == 'combat_radius_km' ? 'active' : ''; ?>">
                    Combat Radius
                    <?php if($sortBy == 'combat_radius_km'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Service Ceiling -->
                <a href="?page=hangar&sort=service_ceiling_m&order=DESC" 
                   class="filter-btn <?php echo $sortBy == 'service_ceiling_m' ? 'active' : ''; ?>">
                    Service Ceiling
                    <?php if($sortBy == 'service_ceiling_m'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Climb Rate -->
                <a href="?page=hangar&sort=rate_of_climb_ms&order=DESC" 
                   class="filter-btn <?php echo $sortBy == 'rate_of_climb_ms' ? 'active' : ''; ?>">
                    Climb Rate
                    <?php if($sortBy == 'rate_of_climb_ms'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Maneuverability -->
                <a href="?page=hangar&sort=turn_time_sec&order=ASC" 
                   class="filter-btn <?php echo $sortBy == 'turn_time_sec' ? 'active' : ''; ?>">
                    Maneuverability
                    <?php if($sortBy == 'turn_time_sec'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Engine Thrust -->
                <a href="?page=hangar&sort=engine_thrust_kn&order=DESC" 
                   class="filter-btn <?php echo $sortBy == 'engine_thrust_kn' ? 'active' : ''; ?>">
                    Engine Thrust
                    <?php if($sortBy == 'engine_thrust_kn'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Max Payload -->
                <a href="?page=hangar&sort=max_payload_kg&order=DESC" 
                   class="filter-btn <?php echo $sortBy == 'max_payload_kg' ? 'active' : ''; ?>">
                    Max Payload
                    <?php if($sortBy == 'max_payload_kg'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Hardpoints -->
                <a href="?page=hangar&sort=hardpoints&order=DESC" 
                   class="filter-btn <?php echo $sortBy == 'hardpoints' ? 'active' : ''; ?>">
                    Hardpoints
                    <?php if($sortBy == 'hardpoints'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Power Rating -->
                <a href="?page=hangar&sort=power_rating&order=DESC" 
                   class="filter-btn <?php echo $sortBy == 'power_rating' ? 'active' : ''; ?>">
                    Power Rating
                    <?php if($sortBy == 'power_rating'): ?>
                        <span class="sort-icon"><?php echo $sortOrder == 'ASC' ? '▲' : '▼'; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if($sortBy != 'name'): ?>
                <a href="?page=hangar" class="reset-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    Reset Sorting
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="cardContainer">

    <?php while($row = $result->fetch_assoc()): 

    $planeName = strtolower($row['name']);
    $planeName = preg_replace("/[^a-z0-9-]/", "", $planeName);

    $imagePath = "assets/planeImages/" . $planeName . ".png";
    ?>

    <div class="jet-card">

        <div class="jet-image">
            <img src="<?php echo $imagePath; ?>" alt="Plane Image">
        </div>

        <div class="jet-content">
            <h2>
                <?php echo htmlspecialchars($row['name']); ?>
            </h2>

            <div class="jet-info">
                <span class="info-badge country-badge"><?php echo htmlspecialchars($row['country']); ?></span>
                <span class="info-badge gen-badge">Gen <?php echo htmlspecialchars($row['generation']); ?></span>
            </div>

            <div class="jet-role">
                <?php echo htmlspecialchars($row['role']); ?>
            </div>

            <div class="quick-stats">
                <div class="quick-stat">
                    <span class="stat-label">Speed</span>
                    <span class="stat-value">Mach <?php echo $row['max_speed_mach']; ?></span>
                </div>
                <div class="quick-stat">
                    <span class="stat-label">Power</span>
                    <span class="stat-value"><?php echo $row['power_rating']; ?>/100</span>
                </div>
            </div>

            <a href="index.php?page=planeDetails&id=<?php echo $row['id']; ?>">
                <button class="jet-btn">View Details</button>
            </a>
        </div>

    </div>

    <?php endwhile; ?>

    </div>

</body>
</html>