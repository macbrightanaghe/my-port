<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Applications | Anaghe Mac Bright</title>
       
</head>

<body>
    <header>
        <a class="logo" href="index.php#home">MC <span>BRIGHT</span></a>
    </header>

    <main class="wrapper">
        <a href="index.php#projects" class="btn">← Back to Main Portfolio</a>
        <h1 class="section-title">Web <span>Applications</span></h1>
        <p class="subtitle">Full-stack dynamic web applications designed and developed using modern standards.</p>

        <div class="grid">
            <!-- Student Management System CRUD Card -->
            <div class="card">
                <h3>Student Management System</h3>
                <p>Full-stack administrative management application featuring dynamic database interaction, SQL injection prevention via PDO prepared statements, and live record management (Create, Read, Update, Delete).</p>
                <div class="tags"><span>PHP</span><span>MySQL</span><span>PDO</span><span>HTML5/CSS3</span></div>
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <a href="CRUD/index.php" target="_blank" class="btn" style="margin-bottom: 0; padding: 0.6rem 1.6rem; font-size: 1.2rem;">Live Demo</a>
                </div>
            </div>

            <div class="card">
                <h3>School Management System</h3>
                <p>Comprehensive system engineered with PHP & MySQL featuring administrative portals, automated tuition tracking, student enrollment, and teacher grade entries.</p>
                <div class="tags"><span>PHP</span><span>MySQL</span><span>CSS3</span><span>XAMPP</span></div>
            </div>

            <div class="card">
                <h3>Agricultural Market System</h3>
                <p>Digital marketplace designed to connect regional agricultural vendors directly with buyers. Features product catalogs, category filtering, and inventory updates.</p>
                <div class="tags"><span>JavaScript</span><span>PHP</span><span>MySQL</span><span>HTML5</span></div>
           
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <a href="CRUD/index.php" target="_blank" class="btn" style="margin-bottom: 0; padding: 0.6rem 1.6rem; font-size: 1.2rem;">Live Demo</a>
            </div>
        </div>
    </main>
</body>

</html>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            text-decoration: none;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        :root {
            --dark-bg: #0d1117;
            --dark-card: #161b22;
            --dark-border: #30363d;
            --accent-glow: #ffb703;
            --text-primary: #f0f6fc;
            --text-secondary: #8b949e;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            padding-bottom: 5rem;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 5%;
            background: rgba(22, 27, 34, 0.95);
            border-bottom: 1px solid var(--dark-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .logo {
            font-size: 2.2rem;
            color: #ffffff;
            font-weight: 700;
        }

        .logo span {
            color: var(--accent-glow);
        }

        .wrapper {
            max-width: 1100px;
            margin: 10rem auto 4rem;
            padding: 0 2rem;
        }

        .section-title {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .section-title span {
            color: var(--accent-glow);
        }

        .subtitle {
            font-size: 1.6rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 2rem;
            background: var(--accent-glow);
            color: #1c1c1c;
            border-radius: 3rem;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 3rem;
            transition: 0.3s ease;
        }

        .btn:hover {
            opacity: 0.9;
            box-shadow: 0 0 10px rgba(255, 183, 3, 0.4);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.5rem;
        }

        .card {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            padding: 2.5rem;
            border-radius: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card h3 {
            font-size: 2.2rem;
            color: var(--accent-glow);
            margin-bottom: 1rem;
        }

        .card p {
            font-size: 1.4rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .tags span {
            display: inline-block;
            background: rgba(255, 183, 3, 0.15);
            color: var(--accent-glow);
            padding: 0.4rem 1rem;
            font-size: 1.2rem;
            border-radius: 1rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
    </style>