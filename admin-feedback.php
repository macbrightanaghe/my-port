<?php
session_start();

// 1. Set your admin password here
$admin_password = 'admin123'; 

// 2. Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header("Location: admin-feedback.php");
    exit();
}

// 3. Handle Login Submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pass'])) {
    if ($_POST['admin_pass'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin-feedback.php");
        exit();
    } else {
        $error = "Invalid password. Access denied.";
    }
}

// 4. Render Login Form if NOT Authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="padding: 2rem; color: #fff; background: #121212; display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div style="background: #1e1e1e; padding: 2rem; border-radius: 8px; border: 1px solid #333; max-width: 350px; width: 100%;">
        <h2 style="margin-top: 0; color: #ffc107;">Admin Login</h2>
        <p style="color: #aaa; font-size: 0.9rem;">Enter password to manage portfolio feedback.</p>
        
        <?php if ($error): ?>
            <p style="color: #ff4d4d; font-size: 0.9rem;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
            <input type="password" name="admin_pass" placeholder="Enter Password" required style="padding: 0.8rem; background: #222; color: #fff; border: 1px solid #444; border-radius: 6px;">
            <button type="submit" style="padding: 0.8rem; background: #ffc107; color: #000; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Login</button>
        </form>
    </div>
</body>
</html>
<?php
exit();
endif;

// --- PROTECTED ADMIN PANEL BELOW ---
$databases = ['agri_market_db', 'school_db', 'library_db', 'student_db'];
$host = 'localhost';
$user = 'root';
$pass = '';

// Handle approval action across specific project databases
if (isset($_GET['approve_id']) && isset($_GET['target_db'])) {
    $id = (int)$_GET['approve_id'];
    $target_db = $_GET['target_db'];

    if (in_array($target_db, $databases)) {
        $conn = @new mysqli($host, $user, $pass, $target_db);
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("UPDATE testimonials SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $conn->close();
        }
    }
    header("Location: admin-feedback.php");
    exit();
}

// Fetch all pending reviews from each project database
$all_pending = [];
foreach ($databases as $dbname) {
    $conn = @new mysqli($host, $user, $pass, $dbname);
    if (!$conn->connect_error) {
        $result = $conn->query("SELECT * FROM testimonials WHERE status = 'pending' ORDER BY id DESC");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['source_db'] = $dbname;
                $all_pending[] = $row;
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Moderation</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="padding: 2rem; color: #fff; background: #121212;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <a href="index.php" style="color: #ffc107; text-decoration: none;">&leftarrow; Back to Main Portfolio</a>
            <h2 style="margin-top: 0.5rem;">Pending Feedback Moderation</h2>
        </div>
        <a href="admin-feedback.php?action=logout" style="background: #dc3545; color: #fff; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-weight: bold;">Logout</a>
    </div>

    <?php if (!empty($all_pending)): ?>
        <?php foreach ($all_pending as $row): ?>
            <div style="background: #1e1e1e; padding: 1rem; margin-bottom: 1rem; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #ffc107;">
                <div>
                    <span style="background: #333; color: #ffc107; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8rem; text-transform: uppercase;">
                        <?= htmlspecialchars($row['project_name']) ?>
                    </span>
                    <p style="color: #ccc; margin-top: 0.5rem; margin-bottom: 0;">
                        <strong><?= htmlspecialchars($row['client_name']) ?>:</strong> "<?= htmlspecialchars($row['feedback']) ?>"
                    </p>
                </div>
                <a href="admin-feedback.php?approve_id=<?= $row['id'] ?>&target_db=<?= $row['source_db'] ?>" style="background: #25D366; color: #000; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-weight: bold;">Approve</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color: #888;">No pending reviews to approve.</p>
    <?php endif; ?>
</body>
</html>