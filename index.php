<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    $dest = $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php';
    header("Location: $dest"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlashCru &ndash; Flash Crew Response System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700;800&family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
        <a href="login.php">Sign In</a>
        <a href="register.php" class="fc-btn-nav-cta">Get Started</a>
    </div>
</nav>

<!-- =================== HERO =================== -->
<section class="fc-hero">
    <div class="fc-hero-content">
        <div class="fc-hero-badge">
            <i class="bi bi-shield-check-fill"></i>
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
        <!-- <div class="fc-hero-actions">
            <a href="register.php" class="fc-btn fc-btn-primary">
                <i class="bi bi-person-plus-fill"></i> Register Now
            </a>
            <a href="login.php" class="fc-btn fc-btn-outline">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </a>
        </div> -->
    </div>

    <!-- Phone mockups -->
    <div class="fc-hero-phones">
        <div class="fc-phone fc-phone-back">
            <div class="fc-phone-screen" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:310px;">
                <div style="width:54px;height:54px;background:var(--fc-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-lightning-charge-fill" style="color:#fff;font-size:22px;"></i>
                </div>
                <div style="color:#fff;font-family:Lexend,sans-serif;font-weight:700;font-size:14px;">FlashCru</div>
                <div style="color:rgba(255,255,255,.4);font-size:10px;">Flash Crew Response</div>
            </div>
        </div>
        <div class="fc-phone fc-phone-front">
            <div class="fc-phone-screen">
                <div class="fc-phone-header">
                    <span class="fc-phone-title"><i class="bi bi-lightning-charge-fill" style="color:var(--fc-primary)"></i> PANIC</span>
                    <i class="bi bi-three-dots" style="color:rgba(255,255,255,.4);font-size:12px;"></i>
                </div>
                <div style="color:rgba(255,255,255,.4);font-size:9px;margin-bottom:8px;">📍 Current Location</div>
                <div class="fc-phone-map">
                    <div class="fc-map-grid"></div>
                    <div class="fc-map-dot"></div>
                    <div style="position:absolute;top:12px;left:12px;width:7px;height:7px;background:#21BF73;border-radius:50%;opacity:.7;"></div>
                    <div style="position:absolute;bottom:16px;right:22px;width:6px;height:6px;background:#5b7cf7;border-radius:50%;opacity:.6;"></div>
                    <div style="position:absolute;top:38%;right:28%;width:6px;height:6px;background:#f9a825;border-radius:50%;opacity:.7;"></div>
                </div>
                <div class="fc-phone-btns">
                    <div class="fc-pBtn fc-pBtn-fire"><i class="bi bi-fire"></i></div>
                    <div class="fc-pBtn fc-pBtn-medical"><i class="bi bi-heart-pulse-fill"></i></div>
                    <div class="fc-pBtn fc-pBtn-security"><i class="bi bi-shield-fill-exclamation"></i></div>
                </div>
                <div class="fc-phone-powered">Powered by FlashCru ⚡</div>
            </div>
        </div>
    </div>
</section>

    <!-- =================== STATS BAR ===================
    <section class="fc-stats-bar">
        <div class="fc-stat-item"><div class="fc-stat-num">3+</div><div class="fc-stat-lbl">Cities Covered</div></div>
        <div class="fc-stat-div"></div>
        <div class="fc-stat-item"><div class="fc-stat-num">47</div><div class="fc-stat-lbl">Barangays Served</div></div>
        <div class="fc-stat-div"></div>
        <div class="fc-stat-item"><div class="fc-stat-num">200+</div><div class="fc-stat-lbl">Security Teams</div></div>
        <div class="fc-stat-div"></div>
        <div class="fc-stat-item"><div class="fc-stat-num">200+</div><div class="fc-stat-lbl">Medical Companies</div></div>
        <div class="fc-stat-div"></div>
        <div class="fc-stat-item"><div class="fc-stat-num">98%</div><div class="fc-stat-lbl">Customer Satisfaction</div></div>
        <div class="fc-stat-div"></div>
        <div class="fc-stat-item"><div class="fc-stat-num">15 Min</div><div class="fc-stat-lbl">Avg Response Time</div></div>
    </section> -->

<!-- =================== FEATURES =================== -->
<section class="fc-features" id="features">
    <div class="container-fluid px-0">
        <div class="text-center mb-5">
            <div class="fc-section-tag">Why Choose FlashCru</div>
            <h2 class="fc-section-title">Everything You Need for<br>Emergency Response</h2>
            <p class="fc-section-sub mx-auto">From instant alerts to rapid team deployment and real-time location updates FLASH CRU keeps your response fast, coordinated, and always in control.</p>
        </div>
        <div class="row g-4">
            <!-- Features 1 -->
            <div class="col-md-4">
                <div class="fc-feat-card">
                    <div class="fc-feat-icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h5>Incident Reporting System</h5>
                    <p>Users can quickly submit emergency reports with complete location and incident details.</p>
                </div>
            </div>
            <!-- Features 2 -->
            <div class="col-md-4">
                <div class="fc-feat-card">
                    <div class="fc-feat-icon grn"><i class="bi bi-people-fill"></i></div>
                    <h5>Smart Location Assignment</h5>
                    <p>Incidents are assigned to the nearest available emergency team based on location.</p>
                </div>
            </div>
            <!-- Features 3 -->
            <div class="col-md-4">
                <div class="fc-feat-card">
                    <div class="fc-feat-icon blu"><i class="bi bi-graph-up-arrow"></i></div>
                    <h5>Team Management</h5>
                    <p>Admins manage emergency teams, members, and their availability status.</p>
                </div>
            </div>
            <!-- Features 4 -->
            <div class="col-md-4">
                <div class="fc-feat-card">
                    <div class="fc-feat-icon red"><i class="bi bi-shield-lock-fill"></i></div>
                    <h5>Incident Dashboard</h5>
                    <p>Admins can monitor, update, and track all reported incidents in real time.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fc-feat-card">
                    <div class="fc-feat-icon grn"><i class="bi bi-journal-check"></i></div>
                    <h5>Role-Based Access</h5>
                    <p>Separate access for Users and Admins ensures secure system control.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fc-feat-card">
                    <div class="fc-feat-icon blu"><i class="bi bi-geo-alt-fill"></i></div>
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
        <!-- Works 1     -->
        <div class="col-md-3"><div class="fc-step">
            <div class="fc-step-num">1</div>
            <h5>Report an Incident</h5>
            <p>Submit incidents with details and location.</p>
        </div></div>
        <!-- Works 2 -->
        <div class="col-md-3"><div class="fc-step">
            <div class="fc-step-num">2</div>
            <h5>Manual Location Updates</h5>
            <p>Reporter locations are manually recorded.</p>
        </div></div>
        <!-- Works 3 -->
        <div class="col-md-3"><div class="fc-step">
            <div class="fc-step-num">3</div>
            <h5>Team Dispatch</h5>
            <p>Notify and send teams quickly.</p>
        </div></div>
        <div class="col-md-3"><div class="fc-step">
            <div class="fc-step-num">4</div>
            <h5>Status & Feedback</h5>
            <p>Update incident status for real-time visibility.</p>
        </div></div>
    </div>
</section>

<!-- =================== CTA =================== -->
<section class="fc-cta">
    <div class="fc-cta-title">Ready to Protect Your Community?</div>
    <p class="fc-cta-sub">Join FlashCru today — fast, organized, and transparent emergency response for everyone.</p>
    <a href="register.php" class="fc-btn fc-btn-primary" style="font-size:15px;padding:14px 38px;margin:0 auto;">
        <i class="bi bi-person-plus-fill"></i> Create Free Account
    </a>
</section>

<!-- =================== FOOTER =================== -->
<footer class="fc-footer">
    <div>
        <div class="fc-footer-brand"><i class="bi bi-lightning-charge-fill" style="color:var(--fc-primary)"></i> FlashCru</div>
        <div style="margin-top:4px;">Emergency Response System &copy; <?= date('Y') ?></div>
    </div>
    <div class="fc-footer-links">
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
        <a href="admin_login.php">Admin</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>