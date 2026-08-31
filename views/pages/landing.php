<?php
// views/pages/landing.php
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/models/JobPosting.php';

use App\Models\JobPosting;

$title = 'ShelfSense | Smart Retail Operations & HR Platform';

// Real, approved, currently-hiring job postings only -- never fabricated.
$publicJobs = [];
try {
    $publicJobs = (new JobPosting())->getPublicListings();
} catch (Exception $e) {
    error_log('landing.php: failed to load public job postings: ' . $e->getMessage());
}

$jobCardsHtml = '';
if (empty($publicJobs)) {
    $jobCardsHtml = '
        <div class="col-lg-10">
            <div class="modern-card p-4 text-center text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No job openings are currently available. Please check back later.
            </div>
        </div>
    ';
} else {
    foreach ($publicJobs as $i => $job) {
        $delay = 'delay-' . min(5, $i + 1);
        $salary = '';
        if ($job['salary_range_min'] || $job['salary_range_max']) {
            $salary = '<span class="job-badge job-badge-yellow">₱' . number_format((float)$job['salary_range_min'], 0)
                . ' - ₱' . number_format((float)$job['salary_range_max'], 0) . '</span>';
        }
        $jobCardsHtml .= '
            <div class="col-lg-10 animate-reveal ' . $delay . '">
                <div class="modern-card job-card p-4">
                    <div class="row align-items-center gy-3">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <span class="job-badge">' . htmlspecialchars($job['department']) . '</span>
                                ' . $salary . '
                            </div>
                            <h4 class="mb-2">' . htmlspecialchars($job['title']) . '</h4>
                            <p class="text-muted small mb-1">' . nl2br(htmlspecialchars(mb_strimwidth($job['description'], 0, 220, '...'))) . '</p>
                            <small class="text-muted"><i class="bi bi-calendar-x me-1"></i>Applications close ' . date('M j, Y', strtotime($job['open_until'])) . '</small>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <a href="?page=apply&job_posting_id=' . (int)$job['id'] . '" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                                Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }
}

$additional_css = '
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<noscript><style>.animate-reveal { opacity: 1 !important; transform: none !important; }</style></noscript>
';

$additional_js = '
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
// Approximate coordinate for NCST, Dasmarinas Aguinaldo Highway (documented
// estimate -- not a surveyed/precise pin). Update if an exact coordinate
// becomes available.
const SHELFSENSE_BRANCH = { lat: 14.3294, lng: 120.9372, label: "NCST - Dasmarinas Aguinaldo Highway Branch" };

function initShelfSenseMap() {
    const el = document.getElementById("branchMap");
    if (!el || typeof L === "undefined") return;
    try {
        const map = L.map("branchMap", { scrollWheelZoom: false }).setView([SHELFSENSE_BRANCH.lat, SHELFSENSE_BRANCH.lng], 15);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
            maxZoom: 19
        }).addTo(map);
        L.marker([SHELFSENSE_BRANCH.lat, SHELFSENSE_BRANCH.lng]).addTo(map)
            .bindPopup("<strong>ShelfSense</strong><br>" + SHELFSENSE_BRANCH.label + "<br><small>Dasmarinas Aguinaldo Highway, NCST</small>")
            .openPopup();
    } catch (e) {
        console.error("Map failed to load:", e);
        el.innerHTML = "<div class=\'d-flex align-items-center justify-content-center h-100 text-muted small text-center p-3\'><i class=\'bi bi-exclamation-triangle me-2\'></i>Map could not be loaded. Branch address: Dasmarinas Aguinaldo Highway, NCST.</div>";
    }
}
document.addEventListener("DOMContentLoaded", initShelfSenseMap);
</script>
<script>
// Role-based apply function
function applyForRole(role) {
    window.location.href = "?page=apply&role=" + encodeURIComponent(role);
}

