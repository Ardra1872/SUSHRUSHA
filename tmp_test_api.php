<?php
session_start();
$_SESSION['user_id'] = 48; // Caretaker
$_SESSION['active_patient_id'] = 10; // Selected Patient
chdir('public/api/simulation/');
require 'get_state.php';
?>
