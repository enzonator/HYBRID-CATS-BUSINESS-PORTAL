<?php
session_start();
require_once "../config/db.php"; // uses $conn (MySQLi)

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["valid_id"]) && $_FILES["valid_id"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES["valid_id"]["tmp_name"];
        $fileName = $_FILES["valid_id"]["name"];
        $fileSize = $_FILES["valid_id"]["size"];
        $fileType = $_FILES["valid_id"]["type"];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ["jpg", "jpeg", "png", "pdf"];

        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = "../uploads/verifications/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = "valid_id_" . $user_id . "_" . time() . "." . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // ✅ FIXED: Use $conn (MySQLi)
                $stmt = $conn->prepare("INSERT INTO verifications (user_id, id_image, status, created_at) VALUES (?, ?, 'Pending', NOW())");
                $stmt->bind_param("is", $user_id, $newFileName);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "✅ Verification request submitted successfully! Please wait for admin approval.";
                } else {
                    $message = "❌ Failed to save verification request.";
                }

                $stmt->close();
            } else {
                $message = "❌ Error moving uploaded file.";
            }
        } else {
            $message = "❌ Only JPG, JPEG, PNG, and PDF files are allowed.";
        }
    } else {
        $message = "⚠️ Please upload a valid ID.";
    }
}
?>

<?php include_once "../includes/header.php"; ?>

<div class="verify-container">
  <h2>Account Verification</h2>
  <p>Upload a valid government-issued ID to verify your account.</p>

  <?php if (!empty($message)) : ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <label for="valid_id">Choose a valid ID image (JPG, PNG, or PDF):</label>
    <input type="file" name="valid_id" id="valid_id" accept=".jpg,.jpeg,.png,.pdf" required>

    <button type="submit">Submit Verification</button>
  </form>
</div>

<style>
  .verify-container {
    max-width: 500px;
    margin: 100px auto;
    background: #fff;
    padding: 25px 30px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    text-align: center;
  }

  .verify-container h2 {
    color: #28a745;
    margin-bottom: 10px;
  }

  .verify-container p {
    color: #555;
    font-size: 15px;
    margin-bottom: 25px;
  }

  form {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  input[type="file"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
  }

  button {
    background: #28a745;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
  }

  button:hover {
    background: #1d7a34;
  }

  .message {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 8px;
    background: #f0f0f0;
  }
</style>
