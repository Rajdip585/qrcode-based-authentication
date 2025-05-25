<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
$qr_data = htmlspecialchars($_SESSION['qr_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <style>
    body {
      background-color: #f4f5fa;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .dashboard-card {
      background-color: #fff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      max-width: 500px;
      width: 100%;
      text-align: center;
    }

    .dashboard-card h2 {
      color: #5a189a;
      margin-bottom: 0.5rem;
    }

    .dashboard-card p {
      color: #555;
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
  <div class="dashboard-card">
    <h2>Welcome, <?php echo $username; ?> 👋</h2>
    <p>You have successfully logged in.</p>

    <form action="logout.php" method="POST" class="mt-4">
      <button type="submit" class="btn btn-purple">Logout</button>
      <button type="button" class="btn btn-outline-secondary" onclick="downloadQR()">Download Login QR</button>
    </form>
  </div>

<script>
function downloadQR() {
  let qrcode = '<?php echo $qr_data; ?>';
    fetch('https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='+encodeURIComponent(qrcode))
        .then(response => {
            if (!response.ok) {
                throw new Error("Failed to download QR code.");
            }
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'login_qr.png';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url); // clean up
        })
        .catch(error => {
            alert("Error: " + error.message);
        });
}
</script>


</body>
</html>
