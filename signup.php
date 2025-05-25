<?php
// signup.php


$host = 'localhost';
$db = 'qr-authentication';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// DB Connection using PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $email === '' || $password === '') {
    die("All fields are required.");
  }

  // Check for existing user
  $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    die("Email already registered.");
  }

  try {
    $pdo->beginTransaction();

    // Check for existing user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      throw new Exception("Email already registered.");
    }

    // Insert new user
    // can use password hashing for extra security
    // $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $password]);

    // Get inserted user ID
    $userId = $pdo->lastInsertId();

    // Generate qr_data hash
    $secret = "#SecretPhrase@123";
    $hash = hash('sha256', $userId . $email . $secret);
    $final_qr_data = '#' . $userId . '#' . $hash;

    // Update user with qr_data
    $stmt = $pdo->prepare("UPDATE users SET qr_data = ? WHERE id = ?");
    $stmt->execute([$final_qr_data, $userId]);

    $pdo->commit();

    die("Registered Successfully");

  } catch (Exception $e) {
    $pdo->rollBack();
    die("Registration failed: " . $e->getMessage());
  }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8f9fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .signup-box {
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      max-width: 420px;
      width: 100%;
    }

    .btn-purple {
      background-color: #7b2cbf;
      border-color: #7b2cbf;
      color: #fff;
    }

    .btn-purple:hover {
      background-color: #5a189a;
      border-color: #5a189a;
    }
  </style>
</head>

<body>
  <div class="signup-box">
    <h4 class="text-center mb-3">Create an Account</h4>
    <form action="#" method="POST">
      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="username" name="username" required />
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" required />
      </div>
      <div class="mb-3">
        <label for="signup-password" class="form-label">Password</label>
        <input type="password" class="form-control" id="signup-password" name="password" required />
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-purple">Sign Up</button>
      </div>
    </form>
  </div>
</body>

</html>