// Progress Bar Animation
window.addEventListener("load", () => {
    setTimeout(() => {
        document.querySelectorAll(".progress-bar-animated").forEach(bar => {
            const targetWidth = bar.getAttribute("data-progress");
            if (targetWidth) {
                bar.style.width = targetWidth;
            }
        });
    }, 300);
});

// Intersection Observer
const observerOptions = { root: null, rootMargin: "0px", threshold: 0.15 };
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add("revealed");
            revealObserver.unobserve(entry.target);
        }
    });
}, observerOptions);

document.querySelectorAll(".animate-reveal").forEach(element => {
    revealObserver.observe(element);
});

// Fallback: if IntersectionObserver never fires for an element (e.g. a
// scripting error elsewhere on the page interrupts execution before this
// point, or a browser quirk), force everything visible after 2s so content
// never stays permanently hidden behind the reveal animation.
setTimeout(() => {
    document.querySelectorAll(".animate-reveal:not(.revealed)").forEach(element => {
        element.classList.add("revealed");
    });
}, 2000);

// Mobile Sidebar
const hamburgerBtn = document.getElementById("hamburgerBtn");
const hamburgerIcon = document.getElementById("hamburgerIcon");
const navbarContent = document.getElementById("navbarContent");
const sidebarOverlay = document.getElementById("sidebarOverlay");

function openSidebar() {
    navbarContent.classList.add("sidebar-open");
    sidebarOverlay.classList.add("show");
    document.body.classList.add("sidebar-no-scroll");
    hamburgerBtn.setAttribute("aria-expanded", "true");
    hamburgerIcon.classList.replace("bi-list", "bi-x-lg");
}

function closeSidebar() {
    navbarContent.classList.remove("sidebar-open");
    sidebarOverlay.classList.remove("show");
    document.body.classList.remove("sidebar-no-scroll");
    hamburgerBtn.setAttribute("aria-expanded", "false");
    hamburgerIcon.classList.replace("bi-x-lg", "bi-list");
}

hamburgerBtn.addEventListener("click", () => {
    navbarContent.classList.contains("sidebar-open") ? closeSidebar() : openSidebar();
});

sidebarOverlay.addEventListener("click", closeSidebar);

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeSidebar();
});

window.addEventListener("resize", () => {
    if (window.innerWidth >= 992 && navbarContent.classList.contains("sidebar-open")) {
        closeSidebar();
    }
});

// Smooth Scroll
document.querySelectorAll("a[href^=\'#\']").forEach(anchor => {
    anchor.addEventListener("click", function(e) {
        const targetId = this.getAttribute("href");
        if (targetId === "#") return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            if (navbarContent.classList.contains("sidebar-open")) {
                closeSidebar();
            }
            
            const navbarHeight = document.querySelector(".navbar").offsetHeight;
            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - navbarHeight - 10;

            setTimeout(() => {
                window.scrollTo({
                    top: offsetPosition,
                    behavior: "smooth"
                });
            }, 100);
        }
    });
});

// ============================================
// NAVBAR ACTIVE HIGHLIGHT - FIXED
// ============================================
window.addEventListener("scroll", () => {
    let scrollPosition = window.scrollY + 200;
    const sections = document.querySelectorAll("section");
    const navLinks = document.querySelectorAll(".nav-link");

    sections.forEach(section => {
        const top = section.offsetTop;
        const height = section.offsetHeight;
        const id = section.getAttribute("id");

        if (scrollPosition >= top && scrollPosition < top + height) {
            navLinks.forEach(link => {
                link.classList.remove("active");
                // Check if the link href ends with the section id
                const href = link.getAttribute("href");
                if (href && href.endsWith("#" + id)) {
                    link.classList.add("active");
                }
            });
        }
    });
});

// Set Home active by default when no section is in view
// This runs once on load and after scroll events
function setDefaultActive() {
    const navLinks = document.querySelectorAll(".nav-link");
    // If no link has active class, set Home as active
    let hasActive = false;
    navLinks.forEach(link => {
        if (link.classList.contains("active")) hasActive = true;
    });
    if (!hasActive) {
        navLinks.forEach(link => {
            const href = link.getAttribute("href");
            if (href && href.endsWith("#home")) {
                link.classList.add("active");
            }
        });
    }
}

