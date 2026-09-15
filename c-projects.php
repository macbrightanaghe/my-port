<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C Programming | Anaghe Mac Bright</title>
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
            font-weight: 600;
        }
    </style>
</head>

<body>
    <header>
        <a class="logo" href="index.php#home">MC <span>BRIGHT</span></a>
    </header>

    <main class="wrapper">
        <a href="index.php#projects" class="btn">← Back to Main Portfolio</a>
        <h1 class="section-title">C Programming & <span>Algorithms</span></h1>
        <p class="subtitle">Low-level procedural programming, matrix arithmetic, and memory allocation logic.</p>

        <div class="grid">
            <div class="card">
                <h3>Matrix Operations Engine</h3>
                <p>Low-level C utility program for computing matrix addition, multiplication, transposition, and
                    determinant evaluations using dynamic 2D array allocation.</p>
                <div class="tags"><span>C Language</span><span>Algorithms</span><span>Memory Allocation</span></div>
                <a href="c-matrix-engine/index.php" class="btn" target="_blank" style="margin-top: 1rem; display: inline-block;">View Project</a>
            </div>

            <div class="card">
                <h3>Data Utility & String Library</h3>
                <p>Custom procedural C programming routines for string manipulation, custom search algorithms, and
                    structured data handling.</p>
                <div class="tags"><span>Pointers</span><span>Structures</span><span>Procedural Logic</span></div>
                <a href="c-string-library/index.php" class="btn" target="_blank" style="margin-top: 1rem; display: inline-block;">View Project</a>
            </div>
        </div>
    </main>
</body>

</html>