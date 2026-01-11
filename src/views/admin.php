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
}



$conn->close();
?>