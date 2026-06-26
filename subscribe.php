<?php
include('db_connect.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email protocol.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO newsletter_subs (email) VALUES (?)");
        $stmt->execute([$email]);
        echo json_encode(['status' => 'success', 'message' => 'Node subscribed successfully.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry code
            echo json_encode(['status' => 'error', 'message' => 'Email already registered in network.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'System error. Try again.']);
        }
    }
}
?>