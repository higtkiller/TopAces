<?php
require_once "other/configDB.php";

$conn = openConnection();
$message = "";
$messageType = "";

// Function to calculate power rating based on plane specs
function calculatePowerRating($max_speed_mach, $combat_radius_km, $service_ceiling_m, $rate_of_climb_ms, $turn_time_sec, $engine_thrust_kn, $max_payload_kg, $hardpoints) {
    // Normalize values to 0-100 scale based on typical fighter jet ranges
    
    // Speed score (Mach 0.8 to 3.5)
    $speedScore = min(($max_speed_mach - 0.8) / (3.5 - 0.8) * 100, 100);
    
    // Combat radius score (200km to 3000km)
    $radiusScore = min(($combat_radius_km - 200) / (3000 - 200) * 100, 100);
    
    // Service ceiling score (8000m to 25000m)
    $ceilingScore = min(($service_ceiling_m - 8000) / (25000 - 8000) * 100, 100);
    
    // Climb rate score (50 m/s to 350 m/s)
    $climbScore = min(($rate_of_climb_ms - 50) / (350 - 50) * 100, 100);
    
    // Turn time score (lower is better: 10s to 30s, inverted)
    $turnScore = max(0, 100 - (($turn_time_sec - 10) / (30 - 10) * 100));
    
    // Engine thrust score (20kN to 200kN)
    $thrustScore = min(($engine_thrust_kn - 20) / (200 - 20) * 100, 100);
    
    // Payload score (500kg to 15000kg)
    $payloadScore = min(($max_payload_kg - 500) / (15000 - 500) * 100, 100);
    
    // Hardpoints score (2 to 15)
    $hardpointsScore = min(($hardpoints - 2) / (15 - 2) * 100, 100);
    
    // Weighted average (different stats have different importance)
    $weights = [
        'speed' => 0.15,
        'radius' => 0.10,
        'ceiling' => 0.10,
        'climb' => 0.15,
        'turn' => 0.20,      // Maneuverability is very important
        'thrust' => 0.15,
        'payload' => 0.10,
        'hardpoints' => 0.05
    ];
    
    $powerRating = (
        $speedScore * $weights['speed'] +
        $radiusScore * $weights['radius'] +
        $ceilingScore * $weights['ceiling'] +
        $climbScore * $weights['climb'] +
        $turnScore * $weights['turn'] +
        $thrustScore * $weights['thrust'] +
        $payloadScore * $weights['payload'] +
        $hardpointsScore * $weights['hardpoints']
    );
    
    // Round to nearest integer and ensure it's between 1-100
    return max(1, min(100, round($powerRating)));
}

// Handle plane deletion
if(isset($_POST['delete_plane']) && isset($_POST['plane_id'])) {
    $planeId = intval($_POST['plane_id']);
    
    $deleteSql = "DELETE FROM planes WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $planeId);
    
    if($deleteStmt->execute()) {
        $message = "Plane deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting plane: " . $conn->error;
        $messageType = "error";
    }
}

// Handle plane addition
if(isset($_POST['add_plane'])) {
    $name = trim($_POST['name']);
    $country = trim($_POST['country']);
    $generation = trim($_POST['generation']);
    $role = trim($_POST['role']);
    $max_speed_mach = floatval($_POST['max_speed_mach']);
    $combat_radius_km = floatval($_POST['combat_radius_km']);
    $service_ceiling_m = intval($_POST['service_ceiling_m']);
    $rate_of_climb_ms = floatval($_POST['rate_of_climb_ms']);
    $turn_time_sec = floatval($_POST['turn_time_sec']);
    $engine_thrust_kn = floatval($_POST['engine_thrust_kn']);
    $max_payload_kg = intval($_POST['max_payload_kg']);
    $hardpoints = intval($_POST['hardpoints']);
    
    // Calculate power rating automatically
    $power_rating = calculatePowerRating(
        $max_speed_mach, 
        $combat_radius_km, 
        $service_ceiling_m, 
        $rate_of_climb_ms, 
        $turn_time_sec, 
        $engine_thrust_kn, 
        $max_payload_kg, 
        $hardpoints
    );
    
    // Validate required fields
    if(empty($name) || empty($country)) {
        $message = "Name and Country are required!";
        $messageType = "error";
    } else {
        // Handle image upload
        $imageUploaded = false;
        if(isset($_FILES['plane_image']) && $_FILES['plane_image']['error'] == 0) {
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
            $fileType = $_FILES['plane_image']['type'];
            
            if(in_array($fileType, $allowedTypes)) {
                // Generate filename from plane name (lowercase, no special chars)
                $imageName = strtolower($name);
                $imageName = preg_replace("/[^a-z0-9-]/", "", $imageName);
                $imageName .= '.png'; // Always save as .png
                
                $uploadDir = 'assets/planeImages/';
                $uploadPath = $uploadDir . $imageName;
                
                // Create directory if it doesn't exist
                if(!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Move uploaded file
                if(move_uploaded_file($_FILES['plane_image']['tmp_name'], $uploadPath)) {
                    $imageUploaded = true;
                } else {
                    $message = "Error uploading image!";
                    $messageType = "error";
                }
            } else {
                $message = "Only PNG and JPG images are allowed!";
                $messageType = "error";
            }
        }
        
        // Insert plane into database (only if no image upload error)
        if($messageType !== "error") {
            $insertSql = "INSERT INTO planes (name, country, generation, role, max_speed_mach, combat_radius_km, service_ceiling_m, rate_of_climb_ms, turn_time_sec, engine_thrust_kn, max_payload_kg, hardpoints, power_rating) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("ssssddidddiii", 
                $name, $country, $generation, $role, 
                $max_speed_mach, $combat_radius_km, $service_ceiling_m, 
                $rate_of_climb_ms, $turn_time_sec, $engine_thrust_kn, 
                $max_payload_kg, $hardpoints, $power_rating
            );
            
            if($insertStmt->execute()) {
                if($imageUploaded) {
                    $message = "Plane and image added successfully! Power Rating: {$power_rating}/100";
                } else {
                    $message = "Plane added successfully (no image uploaded). Power Rating: {$power_rating}/100";
                }
                $messageType = "success";
            } else {
                $message = "Error adding plane: " . $conn->error;
                $messageType = "error";
            }
        }
    }
}

