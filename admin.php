<?php
session_start();
$login_error = '';

// Database Connection Parameters
$host = 'localhost';
$user = 'root';
$pass = '';

// --- INITIALIZE PORTFOLIO DATABASE & ADMIN TABLES SECURELY ---
$init_conn = @new mysqli($host, $user, $pass);
if (!$init_conn->connect_error) {
    $init_conn->query("CREATE DATABASE IF NOT EXISTS portfolio_db");
    $init_conn->close();
}

$port_conn = @new mysqli($host, $user, $pass, 'portfolio_db');
if (!$port_conn->connect_error) {
    // Create Services Table
    $port_conn->query("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        icon_class VARCHAR(100) NOT NULL DEFAULT 'bx bx-code-alt'
    )");

    // Populate default services if empty
    $res_check = $port_conn->query("SELECT COUNT(*) as cnt FROM services");
    if ($res_check && $res_check->fetch_assoc()['cnt'] == 0) {
        $port_conn->query("INSERT INTO services (title, description, icon_class) VALUES 
            ('Web Development', 'Building responsive front-end layouts and robust back-end PHP/MySQL databases.', 'bx bx-code-alt'),
            ('System Architecture', 'Designing complete UML diagrams (Use Case, Class, Sequence) for enterprise software designs.', 'bx bx-diagram'),
            ('App Development', 'Crafting intuitive and functional user interfaces for mobile and multi-platform applications.', 'bx bxl-android')");
    }

    // Create About Me Table for Profile Settings
    $port_conn->query("CREATE TABLE IF NOT EXISTS about_me (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subtitle VARCHAR(255) NOT NULL,
        short_description TEXT NOT NULL,
        full_description TEXT NOT NULL,
        profile_photo VARCHAR(255) NOT NULL DEFAULT ''
    )");

    // Populate default About Me row if empty
    $res_about_check = $port_conn->query("SELECT COUNT(*) as cnt FROM about_me");
    if ($res_about_check && $res_about_check->fetch_assoc()['cnt'] == 0) {
        $def_sub  = "Software Engineering Student";
        $def_short = "Dedicated developer focused on creating clean, scalable code and modeling intuitive system architecture. Experienced in PHP, MySQL, C programming, and StarUML diagram design.";
        $def_full  = "Full-stack developer focused on creating scalable web apps with PHP and MySQL, alongside modeling intuitive system architectures using StarUML.";
        $port_conn->query("INSERT INTO about_me (subtitle, short_description, full_description, profile_photo) VALUES ('$def_sub', '$def_short', '$def_full', '')");
    }

    // Create Admins Table with Hashed Password
    $port_conn->query("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    $res_admin = $port_conn->query("SELECT COUNT(*) as cnt FROM admin_users");
    if ($res_admin && $res_admin->fetch_assoc()['cnt'] == 0) {
        $default_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt_adm = $port_conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $u = 'admin';
        $stmt_adm->bind_param("ss", $u, $default_hash);
        $stmt_adm->execute();
        $stmt_adm->close();
    }
    $port_conn->close();
}

// 1. Handle Admin Login & Logout via Password Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $password_input = $_POST['admin_password'] ?? '';

    $conn = @new mysqli($host, $user, $pass, 'portfolio_db');
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT password FROM admin_users WHERE username = 'admin'");
        $stmt->execute();
        $stmt->bind_result($hashed_pwd);
        if ($stmt->fetch() && password_verify($password_input, $hashed_pwd)) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $login_error = "Invalid Admin Password!";
        }
        $stmt->close();
        $conn->close();
    } else {
        $login_error = "Database connection error.";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    header("Location: admin.php");
    exit;
}

// Helper Function: Process Image Upload for Portfolio Assets
function handlePortfolioImageUpload()
{
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['profile_photo']['tmp_name'];
        $file_name = $_FILES['profile_photo']['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_file_name = 'profile_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                return $new_file_name;
            }
        }
    }
    return null;
}

// Helper Function: Process Image Upload for Testimonials
function handleImageUpload($target_db, $databases)
{
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['photo']['tmp_name'];
        $file_name = $_FILES['photo']['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $folder_path = $databases[$target_db] ?? '';
            if ($folder_path) {
                $upload_dir = $folder_path . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_file_name = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    return $new_file_name;
                }
            }
        }
    }
    return null;
}

$databases = [
    'agri_market_db' => 'agri-market',
    'school_db'      => 'school-system',
    'library_db'     => 'library-system',
    'student_db'     => 'CRUD'
];