// Run after scroll and on load
window.addEventListener("scroll", setDefaultActive);
document.addEventListener("DOMContentLoaded", setDefaultActive);

// Add extra spacing after the fixed navbar
document.addEventListener("DOMContentLoaded", function() {
    const navbar = document.querySelector(".navbar");
    if (navbar) {
        const navbarHeight = navbar.offsetHeight;
        document.querySelectorAll("section").forEach(section => {
            if (section.id === "home") {
                section.style.paddingTop = (navbarHeight + 40) + "px";
            }
        });
    }
});

</script>
';

$content = '
<!-- Ambient Background Glows -->
<div class="ambient-glow-1"></div>
<div class="ambient-glow-2"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="?page=home#home">
            <span class="brand-logo">
                <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="22" height="22">
                <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="22" height="22">
            </span>
            Shelf<span class="text-yellow">Sense</span>
        </a>

        <div class="d-flex align-items-center gap-2 order-lg-3">
            <!-- Dark Mode Toggle Button -->
            <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle Dark Mode" title="Toggle theme">
                <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
            </button>

            <!-- Staff Portal - Desktop only -->
            <a href="?page=login" class="btn btn-portal px-3 py-2 rounded-pill btn-sm d-none d-sm-inline-flex align-items-center">
                <i class="bi bi-shield-lock me-2"></i>Staff Portal
            </a>

            <!-- Hamburger Button -->
            <button class="navbar-toggler border-0 p-1 ms-1 shadow-none" type="button" id="hamburgerBtn" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-1 text-yellow" id="hamburgerIcon"></i>
            </button>
        </div>

        <div class="collapse navbar-collapse order-lg-2" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="?page=home#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="?page=home#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="?page=home#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="?page=home#join">Join Us</a></li>
                <li class="nav-item"><a class="nav-link" href="?page=home#contact">Contact</a></li>
            </ul>
            <!-- Staff Portal - Mobile sidebar only -->
            <a href="?page=login" class="btn btn-portal px-3 py-2 rounded-pill btn-sm sidebar-portal-btn align-items-center">
                <i class="bi bi-shield-lock me-2"></i>Staff Portal
            </a>
        </div>
    </div>
</nav>

<!-- Mobile Sidebar Backdrop -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ============================================ -->
<!-- SECTION 1: HERO -->
<!-- ============================================ -->
<section id="home" class="d-flex align-items-center min-vh-100" style="padding-top:120px;">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6 animate-reveal">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    Next-Gen Retail Infrastructure
                </div>
                <h1 class="hero-title">
                    The Future of Retail Operations: <span class="text-yellow">Smart Inventory</span> & Seamless HR
                </h1>
                <p class="text-muted fs-5 mb-4">
                    Streamlining high-tempo retail environments—from front-desk POS checkout to backend inventory synchronization, employee scheduling, attendance, and automated payroll.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="?page=home#services" class="btn btn-yellow-primary px-4 py-3 rounded-3">
                        Explore Platform <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                    <a href="?page=apply" class="btn btn-yellow-outline px-4 py-3 rounded-3">
                        Join Our Team
                    </a>
                </div>
            </div>

            <div class="col-lg-6 animate-reveal delay-2">
                <div class="hero-dashboard-preview modern-card">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                        <div class="d-flex gap-2">
                            <span class="rounded-circle bg-danger opacity-75 d-inline-block" style="width:10px; height:10px;"></span>
                            <span class="rounded-circle bg-warning opacity-75 d-inline-block" style="width:10px; height:10px;"></span>
                            <span class="rounded-circle bg-success opacity-75 d-inline-block" style="width:10px; height:10px;"></span>
                        </div>
                        <small class="text-muted font-heading"><i class="bi bi-cpu text-yellow me-1"></i> ShelfSense Core v2.4</small>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="p-3 rounded-3 bg-subtle-yellow border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted fw-medium"><i class="bi bi-diagram-3 me-1"></i>Integrated Business Systems</small>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Connected</span>
                                </div>
                                <span class="fs-6">POS &bull; Inventory &bull; HR &bull; Procurement &bull; Finance</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-subtle-yellow border h-100">
                                <small class="text-muted d-block fw-medium"><i class="bi bi-person-badge me-1"></i>HR Recruitment</small>
                                <span class="fs-6">Application &rarr; Interviews &rarr; Contract &rarr; Hired</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-subtle-yellow border h-100">
                                <small class="text-muted d-block fw-medium"><i class="bi bi-arrow-left-right me-1"></i>Procurement Flow</small>
                                <span class="fs-6">Requisition &rarr; Supplier &rarr; Finance &rarr; Delivery</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- SECTION 2: ABOUT -->
