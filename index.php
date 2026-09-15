<?php
session_start();
$login_error = '';
$contact_status = '';

// 1. Handle Admin Login & Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $password = $_POST['admin_password'] ?? '';
    if ($password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php#admin-section");
        exit;
    } else {
        $login_error = "Invalid Admin Password!";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    header("Location: index.php#admin-section");
    exit;
}

// 2. Database Mapping
$databases = [
    'agri_market_db' => 'agri-market',
    'school_db'      => 'school-system',
    'library_db'     => 'library-system',
    'student_db'     => 'CRUD'
];
$host = 'localhost';
$user = 'root';
$pass = '';

$db_status = [];

// Helper Function: Process Image Upload
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

// 3. Handle Public "Get In Touch" Contact Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    $c_name    = trim($_POST['name'] ?? '');
    $c_email   = trim($_POST['email'] ?? '');
    $c_subject = trim($_POST['subject'] ?? '');
    $c_msg     = trim($_POST['message'] ?? '');

    if (!empty($c_name) && !empty($c_email) && !empty($c_msg)) {
        $conn = @new mysqli($host, $user, $pass, 'agri_market_db');
        if (!$conn->connect_error) {
            $conn->query("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $c_name, $c_email, $c_subject, $c_msg);
            if ($stmt->execute()) {
                $contact_status = "success";
            }
            $stmt->close();
            $conn->close();
        }
    }
}

// 4. Admin Operations (CRUD Testimonials & Delete Messages)
if (!empty($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

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
        header("Location: index.php#admin-messages");
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
        header("Location: index.php#admin-section");
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
        header("Location: index.php#admin-section");
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
            header("Location: index.php#admin-section");
            exit;
        }
    }
}

// 5. Fetch Edit Data if Admin Requested
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

// 6. Fetch Contact Messages for Admin Inbox
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

// 7. Fetch Approved Testimonials, Pending Submissions & DB Health
$approved_testimonials = [];
$pending_testimonials  = [];
$total_approved_count  = 0;
$total_pending_count   = 0;

foreach ($databases as $dbname => $folder) {
    $conn = @new mysqli($host, $user, $pass, $dbname);
    if (!$conn->connect_error) {
        $db_status[$dbname] = 'Connected';

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
    } else {
        $db_status[$dbname] = 'Disconnected';
    }
}

// ... end of your previous loop ...


?> <!-- This closes the loop block -->