// 2. Admin CRUD Operations (Logged In)
if (!empty($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- UPDATE ABOUT ME SECTION ---
    if (isset($_POST['update_about'])) {
        $subtitle   = trim($_POST['subtitle'] ?? '');
        $short_desc = trim($_POST['short_description'] ?? '');
        $full_desc  = trim($_POST['full_description'] ?? '');
        $curr_photo = $_POST['current_photo'] ?? '';

        $new_photo = handlePortfolioImageUpload();
        $photo_to_save = $new_photo ? $new_photo : $curr_photo;

        $conn = @new mysqli($host, $user, $pass, 'portfolio_db');
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("UPDATE about_me SET subtitle = ?, short_description = ?, full_description = ?, profile_photo = ? WHERE id = 1");
            $stmt->bind_param("ssss", $subtitle, $short_desc, $full_desc, $photo_to_save);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
        header("Location: admin.php#admin-about");
        exit;
    }

    // --- CREATE NEW SERVICE ---
    if (isset($_POST['add_service'])) {
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $icon  = trim($_POST['icon_class'] ?? 'bx bx-code-alt');

        if (!empty($title) && !empty($desc)) {
            $conn = @new mysqli($host, $user, $pass, 'portfolio_db');
            if (!$conn->connect_error) {
                $stmt = $conn->prepare("INSERT INTO services (title, description, icon_class) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $title, $desc, $icon);
                $stmt->execute();
                $conn->close();
            }
        }
        header("Location: admin.php#admin-services");
        exit;
    }

    // --- UPDATE SERVICES ---
    if (isset($_POST['update_service'])) {
        $service_id = (int)($_POST['service_id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $desc       = trim($_POST['description'] ?? '');

        if ($service_id > 0) {
            $conn = @new mysqli($host, $user, $pass, 'portfolio_db');
            if (!$conn->connect_error) {
                $stmt = $conn->prepare("UPDATE services SET title = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $title, $desc, $service_id);
                $stmt->execute();
                $conn->close();
            }
        }
        header("Location: admin.php#admin-services");
        exit;
    }

    // --- DELETE SERVICE ---
    if (isset($_POST['delete_service'])) {
        $service_id = (int)($_POST['service_id'] ?? 0);
        if ($service_id > 0) {
            $conn = @new mysqli($host, $user, $pass, 'portfolio_db');
            if (!$conn->connect_error) {
                $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
                $stmt->bind_param("i", $service_id);
                $stmt->execute();
                $conn->close();
            }
        }
        header("Location: admin.php#admin-services");
        exit;
    }

    // --- DELETE CONTACT MESSAGE ---
    if (isset($_POST['delete_msg'])) {
        $msg_id = (int)($_POST['msg_id'] ?? 0);
        if ($msg_id > 0) {
            $conn = @new mysqli($host, $user, $pass, 'agri_market_db');
            if (!$conn->connect_error) {
                $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->bind_param("i", $msg_id);
                $stmt->execute();
                $conn->close();
            }
        }
        header("Location: admin.php#admin-messages");
        exit;
    }

    // --- CREATE TESTIMONIAL ---
    if (isset($_POST['action_create'])) {
        $target_db   = $_POST['db_name'] ?? '';
        $client_name = trim($_POST['client_name'] ?? '');
        $project     = trim($_POST['project_name'] ?? '');
        $rating      = (int)($_POST['rating'] ?? 5);
        $feedback    = trim($_POST['feedback'] ?? '');
        $status      = $_POST['status'] ?? 'approved';

        $uploaded_photo = handleImageUpload($target_db, $databases) ?? '';

        if (array_key_exists($target_db, $databases) && !empty($client_name) && !empty($feedback)) {
            $conn = @new mysqli($host, $user, $pass, $target_db);
            if (!$conn->connect_error) {
                $stmt = $conn->prepare("INSERT INTO testimonials (client_name, project_name, rating, feedback, status, photo) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisss", $client_name, $project, $rating, $feedback, $status, $uploaded_photo);
                $stmt->execute();
                $conn->close();
            }
        }
        header("Location: admin.php#admin-testimonials");
        exit;
    }

    // --- UPDATE TESTIMONIAL ---
    if (isset($_POST['action_update'])) {
        $target_db   = $_POST['db_name'] ?? '';
        $review_id   = (int)($_POST['review_id'] ?? 0);
        $client_name = trim($_POST['client_name'] ?? '');
        $project     = trim($_POST['project_name'] ?? '');
        $rating      = (int)($_POST['rating'] ?? 5);
        $feedback    = trim($_POST['feedback'] ?? '');
        $status      = $_POST['status'] ?? 'approved';
        $current_img = $_POST['current_photo'] ?? '';

        $new_photo     = handleImageUpload($target_db, $databases);
        $photo_to_save = $new_photo ? $new_photo : $current_img;

        if (array_key_exists($target_db, $databases) && $review_id > 0) {
            $conn = @new mysqli($host, $user, $pass, $target_db);
            if (!$conn->connect_error) {
                $stmt = $conn->prepare("UPDATE testimonials SET client_name=?, project_name=?, rating=?, feedback=?, status=?, photo=? WHERE id=?");
                $stmt->bind_param("ssisssi", $client_name, $project, $rating, $feedback, $status, $photo_to_save, $review_id);
                $stmt->execute();
                $conn->close();
            }
        }
        header("Location: admin.php#admin-testimonials");
        exit;
    }

    // --- QUICK APPROVE & DELETE TESTIMONIAL ---
    if (isset($_POST['admin_action'])) {
        $target_db = $_POST['db_name'] ?? '';
        $review_id = (int)($_POST['review_id'] ?? 0);
        $action    = $_POST['admin_action'];

        if (array_key_exists($target_db, $databases) && $review_id > 0) {
            $conn = @new mysqli($host, $user, $pass, $target_db);
            if (!$conn->connect_error) {
                if ($action === 'approve') {
                    $stmt = $conn->prepare("UPDATE testimonials SET status = 'approved' WHERE id = ?");
                    $stmt->bind_param("i", $review_id);
                    $stmt->execute();
                } elseif ($action === 'delete') {
                    $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
                    $stmt->bind_param("i", $review_id);
                    $stmt->execute();
                }
                $conn->close();
            }
            header("Location: admin.php#admin-testimonials");
            exit;
        }
    }
}

// Fetch About Me Data
$about_data = [
    'subtitle' => 'Software Engineering Student',
    'short_description' => 'Dedicated developer focused on creating clean, scalable code and modeling intuitive system architecture.',
    'full_description' => 'Full-stack developer focused on creating scalable web apps with PHP and MySQL, alongside modeling intuitive system architectures using StarUML.',
    'profile_photo' => ''
];
$conn = @new mysqli($host, $user, $pass, 'portfolio_db');
if (!$conn->connect_error) {
    $res_abt = $conn->query("SELECT * FROM about_me WHERE id = 1");
    if ($res_abt && $res_abt->num_rows > 0) {
        $about_data = $res_abt->fetch_assoc();
    }
    $conn->close();
}

// Fetch Editing Item Data for Testimonials
$edit_data = null;
if (!empty($_SESSION['admin_logged_in']) && isset($_GET['edit_id']) && isset($_GET['edit_db'])) {
    $e_id = (int)$_GET['edit_id'];
    $e_db = $_GET['edit_db'];
    if (array_key_exists($e_db, $databases) && $e_id > 0) {
        $conn = @new mysqli($host, $user, $pass, $e_db);
        if (!$conn->connect_error) {
            $res = $conn->query("SELECT * FROM testimonials WHERE id = $e_id");
            if ($res && $res->num_rows > 0) {
                $edit_data = $res->fetch_assoc();
                $edit_data['db_origin'] = $e_db;
            }
            $conn->close();
        }
    }
}

// Fetch Contact Inbox Messages
$inbox_messages = [];
if (!empty($_SESSION['admin_logged_in'])) {
    $conn = @new mysqli($host, $user, $pass, 'agri_market_db');
    if (!$conn->connect_error) {
        $res_msg = $conn->query("SELECT * FROM messages ORDER BY id DESC");
        if ($res_msg) {
            while ($m_row = $res_msg->fetch_assoc()) {
                $inbox_messages[] = $m_row;
            }
        }
        $conn->close();
    }
}

// Fetch Services List
$services_list = [];
if (!empty($_SESSION['admin_logged_in'])) {
    $conn = @new mysqli($host, $user, $pass, 'portfolio_db');
    if (!$conn->connect_error) {
        $res_srv = $conn->query("SELECT * FROM services ORDER BY id DESC");
        if ($res_srv) {
            while ($s_row = $res_srv->fetch_assoc()) {
                $services_list[] = $s_row;
            }
        }
        $conn->close();
    }
}

// Fetch Testimonial Stats & Feeds
$approved_testimonials = [];
$pending_testimonials  = [];
$total_approved_count  = 0;
$total_pending_count   = 0;

foreach ($databases as $dbname => $folder) {
    $conn = @new mysqli($host, $user, $pass, $dbname);
    if (!$conn->connect_error) {
        $res_approved = $conn->query("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY id DESC");
        if ($res_approved) {
            while ($row = $res_approved->fetch_assoc()) {
                $row['folder_path'] = $folder;
                $row['db_origin']   = $dbname;
                $approved_testimonials[] = $row;
            }
            $total_approved_count += $res_approved->num_rows;
        }

        $res_pending = $conn->query("SELECT * FROM testimonials WHERE status = 'pending' ORDER BY id DESC");
        if ($res_pending) {
            while ($row = $res_pending->fetch_assoc()) {
                $row['folder_path'] = $folder;
                $row['db_origin']   = $dbname;
                $pending_testimonials[] = $row;
            }
            $total_pending_count += $res_pending->num_rows;
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MC Bright Portfolio</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Responsive CSS Rules for Admin Navigation Menu Toggle */
        #menu-icon {
            display: none;
            font-size: 2.2rem;
            cursor: pointer;
            color: #ffc107;
            background: transparent;
            border: none;
            padding: 0;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 900px) {
            #menu-icon {
                display: flex !important;
            }

            .admin-nav-links {
                display: none !important;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: #1a1a1a;
                flex-direction: column !important;
                align-items: stretch !important;
                padding: 1.2rem;
                gap: 0.8rem !important;
                border: 1px solid #333;
                border-radius: 6px;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.6);
                z-index: 100;
            }

            .admin-nav-links.active {
                display: flex !important;
            }

            .admin-nav-links a {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body style="background: #121212; color: #fff; font-family: Arial, sans-serif; margin: 0; padding: 2rem;">

    <div style="max-width: 1100px; margin: 0 auto; background: #1e1e1e; padding: 2rem; border-radius: 8px; border: 1px solid #333;">

        <?php if (!empty($_SESSION['admin_logged_in'])): ?>
            <!-- DASHBOARD INTERFACE WITH ENHANCED HAMBURGER NAVIGATION MENU -->
            <div class="admin-header-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 1.5rem; border-bottom: 1px solid #333; padding-bottom: 1rem; position: relative;">
                <div>
                    <h2 style="color: #ffc107; font-size: 1.6rem; margin-bottom: 0.3rem;">⚡ Admin Command Center</h2>
                    <p style="color: #aaa; font-size: 0.9rem;">Manage Profile Info, Services, Testimonials & Inbox, MC</p>
                </div>

                <!-- Admin Mobile Hamburger Icon -->
                <div id="menu-icon" class="admin-menu-icon">
                    <i class='bx bx-menu'></i>
                </div>
                <ul class="admin-nav-links navbar">
                    <!-- Admin nav items here -->
                </ul>

                <!-- Admin Action Links / Menu with Jump-to Sections -->
                <div class="navbar admin-nav-links" id="adminNavLinks" style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                    <a href="#admin-about" style="padding: 0.5rem 0.8rem; background: #252525; color: #00bcd4; border: 1px solid #00bcd4; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">Edit Profile</a>
                    <a href="#admin-services" style="padding: 0.5rem 0.8rem; background: #252525; color: #ffc107; border: 1px solid #ffc107; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">Manage Portfolio</a>
                    <a href="#admin-messages" style="padding: 0.5rem 0.8rem; background: #252525; color: #00bcd4; border: 1px solid #00bcd4; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">Received Contact</a>
                    <a href="#admin-testimonials" style="padding: 0.5rem 0.8rem; background: #252525; color: #28a745; border: 1px solid #28a745; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">Add Testimonial</a>
                    <a href="index.php" target="_blank" style="padding: 0.5rem 0.8rem; background: #00bcd4; color: #000; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">View Portfolio</a>
                    <a href="admin.php?action=logout" style="padding: 0.5rem 0.8rem; background: #dc3545; color: #fff; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">Logout</a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 180px; background: #252525; padding: 1rem; border-radius: 6px; border-left: 4px solid #00bcd4;">
                    <span style="color: #aaa; font-size: 0.8rem; text-transform: uppercase;">📬 Inbox Messages</span>
                    <h3 style="color: #00bcd4; font-size: 1.8rem; margin-top: 0.3rem;"><?= count($inbox_messages) ?></h3>
                </div>
                <div style="flex: 1; min-width: 180px; background: #252525; padding: 1rem; border-radius: 6px; border-left: 4px solid #ffc107;">
                    <span style="color: #aaa; font-size: 0.8rem; text-transform: uppercase;">🛠️ Services Listed</span>
                    <h3 style="color: #ffc107; font-size: 1.8rem; margin-top: 0.3rem;"><?= count($services_list) ?></h3>
                </div>
                <div style="flex: 1; min-width: 180px; background: #252525; padding: 1rem; border-radius: 6px; border-left: 4px solid #28a745;">
                    <span style="color: #aaa; font-size: 0.8rem; text-transform: uppercase;">Approved Reviews</span>
                    <h3 style="color: #28a745; font-size: 1.8rem; margin-top: 0.3rem;"><?= $total_approved_count ?></h3>
                </div>
            </div>

            <!-- 0. MANAGE ABOUT ME & PROFILE PHOTO SECTION -->
            <div id="admin-about" style="background: #252525; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #00bcd4; scroll-margin-top: 2rem;">
                <h3 style="color: #00bcd4; font-size: 1.2rem; margin-bottom: 1rem;">🖼️ Edit "About Me" & Profile Photo</h3>

                <form method="POST" action="admin.php#admin-about" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1rem;">
                    <input type="hidden" name="current_photo" value="<?= htmlspecialchars($about_data['profile_photo']) ?>">

                    <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; background: #1a1a1a; padding: 1rem; border-radius: 6px;">
                        <div>
                            <?php if (!empty($about_data['profile_photo']) && file_exists('uploads/' . $about_data['profile_photo'])): ?>
                                <img src="uploads/<?= htmlspecialchars($about_data['profile_photo']) ?>" alt="Profile Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #ffc107;">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; border-radius: 50%; background: #333; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 0.75rem; text-align: center;">No Photo</div>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; min-width: 240px;">
                            <label style="color: #aaa; font-size: 0.8rem; display: block; margin-bottom: 0.3rem;">Upload New Profile Picture (.jpg, .png, .webp)</label>
                            <input type="file" name="profile_photo" accept="image/*" style="width: 100%; padding: 0.5rem; background: #222; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box; font-size: 0.85rem;">
                        </div>
                    </div>

                    <div>
                        <label style="color: #aaa; font-size: 0.8rem;">Subtitle Headline (e.g. Software Engineering Student)</label>
                        <input type="text" name="subtitle" value="<?= htmlspecialchars($about_data['subtitle']) ?>" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box; margin-top: 0.3rem;">
                    </div>

                    <div>
                        <label style="color: #aaa; font-size: 0.8rem;">Short Description (Shown on Main Portfolio Page)</label>
                        <textarea name="short_description" rows="3" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box; margin-top: 0.3rem;"><?= htmlspecialchars($about_data['short_description']) ?></textarea>
                    </div>

                    <div>
                        <label style="color: #aaa; font-size: 0.8rem;">Full Description (Shown on Expanded "Read More" Details Page)</label>
                        <textarea name="full_description" rows="3" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box; margin-top: 0.3rem;"><?= htmlspecialchars($about_data['full_description']) ?></textarea>
                    </div>

                    <div>
                        <button type="submit" name="update_about" style="padding: 0.6rem 1.2rem; background: #00bcd4; color: #000; font-weight: bold; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Save Profile Changes</button>
                    </div>
                </form>
            </div>

            <!-- 1. MANAGE SERVICES SECTION WITH LIVE SEARCH -->
            <div id="admin-services" style="background: #252525; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #ffc107; scroll-margin-top: 2rem;">
                <h3 style="color: #ffc107; font-size: 1.2rem; margin-bottom: 1rem;">🛠️ Manage Portfolio Services</h3>

                <!-- Add New Service Form -->
                <form method="POST" action="admin.php#admin-services" style="background: #1a1a1a; padding: 1rem; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 1.5rem;">
                    <h4 style="color: #28a745; margin-top: 0; font-size: 1rem;">➕ Add New Service</h4>
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 0.8rem;">
                        <div>
                            <label style="color: #aaa; font-size: 0.8rem;">Service Title</label>
                            <input type="text" name="title" placeholder="e.g. Mobile App Dev" required style="width: 100%; padding: 0.6rem; background: #222; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box; margin-top: 0.3rem;">
                        </div>
                        <div>
                            <label style="color: #aaa; font-size: 0.8rem;">BoxIcon Class</label>
                            <input type="text" name="icon_class" value="bx bx-code-alt" required style="width: 100%; padding: 0.6rem; background: #222; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box; margin-top: 0.3rem;">
                        </div>
                    </div>
                    <div style="margin-bottom: 0.8rem;">
                        <label style="color: #aaa; font-size: 0.8rem;">Service Description</label>
                        <textarea name="description" rows="2" placeholder="Describe the service..." required style="width: 100%; padding: 0.6rem; background: #222; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box; margin-top: 0.3rem;"></textarea>
                    </div>
                    <button type="submit" name="add_service" style="padding: 0.5rem 1rem; background: #28a745; color: #fff; font-weight: bold; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Add Service</button>
                </form>

                <!-- Live Search Bar Header for Services -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                    <h4 style="color: #fff; font-size: 1rem; margin: 0;">Existing Services</h4>
                    <div style="position: relative; flex: 1; max-width: 300px; min-width: 220px;">
                        <input type="text" id="serviceSearchInput" onkeyup="filterServices()" placeholder="🔍 Search services..." style="width: 100%; padding: 0.5rem 0.8rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Existing Services Grid Layout -->
                <?php if (!empty($services_list)): ?>
                    <div id="servicesGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                        <?php foreach ($services_list as $srv): ?>
                            <div class="service-card" data-title="<?= strtolower(htmlspecialchars($srv['title'])) ?>" data-desc="<?= strtolower(htmlspecialchars($srv['description'])) ?>" style="background: #1a1a1a; padding: 1rem; border-radius: 6px; border-top: 3px solid #ffc107; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <form method="POST" action="admin.php#admin-services" style="margin: 0;">
                                        <input type="hidden" name="service_id" value="<?= $srv['id'] ?>">
                                        <div style="margin-bottom: 0.6rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <label style="color: #aaa; font-size: 0.75rem;">Service Title</label>
                                                <span style="color: #ffc107; font-size: 0.75rem;"><i class="<?= htmlspecialchars($srv['icon_class']) ?>"></i> ID: <?= $srv['id'] ?></span>
                                            </div>
                                            <input type="text" name="title" value="<?= htmlspecialchars($srv['title']) ?>" required style="width: 100%; padding: 0.5rem; background: #222; color: #fff; border: 1px solid #555; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; margin-top: 0.2rem;">
                                        </div>
                                        <div style="margin-bottom: 0.8rem;">
                                            <label style="color: #aaa; font-size: 0.75rem;">Service Description</label>
                                            <textarea name="description" rows="2" required style="width: 100%; padding: 0.5rem; background: #222; color: #fff; border: 1px solid #555; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box; margin-top: 0.2rem;"><?= htmlspecialchars($srv['description']) ?></textarea>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button type="submit" name="update_service" style="flex: 1; padding: 0.45rem; background: #ffc107; color: #000; font-weight: bold; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">Save</button>
                                    </form>
                                    <form method="POST" action="admin.php#admin-services" style="margin: 0; flex: 1;">
                                        <input type="hidden" name="service_id" value="<?= $srv['id'] ?>">
                                        <button type="submit" name="delete_service" onclick="return confirm('Delete this service?');" style="width: 100%; padding: 0.45rem; background: #dc3545; color: #fff; font-weight: bold; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">Delete</button>
                                    </form>
                                </div>
                            </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p id="noServiceFound" style="display: none; color: #888; font-size: 0.85rem; font-style: italic; text-align: center; padding: 1.5rem;">No services match your search keyword.</p>
        <?php else: ?>
            <p style="color: #888; font-size: 0.85rem; font-style: italic;">No services found.</p>
        <?php endif; ?>
    </div>

    <!-- 2. CLIENT MESSAGES INBOX WITH LIVE SEARCH -->
    <div id="admin-messages" style="background: #252525; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #00bcd4; scroll-margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <h3 style="color: #00bcd4; font-size: 1.2rem; margin: 0;">📬 Received Contact Form Messages</h3>
            <div style="position: relative; flex: 1; max-width: 300px; min-width: 220px;">
                <input type="text" id="msgSearchInput" onkeyup="filterMessages()" placeholder="🔍 Search messages by name, email..." style="width: 100%; padding: 0.5rem 0.8rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
            </div>
        </div>

        <?php if (!empty($inbox_messages)): ?>
            <div id="messagesContainer" style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($inbox_messages as $msg): ?>
                    <div class="message-card" data-search="<?= strtolower(htmlspecialchars($msg['sender_name'] . ' ' . $msg['email'] . ' ' . $msg['subject'] . ' ' . $msg['message'])) ?>" style="background: #1a1a1a; padding: 1rem; border-radius: 6px; border-left: 4px solid #00bcd4;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div>
                                <h4 style="color: #fff; font-size: 1rem; margin: 0;"><?= htmlspecialchars($msg['sender_name']) ?></h4>
                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color: #ffc107; font-size: 0.85rem; text-decoration: none;"><?= htmlspecialchars($msg['email']) ?></a>
                            </div>
                            <span style="color: #777; font-size: 0.75rem;"><?= htmlspecialchars($msg['created_at']) ?></span>
                        </div>
                        <div style="margin-bottom: 0.8rem;">
                            <strong style="color: #00bcd4; font-size: 0.85rem;">Subject: <?= htmlspecialchars($msg['subject']) ?></strong>
                            <p style="color: #ddd; font-size: 0.9rem; margin-top: 0.3rem; background: #222; padding: 0.8rem; border-radius: 4px; line-height: 1.4;"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        </div>
                        <form method="POST" action="admin.php#admin-messages" style="margin: 0; text-align: right;">
                            <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                            <button type="submit" name="delete_msg" onclick="return confirm('Delete this message?');" style="padding: 0.4rem 0.8rem; background: #dc3545; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold;">Delete Message</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            <p id="noMsgFound" style="display: none; color: #888; font-size: 0.85rem; font-style: italic; text-align: center; padding: 1.5rem;">No messages match your search keyword.</p>
        <?php else: ?>
            <p style="color: #888; font-size: 0.85rem; font-style: italic;">No contact messages received yet.</p>
        <?php endif; ?>
    </div>

    <!-- JavaScript for Live Filtering & Mobile Navigation Toggle -->
    <script src="script.js"></script>
    <script>
        // Toggle Admin Mobile Navigation Menu
        const menuIcon = document.getElementById('menu-icon');
        const adminNavLinks = document.getElementById('adminNavLinks');

        if (menuIcon && adminNavLinks) {
            menuIcon.addEventListener('click', () => {
                adminNavLinks.classList.toggle('active');
            });

            // Automatically close menu when any menu link is clicked on mobile
            const navAnchors = adminNavLinks.querySelectorAll('a');
            navAnchors.forEach(anchor => {
                anchor.addEventListener('click', () => {
                    adminNavLinks.classList.remove('active');
                });
            });
        }

        function filterServices() {
            let input = document.getElementById('serviceSearchInput').value.toLowerCase();
            let cards = document.getElementsByClassName('service-card');
            let visibleCount = 0;
            let noMatchEl = document.getElementById('noServiceFound');

            for (let i = 0; i < cards.length; i++) {
                let title = cards[i].getAttribute('data-title');
                let desc = cards[i].getAttribute('data-desc');

                if (title.includes(input) || desc.includes(input)) {
                    cards[i].style.display = "flex";
                    visibleCount++;
                } else {
                    cards[i].style.display = "none";
                }
            }

            if (noMatchEl) {
                noMatchEl.style.display = (visibleCount === 0) ? "block" : "none";
            }
        }

        function filterMessages() {
            let input = document.getElementById('msgSearchInput').value.toLowerCase();
            let cards = document.getElementsByClassName('message-card');
            let visibleCount = 0;
            let noMatchEl = document.getElementById('noMsgFound');

            for (let i = 0; i < cards.length; i++) {
                let searchData = cards[i].getAttribute('data-search');

                if (searchData.includes(input)) {
                    cards[i].style.display = "block";
                    visibleCount++;
                } else {
                    cards[i].style.display = "none";
                }
            }

            if (noMatchEl) {
                noMatchEl.style.display = (visibleCount === 0) ? "block" : "none";
            }
        }
    </script>

    <!-- 3. TESTIMONIALS MANAGEMENT -->
    <div id="admin-testimonials" style="background: #252525; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #444; scroll-margin-top: 2rem;">
        <?php if ($edit_data): ?>
            <h3 style="color: #00bcd4; font-size: 1.2rem; margin-bottom: 1rem;">✏️ Edit Testimonial #<?= $edit_data['id'] ?></h3>
            <form method="POST" action="admin.php#admin-testimonials" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <input type="hidden" name="review_id" value="<?= $edit_data['id'] ?>">
                <input type="hidden" name="db_name" value="<?= $edit_data['db_origin'] ?>">
                <input type="hidden" name="current_photo" value="<?= htmlspecialchars($edit_data['photo'] ?? '') ?>">

                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Client Name</label>
                    <input type="text" name="client_name" value="<?= htmlspecialchars($edit_data['client_name']) ?>" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Project Name</label>
                    <input type="text" name="project_name" value="<?= htmlspecialchars($edit_data['project_name']) ?>" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Rating (1 to 5 Stars)</label>
                    <select name="rating" style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <option value="<?= $s ?>" <?= ((int)$edit_data['rating'] === $s) ? 'selected' : '' ?>><?= $s ?> Star<?= $s > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Status</label>
                    <select name="status" style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                        <option value="approved" <?= ($edit_data['status'] === 'approved') ? 'selected' : '' ?>>Approved</option>
                        <option value="pending" <?= ($edit_data['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="color: #aaa; font-size: 0.8rem;">Client Photo Avatar</label>
                    <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="color: #aaa; font-size: 0.8rem;">Feedback Content</label>
                    <textarea name="feedback" rows="3" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;"><?= htmlspecialchars($edit_data['feedback']) ?></textarea>
                </div>
                <div style="grid-column: 1 / -1; display: flex; gap: 0.5rem;">
                    <button type="submit" name="action_update" style="padding: 0.7rem 1.2rem; background: #00bcd4; color: #000; font-weight: bold; border: none; border-radius: 4px; cursor: pointer;">Update Testimonial</button>
                    <a href="admin.php#admin-testimonials" style="padding: 0.7rem 1.2rem; background: #555; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Cancel Edit</a>
                </div>
            </form>
        <?php else: ?>
            <h3 style="color: #28a745; font-size: 1.2rem; margin-bottom: 1rem;">➕ Add New Testimonial</h3>
            <form method="POST" action="admin.php#admin-testimonials" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Target Database</label>
                    <select name="db_name" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                        <?php foreach ($databases as $db_key => $db_folder): ?>
                            <option value="<?= $db_key ?>"><?= $db_key ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Client Name</label>
                    <input type="text" name="client_name" placeholder="e.g. John Doe" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Project Name</label>
                    <input type="text" name="project_name" placeholder="e.g. Agri-Market" required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Rating (1 to 5 Stars)</label>
                    <select name="rating" style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                </div>
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Status</label>
                    <select name="status" style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                        <option value="approved">Approved (Live)</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div>
                    <label style="color: #aaa; font-size: 0.8rem;">Client Photo Avatar</label>
                    <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="color: #aaa; font-size: 0.8rem;">Feedback Content</label>
                    <textarea name="feedback" rows="3" placeholder="Enter review message..." required style="width: 100%; padding: 0.6rem; background: #1a1a1a; color: #fff; border: 1px solid #555; border-radius: 4px; box-sizing: border-box;"></textarea>
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="action_create" style="padding: 0.7rem 1.2rem; background: #28a745; color: #fff; font-weight: bold; border: none; border-radius: 4px; cursor: pointer;">Add Testimonial</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Pending Approvals Feed -->
    <h3 style="color: #ffc107; font-size: 1.1rem; margin-bottom: 0.8rem;">⏳ Pending Approvals Feed</h3>
    <?php if (!empty($pending_testimonials)): ?>
        <div style="display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 2rem;">
            <?php foreach ($pending_testimonials as $item): ?>
                <div style="background: #252525; padding: 1rem; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <span style="background: #ffc107; color: #000; font-size: 0.7rem; font-weight: bold; padding: 0.2rem 0.5rem; border-radius: 3px; text-transform: uppercase;">
                            <?= htmlspecialchars($item['project_name']) ?> (<?= $item['db_origin'] ?>)
                        </span>
                        <h4 style="color: #fff; margin: 0.4rem 0 0.2rem 0;"><?= htmlspecialchars($item['client_name']) ?></h4>
                        <p style="color: #ccc; font-size: 0.85rem; margin: 0;">"<?= htmlspecialchars($item['feedback']) ?>"</p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <a href="admin.php?edit_id=<?= $item['id'] ?>&edit_db=<?= $item['db_origin'] ?>#admin-testimonials" style="padding: 0.5rem 0.8rem; background: #00bcd4; color: #000; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">Edit</a>
                        <form method="POST" action="admin.php#admin-testimonials" style="margin: 0;">
                            <input type="hidden" name="db_name" value="<?= $item['db_origin'] ?>">
                            <input type="hidden" name="review_id" value="<?= $item['id'] ?>">
                            <button type="submit" name="admin_action" value="approve" style="padding: 0.5rem 0.8rem; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.8rem;">Approve</button>
                        </form>
                        <form method="POST" action="admin.php#admin-testimonials" style="margin: 0;">
                            <input type="hidden" name="db_name" value="<?= $item['db_origin'] ?>">
                            <input type="hidden" name="review_id" value="<?= $item['id'] ?>">
                            <button type="submit" name="admin_action" value="delete" onclick="return confirm('Delete this pending review?');" style="padding: 0.5rem 0.8rem; background: #dc3545; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.8rem;">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: #888; font-size: 0.85rem; font-style: italic; margin-bottom: 2rem;">No pending submissions waiting for approval.</p>
    <?php endif; ?>

<?php else: ?>
    <!-- LOGIN SCREEN -->
    <div style="text-align: center; max-width: 400px; margin: 0 auto; padding: 3rem 0;">
        <h2 style="color: #ffc107; margin-bottom: 0.5rem; font-size: 1.5rem;">🔒 Admin Login</h2>
        <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 1.5rem;">Enter password to access dashboard controls</p>

        <?php if (!empty($login_error)): ?>
            <p style="color: #ff4d4d; font-size: 0.9rem; font-weight: bold; margin-bottom: 1rem;"><?= $login_error ?></p>
        <?php endif; ?>

        <form method="POST" action="admin.php" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="password" name="admin_password" placeholder="Enter Admin Password" required style="width: 100%; padding: 0.8rem; background: #222; color: #fff; border: 1px solid #444; border-radius: 4px; box-sizing: border-box;">
            <button type="submit" name="admin_login" style="background: #ffc107; color: #000; font-weight: bold; cursor: pointer; border: none; padding: 0.8rem; border-radius: 4px;">Login to Dashboard</button>
        </form>
        <div style="margin-top: 1.5rem;">
            <a href="index.php" style="color: #00bcd4; text-decoration: none; font-size: 0.85rem;">&larr; Back to Portfolio</a>
        </div>
    </div>
<?php endif; ?>

</div>

</body>

</html>