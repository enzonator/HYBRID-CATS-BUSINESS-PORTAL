<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

if (isset($_GET['id'], $_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    $newStatus = '';
    if ($action === 'approve') {
        $newStatus = 'Approved';
    } elseif ($action === 'reject') {
        $newStatus = 'Rejected';
    }

    if ($newStatus !== '') {
        // ✅ Update verification request status
        $stmt = $conn->prepare("UPDATE verifications SET status = ? WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $newStatus, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0 && $newStatus === 'Approved') {
            // ✅ Fetch user_id from verification record
            $userQuery = $conn->prepare("SELECT user_id FROM verifications WHERE id = ?");
            if (!$userQuery) {
                die("Prepare failed (userQuery): " . $conn->error);
            }
            $userQuery->bind_param("i", $id);
            $userQuery->execute();
            $userResult = $userQuery->get_result()->fetch_assoc();

            if ($userResult) {
                $userId = $userResult['user_id'];

                // ✅ Update user as verified (fixed column name)
                $updateUser = $conn->prepare("UPDATE users SET verified = 1 WHERE id = ?");
                if (!$updateUser) {
                    die("Prepare failed (updateUser): " . $conn->error);
                }
                $updateUser->bind_param("i", $userId);
                $updateUser->execute();
            }
        }

        header("Location: verify-requests.php?status=" . strtolower($newStatus));
        exit;
    }
}

header("Location: verify-requests.php?status=error");
exit;
 