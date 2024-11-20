<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Limkokwing ARS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --text-light: #ecf0f1;
            --text-dark: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
        }

        .hero {
            background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(44, 62, 80, 0.9)),
                        url('assets/images/campus.jpg') center/cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--text-light);
            padding: 1rem;
        }

        .hero-content {
            max-width: 800px;
            padding: 2rem;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.3s, background-color 0.3s;
            min-width: 150px;
        }

        .btn:hover {
            transform: translateY(-3px);
        }

        .btn-primary {
            background: var(--secondary-color);
            color: var(--text-light);
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: var(--accent-color);
            color: var(--text-light);
        }

        .btn-secondary:hover {
            background: #c0392b;
        }

        .features {
            padding: 4rem 2rem;
            background: #f5f6fa;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            padding: 0 1rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .feature-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .footer {
            background: var(--primary-color);
            color: var(--text-light);
            text-align: center;
            padding: 2rem;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .cta-buttons {
                flex-direction: column;
                padding: 0 1rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .feature-card {
                margin: 0 1rem;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 1.5rem;
            }

            .hero-content {
                padding: 1rem;
            }

            .features {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-content">
            <h1>Limkokwing Academic Reporting System</h1>
            <p>Streamline your academic reporting process with our comprehensive management system</p>
            <div class="cta-buttons">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-secondary">Register as Admin</a>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <h3>Academic Management</h3>
                <p>Efficiently manage academic years, semesters, and course modules</p>
            </div>
            <div class="feature-card">
                <h3>Student Tracking</h3>
                <p>Monitor student attendance and academic progress</p>
            </div>
            <div class="feature-card">
                <h3>Report Generation</h3>
                <p>Generate comprehensive academic reports and analytics</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Limkokwing University. All rights reserved.</p>
    </footer>
</body>
</html>