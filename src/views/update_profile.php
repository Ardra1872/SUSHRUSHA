<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $patient_id = $_SESSION['user_id'];
  $dob = $_POST['dob'];
  $gender = $_POST['gender'];
  $blood = $_POST['blood_group'];
  $height = $_POST['height_cm'];
  $weight = $_POST['weight_kg'];
  $emergency = $_POST['emergency_contact'];

  // Update emergency contact in users table
  $stmt = $conn->prepare("
    UPDATE users SET emergency_contact = ?
    WHERE id = ?
  ");
  $stmt->bind_param("si", $emergency, $patient_id);
  $stmt->execute();

  // Check if profile exists
  $check = $conn->prepare("SELECT patient_id FROM patient_profile WHERE patient_id=?");
  $check->bind_param("i", $patient_id);
  $check->execute();
  $exists = $check->get_result()->num_rows > 0;

  if ($exists) {
    // Update
    $stmt = $conn->prepare("
      UPDATE patient_profile
      SET dob=?, gender=?, blood_group=?, height_cm=?, weight_kg=?
      WHERE patient_id=?
    ");
    $stmt->bind_param("sssiii", $dob, $gender, $blood, $height, $weight, $patient_id);
  } else {
    // Insert
    $stmt = $conn->prepare("
      INSERT INTO patient_profile
      (dob, gender, blood_group, height_cm, weight_kg, patient_id)
      VALUES (?,?,?,?,?,?)
    ");
    $stmt->bind_param("sssiii", $dob, $gender, $blood, $height, $weight, $patient_id);
  }

  $stmt->execute();
  header("Location: dashboard.php");
  exit();
}
