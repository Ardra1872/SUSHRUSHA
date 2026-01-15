<?php
require '../config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS `caretaker_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `caretaker_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `note_type` enum('general','medicine') NOT NULL DEFAULT 'general',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `caretaker_id` (`caretaker_id`),
  KEY `medicine_id` (`medicine_id`),
  CONSTRAINT `caretaker_notes_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caretaker_notes_ibfk_2` FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caretaker_notes_ibfk_3` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo json_encode([
        'status' => 'success',
        'message' => 'caretaker_notes table created successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error creating table: ' . $conn->error
    ]);
}

$conn->close();
?>