<!-- ============================================ -->
<section id="about" class="position-relative" style="padding-top:60px; padding-bottom:60px;">
    <div class="container">
        <div class="row justify-content-center text-center mb-5 animate-reveal">
            <div class="col-lg-8">
                <h2 class="fs-1 fw-bold mb-3">Architected for <span class="text-yellow">Modern Retail</span></h2>
                <p class="text-muted fs-5">
                    ShelfSense connects Point of Sale, Inventory, HR, Procurement, and Finance into one system, so information that used to live in separate spreadsheets and separate teams flows automatically between them&mdash;a requisition raised in-store reaches the right supplier and the right approver without anyone re-typing it, and a new hire moves through interviews and onboarding on one shared record.
                </p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6 animate-reveal delay-1">
                <div class="modern-card p-4 h-100">
                    <h5 class="mb-2"><i class="bi bi-bullseye text-yellow me-2"></i>Our Mission</h5>
                    <p class="text-muted mb-0">
                        To give growing retail operations a single, automated system that connects point-of-sale, inventory, procurement, HR, and finance&mdash;removing manual handoffs and disconnected spreadsheets between departments.
                    </p>
                </div>
            </div>
            <div class="col-md-6 animate-reveal delay-2">
                <div class="modern-card p-4 h-100">
                    <h5 class="mb-2"><i class="bi bi-eye text-yellow me-2"></i>Our Vision</h5>
                    <p class="text-muted mb-0">
                        A retail business where every department&mdash;from the cashier counter to the finance office&mdash;works from the same real-time data, so decisions are made on facts, not guesswork.
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4 animate-reveal delay-1">
                <div class="modern-card p-4 h-100">
                    <div class="icon-box">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h4 class="mb-3">Role-Based Access</h4>
                    <p class="text-muted mb-0">
                        Granular permission levels designed for Cashiers, Store Managers, Inventory Logistics Officers, and HR Administrators.
                    </p>
                </div>
            </div>

            <div class="col-md-4 animate-reveal delay-2">
                <div class="modern-card p-4 h-100">
                    <div class="icon-box">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <h4 class="mb-3">Real-Time POS</h4>
                    <p class="text-muted mb-0">
                        High-throughput checkout interface that instant-syncs inventory levels directly with backend tracking systems.
                    </p>
                </div>
            </div>

            <div class="col-md-4 animate-reveal delay-3">
                <div class="modern-card p-4 h-100">
                    <div class="icon-box">
                        <i class="bi bi-cpu"></i>
                    </div>
                    <h4 class="mb-3">Automated Engine</h4>
                    <p class="text-muted mb-0">
                        Integrated shift scheduling, automated biometric time-tracking, and seamless end-of-month payroll processing.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- SECTION 3: SERVICES -->
