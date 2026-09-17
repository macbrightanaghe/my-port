<?php
// Handle AJAX Contact Form Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_contact'])) {
    header('Content-Type: application/json');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        $msg_conn = @new mysqli('localhost', 'root', '', 'agri_market_db');
        if (!$msg_conn->connect_error) {
            $msg_conn->query("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $msg_conn->prepare("INSERT INTO messages (sender_name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Thank you! Your message has been sent successfully.']);
                exit;
            }
            $stmt->close();
            $msg_conn->close();
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Please fill out all required fields correctly.']);
    exit;
}

// Fetch Dynamic About Me & Profile Photo from portfolio_db
$about = [
    'subtitle' => 'Software Engineering Student',
    'short_description' => 'Dedicated developer focused on creating clean, scalable code and modeling intuitive system architecture. Experienced in PHP, MySQL, C programming, and StarUML diagram design.',
    'full_description' => 'Full-stack developer focused on creating scalable web apps with PHP and MySQL, alongside modeling intuitive system architectures using StarUML.',
    'profile_photo' => ''
];
$port_conn = @new mysqli('localhost', 'root', '', 'portfolio_db');
if (!$port_conn->connect_error) {
    $res_abt = $port_conn->query("SELECT * FROM about_me WHERE id = 1");
    if ($res_abt && $res_abt->num_rows > 0) {
        $about = $res_abt->fetch_assoc();
    }

    // Fetch Dynamic Services from portfolio_db
    $dynamic_services = [];
    $srv_res = $port_conn->query("SELECT * FROM services");
    if ($srv_res) {
        while ($s_row = $srv_res->fetch_assoc()) {
            $dynamic_services[] = $s_row;
        }
    }
    $port_conn->close();
}

// Fetch Approved Testimonials from databases
$approved_testimonials = [];
$databases = [
    'agri_market_db' => 'agri-market',
    'school_db'      => 'school-system',
    'library_db'     => 'library-system',
    'student_db'     => 'CRUD'
];
foreach ($databases as $dbname => $folder) {
    $conn = @new mysqli('localhost', 'root', '', $dbname);
    if (!$conn->connect_error) {
        $res_approved = $conn->query("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY id DESC");
        if ($res_approved) {
            while ($row = $res_approved->fetch_assoc()) {
                $row['folder_path'] = $folder;
                $approved_testimonials[] = $row;
            }
        }
        $conn->close();
    }
}
?>
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
            <a href="admin.php" target="_blank">Admin Panel</a>
            <a href="#contact">Contact</a>

            <button id="theme-toggle" class="theme-toggle-btn" type="button">
                <span id="theme-icon">🌙</span> <span id="theme-text">Dark</span>
            </button>
        </nav>
    </header>

    <!-- Home Section -->
    <section class="home" id="home">
        <div class="home-img">
            <?php if (!empty($about['profile_photo']) && file_exists('uploads/' . $about['profile_photo'])): ?>
                <img alt="Anaghe Mac Profile" src="uploads/<?= htmlspecialchars($about['profile_photo']) ?>" />
            <?php else: ?>
                <img alt="Anaghe Mac Profile" src="bright.jpg" />
            <?php endif; ?>
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
            <h3>I'm a <span><?= htmlspecialchars($about['subtitle']) ?></span></h3>
            <p><?= htmlspecialchars($about['short_description']) ?></p>
            <a class="btn" href="readmi.php">Read More Details</a>
        </div>
        <div class="about-img">
            <?php if (!empty($about['profile_photo']) && file_exists('uploads/' . $about['profile_photo'])): ?>
                <img alt="About Anaghe Mac" src="uploads/<?= htmlspecialchars($about['profile_photo']) ?>" />
            <?php else: ?>
                <img alt="About Anaghe Mac" src="mac.jpg" />
            <?php endif; ?>
        </div>
    </section>

    <!-- Dynamic Services Section -->
    <section class="services" id="services">
        <h2 class="heading">My <span>Services</span></h2>
        <div class="services-container">
            <?php if (!empty($dynamic_services)): ?>
                <?php foreach ($dynamic_services as $srv): ?>
                    <div class="services-box">
                        <i class="<?= htmlspecialchars($srv['icon_class'] ?? 'bx bx-code-alt') ?>"></i>
                        <h3><?= htmlspecialchars($srv['title']) ?></h3>
                        <p><?= htmlspecialchars($srv['description']) ?></p>
                        <a class="btn" href="#contact">Inquire Now</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="services-box">
                    <i class="bx bx-code-alt"></i>
                    <h3>Web Development</h3>
                    <p>Building responsive layouts and robust databases.</p>
                    <a class="btn" href="#contact">Inquire Now</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Interactive Projects Showcase Section -->
    <section class="projects" id="projects">
        <h2 class="heading">Featured <span>Projects</span></h2>

        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="web">Web Apps</button>
            <button class="filter-btn" data-filter="uml">UML Modeling</button>
            <button class="filter-btn" data-filter="c">C Programs</button>
        </div>

        <div class="projects-container">
            <div class="project-card" data-category="web">
                <h3>Student Management System</h3>
                <p>Full-stack administrative management app.</p>
                <a href="../CRUD/index.php" class="btn" target="_blank">View Project</a>
            </div>
            <div class="project-card" data-category="web">
                <h3>School Management System</h3>
                <p>Comprehensive system engineered with PHP & MySQL.</p>
                <a href="../school-system/index.php" class="btn" target="_blank">View Project</a>
            </div>
            <div class="project-card" data-category="web">
                <h3>Agricultural Market System</h3>
                <p>Digital marketplace designed to connect farmers.</p>
                <a href="../agri-market/index.php" class="btn" target="_blank">View Project</a>
            </div>
            <div class="project-card" data-category="web">
                <h3>Library Management System</h3>
                <p>Comprehensive system engineered with PHP & MySQL.</p>
                <a href="../library-system/index.php" class="btn" target="_blank">View Project</a>
            </div>
            <div class="project-card" data-category="uml">
                <h3>Library Management Architecture</h3>
                <p>Use Case, Class, and Sequence diagrams modeled in StarUML.</p>
                <a href="../uml-library/index.php" class="btn" target="_blank">View Project</a>
            </div>
            <div class="project-card" data-category="c">
                <h3>Matrix Operations Engine</h3>
                <p>C console programs for array operations and control structures.</p>
                <a href="../c-matrix/index.php" class="btn" target="_blank">View Project</a>
            </div>
            <div class="project-card" data-category="c">
                <h3>Data Utility & String Library</h3>
                <p>Custom procedural C programming routines for string manipulation and data handling.</p>
                <a href="../c-string-library/index.php" class="btn" target="_blank">View Project</a>
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

    <!-- AJAX Live Contact Form & WhatsApp Section -->
    <section id="contact" class="contact-section">
        <h2 class="heading">Get In <span>Touch</span></h2>

        <!-- WhatsApp QR Code Integration Container -->
        <div class="whatsapp-contact-container" style="text-align: center; margin-bottom: 2.5rem; padding: 1.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1); max-width: 600px; margin-left: auto; margin-right: auto;">
            <h3 style="margin-bottom: 0.8rem; font-size: 1.3rem;">Chat with Me on WhatsApp</h3>
            <img src="qrcode.png" alt="WhatsApp QR Code" style="width: 160px; height: 160px; border-radius: 6px; margin-bottom: 1rem; border: 3px solid #0ef;" />
            <p style="margin-bottom: 1rem; font-size: 0.95rem; color: #aaa;">Scan the code with your phone camera or click below to start a project chat:</p>
            <a href="https://wa.me/237653412432?text=Hello%20MC,%20I%20want%20to%20discuss%20a%20project%20com%20with%20you!" target="_blank" class="btn" style="display: inline-block; text-decoration: none;">Chat on WhatsApp</a>
        </div>

        <div id="form-alert" style="display: none; text-align: center; padding: 0.8rem; border-radius: 6px; max-width: 600px; margin: 0 auto 1.5rem auto; font-weight: bold;"></div>

        <form id="contact-form" class="contact-form">
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

            <button type="submit" id="form-submit-btn" class="btn">Send Message</button>
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

    <!-- External and Main JavaScript File References -->
    <script src="https://unpkg.com/typed.js@2.1.0/dist/typed.umd.js"></script>
    <script src="script.js"></script>
</body>

</html>