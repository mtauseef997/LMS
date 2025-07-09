<?php
session_start();


if (isset($_SESSION['user_role'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
        case 'teacher':
            header('Location: teacher/dashboard.php');
            exit;
        case 'student':
            header('Location: student/dashboard.php');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduLearn - Learning Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <header class="header">
        <div class="nav-container">
            <div class="logo">EduLearn</div>
            <nav class="nav-links">
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </nav>
            <div class="cta-buttons">
                <a href="login.php" class="btn btn-primary">Login</a>
            </div>
        </div>
    </header>


    <section class="hero">
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>
        <div class="hero-container">
            <div class="hero-content">
                <h1>Transform Your Learning Experience</h1>
                <p>Join thousands of students and educators in our comprehensive Learning Management System. Access
                    courses, track progress, and achieve your educational goals with cutting-edge technology.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-white btn-large">Access Learning Portal</a>
                    <a href="#features" class="btn btn-outline btn-large">Explore Features</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-card">
                    <h3 style="color: white; margin-bottom: 1rem;">Quick Access</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div
                            style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 10px; color: white;">
                            <i class="fas fa-user-graduate" style="margin-right: 0.5rem;"></i>
                            Student Portal
                        </div>
                        <div
                            style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 10px; color: white;">
                            <i class="fas fa-chalkboard-teacher" style="margin-right: 0.5rem;"></i>
                            Teacher Dashboard
                        </div>
                        <div
                            style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 10px; color: white;">
                            <i class="fas fa-cog" style="margin-right: 0.5rem;"></i>
                            Admin Panel
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="features" id="features">
        <div class="features-container">
            <div class="section-header">
                <h2>Why Choose EduLearn?</h2>
                <p>Our platform offers comprehensive tools and features designed to enhance the learning experience for
                    students, teachers, and administrators.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3>Interactive Courses</h3>
                    <p>Engage with multimedia content, interactive quizzes, and hands-on assignments designed to enhance
                        your learning experience.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Progress Tracking</h3>
                    <p>Monitor your learning journey with detailed analytics, grade tracking, and personalized progress
                        reports.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Collaborative Learning</h3>
                    <p>Connect with peers, participate in group discussions, and collaborate on projects in a supportive
                        environment.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access your courses anytime, anywhere with our responsive design that works perfectly on all
                        devices.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Certifications</h3>
                    <p>Earn recognized certificates upon course completion to showcase your achievements and advance
                        your career.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Get help whenever you need it with our dedicated support team available around the clock.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="stats-container">
            <div class="stat-item">
                <h3>10,000+</h3>
                <p>Active Students</p>
            </div>
            <div class="stat-item">
                <h3>500+</h3>
                <p>Expert Teachers</p>
            </div>
            <div class="stat-item">
                <h3>1,000+</h3>
                <p>Courses Available</p>
            </div>
            <div class="stat-item">
                <h3>95%</h3>
                <p>Success Rate</p>
            </div>
        </div>
    </section>

    <section style="padding: 5rem 0; background: white; text-align: center;">
        <div style="max-width: 800px; margin: 0 auto; padding: 0 2rem;">
            <h2
                style="font-size: 2.5rem; margin-bottom: 1rem; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                Ready to Access Your Learning Portal?</h2>
            <p style="font-size: 1.2rem; color: #666; margin-bottom: 2rem;">Access our comprehensive learning management system.
                Contact your administrator for account access and unlock world-class educational content.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="login.php" class="btn btn-primary btn-large">Access Portal</a>
                <a href="login.php" class="btn btn-secondary btn-large">Sign In</a>
            </div>
        </div>
    </section>


    <footer style="background: #333; color: white; padding: 3rem 0; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
            <div class="logo" style="color: white; margin-bottom: 1rem;">EduLearn</div>
            <p style="margin-bottom: 2rem; opacity: 0.8;">Empowering education through technology</p>
            <div style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 2rem; flex-wrap: wrap;">
                <a href="#" style="color: white; text-decoration: none;">Privacy Policy</a>
                <a href="#" style="color: white; text-decoration: none;">Terms of Service</a>
                <a href="#" style="color: white; text-decoration: none;">Support</a>
                <a href="#" style="color: white; text-decoration: none;">Contact</a>
            </div>
            <p style="opacity: 0.6;">&copy; 2024 EduLearn. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });


        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = 'none';
            }
        });

        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statItems = entry.target.querySelectorAll('.stat-item h3');
                    statItems.forEach((item, index) => {
                        const finalValue = item.textContent;
                        const numericValue = parseInt(finalValue.replace(/[^0-9]/g, ''));
                        const suffix = finalValue.replace(/[0-9]/g, '');

                        let currentValue = 0;
                        const increment = numericValue / 50;

                        const timer = setInterval(() => {
                            currentValue += increment;
                            if (currentValue >= numericValue) {
                                item.textContent = finalValue;
                                clearInterval(timer);
                            } else {
                                item.textContent = Math.floor(currentValue) + suffix;
                            }
                        }, 30);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            observer.observe(statsSection);
        }
    </script>
</body>

</html>