<!-- ============================================ -->
<section id="services" class="bg-subtle-yellow border-top border-bottom" style="padding-top:60px; padding-bottom:60px;">
    <div class="container">
        <div class="row justify-content-center text-center mb-5 animate-reveal">
            <div class="col-lg-8">
                <h2 class="fs-1 fw-bold mb-3">Core Platform <span class="text-yellow">Capabilities</span></h2>
                <p class="text-muted fs-5">Bridging the gap between physical storefronts and digital workforce management.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3 animate-reveal delay-1">
                <div class="modern-card p-4 h-100">
                    <div class="icon-box">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <h5 class="mb-3">Smart Inventory</h5>
                    <p class="text-muted small mb-0">
                        Real-time stock alerts, SKU classification for books and stationery, automated reorder thresholds, and vendor tracking.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 animate-reveal delay-2">
                <div class="modern-card p-4 h-100">
                    <div class="icon-box">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <h5 class="mb-3">Integrated POS</h5>
                    <p class="text-muted small mb-0">
                        Rapid barcode scanning, instant multi-payment processing, custom receipt generation, and discount code validation.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 animate-reveal delay-3">
                <div class="modern-card p-4 h-100">
                    <div class="icon-box">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <h5 class="mb-3">HR & Scheduling</h5>
                    <p class="text-muted small mb-0">
                        Shift assignment algorithms, leave management, employee role mapping, and store-level attendance monitoring.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 animate-reveal delay-4">
                <div class="modern-card p-4 h-100">
                    <div class="icon-box">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <h5 class="mb-3">Automated Payroll</h5>
                    <p class="text-muted small mb-0">
                        Precision timekeeping integration, overtime computation, tax deductions, and one-click salary dispatching.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- SECTION 4: JOIN US -->
<!-- ============================================ -->
<section id="join" class="position-relative" style="padding-top:60px; padding-bottom:60px;">
    <div class="container">
        <div class="row justify-content-center text-center mb-2 animate-reveal">
            <div class="col-lg-8">
                <div class="hero-badge mb-3">
                    <span class="hero-badge-dot"></span>
                    We are Hiring
                </div>
                <h2 class="fs-1 fw-bold mb-3">Build the Future of <span class="text-yellow">Retail Tech</span></h2>
                <p class="text-muted fs-5">Join our engineering and operations team in revolutionizing how store networks operate.</p>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
        ' . $jobCardsHtml . '
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- SECTION 5: CONTACT -->
<!-- ============================================ -->
<section id="contact" class="bg-subtle-yellow border-top" style="padding-top:60px; padding-bottom:60px;">
    <div class="container">
        <div class="modern-card p-4 p-md-5 animate-reveal">
            <div class="row gy-5">
                <div class="col-lg-6">
                    <h2 class="fs-1 fw-bold mb-3">Connect With <span class="text-yellow">ShelfSense</span></h2>
                    <p class="text-muted mb-4">
                        Have questions about deploying ShelfSense in your retail stores or partnering with us? Reach out to our team.
                    </p>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-box mb-0"><i class="bi bi-envelope"></i></div>
                        <div>
                            <small class="text-muted d-block">Enterprise Inquiries</small>
                            <span class="fw-semibold">contact@shelfsense.io</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-box mb-0"><i class="bi bi-telephone"></i></div>
                        <div>
                            <small class="text-muted d-block">Direct Line</small>
                            <span class="fw-semibold">+1 (800) 555-SENSE</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-box mb-0"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <small class="text-muted d-block">Branch Location</small>
                            <span class="fw-semibold">Dasmarinas Aguinaldo Highway, NCST</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-start gap-3 fs-4 mt-4">
                        <a href="#" class="text-muted"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-github"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-discord"></i></a>
                    </div>
                </div>

                <div class="col-lg-6">
                    <h5 class="mb-2"><i class="bi bi-pin-map text-yellow me-2"></i>Find Us</h5>
                    <p class="text-muted small mb-2">Approximate location &mdash; Dasmarinas Aguinaldo Highway, NCST.</p>
                    <div id="branchMap" style="height:280px; border-radius:12px; overflow:hidden; border:1px solid var(--border-color, #ddd); background:#f3f3f3;"></div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<style>
    .site-footer {
        background: var(--bg-card-subtle);
        border-top: 1px solid var(--border-color);
        padding: 56px 0 28px;
    }
    .site-footer .footer-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: "Space Grotesk", sans-serif;
        font-weight: 700;
        font-size: 1.3rem;
        color: var(--text-main);
        text-decoration: none;
    }
    .site-footer .footer-brand:hover {
        color: var(--text-main);
    }
    .site-footer .footer-tagline {
        color: var(--text-muted);
        font-size: 0.9rem;
        max-width: 260px;
        margin: 14px 0 18px;
    }
    .site-footer .footer-social {
        display: flex;
        gap: 14px;
        margin-bottom: 24px;
    }
    .site-footer .footer-social a {
        color: var(--text-muted);
        font-size: 1.1rem;
        transition: color 0.2s ease;
    }
    .site-footer .footer-social a:hover {
        color: var(--brand-yellow-hover);
    }
    [data-bs-theme="dark"] .site-footer .footer-social a:hover {
        color: var(--brand-yellow);
    }
    .site-footer .footer-col h6 {
        color: var(--text-main);
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 16px;
    }
    .site-footer .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .site-footer .footer-col ul li {
        margin-bottom: 12px;
    }
    .site-footer .footer-col ul li a {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.2s ease;
    }
    .site-footer .footer-col ul li a:hover {
        color: var(--brand-yellow-hover);
    }
    [data-bs-theme="dark"] .site-footer .footer-col ul li a:hover {
        color: var(--brand-yellow);
    }
    .site-footer .footer-bottom {
        border-top: 1px solid var(--border-color);
        margin-top: 40px;
        padding-top: 20px;
        text-align: center;
        color: var(--text-muted);
        font-size: 0.78rem;
        letter-spacing: 0.02em;
    }