<?php
// Initialize contact status
$contact_status = '';
if (isset($_POST['send_contact'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        $msg_conn = new mysqli('localhost', 'root', '', 'agri_market_db');
        if (!$msg_conn->connect_error) {
            $stmt = $msg_conn->prepare("INSERT INTO messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            if ($stmt->execute()) {
                $contact_status = 'success';
            }
            $stmt->close();
            $msg_conn->close();
        }
    }
}
?> <!-- This closes the contact form block right before <!DOCTYPE html> -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Anaghe Mac | Software Engineering Portfolio</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet" />
</head>

<body>

    <!-- Header / Navbar -->
    <header class="header">
        <a class="logo" href="index.php#home">MC <span>BRIGHT</span></a>

        <div class="menu-icon" id="menu-icon">
            <i class='bx bx-menu'></i>
        </div>

        <nav class="navbar" id="nav-links">
            <a href="#home" class="active">Home</a>
            <a href="#about">About</a>
            <a href="#services">Services</a>
            <a href="#projects">Projects</a>
            <a href="#testimonials">Testimonials</a>
            <a href="#admin-section">Admin Panel</a>
            <a href="#contact">Contact</a>

            <button id="theme-toggle" class="theme-toggle-btn" type="button">
                <span id="theme-icon">🌙</span> <span id="theme-text">Dark</span>
            </button>
        </nav>
    </header>

    <!-- Home Section -->
    <section class="home" id="home">
        <div class="home-img">
            <img alt="Anaghe Mac Profile" src="bright.jpg" />
        </div>
        <div class="home-content">
            <h3>Hello, I'm</h3>
            <h1>Anaghe Mac</h1>
            <h3>And I'm a <span class="multiple-text"></span></h3>
            <p>Software Engineering student passionate about designing, developing, and deploying robust web applications, database architectures, and digital systems.</p>
            <div class="social-media">
                <a href="https://github.com" target="_blank" aria-label="GitHub"><i class="bx bxl-github"></i></a>
                <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn"><i class="bx bxl-linkedin"></i></a>
                <a href="#" aria-label="Facebook"><i class="bx bxl-facebook"></i></a>
                <a href="#" aria-label="Instagram"><i class="bx bxl-instagram"></i></a>
            </div>
            <a class="btn" href="#contact">Get In Touch</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="about-content">
            <h2 class="heading">About <span>Me</span></h2>
            <h3>I'm a <span>Software Engineering Student</span></h3>
            <p>Dedicated developer focused on creating clean, scalable code and modeling intuitive system architecture. Experienced in PHP, MySQL, C programming, and StarUML diagram design.</p>
            <a class="btn" href="readmi.php">Read More Details</a>
        </div>
        <div class="about-img">
            <img alt="About Anaghe Mac" src="mac.jpg" />
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <h2 class="heading">My <span>Services</span></h2>
        <div class="services-container">
            <div class="services-box">
                <i class="bx bx-code-alt"></i>
                <h3>Web Development</h3>
                <p>Building responsive front-end layouts and robust back-end PHP/MySQL databases.</p>
                <a class="btn" href="#contact">Inquire Now</a>
            </div>
            <div class="services-box">
                <i class="bx bx-diagram"></i>
                <h3>System Architecture</h3>
                <p>Designing complete UML diagrams (Use Case, Class, Sequence) for enterprise software designs.</p>
                <a class="btn" href="#contact">Inquire Now</a>
            </div>
            <div class="services-box">
                <i class="bx bxl-android"></i>
                <h3>App Development</h3>
                <p>Crafting intuitive and functional user interfaces for mobile and multi-platform applications.</p>
                <a class="btn" href="#contact">Inquire Now</a>
            </div>
        </div>
    </section>

    <!-- Interactive Projects Showcase Section -->
    <section class="projects" id="projects">
        <h2 class="heading">Featured <span>Projects</span></h2>

        <div class="filter-buttons">
            <a href="#projects" class="filter-btn active" data-filter="all">All</a>
            <a href="web-projects.php" class="filter-btn">Web Apps</a>
            <a href="uml-projects.php" class="filter-btn">UML Modeling</a>
            <a href="c-projects.php" class="filter-btn">C Programs</a>

            <div class="projects-container">
                <div class="project-card">
                    <h3>Student Management System</h3>
                    <p>Full-stack administrative management app.</p>
                    <a href="CRUD/index.php" class="btn" target="_blank">View Project</a>
                </div>
                <div class="project-card">
                    <h3>School Management System</h3>
                    <p>Comprehensive system engineered with PHP & MySQL.</p>
                    <a href="school-system/index.php" class="btn" target="_blank">View Project</a>
                </div>
                <div class="project-card">
                    <h3>Agricultural Market System</h3>
                    <p>Digital marketplace designed to connect farmers.</p>
                    <a href="agri-market/index.php" class="btn" target="_blank">View Project</a>
                </div>
                <div class="project-card">
                    <h3>Library Management System</h3>
                    <p>Comprehensive system engineered with PHP & MySQL.</p>
                    <a href="library-system/index.php" class="btn" target="_blank">View Project</a>
                </div>
                <div class="project-card">
                    <h3>Library Management Architecture</h3>
                    <p>Use Case, Class, and Sequence diagrams modeled in StarUML.</p>
                    <a href="uml-library/index.php" class="btn" target="_blank">View Project</a>
                </div>
                <div class="project-card">
                    <h3>Matrix Operations Engine</h3>
                    <p>C console programs for array operations and control structures.</p>
                    <a href="c-matrix/index.php" class="btn" target="_blank">View Project</a>
                </div>
                <div class="project-card">
                    <h3>Data Utility & String Library</h3>
                    <p>Custom procedural C programming routines for string manipulation, custom search algorithms, and structured data handling.</p>
                    <a href="c-string-library/index.php" class="btn" target="_blank">View Project</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <h2 class="heading">Client <span>Testimonials</span></h2>
        <div class="testimonials-container">
            <div class="testimonial-card">
                <img alt="Tracy" src="TRACY.jpg" />
                <h3>TRACY</h3>
                <div class="stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
                <p>MC Bright delivers clean, functional code with great attention to specification details.</p>
            </div>
            <div class="testimonial-card">
                <img alt="Lulu" src="LUNA.jpg" />
                <h3>LULU</h3>
                <div class="stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
                <p>Exceptional problem-solving skills and clean database organization throughout our project.</p>
            </div>
            <div class="testimonial-card">
                <img alt="The Ram" src="THE RAM.jpg" />
                <h3>THE RAM</h3>
                <div class="stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
                <p>Highly dedicated developer and reliable collaborator on engineering workflows.</p>
            </div>

            <?php if (!empty($approved_testimonials)): ?>
                <?php foreach ($approved_testimonials as $review): ?>
                    <?php
                    $photo_file = $review['folder_path'] . '/uploads/' . $review['photo'];
                    $img_src = (!empty($review['photo']) && file_exists($photo_file)) ? $photo_file : 'bright.jpg';
                    $rating_stars = (int)($review['rating'] ?? 5);
                    ?>
                    <div class="testimonial-card" style="border-top: 3px solid #ffc107;">
                        <img src="<?= $img_src ?>" alt="Client Photo" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem; border: 2px solid #ffc107;" />
                        <span style="background: #333; color: #ffc107; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: bold; display: inline-block; margin-bottom: 0.5rem;">
                            <?= htmlspecialchars($review['project_name']) ?>
                        </span>
                        <h3><?= htmlspecialchars($review['client_name']) ?></h3>
                        <div class="stars" style="color: #ffc107;">
                            <?php for ($i = 0; $i < $rating_stars; $i++): ?>
                                <i class="bx bxs-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p>"<?= htmlspecialchars($review['feedback']) ?>"</p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Dynamic Admin Section (Full CRUD Command Center & Inbox) -->
    <section id="admin-section" style="padding: 3rem 2rem; background: #121212;">
        <div style="max-width: 950px; margin: 0 auto; background: #1e1e1e; padding: 2rem; border-radius: 8px; border: 1px solid #333;">

            <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                <!-- ADMIN IS LOGGED IN -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 1.5rem; border-bottom: 1px solid #333; padding-bottom: 1rem;">
                    <div>
                        <h2 style="color: #ffc107; font-size: 1.6rem; margin-bottom: 0.3rem;">⚡ Admin CRUD & Inbox Dashboard</h2>
                        <p style="color: #aaa; font-size: 0.9rem;">Manage Testimonials & Read Client Messages, MC</p>
                    </div>
                    <a href="index.php?action=logout#admin-section" style="padding: 0.5rem 1rem; background: #dc3545; color: #fff; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.85rem;">Logout Admin</a>
                </div>

                <!-- 1. Stats Counter -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 180px; background: #252525; padding: 1rem; border-radius: 6px; border-left: 4px solid #00bcd4;">
                        <span style="color: #aaa; font-size: 0.8rem; text-transform: uppercase;">📬 Inbox Messages</span>
                        <h3 style="color: #00bcd4; font-size: 1.8rem; margin-top: 0.3rem;"><?= count($inbox_messages) ?></h3>
                    </div>
                    <div style="flex: 1; min-width: 180px; background: #252525; padding: 1rem; border-radius: 6px; border-left: 4px solid #28a745;">
                        <span style="color: #aaa; font-size: 0.8rem; text-transform: uppercase;">Approved Reviews</span>
                        <h3 style="color: #28a745; font-size: 1.8rem; margin-top: 0.3rem;"><?= $total_approved_count ?></h3>
                    </div>
                    <div style="flex: 1; min-width: 180px; background: #252525; padding: 1rem; border-radius: 6px; border-left: 4px solid #ffc107;">
                        <span style="color: #aaa; font-size: 0.8rem; text-transform: uppercase;">Pending Reviews</span>
                        <h3 style="color: #ffc107; font-size: 1.8rem; margin-top: 0.3rem;"><?= $total_pending_count ?></h3>
                    </div>
                </div>

                <!-- 2. CLIENT MESSAGES INBOX SECTION -->
                <div id="admin-messages" style="background: #252525; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #00bcd4;">
                    <h3 style="color: #00bcd4; font-size: 1.2rem; margin-bottom: 1rem;">📬 Received Contact Form Messages</h3>

                    <?php if (!empty($inbox_messages)): ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($inbox_messages as $msg): ?>
                                <div style="background: #1a1a1a; padding: 1rem; border-radius: 6px; border-left: 4px solid #00bcd4;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <div>
                                            <h4 style="color: #fff; font-size: 1rem; margin: 0;"><?= htmlspecialchars($msg['name']) ?></h4>
                                            <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color: #ffc107; font-size: 0.85rem; text-decoration: none;"><?= htmlspecialchars($msg['email']) ?></a>
                                        </div>
                                        <span style="color: #777; font-size: 0.75rem;"><?= htmlspecialchars($msg['created_at']) ?></span>
                                    </div>
                                    <div style="margin-bottom: 0.8rem;">
                                        <strong style="color: #00bcd4; font-size: 0.85rem;">Subject: <?= htmlspecialchars($msg['subject']) ?></strong>
                                        <p style="color: #ddd; font-size: 0.9rem; margin-top: 0.3rem; background: #222; padding: 0.8rem; border-radius: 4px; line-height: 1.4;"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                    </div>
                                    <form method="POST" action="index.php#admin-messages" style="margin: 0; text-align: right;">
                                        <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" name="delete_msg" onclick="return confirm('Delete this message from inbox?');" style="padding: 0.4rem 0.8rem; background: #dc3545; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold;">Delete Message</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #888; font-size: 0.85rem; font-style: italic;">No contact messages received yet.</p>
                    <?php endif; ?>
                </div>

                <!-- 3. CREATE or UPDATE TESTIMONIAL FORM -->
                <div style="background: #252525; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #444;">
                    <?php if ($edit_data): ?>
                        <h3 style="color: #00bcd4; font-size: 1.2rem; margin-bottom: 1rem;">✏️ Edit Testimonial #<?= $edit_data['id'] ?></h3>
                        <form method="POST" action="index.php#admin-section" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
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
                                <a href="index.php#admin-section" style="padding: 0.7rem 1.2rem; background: #555; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Cancel Edit</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <h3 style="color: #28a745; font-size: 1.2rem; margin-bottom: 1rem;">➕ Create / Add New Testimonial</h3>
                        <form method="POST" action="index.php#admin-section" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
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

                <!-- 4. Manage Pending Submissions Feed -->
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
                                    <a href="index.php?edit_id=<?= $item['id'] ?>&edit_db=<?= $item['db_origin'] ?>#admin-section" style="padding: 0.5rem 0.8rem; background: #00bcd4; color: #000; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">Edit</a>
                                    <form method="POST" action="index.php#admin-section" style="margin: 0;">
                                        <input type="hidden" name="db_name" value="<?= $item['db_origin'] ?>">
                                        <input type="hidden" name="review_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="admin_action" value="approve" style="padding: 0.5rem 0.8rem; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.8rem;">Approve</button>
                                    </form>
                                    <form method="POST" action="index.php#admin-section" style="margin: 0;">
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

                <!-- 5. Manage Live Approved Testimonials -->
                <h3 style="color: #28a745; font-size: 1.1rem; margin-bottom: 0.8rem;">✅ Approved Reviews Moderation</h3>
                <?php if (!empty($approved_testimonials)): ?>
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                        <?php foreach ($approved_testimonials as $item): ?>
                            <div style="background: #252525; padding: 1rem; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-left: 3px solid #28a745;">
                                <div>
                                    <span style="background: #333; color: #28a745; font-size: 0.7rem; font-weight: bold; padding: 0.2rem 0.5rem; border-radius: 3px; text-transform: uppercase;">
                                        <?= htmlspecialchars($item['project_name']) ?> (<?= $item['db_origin'] ?>)
                                    </span>
                                    <h4 style="color: #fff; margin: 0.4rem 0 0.2rem 0;"><?= htmlspecialchars($item['client_name']) ?></h4>
                                    <p style="color: #ccc; font-size: 0.85rem; margin: 0;">"<?= htmlspecialchars($item['feedback']) ?>"</p>
                                </div>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <a href="index.php?edit_id=<?= $item['id'] ?>&edit_db=<?= $item['db_origin'] ?>#admin-section" style="padding: 0.5rem 0.8rem; background: #00bcd4; color: #000; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem;">Edit</a>
                                    <form method="POST" action="index.php#admin-section" style="margin: 0;">
                                        <input type="hidden" name="db_name" value="<?= $item['db_origin'] ?>">
                                        <input type="hidden" name="review_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="admin_action" value="delete" onclick="return confirm('Remove this review from homepage?');" style="padding: 0.5rem 0.8rem; background: #dc3545; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.8rem;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #888; font-size: 0.85rem; font-style: italic;">No approved reviews currently in databases.</p>
                <?php endif; ?>

            <?php else: ?>
                <!-- ADMIN IS LOGGED OUT -->
                <div style="text-align: center; max-width: 400px; margin: 0 auto;">
                    <h2 style="color: #ffc107; margin-bottom: 0.5rem; font-size: 1.5rem;">🔒 Admin Login</h2>
                    <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 1.5rem;">Enter password to view admin activities & manage feedback</p>

                    <?php if (!empty($login_error)): ?>
                        <p style="color: #ff4d4d; font-size: 0.9rem; font-weight: bold; margin-bottom: 1rem;"><?= $login_error ?></p>
                    <?php endif; ?>

                    <form method="POST" action="index.php#admin-section" style="display: flex; flex-direction: column; gap: 1rem;">
                        <input type="password" name="admin_password" placeholder="Enter Admin Password" required style="width: 100%; padding: 0.8rem; background: #222; color: #fff; border: 1px solid #444; border-radius: 4px; box-sizing: border-box;">
                        <button type="submit" name="admin_login" class="btn" style="background: #ffc107; color: #000; font-weight: bold; cursor: pointer; border: none; padding: 0.8rem;">Login to Dashboard</button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Live Contact Form Section -->
    <section id="contact" class="contact-section">
        <h2 class="heading">Get In <span>Touch</span></h2>

        <?php if ($contact_status === 'success'): ?>
            <p style="text-align: center; color: #28a745; background: #1e3a24; padding: 0.8rem; border-radius: 6px; max-width: 600px; margin: 0 auto 1.5rem auto; font-weight: bold; border: 1px solid #28a745;">
                ✓ Thank you! Your message has been sent to MC's inbox.
            </p>
        <?php endif; ?>

        <form id="contact-form" action="#contact" method="POST" class="contact-form">
            <div class="form-group">
                <input type="text" name="name" placeholder="Your Full Name" required class="form-input">
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Your Email Address" required class="form-input">
            </div>
            <div class="form-group">
                <input type="text" name="subject" placeholder="Subject" required class="form-input">
            </div>
            <div class="form-group">
                <textarea name="message" rows="5" placeholder="Your Message" required class="form-input textarea-input"></textarea>
            </div>

            <button type="submit" name="send_contact" id="form-submit-btn" class="btn">Send Message</button>
        </form>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="social">
            <a href="https://github.com" target="_blank" aria-label="GitHub"><i class="bx bxl-github"></i></a>
            <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn"><i class="bx bxl-linkedin"></i></a>
            <a href="#" aria-label="Facebook"><i class="bx bxl-facebook"></i></a>
            <a href="#" aria-label="Instagram"><i class="bx bxl-instagram"></i></a>
        </div>
        <p class="copyright">© 2026 Anaghe Mac | All Rights Reserved</p>
    </footer>

    <script src="https://unpkg.com/typed.js@2.1.0/dist/typed.umd.js"></script>
    <script src="script.js"></script>
</body>

</html>