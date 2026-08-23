<?php
// views/pages/landing.php
$title = 'ShelfSense | Smart Retail Operations & HR Platform';
$additional_js = '
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
            <span class="brand-mark"></span>
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
                    The Future of Retail Operations: <span class="text-yellow">Smart Inventory</span> & <span class="text-muted">Seamless HR</span>
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
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-subtle-yellow border">
                                <small class="text-muted d-block fw-medium">Real-Time POS Volume</small>
                                <span class="fs-4 font-heading text-yellow">$18,420.00</span>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar progress-bar-yellow progress-bar-animated" data-progress="75%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-subtle-yellow border">
                                <small class="text-muted d-block fw-medium">Active Shift Workforce</small>
                                <span class="fs-4 font-heading">34 / 36 On-Clock</span>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar progress-bar-yellow opacity-75 progress-bar-animated" data-progress="94%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 rounded-3 bg-subtle-yellow border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted fw-medium">Stock Sync Monitor (Bookstores & Merchandise)</small>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Optimal</span>
                                </div>
                                <div class="d-flex align-items-baseline gap-2 mb-1">
                                    <span class="fs-5 font-heading">99.8% Sync Efficiency</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar progress-bar-yellow progress-bar-animated" data-progress="99.8%"></div>
                                </div>
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
                    ShelfSense is an integrated, role-based retail ecosystem engineered specifically for high-SKU operations—such as bookstores, school supply chains, and general merchandise hubs.
                </p>
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
            <!-- Job Card 1 - Head HR -->
            <div class="col-lg-10 animate-reveal delay-1">
                <div class="modern-card job-card p-4">
                    <div class="row align-items-center gy-3">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="job-badge">Leadership</span>
                                <span class="job-badge job-badge-yellow">Full-Time</span>
                                <span class="badge bg-danger">Senior Role</span>
                            </div>
                            <h4 class="mb-2">Head of Human Resources</h4>
                            <p class="text-muted small mb-0">
                                Lead our HR department, develop people strategies, oversee recruitment, employee relations, and organizational development.
                            </p>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <a href="?page=apply&role=hr_head" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                                Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Card 2 - Head Finance -->
            <div class="col-lg-10 animate-reveal delay-2">
                <div class="modern-card job-card p-4">
                    <div class="row align-items-center gy-3">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="job-badge">Leadership</span>
                                <span class="job-badge job-badge-yellow">Full-Time</span>
                                <span class="badge bg-danger">Senior Role</span>
                            </div>
                            <h4 class="mb-2">Head of Finance</h4>
                            <p class="text-muted small mb-0">
                                Lead our finance team, manage financial strategy, budgeting, forecasting, payroll oversight, and compliance.
                            </p>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <a href="?page=apply&role=finance_head" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                                Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Card 3 - Cashier -->
            <div class="col-lg-10 animate-reveal delay-3">
                <div class="modern-card job-card p-4">
                    <div class="row align-items-center gy-3">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="job-badge">Store Operations</span>
                                <span class="job-badge job-badge-yellow">Full-Time</span>
                            </div>
                            <h4 class="mb-2">Retail Cashier</h4>
                            <p class="text-muted small mb-0">
                                Handle daily sales transactions, customer service, and maintain accurate cash handling procedures.
                            </p>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <a href="?page=apply&role=cashier" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                                Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Card 4 - HR Staff -->
            <div class="col-lg-10 animate-reveal delay-4">
                <div class="modern-card job-card p-4">
                    <div class="row align-items-center gy-3">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="job-badge">Human Resources</span>
                                <span class="job-badge job-badge-yellow">Full-Time</span>
                            </div>
                            <h4 class="mb-2">HR Staff</h4>
                            <p class="text-muted small mb-0">
                                Manage recruitment, employee records, onboarding, and support HR operations across the organization.
                            </p>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <a href="?page=apply&role=hr_staff" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                                Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Card 5 - Finance Staff -->
            <div class="col-lg-10 animate-reveal delay-5">
                <div class="modern-card job-card p-4">
                    <div class="row align-items-center gy-3">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="job-badge">Finance</span>
                                <span class="job-badge job-badge-yellow">Full-Time</span>
                            </div>
                            <h4 class="mb-2">Finance Staff</h4>
                            <p class="text-muted small mb-0">
                                Handle financial transactions, payroll processing, budget monitoring, and financial reporting.
                            </p>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <a href="?page=apply&role=finance_staff" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                                Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

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

                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box mb-0"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <small class="text-muted d-block">Global Headquarters</small>
                            <span class="fw-semibold">Cyber Hub Tower 4, Tech District, CA</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 d-flex flex-column justify-content-center">
                    <div class="p-4 rounded-3 bg-light-yellow border border-warning-subtle text-center">
                        <i class="bi bi-headset fs-1 text-yellow mb-3 d-block"></i>
                        <h4 class="mb-2">Need Immediate Support?</h4>
                        <p class="text-muted small mb-4">Our systems engineers are available 24/7 for store onboarding and assistance.</p>
                        
                        <div class="d-flex justify-content-center gap-3 fs-4">
                            <a href="#" class="text-muted"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-muted"><i class="bi bi-twitter-x"></i></a>
                            <a href="#" class="text-muted"><i class="bi bi-github"></i></a>
                            <a href="#" class="text-muted"><i class="bi bi-discord"></i></a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="py-4 bg-body border-top">
    <div class="container text-center">
        <p class="text-muted small mb-0">
            &copy; 2026 ShelfSense Systems Inc. All rights reserved. Powered by Smart Retail Engines.
        </p>
    </div>
</footer>
';

require_once __DIR__ . '/../layouts/main.php';