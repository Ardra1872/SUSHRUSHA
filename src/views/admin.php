<?php
session_start();
$isApiCall = isset($_GET['action']) || isset($_POST['action']);
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if ($isApiCall) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Unauthorized"
        ]);
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}





$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {

    // Fetch metrics
    case 'metrics':
        $activePatients = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='patient' AND status='active'")->fetch_assoc()['total'];
        $activeCaretakers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='caretaker' AND status='active'")->fetch_assoc()['total'];
        $missedDoses = $conn->query("SELECT COUNT(*) as total FROM medicine_logs WHERE status='missed' AND DATE(date)=CURDATE()")->fetch_assoc()['total'];
        $avgAdherence = $conn->query("SELECT AVG(adherence) as avg FROM medicine_logs WHERE DATE(date)=CURDATE()")->fetch_assoc()['avg'];

        echo json_encode([
            'status'=>'success',
            'activePatients'=>$activePatients,
            'activeCaretakers'=>$activeCaretakers,
            'missedDoses'=>$missedDoses,
            'avgAdherence'=>round($avgAdherence)
        ]);
        break;

    // Fetch recent users
    case 'recent_users':
        $sql = "SELECT id, name, email, role, status, adherence FROM users ORDER BY created_at DESC LIMIT 10";
        $result = $conn->query($sql);
        $users = [];
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['status'=>'success','users'=>$users]);
        break;

    // Add patient
    case 'add_patient':
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

        if($name && $email && $password) {
            $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,?,?)");
            $role = 'patient';
            $status = 'active';
            $stmt->bind_param("sssss",$name,$email,$password,$role,$status);
            $stmt->execute();
            echo json_encode(['status'=>'success','message'=>'Patient added successfully']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Missing fields']);
        }
        break;

    // Broadcast alert
    case 'broadcast_alert':
        $message = $_POST['message'] ?? '';
        if($message) {
            $stmt = $conn->prepare("INSERT INTO alerts (message,created_at) VALUES (?,NOW())");
            $stmt->bind_param("s",$message);
            $stmt->execute();
            echo json_encode(['status'=>'success','message'=>'Alert broadcasted']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Message cannot be empty']);
        }
        break;

    // Review flags
    case 'flags':
        $sql = "SELECT id, user_id, type, description, created_at FROM flags ORDER BY created_at DESC";
        $result = $conn->query($sql);
        $flags = [];
        while($row = $result->fetch_assoc()) {
            $flags[] = $row;
        }
        echo json_encode(['status'=>'success','flags'=>$flags]);
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Invalid action']);
        break;
}
case 'medicine_requests':
    $sql = "
      SELECT mr.id, mr.name, mr.dosage, mr.form, u.name AS requester
      FROM medicine_requests mr
      JOIN users u ON u.id = mr.requested_by
      WHERE mr.status = 'pending'
      ORDER BY mr.created_at DESC
    ";

    $result = $conn->query($sql);
    $requests = [];

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }

    echo json_encode(['status'=>'success','requests'=>$requests]);
    break;
case 'approve_medicine':
    $id = $_POST['id'] ?? 0;

    $stmt = $conn->prepare("
      SELECT name FROM medicine_requests WHERE id=? AND status='pending'
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res) {
        echo json_encode(['status'=>'error','message'=>'Request not found']);
        exit;
    }

    // Insert into catalog (ignore duplicates safely)
    $stmt = $conn->prepare("
      INSERT IGNORE INTO medicine_catalog (name)
      VALUES (?)
    ");
    $stmt->bind_param("s",$res['name']);
    $stmt->execute();

    // Update request status
    $conn->query("
      UPDATE medicine_requests
      SET status='approved'
      WHERE id=$id
    ");

    echo json_encode(['status'=>'success','message'=>'Medicine approved & added']);
    break;
case 'reject_medicine':
    $id = $_POST['id'] ?? 0;

    $conn->query("
      UPDATE medicine_requests
      SET status='rejected'
      WHERE id=$id
    ");

    echo json_encode(['status'=>'success','message'=>'Request rejected']);
    break;

?>