</style>

<footer class="site-footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-12">
                <a href="?page=home#home" class="footer-brand">
                    <span class="brand-logo">
                        <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="24" height="24">
                        <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="24" height="24">
                    </span>
                    <span>Shelf<span class="text-yellow">Sense</span></span>
                </a>
                <p class="footer-tagline">
                    Smart inventory control, HR, and retail operations &mdash; all in one platform.
                </p>
                <div class="footer-social">
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" aria-label="X (Twitter)"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" aria-label="GitHub"><i class="bi bi-github"></i></a>
                    <a href="#" aria-label="Discord"><i class="bi bi-discord"></i></a>
                </div>
                <p class="text-muted small mb-0">&copy; 2026 ShelfSense Systems Inc. All rights reserved.</p>
            </div>

            <div class="col-lg-2 col-6 footer-col">
                <h6>Company</h6>
                <ul>
                    <li><a href="?page=home#home">Home</a></li>
                    <li><a href="?page=home#about">About</a></li>
                    <li><a href="?page=home#services">Services</a></li>
                    <li><a href="?page=home#contact">Contact</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-6 footer-col">
                <h6>Careers</h6>
                <ul>
                    <li><a href="?page=home#join">Open Positions</a></li>
                    <li><a href="?page=apply">Apply Now</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-6 footer-col">
                <h6>Get in Touch</h6>
                <ul>
                    <li><a href="mailto:contact@shelfsense.io">contact@shelfsense.io</a></li>
                    <li><a href="tel:+18005557367">+1 (800) 555-SENSE</a></li>
                    <li><a href="?page=home#contact">Branch Location</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-6 footer-col">
                <h6>Access</h6>
                <ul>
                    <li><a href="?page=login"><i class="bi bi-shield-lock me-1"></i>Staff Portal</a></li>
                    <li><a href="?page=forgot_password">Forgot Password</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            ShelfSense is an internal retail operations platform. Not a public storefront or e-commerce service.
        </div>
    </div>
</footer>
';

require_once __DIR__ . '/../layouts/main.php';