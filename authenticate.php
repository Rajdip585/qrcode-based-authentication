<?php
// authenticate.php

if (!session_id()) {
    session_start();
}

header('Content-Type: application/json');

$host = 'localhost';
$db   = 'qr-authentication';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$secret = '#SecretPhrase@123'; // must match with the signup hash

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// QR Code login
if (isset($_POST['send']) && isset($_POST['credential'])) {
    $qrData = trim($_POST['credential']);

    // Expected format: #userId#hash
    if (preg_match('/^#(\d+)#([a-f0-9]{64})$/', $qrData, $matches)) {
        $userId = $matches[1];
        $receivedHash = $matches[2];

        // Fetch user's email using ID
        $stmt = $pdo->prepare("SELECT email, qr_data, name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $expectedHash = hash('sha256', $userId . $user['email'] . $secret);
            $expectedQrData = '#' . $userId . '#' . $expectedHash;

            if ($expectedHash === $receivedHash && $user['qr_data'] === $expectedQrData) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $user['name'];
                $_SESSION['qr_data'] = $user['qr_data'];
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid QR data']);
    exit;
}
?>
