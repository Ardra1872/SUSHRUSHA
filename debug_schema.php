<?php
require 'src/config/db.php';

$result = $conn->query("DESCRIBE users");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
