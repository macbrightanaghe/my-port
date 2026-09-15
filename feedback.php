<?php
require_once 'db.php';

// 1. Save new feedback as 'pending'
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['client_name'];
    $message = $_POST['feedback'];

    $stmt = $conn->prepare("INSERT INTO testimonials (client_name, feedback, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ss", $name, $message);
    $stmt->execute();
    $msg = "Thank you! Your feedback is waiting for admin approval.";
}

// 2. Fetch ONLY approved feedback
$approved_reviews = $conn->query("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback & Reviews</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="padding: 2rem; color: #fff; background: #121212;">
    <a href="index.php" class="btn">&leftarrow; Back to Portfolio</a>
    <h2>Leave Your Feedback</h2>

    <?php if (isset($msg)): ?>
        <p style="color: #25D366;"><?= $msg ?></p>
    <?php endif; ?>

    <!-- Form for users -->
    <form action="feedback.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem; max-width: 400px; margin: 1.5rem 0;">
        <input type="text" name="client_name" placeholder="Your Name" required style="padding: 0.8rem; background: #222; color: #fff; border: 1px solid #444; border-radius: 6px;">
        <textarea name="feedback" placeholder="Your Feedback" rows="4" required style="padding: 0.8rem; background: #222; color: #fff; border: 1px solid #444; border-radius: 6px;"></textarea>
        <button type="submit" class="btn">Submit Review</button>
    </form>

    <hr style="border-color: #333; margin: 2rem 0;">

    <h2>Approved Reviews</h2>
    <?php if ($approved_reviews->num_rows > 0): ?>
        <?php while($row = $approved_reviews->fetch_assoc()): ?>
            <div style="background: #1e1e1e; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                <p style="color: #ddd;">"<?= htmlspecialchars($row['feedback']) ?>"</p>
                <h4 style="color: var(--accent-glow, #ffc107); margin-top: 0.5rem;">- <?= htmlspecialchars($row['client_name']) ?></h4>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: #888;">No approved reviews yet.</p>
    <?php endif; ?>
</body>
</html>