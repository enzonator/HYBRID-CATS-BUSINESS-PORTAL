<?php
session_start();
require_once "../config/db.php";

// Only logged-in users can sell
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = $_POST['age'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $address = $_POST['address'];
    $user_id = $_SESSION['user_id'];
    
    // Health information
    $vaccinated = isset($_POST['vaccinated']) ? 1 : 0;
    $neutered = isset($_POST['neutered']) ? 1 : 0;
    $health_status = $_POST['health_status'] ?? '';

    $sql = "INSERT INTO pets (user_id, name, breed, age, price, description, latitude, longitude, address, vaccinated, neutered, health_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("isssdssdssis", $user_id, $name, $breed, $age, $price, $description, $latitude, $longitude, $address, $vaccinated, $neutered, $health_status);

    if ($stmt->execute()) {
        $pet_id = $stmt->insert_id;

        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName)) {
                $filename = time() . "_" . basename($_FILES["images"]["name"][$key]);
                $targetFilePath = $targetDir . $filename;

                if (move_uploaded_file($tmpName, $targetFilePath)) {
                    $imgSql = "INSERT INTO pet_images (pet_id, filename) VALUES (?, ?)";
                    $imgStmt = $conn->prepare($imgSql);
                    $imgStmt->bind_param("is", $pet_id, $filename);
                    $imgStmt->execute();
                }
            }
        }

        $message = "Your pet has been listed successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
}
?>

<?php include_once "../includes/header.php"; ?>

<div class="container" style="max-width: 900px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0,0,0,0.1);">
    <h2 style="text-align:center; margin-bottom:20px; color:#333;">Sell Your Cat</h2>

    <?php if (!empty($message)): ?>
        <div style="padding: 10px; margin-bottom: 15px; border-radius: 5px; background: #f0f8ff; color: #333;">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 15px;">
        <input type="text" name="name" placeholder="Pet Name" required class="form-input">

        <!-- Enhanced Breed Dropdown with Search -->
        <select name="breed" id="breed" class="form-input" required>
            <option value="">Select Breed</option>
            <option value="Abyssinian">Abyssinian</option>
            <option value="American Bobtail">American Bobtail</option>
            <option value="American Curl">American Curl</option>
            <option value="American Shorthair">American Shorthair</option>
            <option value="American Wirehair">American Wirehair</option>
            <option value="Balinese">Balinese</option>
            <option value="Bengal">Bengal</option>
            <option value="Birman">Birman</option>
            <option value="Bombay">Bombay</option>
            <option value="British Longhair">British Longhair</option>
            <option value="British Shorthair">British Shorthair</option>
            <option value="Burmese">Burmese</option>
            <option value="Burmilla">Burmilla</option>
            <option value="Chartreux">Chartreux</option>
            <option value="Chausie">Chausie</option>
            <option value="Cornish Rex">Cornish Rex</option>
            <option value="Cymric">Cymric</option>
            <option value="Devon Rex">Devon Rex</option>
            <option value="Egyptian Mau">Egyptian Mau</option>
            <option value="Exotic Shorthair">Exotic Shorthair</option>
            <option value="Havana Brown">Havana Brown</option>
            <option value="Himalayan">Himalayan</option>
            <option value="Japanese Bobtail">Japanese Bobtail</option>
            <option value="Khao Manee">Khao Manee</option>
            <option value="Korat">Korat</option>
            <option value="Kurilian Bobtail">Kurilian Bobtail</option>
            <option value="LaPerm">LaPerm</option>
            <option value="Lykoi">Lykoi</option>
            <option value="Maine Coon">Maine Coon</option>
            <option value="Manx">Manx</option>
            <option value="Munchkin">Munchkin</option>
            <option value="Nebelung">Nebelung</option>
            <option value="Norwegian Forest Cat">Norwegian Forest Cat</option>
            <option value="Ocicat">Ocicat</option>
            <option value="Oriental Longhair">Oriental Longhair</option>
            <option value="Oriental Shorthair">Oriental Shorthair</option>
            <option value="Persian">Persian</option>
            <option value="Peterbald">Peterbald</option>
            <option value="Pixiebob">Pixiebob</option>
            <option value="Ragdoll">Ragdoll</option>
            <option value="Russian Blue">Russian Blue</option>
            <option value="Savannah">Savannah</option>
            <option value="Scottish Fold">Scottish Fold</option>
            <option value="Selkirk Rex">Selkirk Rex</option>
            <option value="Serengeti">Serengeti</option>
            <option value="Siamese">Siamese</option>
            <option value="Siberian">Siberian</option>
            <option value="Singapura">Singapura</option>
            <option value="Snowshoe">Snowshoe</option>
            <option value="Somali">Somali</option>
            <option value="Sphynx">Sphynx</option>
            <option value="Tonkinese">Tonkinese</option>
            <option value="Toyger">Toyger</option>
            <option value="Turkish Angora">Turkish Angora</option>
            <option value="Turkish Van">Turkish Van</option>
            <option value="York Chocolate">York Chocolate</option>
            <option value="Other">Other</option>
        </select>

        <input type="text" name="age" placeholder="Age (e.g. 2 years)" required class="form-input">
        <input type="number" step="0.01" name="price" placeholder="Price (₱)" required class="form-input">

        <textarea name="description" placeholder="Description" rows="4" class="form-input"></textarea>

        <!-- Health Information Section -->
        <div class="health-section">
            <label style="font-weight:bold; font-size:16px; color:#333; margin-bottom:10px; display:block;">🏥 Health Information</label>
            
            <div class="health-checkboxes">
                <label class="checkbox-label">
                    <input type="checkbox" name="vaccinated" value="1">
                    <span class="checkbox-text">
                        <strong>Vaccinated</strong>
                        <small>Pet has received necessary vaccinations</small>
                    </span>
                </label>

                <label class="checkbox-label">
                    <input type="checkbox" name="neutered" value="1">
                    <span class="checkbox-text">
                        <strong>Neutered/Spayed</strong>
                        <small>Pet has been neutered or spayed</small>
                    </span>
                </label>
            </div>

            <textarea name="health_status" placeholder="Health Status (Optional) - Describe any health conditions, recent vet visits, or special care requirements..." rows="3" class="form-input" style="margin-top:10px;"></textarea>
        </div>

        <!-- Multiple image input -->
        <input type="file" name="images[]" accept="image/*" multiple required class="form-input">

        <!-- Location Section -->
        <label style="font-weight:bold;">📍 Pet Location:</label>
        <input type="text" id="address" name="address" placeholder="Click on the map to set location" readonly class="form-input">
        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">

        <div id="map" style="height: 300px; width: 100%; border-radius: 8px; margin-bottom: 10px;"></div>

        <!-- Display current location -->
        <div id="location-info" style="padding:10px; background:#f8f9fa; border:1px solid #ddd; border-radius:6px; font-size:14px; color:#333; display:none;">
            📍 <b>Current Location:</b><br>
            <span id="loc-address">Not set</span><br>
            <span id="loc-coords"></span>
        </div>

        <button type="submit" style="padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            List Pet for Sale
        </button>
    </form>
