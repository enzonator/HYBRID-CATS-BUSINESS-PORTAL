<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT username, email, first_name, last_name, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Handle remove profile picture
    if (isset($_POST['remove_profile_pic'])) {
        // Delete old profile picture (if not default)
        if (!empty($user['profile_pic']) && $user['profile_pic'] !== "default.jpg" && $user['profile_pic'] !== "profile_pics/default.jpg") {
            $oldPath = "../uploads/" . $user['profile_pic'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        // Set to default
        $profile_pic = "profile_pics/default.jpg";
        $updateSql = "UPDATE users SET profile_pic = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $profile_pic, $user_id);
        
        if ($updateStmt->execute()) {
            $success = "Profile picture removed successfully!";
            $user['profile_pic'] = $profile_pic;
        } else {
            $error = "Failed to remove profile picture.";
        }
    } else {
        // Handle normal form submission
        $email = $_POST['email'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name  = $_POST['last_name'] ?? '';
        $profile_pic = $user['profile_pic']; // Keep old picture if not updated

        // Handle profile picture upload
        if (!empty($_FILES['profile_pic']['name'])) {
            $targetDir = "../uploads/profile_pics/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

            // Allowed file types
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileType, $allowedTypes)) {

                // Delete old profile picture (if not default)
                if (!empty($user['profile_pic']) && $user['profile_pic'] !== "default.jpg" && $user['profile_pic'] !== "profile_pics/default.jpg") {
                    $oldPath = "../uploads/" . $user['profile_pic'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                // Move uploaded file
                if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFilePath)) {
                    // Save relative path under uploads folder
                    $profile_pic = "profile_pics/" . $fileName;
                } else {
                    $error = "Error uploading profile picture.";
                }
            } else {
                $error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
        }

        // Update user info
        $updateSql = "UPDATE users SET email = ?, first_name = ?, last_name = ?, profile_pic = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            die("SQL error: " . $conn->error);
        }
        $updateStmt->bind_param("ssssi", $email, $first_name, $last_name, $profile_pic, $user_id);

        if ($updateStmt->execute()) {
            $success = "Profile updated successfully";
            $user['email'] = $email;
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['profile_pic'] = $profile_pic;
        } else {
            $error = "Failed to update profile.";
        }
    }
}

// ✅ Determine correct profile picture path FOR BROWSER DISPLAY
if (!empty($user['profile_pic'])) {
    // Check if file exists on server
    $serverPath = "../uploads/" . $user['profile_pic'];
    if (file_exists($serverPath)) {
        // Path for browser (relative to web root)
        $profilePicPath = "../uploads/" . $user['profile_pic'];
    } else {
        // File doesn't exist, use default
        $profilePicPath = "../uploads/profile_pics/default.jpg";
    }
} else {
    // No profile pic set, use default
    $profilePicPath = "../uploads/profile_pics/default.jpg";
}

// ✅ Final fallback check
if (!file_exists($profilePicPath)) {
    $profilePicPath = "../uploads/default.jpg";
}
?>

<style>
.dashboard {
    display: flex;
    min-height: 100vh;
    background: #f9f9f9;
}
.profile-container {
    flex-grow: 1;
    padding: 40px;
}
.profile-box {
    max-width: 500px;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
}
.profile-box h2 {
    margin-bottom: 20px;
    text-align: center;
    user-select: none;
}
.profile-pic {
    text-align: center;
    margin-bottom: 15px;
}
.profile-pic img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #007bff;
}
.profile-box label {
    display: block;
    margin: 10px 0 5px;
    font-weight: bold;
}
.profile-box input[type="text"],
.profile-box input[type="email"] {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
}
.profile-box input[type="file"] {
    display: none;
}
.file-upload-btn {
    display: block;
    width: 100%;
    padding: 10px;
    background: #6c757d;
    border: none;
    color: #fff;
    border-radius: 6px;
    cursor: pointer;
    margin-bottom: 15px;
    text-align: center;
    font-weight: normal;
}
.file-upload-btn:hover {
    background: #5a6268;
}
.file-name-display {
    color: #28a745;
    font-size: 14px;
    margin-top: -10px;
    margin-bottom: 10px;
    font-style: italic;
}
.profile-box button {
    width: 100%;
    padding: 10px;
    background: #007bff;
    border: none;
    color: #fff;
    border-radius: 6px;
    cursor: pointer;
}
.profile-box button:hover {
    background: #0056b3;
}
.btn-remove {
    background: #dc3545;
    margin-top: 10px;
}
.btn-remove:hover {
    background: #c82333;
}
.success {
    color: green;
    margin-bottom: 10px;
}
.error {
    color: red;
    margin-bottom: 10px;
}
</style>

<script>
// Show selected file name
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profile_pic');
    const fileLabel = document.querySelector('.file-upload-btn');
    const originalText = fileLabel.textContent;
    
    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            fileLabel.textContent = '✓ ' + this.files[0].name;
            fileLabel.style.background = '#28a745';
        } else {
            fileLabel.textContent = originalText;
            fileLabel.style.background = '#6c757d';
        }
    });
});
</script>

<div class="dashboard">
    <!-- Sidebar -->
    <?php include_once "../includes/sidebar.php"; ?>

    <!-- Profile Section -->
    <div class="profile-container">
        <div class="profile-box">
            <h2>My Profile</h2>

            <?php if (!empty($success)): ?>
                <p class="success"><?= htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div class="profile-pic">
                <img src="<?= htmlspecialchars($profilePicPath); ?>" alt="Profile Picture" onerror="this.src='../uploads/default.jpg'">
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_pic" id="profile_pic" accept=".jpg,.jpeg,.png,.gif">
                <label for="profile_pic" class="file-upload-btn">Choose Profile Picture</label>

                <label>Username</label>
                <input type="text" value="<?= htmlspecialchars($user['username']); ?>" disabled>

                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>">

                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>">

                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>">

                <button type="submit">Update Profile</button>
            </form>

            <!-- Remove Profile Picture Button -->
            <?php if (!empty($user['profile_pic']) && $user['profile_pic'] !== "profile_pics/default.jpg"): ?>
            <form method="POST" style="margin-top: 10px;">
                <button type="submit" name="remove_profile_pic" class="btn-remove" onclick="return confirm('Are you sure you want to remove your profile picture?');">Remove Profile Picture</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>