// Fetch all planes
$sql = "SELECT * FROM planes ORDER BY name ASC";
$result = $conn->query($sql);
$planes = [];
while($row = $result->fetch_assoc()) {
    $planes[] = $row;
}

$currentTheme = $_SESSION['theme'] ?? 'dark';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Top Aces</title>
    <link rel="stylesheet" href="styles/themes.css">
    <link rel="stylesheet" href="styles/admin.css">
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
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Admin Panel
            </h1>
            <p>Manage aircraft database</p>
        </div>

        <?php if($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?php if($messageType === 'success'): ?>
                    <polyline points="20 6 9 17 4 12"/>
                <?php else: ?>
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                <?php endif; ?>
            </svg>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Add Plane Form -->
        <div class="card">
            <h2>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add New Aircraft
            </h2>
            
            <form method="POST" enctype="multipart/form-data" class="add-plane-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Aircraft Name *</label>
                        <input type="text" name="name" required placeholder="F-16 Fighting Falcon">
                    </div>

                    <div class="form-group">
                        <label>Country *</label>
                        <input type="text" name="country" required placeholder="United States">
                    </div>

                    <div class="form-group">
                        <label>Generation</label>
                        <input type="text" name="generation" placeholder="4.5">
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" name="role" placeholder="Multirole Fighter">
                    </div>

                    <div class="form-group">
                        <label>Max Speed (Mach)</label>
                        <input type="number" step="0.01" name="max_speed_mach" placeholder="2.0" min="0" max="5">
                    </div>

                    <div class="form-group">
                        <label>Combat Radius (km)</label>
                        <input type="number" step="0.1" name="combat_radius_km" placeholder="550.0" min="0">
                    </div>

                    <div class="form-group">
                        <label>Service Ceiling (m)</label>
                        <input type="number" name="service_ceiling_m" placeholder="15000" min="0">
                    </div>

                    <div class="form-group">
                        <label>Rate of Climb (m/s)</label>
                        <input type="number" step="0.1" name="rate_of_climb_ms" placeholder="254.0" min="0">
                    </div>

                    <div class="form-group">
                        <label>Turn Time (sec)</label>
                        <input type="number" step="0.1" name="turn_time_sec" placeholder="16.8" min="0">
                    </div>

                    <div class="form-group">
                        <label>Engine Thrust (kN)</label>
                        <input type="number" step="0.1" name="engine_thrust_kn" placeholder="127.0" min="0">
                    </div>

                    <div class="form-group">
                        <label>Max Payload (kg)</label>
                        <input type="number" name="max_payload_kg" placeholder="7700" min="0">
                    </div>

                    <div class="form-group">
                        <label>Hardpoints</label>
                        <input type="number" name="hardpoints" placeholder="11" min="0">
                    </div>

                    <div class="form-group full-width">
                        <label>Aircraft Image (PNG/JPG)</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="plane_image" id="planeImage" accept="image/png,image/jpeg,image/jpg">
                            <label for="planeImage" class="file-upload-label">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                <span id="fileName">Choose image file...</span>
                            </label>
                        </div>
                        <small style="color: #64748B; margin-top: 5px; display: block;">Image will be saved as: planename.png (lowercase, no spaces)</small>
                    </div>
                </div>

                <button type="submit" name="add_plane" class="btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Aircraft
                </button>
            </form>
        </div>

        <!-- Existing Planes -->
        <div class="card">
            <h2>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
                Manage Aircraft (<?php echo count($planes); ?> total)
            </h2>

            <div class="search-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="searchInput" placeholder="Search aircraft...">
            </div>

            <div class="planes-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Country</th>
                            <th>Max Speed</th>
                            <th>Power Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="planesTableBody">
                        <?php foreach($planes as $plane): ?>
                        <tr data-name="<?php echo strtolower($plane['name']); ?>" data-country="<?php echo strtolower($plane['country']); ?>">
                            <td><?php echo $plane['id']; ?></td>
                            <td class="plane-name"><?php echo htmlspecialchars($plane['name']); ?></td>
                            <td><?php echo htmlspecialchars($plane['country']); ?></td>
                            <td>Mach <?php echo $plane['max_speed_mach']; ?></td>
                            <td>
                                <div class="rating-bar">
                                    <div class="rating-fill" style="width: <?php echo $plane['power_rating']; ?>%"></div>
                                </div>
                                <span class="rating-text"><?php echo $plane['power_rating']; ?>/100</span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($plane['name']); ?>?');">
                                    <input type="hidden" name="plane_id" value="<?php echo $plane['id']; ?>">
                                    <button type="submit" name="delete_plane" class="btn-delete">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('planesTableBody');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');
            
            for(let row of rows) {
                const name = row.getAttribute('data-name');
                const country = row.getAttribute('data-country');
                
                if(name.includes(searchTerm) || country.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // File input display name
        const fileInput = document.getElementById('planeImage');
        const fileName = document.getElementById('fileName');
        
        if(fileInput) {
            fileInput.addEventListener('change', function() {
                if(this.files && this.files[0]) {
                    fileName.textContent = this.files[0].name;
                } else {
                    fileName.textContent = 'Choose image file...';
                }
            });
        }
    </script>
</body>
</html>