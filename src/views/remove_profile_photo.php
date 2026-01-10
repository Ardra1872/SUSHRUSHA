<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current profile photo path
$stmt = $conn->prepare("SELECT profile_photo FROM patient_profile WHERE patient_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
    $photoPath = $profile['profile_photo'];
    
    // Delete file if it exists
    if (!empty($photoPath)) {
        $filePath = "../" . $photoPath;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

$stmt->close();

// Remove profile photo from database
$stmt = $conn->prepare("UPDATE patient_profile SET profile_photo = NULL WHERE patient_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

$_SESSION['success'] = "Profile photo removed successfully!";
header("Location: profile.php");
exit();
?>
