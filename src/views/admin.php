<?php
session_start();
include '../config/db.php';

// Force JSON output for all responses
header('Content-Type: application/json');

// ---------------------------
// API call detection
// ---------------------------
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$isApiCall = !empty($action);

// ---------------------------
// Admin authentication
// ---------------------------
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

// ---------------------------
// Action resolver
// ---------------------------
switch ($action) {

    // ---------------------------
    // Get Pending Medicine Requests
    // ---------------------------
    case 'medicine_requests':
        $sql = "
            SELECT mr.id, mr.name, mr.dosage, mr.form, u.name AS requester
            FROM medicine_requests mr
            JOIN users u ON mr.requested_by = u.id
            WHERE mr.status = 'pending'
            ORDER BY mr.created_at DESC
        ";
        $result = $conn->query($sql);

        $requests = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $requests[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'dosage' => $row['dosage'],
                    'form' => $row['form'],
                    'requester' => $row['requester']
                ];
            }
        }

        echo json_encode([
            'status' => 'success',
            'requests' => $requests
        ]);
        exit;

    // ---------------------------
    // Approve Medicine Request
    // ---------------------------
   case 'approve_medicine':
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {

        // 1️⃣ Fetch the request details
        $stmt = $conn->prepare("SELECT name, dosage, form FROM medicine_requests WHERE id=? AND status='pending'");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: '.$conn->error]);
            exit;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        $stmt->close();

        if (!$request) {
            echo json_encode(['status' => 'error', 'message' => 'Request not found or already processed']);
            exit;
        }

        // 2️⃣ Insert into medicine_catalogue (check table name!)
        $stmt = $conn->prepare("INSERT INTO  medicine_catalog (name, dosage, form) VALUES (?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Insert prepare failed: '.$conn->error]);
            exit;
        }
        $stmt->bind_param("sss", $request['name'], $request['dosage'], $request['form']);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Insert failed: '.$stmt->error]);
            exit;
        }
        $stmt->close();

        // 3️⃣ Update the request status to approved
        $stmt = $conn->prepare("UPDATE medicine_requests SET status='approved' WHERE id=?");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Update prepare failed: '.$conn->error]);
            exit;
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Update failed: '.$stmt->error]);
            exit;
        }
        $stmt->close();

        echo json_encode(['status' => 'success', 'message' => 'Medicine request approved and added to catalogue']);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }
    exit;

    // ---------------------------
    // Reject Medicine Request
    // ---------------------------
  case 'reject_medicine':
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $conn->query("UPDATE medicine_requests SET status='rejected' WHERE id=$id");
        echo json_encode(['status' => 'success', 'message' => 'Medicine request rejected']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }
    exit;



    // ---------------------------
    // Get Patient Count
    // ---------------------------
    case 'patient_count':
        $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='patient'");
        $count = $result->fetch_assoc()['total'] ?? 0;
        echo json_encode([
            'status' => 'success',
            'total_patients' => $count
        ]);
        exit;

    // ---------------------------
    // Invalid Action
    // ---------------------------
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
        exit;
case 'fetch_users':
    $result = $conn->query("SELECT id, name, role, emergency_contact FROM users");
    $users = [];
    while($row = $result->fetch_assoc()){
        $users[] = $row;
    }
    echo json_encode(['status'=>'success','users'=>$users]);
    exit;


