<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* -------------------------
   1️⃣ GET & VALIDATE INPUT
-------------------------- */
$dob       = !empty($_POST['dob']) ? $_POST['dob'] : null;
$gender    = !empty($_POST['gender']) ? $_POST['gender'] : null;
$blood     = !empty($_POST['blood_group']) ? $_POST['blood_group'] : null;
$height    = !empty($_POST['height_cm']) ? (int) $_POST['height_cm'] : null;
$weight    = !empty($_POST['weight_kg']) ? (float) $_POST['weight_kg'] : null;
$emergency = !empty($_POST['emergency_contact']) ? trim($_POST['emergency_contact']) : null;

// Only validate dob and emergency_contact if they're being updated
// (If they're not provided, we'll keep existing values in the database)

/* -------------------------
   2️⃣ HANDLE PROFILE PHOTO
-------------------------- */
$photoPath = null;

if (!empty($_FILES['profile_photo']['name'])) {

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "Invalid image format";
        header("Location: profile.php");
        exit();
    }

    $fileName = "profile_" . $user_id . "." . $ext;
    $uploadDir = "../uploads/profile/";
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)) {
        $_SESSION['error'] = "Failed to upload image";
        header("Location: profile.php");
        exit();
    }

    $photoPath = "uploads/profile/" . $fileName;
}

/* -------------------------
   3️⃣ DATABASE TRANSACTION
-------------------------- */
$conn->begin_transaction();

try {

    // Update emergency contact in users table (only if provided)
    if ($emergency !== null) {
        $stmt = $conn->prepare("
            UPDATE users 
            SET emergency_contact = ?
            WHERE id = ?
        ");
        $stmt->bind_param("si", $emergency, $user_id);
        $stmt->execute();
    }

    // Insert / Update patient profile
    // Build update clause - only update fields that are not null
    $updateClauses = [];
    $updateParams = [];
    $updateTypes = '';
    
    if ($dob !== null) {
        $updateClauses[] = 'dob = ?';
        $updateParams[] = $dob;
        $updateTypes .= 's';
    }
    
    if ($gender !== null) {
        $updateClauses[] = 'gender = ?';
        $updateParams[] = $gender;
        $updateTypes .= 's';
    }
    
    if ($blood !== null) {
        $updateClauses[] = 'blood_group = ?';
        $updateParams[] = $blood;
        $updateTypes .= 's';
    }
    
    if ($height !== null) {
        $updateClauses[] = 'height_cm = ?';
        $updateParams[] = $height;
        $updateTypes .= 'i';
    }
    
    if ($weight !== null) {
        $updateClauses[] = 'weight_kg = ?';
        $updateParams[] = $weight;
        $updateTypes .= 'd';
    }
    
    if ($photoPath !== null) {
        $updateClauses[] = 'profile_photo = ?';
        $updateParams[] = $photoPath;
        $updateTypes .= 's';
    }
    
    // Check if we have any fields to update
    if (!empty($updateClauses)) {
        // First, ensure the record exists
        $checkStmt = $conn->prepare("SELECT patient_id FROM patient_profile WHERE patient_id = ?");
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();
        
        if ($exists) {
            // Update existing record
            $updateSql = "UPDATE patient_profile SET " . implode(', ', $updateClauses) . " WHERE patient_id = ?";
            $updateParams[] = $user_id;
            $updateTypes .= 'i';
            
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param($updateTypes, ...$updateParams);
            $stmt->execute();
        } else {
            // Insert new record with only provided fields
            $insertFields = ['patient_id'];
            $insertValues = [$user_id];
            $insertTypes = 'i';
            $insertPlaceholders = ['?'];
            
            if ($dob !== null) {
                $insertFields[] = 'dob';
                $insertValues[] = $dob;
                $insertTypes .= 's';
                $insertPlaceholders[] = '?';
            }
            if ($gender !== null) {
                $insertFields[] = 'gender';
                $insertValues[] = $gender;
                $insertTypes .= 's';
                $insertPlaceholders[] = '?';
            }
            if ($blood !== null) {
                $insertFields[] = 'blood_group';
                $insertValues[] = $blood;
                $insertTypes .= 's';
                $insertPlaceholders[] = '?';
            }
            if ($height !== null) {
                $insertFields[] = 'height_cm';
                $insertValues[] = $height;
                $insertTypes .= 'i';
                $insertPlaceholders[] = '?';
            }
            if ($weight !== null) {
                $insertFields[] = 'weight_kg';
                $insertValues[] = $weight;
                $insertTypes .= 'd';
                $insertPlaceholders[] = '?';
            }
            if ($photoPath !== null) {
                $insertFields[] = 'profile_photo';
                $insertValues[] = $photoPath;
                $insertTypes .= 's';
                $insertPlaceholders[] = '?';
            }
            
            $insertSql = "INSERT INTO patient_profile (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param($insertTypes, ...$insertValues);
            $stmt->execute();
        }
    }

    $stmt->execute();

    $conn->commit();

    $_SESSION['success'] = "Profile updated successfully";
    header("Location: profile.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Something went wrong. Please try again.";
    header("Location: profile.php");
    exit();
}
