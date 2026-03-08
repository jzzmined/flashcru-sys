<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (isset($_SESSION['user_id'])) {
    $dest = $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php';
    header("Location: $dest");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlashCru &ndash; Flash Crew Response System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700;800&family=Roboto:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* ── Global: all icons solid black ── */
        .fc-feat-icon i,
        .fc-footer-new i,
        .fc-footer-nav-links a i,
        .fc-contact-icon i {
            color: #111 !important;
        }

        /* Feature icon containers — light grey bg, black icon */
        .fc-feat-icon {
            background: #f0f0f0 !important;
            color: #111 !important;
        }

        /* Nav logo: black bg */
        .fc-nav-logo {
            background: #111 !important;
        }

        .fc-nav-logo i {
            color: #fff !important;
        }

        /* Step circles — black */
        .fc-step-num {
            background: #111 !important;
        }

        /* Map dot — black */
        .fc-map-dot {
            background: #111 !important;
        }

        @keyframes fcPulse {

            0%,
            100% {
                box-shadow: 0 0 0 5px rgba(0, 0, 0, .15);
            }

            50% {
                box-shadow: 0 0 0 14px rgba(0, 0, 0, .0);
            }
        }

        /* Section tag color */
        .fc-section-tag {
            color: #111 !important;
        }

        /* Features section white bg */
        .fc-features {
            background: #fff !important;
        }

        /* How It Works: light grey */
        .fc-how {
            background: #f7f9fc !important;
        }

        /* ────────────────────────────
           NEW FOOTER STYLES
        ──────────────────────────── */
        .fc-footer-new {
            background: #fff;
            border-top: 2px solid #111;
            padding: 64px 48px 40px;
            color: #111;
        }

        .fc-footer-new .fc-footer-brand {
            font-family: 'Lexend', sans-serif;
            font-weight: 800;
            font-size: 22px;
            color: #111;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .fc-footer-new .fc-footer-brand-icon {
            width: 40px;
            height: 40px;
            background: #111;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fc-footer-new .fc-footer-brand-icon i {
            color: #fff !important;
            font-size: 18px;
        }

        .fc-footer-tagline {
            color: #555;
            font-size: 13.5px;
            max-width: 280px;
            line-height: 1.75;
            margin-bottom: 0;
        }

        .fc-footer-new h6 {
            font-family: 'Lexend', sans-serif;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #111;
            margin-bottom: 20px;
        }

        .fc-footer-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 13.5px;
            color: #444;
        }

        .fc-contact-icon {
            width: 34px;
            height: 34px;
            background: #f2f2f2;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .fc-contact-icon i {
            color: #111 !important;
            font-size: 15px;
        }

        .fc-contact-text strong {
            display: block;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #111;
            margin-bottom: 2px;
        }

        .fc-footer-nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .fc-footer-nav-links li {
            margin-bottom: 10px;
        }

        .fc-footer-nav-links a {
            color: #444;
            font-size: 13.5px;
            text-decoration: none;
            transition: color .2s;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .fc-footer-nav-links a:hover {
            color: #111;
        }

        .fc-footer-nav-links a i {
            color: #111 !important;
            font-size: 12px;
        }

        .fc-social-links {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .fc-social-btn {
            width: 38px;
            height: 38px;
            background: #f2f2f2;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background .2s;
        }

        .fc-social-btn:hover {
            background: #111;
        }

        .fc-social-btn i {
            color: #111 !important;
            font-size: 16px;
            transition: color .2s;
        }

        .fc-social-btn:hover i {
            color: #fff !important;
        }

        .fc-footer-bottom {
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid #e4e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12.5px;
            color: #888;
        }

        .fc-footer-bottom a {
            color: #555;
            text-decoration: none;
            transition: color .2s;
        }

        .fc-footer-bottom a:hover {
            color: #111;
        }

        @media (max-width: 768px) {
            .fc-footer-new {
                padding: 48px 24px 32px;
            }

            .fc-footer-bottom {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>

<body>

    <!-- =================== NAVBAR =================== -->
    <nav class="fc-public-nav">
        <a href="index.php" class="fc-nav-brand" style="text-decoration:none;">
            <div class="fc-nav-logo"><i class="bi bi-lightning-charge-fill"></i></div>
            <div>
                <div class="fc-nav-title">FlashCru</div>
                <div class="fc-nav-sub">Emergency Response System</div>
            </div>
        </a>
        <div class="fc-nav-links">
            <a href="#features">Features</a>
            <a href="#how">How It Works</a>
            <a href="#contact">Contact</a>
            <a href="login.php" class="nav-link-auth">Sign In</a>
            <a href="admin_login.php" class="nav-link-admin">
                <i class="bi bi-lock-fill" style="font-size: 12px; margin-right: 5px; opacity: 0.7;"></i>
                Admin</a>
            <a href="register.php" class="fc-btn-nav-cta">Get Started</a>
        </div>
    </nav>

    <!-- =================== HERO =================== -->
    <section class="fc-hero">
        <!-- Left: Text Content -->
        <div class="fc-hero-content">
            <div class="fc-hero-badge">
                <span class="fc-hero-badge-dot"></span>
                Emergency Response Platform
            </div>
            <h1 class="fc-hero-title">
                We are Your <span>#1</span><br>
                Emergency<br>
                Response System
            </h1>
            <p class="fc-hero-sub">
                FlashCru powers Davao City with faster, smarter emergency response.
            </p>
            <div class="fc-hero-actions">
                <a href="register.php" class="fc-hero-btn-primary">Get Started</a>
                <a href="#features" class="fc-hero-btn-outline">Learn More</a>
            </div>
            <!-- Social proof -->
            <div class="fc-hero-social-proof">
                <div class="fc-hero-avatars">
                    <div class="fc-avatar" style="background:#e53e3e;">J</div>
                    <div class="fc-avatar" style="background:#805ad5;">N</div>
                    <div class="fc-avatar" style="background:#2b6cb0;">M</div>
                </div>
                <span>Joined by 2,500+ professionals</span>
            </div>
        </div>

        <!-- Right: Floating UI Card -->
        <div class="fc-hero-card-wrap">
            <div class="fc-hero-card">
                <!-- Top icon row -->
                <div class="fc-hero-card-icons">
                    <div class="fc-hci fc-hci-primary">
                        <i class="bi bi-lightning-charge-fill"></i>
                        <span>Fast</span>
                    </div>
                    <div class="fc-hci">
                        <i class="bi bi-shield-check"></i>
                        <span>Secure</span>
                    </div>
                    <div class="fc-hci">
                        <i class="bi bi-globe"></i>
                        <span>Global</span>
                    </div>
                </div>

                <!-- Divider -->
                <div class="fc-hero-card-divider"></div>

                <!-- Stats row -->
                <div class="fc-hero-card-stats">
                    <div class="fc-hcs-item">
                        <div class="fc-hcs-label">Latency</div>
                        <div class="fc-hcs-value">0.02ms</div>
                    </div>
                    <div class="fc-hcs-item">
                        <div class="fc-hcs-label">Uptime</div>
                        <div class="fc-hcs-value fc-hcs-green">99.9%</div>
                    </div>
                </div>

                <!-- Live pulse ring -->
                <div class="fc-hero-card-pulse">
                    <div class="fc-pulse-ring"></div>
                    <div class="fc-pulse-core"><i class="bi bi-broadcast"></i></div>
                </div>

                <!-- Incident type chips -->
                <div class="fc-hero-card-chips">
                    <span class="fc-chip fc-chip-fire"><i class="bi bi-fire"></i> Fire</span>
                    <span class="fc-chip fc-chip-med"><i class="bi bi-heart-pulse"></i> Medical</span>
                    <span class="fc-chip fc-chip-sec"><i class="bi bi-shield-exclamation"></i> Security</span>
                </div>
            </div>
        </div>
    </section>

    <!-- =================== FEATURES =================== -->
    <section class="fc-features" id="features">
        <div class="container-fluid px-0">
            <div class="text-center mb-5">
                <div class="fc-section-tag">Why Choose FlashCru</div>
                <h2 class="fc-section-title">Everything You Need for<br>Emergency Response</h2>
                <p class="fc-section-sub mx-auto">From instant alerts to rapid team deployment and real-time location
                    updates, FlashCru keeps your response fast, coordinated, and always in control.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="fc-feat-card">
                        <div class="fc-feat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <h5>Incident Reporting System</h5>
                        <p>Users can quickly submit emergency reports with complete location and incident details.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fc-feat-card">
                        <div class="fc-feat-icon"><i class="bi bi-people-fill"></i></div>
                        <h5>Smart Location Assignment</h5>
                        <p>Incidents are assigned to the nearest available emergency team based on location.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fc-feat-card">
                        <div class="fc-feat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <h5>Team Management</h5>
                        <p>Admins manage emergency teams, members, and their availability status.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fc-feat-card">
                        <div class="fc-feat-icon"><i class="bi bi-shield-lock-fill"></i></div>
                        <h5>Incident Dashboard</h5>
                        <p>Admins can monitor, update, and track all reported incidents in real time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fc-feat-card">
                        <div class="fc-feat-icon"><i class="bi bi-journal-check"></i></div>
                        <h5>Role-Based Access</h5>
                        <p>Separate access for Users and Admins ensures secure system control.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fc-feat-card">
                        <div class="fc-feat-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <h5>Activity Log</h5>
                        <p>All system actions are recorded for monitoring and accountability.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =================== HOW IT WORKS =================== -->
    <section class="fc-how" id="how">
        <div class="text-center mb-5">
            <div class="fc-section-tag">Simple &amp; Effective</div>
            <h2 class="fc-section-title">How FlashCru Works</h2>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-3">
                <div class="fc-step">
                    <div class="fc-step-num">1</div>
                    <h5>Report an Incident</h5>
                    <p>Submit incidents with details and location.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="fc-step">
                    <div class="fc-step-num">2</div>
                    <h5>Manual Location Updates</h5>
                    <p>Reporter locations are manually recorded.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="fc-step">
                    <div class="fc-step-num">3</div>
                    <h5>Team Dispatch</h5>
                    <p>Notify and send teams quickly.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="fc-step">
                    <div class="fc-step-num">4</div>
                    <h5>Status &amp; Feedback</h5>
                    <p>Update incident status for real-time visibility.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- =================== FOOTER =================== -->
    <footer class="fc-footer-new" id="contact">
        <div class="container-fluid px-0">
            <div class="row g-5">

                <!-- Brand & Social -->
                <div class="col-lg-4 col-md-6">
                    <div class="fc-footer-brand">
                        <div class="fc-footer-brand-icon">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        FlashCru
                    </div>
                    <p class="fc-footer-tagline">
                        Empowering Davao City communities with fast, coordinated, and transparent emergency response —
                        available 24/7.
                    </p>
                    <div class="fc-social-links">
                        <a href="#" class="fc-social-btn" title="Facebook" aria-label="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="fc-social-btn" title="Twitter / X" aria-label="Twitter">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <a href="#" class="fc-social-btn" title="Instagram" aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" class="fc-social-btn" title="YouTube" aria-label="YouTube">
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="col-lg-4 col-md-6">
                    <h6>Contact Us</h6>

                    <div class="fc-footer-contact-item">
                        <div class="fc-contact-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div class="fc-contact-text">
                            <strong>Phone</strong>
                            +63 (082) 123-4567
                        </div>
                    </div>

                    <div class="fc-footer-contact-item">
                        <div class="fc-contact-icon"><i class="bi bi-phone-fill"></i></div>
                        <div class="fc-contact-text">
                            <strong>Mobile / Hotline</strong>
                            0906 130 3795
                        </div>
                    </div>

                    <div class="fc-footer-contact-item">
                        <div class="fc-contact-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div class="fc-contact-text">
                            <strong>Email</strong>
                            support@flashcru.gov.ph
                        </div>
                    </div>

                    <div class="fc-footer-contact-item">
                        <div class="fc-contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div class="fc-contact-text">
                            <strong>Office Address</strong>
                            City Hall Compound, San Pedro St.,<br>Davao City, 8000 Davao del Sur
                        </div>
                    </div>

                    <div class="fc-footer-contact-item">
                        <div class="fc-contact-icon"><i class="bi bi-clock-fill"></i></div>
                        <div class="fc-contact-text">
                            <strong>Operating Hours</strong>
                            24 / 7 — Always Available
                        </div>
                    </div>
                </div>

                <!-- Quick Links + Emergency Lines -->
                <div class="col-lg-2 col-md-6">
                    <h6>Quick Links</h6>
                    <ul class="fc-footer-nav-links">
                        <li><a href="#features"><i class="bi bi-chevron-right"></i> Features</a></li>
                        <li><a href="#how"><i class="bi bi-chevron-right"></i> How It Works</a></li>
                        <li><a href="login.php"><i class="bi bi-chevron-right"></i> Sign In</a></li>
                        <li><a href="register.php"><i class="bi bi-chevron-right"></i> Register</a></li>
                        <li><a href="admin_login.php"><i class="bi bi-chevron-right"></i> Admin Portal</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6>Emergency Lines</h6>
                    <ul class="fc-footer-nav-links">
                        <li><a href="tel:911"><i class="bi bi-chevron-right"></i> 911 — National</a></li>
                        <li><a href="tel:117"><i class="bi bi-chevron-right"></i> 117 — NDRRMC</a></li>
                        <li><a href="tel:143"><i class="bi bi-chevron-right"></i> 143 — Red Cross</a></li>
                        <li><a href="tel:166"><i class="bi bi-chevron-right"></i> 166 — PNP</a></li>
                        <li><a href="tel:160"><i class="bi bi-chevron-right"></i> 160 — BFP</a></li>
                    </ul>
                </div>

            </div>

            <!-- Bottom bar -->
            <div class="fc-footer-bottom">
                <div>&copy; <?= date('Y') ?> FlashCru &mdash; Flash Crew Response System. All rights reserved.</div>
                <div style="display:flex;gap:20px;flex-wrap:wrap;">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Use</a>
                    <a href="#">Accessibility</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>