case 'delete_user':
    $id = intval($_POST['id'] ?? 0);
    if($id > 0){
        $conn->query("DELETE FROM users WHERE id=$id");
        echo json_encode(['status'=>'success','message'=>'User deleted successfully']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Invalid User ID']);
    }
    exit;
 case 'add_user':
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? 'patient';

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0){
            echo json_encode(['status'=>'error','message'=>'Email already exists']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        if($stmt->execute()){
            echo json_encode(['status'=>'success','message'=>'User added successfully']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Failed to add user']);
        }
        exit;
    case 'add_broadcast':
    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
        echo json_encode(['status'=>'error','message'=>'Message cannot be empty']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO broadcasts (message, created_by) VALUES (?, ?)");
    $stmt->bind_param("si", $message, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Broadcast sent successfully']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to send broadcast']);
    }
    exit;

    case 'fetch_broadcasts':
    $result = $conn->query("
        SELECT b.id, b.message, b.created_at, u.name AS admin_name
        FROM broadcasts b
        JOIN users u ON b.created_by = u.id
        ORDER BY b.created_at DESC
    ");

    $broadcasts = [];
    while ($row = $result->fetch_assoc()) {
        $broadcasts[] = $row;
    }

    echo json_encode(['status'=>'success','broadcasts'=>$broadcasts]);
    exit;
    case 'edit_broadcast':
    $id = intval($_POST['id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($id <= 0 || $message === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid broadcast data'
        ]);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE broadcasts SET message = ? WHERE id = ? AND created_by = ?"
    );
    $stmt->bind_param("sii", $message, $id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Broadcast updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update broadcast'
        ]);
    }

    $stmt->close();
    exit;

    case 'delete_broadcast':
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid broadcast ID'
        ]);
        exit;
    }

    $stmt = $conn->prepare(
        "DELETE FROM broadcasts WHERE id = ? AND created_by = ?"
    );
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Broadcast deleted successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete broadcast'
        ]);
    }

    $stmt->close();
    exit;

    case 'patient_count':
    $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='patient'");
    $count = $result->fetch_assoc()['total'] ?? 0;
    echo json_encode([
        'status' => 'success',
        'total_patients' => $count
    ]);
    exit;
    case 'caretaker_count':
    $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='caretaker'");
    $count = $result->fetch_assoc()['total'] ?? 0;
    echo json_encode([
        'status' => 'success',
        'total_caretaker' => $count
    ]);
    exit;

case 'user_distribution':
    $patients = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='patient'")
                     ->fetch_assoc()['c'] ?? 0;

    $caretakers = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='caretaker'")
                       ->fetch_assoc()['c'] ?? 0;

    echo json_encode([
        'status' => 'success',
        'patients' => $patients,
        'caretakers' => $caretakers,
        'total' => $patients + $caretakers
    ]);
    exit;
    // ---------------------------
// Medicine Requests Over Time (last 7 days)
// ---------------------------
case 'medicine_requests_over_time':
    // Get dates for last 7 days
    $days = [];
    $labels = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $days[] = $date;
        $labels[] = date('D', strtotime($date)); // Mon, Tue, etc
    }

    // Initialize counts
    $counts = array_fill(0, 7, 0);

    $sql = "
        SELECT DATE(created_at) AS req_date, COUNT(*) AS total
        FROM medicine_requests
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
    ";
    $result = $conn->query($sql);
    if($result){
        while($row = $result->fetch_assoc()){
            $index = array_search($row['req_date'], $days);
            if($index !== false){
                $counts[$index] = (int)$row['total'];
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'labels' => $labels,
        'data' => $counts
    ]);
    exit;
    
case 'edit_profile':
    $userId = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $profile_photo = $_FILES['profile_photo'] ?? null;

    if ($name === '') {
        echo json_encode(['status'=>'error','message'=>'Name cannot be empty']);
        exit;
    }

    $updates = [];
    $params = [];
    $types = '';

    // Name update
    $updates[] = "name=?";
    $params[] = $name;
    $types .= 's';

    // Password update if provided
    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = "password=?";
        $params[] = $hashed;
        $types .= 's';
    }

    // Profile photo upload
    $newFileName = '';
    if ($profile_photo && $profile_photo['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($profile_photo['name'], PATHINFO_EXTENSION);
        $newFileName = 'profile_' . $userId . '_' . time() . '.' . $ext;
        $uploadPath = '../uploads/' . $newFileName;

        if (!move_uploaded_file($profile_photo['tmp_name'], $uploadPath)) {
            echo json_encode(['status'=>'error','message'=>'Failed to upload profile picture']);
            exit;
        }

        $updates[] = "profile_photo=?";
        $params[] = $newFileName;
        $types .= 's';
    }

    // Build query
    $sql = "UPDATE users SET " . implode(',', $updates) . " WHERE id=?";
    $params[] = $userId;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status'=>'error','message'=>'Prepare failed: '.$conn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        echo json_encode([
            'status'=>'success',
            'message'=>'Profile updated successfully',
            'new_profile_photo' => $newFileName ? ('uploads/' . $newFileName) : ''
        ]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Update failed: '.$stmt->error]);
    }
    $stmt->close();
    exit;

}



$conn->close();
?>