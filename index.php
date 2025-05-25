<?php
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


// Password login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
  $emailOrUsername = trim($_POST['username']);
  $password = $_POST['password'];

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$emailOrUsername]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && $user['password'] === $password) {

    if (!session_id()) {
      session_start();
    }


    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $user['name'];
    $_SESSION['qr_data'] = $user['qr_data'];
    header('Location: dashboard.php');
    exit;

  } else {
    die('invalid credentials');
  }
}

// If no condition matched
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Page</title>

  <script type="text/javascript" src="./llqrcode.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    body {
      background-color: #f0f2f5;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
    }

    .login-box {
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
      max-width: 420px;
      width: 100%;
    }

    .btn-purple {
      background-color: #7b2cbf;
      border-color: #7b2cbf;
      color: #fff;
    }

    .btn-purple:hover,
    .btn-purple.active {
      background-color: #5a189a !important;
      border-color: #5a189a !important;
      color: #fff !important;
    }

    .toggle-buttons .btn {
      width: 50%;
      border-radius: 0;
    }

    #qr-login-section {
      display: none;
      text-align: center;
    }

    #camera-preview {
      border: 1px solid #ccc;
      border-radius: 8px;
      width: 100%;
      height: auto;
    }

    .signup-link {
      text-align: center;
      margin-top: 1rem;
    }
  </style>
</head>

<body>
  <div class="login-box">
    <div class="toggle-buttons d-flex mb-3">
      <button id="btn-password" class="btn btn-purple active">Password Login</button>
      <button id="btn-qr" class="btn btn-outline-secondary">QR Login</button>
    </div>

    <!-- Password Login Section -->
    <div id="password-login-section">
      <h4 class="text-center mb-3">Login</h4>
      <form method="POST">
        <div class="mb-3">
          <label for="username" class="form-label">Email</label>
          <input type="text" class="form-control" id="username" name="username" required />
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" required />
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-purple">Login</button>
        </div>
      </form>
    </div>

    <!-- QR Login Section -->
    <div id="qr-login-section">
      <h5 class="text-center">Scan QR Code</h5>
      <div id="qr-output">
        <video id="qr-video" width="300" height="225" style="border-radius: 10px;"></video>
        <canvas id="qr-canvas" width="300" height="225" style="display: none;"></canvas>
        <div id="qr-status" class="mt-3 text-secondary">Scanning QR Code...</div>
      </div>
    </div>

    <div class="signup-link">
      <p>Don't have an account? <a href="signup.php">Create one</a></p>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>

    const btnPassword = document.getElementById("btn-password");
    const btnQR = document.getElementById("btn-qr");
    const sectionPassword = document.getElementById("password-login-section");
    const sectionQR = document.getElementById("qr-login-section");
    const video = document.getElementById("qr-video");
    const canvas = document.getElementById('qr-canvas');
    const context = canvas.getContext('2d');
    let scanning = false;

    // Set QR callback ONCE
    function setupQRCodeCallback() {
      if (typeof qrcode === 'undefined' || !qrcode) {
        console.error("qrcode.js not loaded properly");
        return;
      }

      qrcode.callback = function (data) {
        if (!scanning) return;

        scanning = false;
        document.getElementById("qr-status").innerText = "QR code detected!";
        if (video.srcObject) {
          video.srcObject.getTracks().forEach(track => track.stop());
          video.srcObject = null;
        }

        fetch("authenticate.php", {
          method: "POST",
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ send: true, credential: data })
        })
          .then(async res => {
            const text = await res.text();
            try {
              const json = JSON.parse(text);
              if (json.success) {
                alert("Login successful!");
                window.location.href = 'dashboard.php';
              } else {
                alert("Invalid QR credentials!");
                window.location.href = 'index.php';
              }
            } catch (e) {
              console.error("Invalid JSON returned:", text);
              alert("Server error: Invalid JSON response");
            }
          })
          .catch(err => {
            console.error("Fetch error:", err);
            alert("Server error: " + err.message);
          });
      };
    }

    function tick() {
      if (!scanning) return;

      if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        try {
          qrcode.decode();
        } catch (e) {
          // Expected decode errors — ignore
        }
      }

      if (scanning) {
        requestAnimationFrame(tick); // Scan every 300ms to avoid spam
      }
    }

    function startQRScanner() {
      scanning = true;
      tick();
    }

    btnPassword.addEventListener("click", () => {
      sectionPassword.style.display = "block";
      sectionQR.style.display = "none";
      btnPassword.classList.add("btn-purple", "active");
      btnPassword.classList.remove("btn-outline-secondary");
      btnQR.classList.remove("btn-purple", "active");
      btnQR.classList.add("btn-outline-secondary");

      if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
      }
      scanning = false;
    });

    btnQR.addEventListener("click", async () => {
      sectionPassword.style.display = "none";
      sectionQR.style.display = "block";
      btnQR.classList.add("btn-purple", "active");
      btnQR.classList.remove("btn-outline-secondary");
      btnPassword.classList.remove("btn-purple", "active");
      btnPassword.classList.add("btn-outline-secondary");

      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
        video.setAttribute("playsinline", true);
        video.srcObject = stream;
        video.play();
        startQRScanner();
      } catch (err) {
        document.getElementById("qr-status").innerText = "Camera access denied!";
        console.error("Camera error:", err);
      }
    });

    window.addEventListener("DOMContentLoaded", () => {
      setupQRCodeCallback();
      btnPassword.click();
    });



  </script>
</body>

</html>