</div>

<style>
    .form-input {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
        width: 100%;
    }
    .form-input:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0,123,255,0.3);
    }
    .select2-container .select2-selection--single {
        height: 42px !important;
        padding: 6px 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
    }

    /* Health Section Styling */
    .health-section {
        padding: 20px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin: 5px 0;
    }

    .health-checkboxes {
        display: grid;
        gap: 15px;
        margin-top: 10px;
    }

    .checkbox-label {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .checkbox-label:hover {
        border-color: #007bff;
        background: #f0f8ff;
    }

    .checkbox-label input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-top: 2px;
        cursor: pointer;
        accent-color: #007bff;
    }

    .checkbox-label input[type="checkbox"]:checked + .checkbox-text {
        color: #007bff;
    }

    .checkbox-text {
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex: 1;
    }

    .checkbox-text strong {
        font-size: 15px;
        color: #333;
    }

    .checkbox-text small {
        font-size: 13px;
        color: #666;
        font-weight: normal;
    }
</style>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Leaflet CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Select2 CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Enhance breed dropdown with Select2
    $('#breed').select2({
        placeholder: "Search or select a breed",
        allowClear: true,
        width: '100%'
    });

    // Initialize Map (default center: Manila)
    var map = L.map('map').setView([14.5995, 120.9842], 13);

    // OpenStreetMap Tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var marker = null;

    // Function to update form + info box
    function updateLocation(lat, lon) {
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`, {
            headers: {
                'User-Agent': 'MyLeafletApp/1.0 (your-email@example.com)',
                'Accept-Language': 'en'
            }
        })
        .then(response => response.json())
        .then(data => {
            let address = data.display_name || "Unknown location";

            $("#address").val(address);
            $("#latitude").val(lat);
            $("#longitude").val(lon);

            $("#loc-address").text(address);
            $("#loc-coords").text(`Lat: ${lat.toFixed(5)}, Lng: ${lon.toFixed(5)}`);
            $("#location-info").show();
        })
        .catch(err => {
            console.error("Geocoding error:", err);
            $("#address").val("Could not get address");
            $("#loc-address").text("Could not get address");
        });
    }

    // Click to drop/move marker
    map.on('click', function(e) {
        var lat = e.latlng.lat;
        var lon = e.latlng.lng;

        if (!marker) {
            marker = L.marker([lat, lon], { draggable: true }).addTo(map);

            // Update location when dragged
            marker.on('dragend', function(event) {
                var newLat = event.target.getLatLng().lat;
                var newLon = event.target.getLatLng().lng;
                updateLocation(newLat, newLon);
            });
        } else {
            marker.setLatLng([lat, lon]);
        }

        updateLocation(lat, lon);
    });

    // Auto-center map to user's GPS
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;

            map.setView([lat, lon], 15);

            // Drop a marker at current GPS
            if (!marker) {
                marker = L.marker([lat, lon], { draggable: true }).addTo(map);
                marker.on('dragend', function(event) {
                    var newLat = event.target.getLatLng().lat;
                    var newLon = event.target.getLatLng().lng;
                    updateLocation(newLat, newLon);
                });
            } else {
                marker.setLatLng([lat, lon]);
            }

            updateLocation(lat, lon);
        });
    }
});
</script>

<?php include_once "../includes/footer.php"; ?>