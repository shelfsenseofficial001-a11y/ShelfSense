<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ShelfSense | Smart Retail Operations & HR Platform</title>
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    /* ==========================================================================
       1. DESIGN SYSTEM & CONSISTENT YELLOW COLOR THEMES
       ========================================================================== */
    html {
      scroll-behavior: smooth;
    }

    :root {
      /* Light Mode Palette */
      --bg-body: #fefdfa;
      --bg-card: #ffffff;
      --bg-card-subtle: #fefce8;
      --border-color: #fef08a;
      
      /* Primary Yellow Palette */
      --brand-yellow: #ffc414;        /* Main Yellow */
      --brand-yellow-hover: #eeab1a;  /* Darker Yellow for Hover/Text on light backgrounds */
      --brand-yellow-btn-text: #1a1a1a;/* Dark readable text for primary buttons */
      --light-yellow-accent: #fef9c3; /* Light Accent Tint */
      --light-yellow-subtle: #fefce8; /* Soft Yellow Tint */
      
      --text-main: #18181b;
      --text-muted: #71717a;
      --grid-opacity: 0.05;
      --glow-opacity: 0.25;
    }

    [data-bs-theme="dark"] {
      /* Dark Mode Palette */
      --bg-body: #121210;
      --bg-card: #1c1a14;
      --bg-card-subtle: #262319;
      --border-color: #3f3922;
      
      --brand-yellow: #facc15;
      --brand-yellow-hover: #f8c52c;
      --brand-yellow-btn-text: #1a1a1a;
      --light-yellow-accent: #2e2a17;
      --light-yellow-subtle: #1e1b10;
      
      --text-main: #f4f4f5;
      --text-muted: #a1a1aa;
      --grid-opacity: 0.08;
      --glow-opacity: 0.15;
    }

    body {
      background-color: var(--bg-body);
      color: var(--text-main);
      font-family: 'Inter', sans-serif;
      overflow-x: hidden;
      position: relative;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Soft Grid Pattern Background */
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background-image: 
        linear-gradient(to right, rgba(234, 179, 8, var(--grid-opacity)) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(234, 179, 8, var(--grid-opacity)) 1px, transparent 1px);
      background-size: 40px 40px;
      z-index: -2;
      pointer-events: none;
    }

    /* Ambient Soft Yellow Glows */
    .ambient-glow-1 {
      position: fixed;
      top: -10%;
      left: -10%;
      width: 50vw;
      height: 50vw;
      background: radial-gradient(circle, rgba(250, 204, 21, var(--glow-opacity)) 0%, rgba(254, 253, 250, 0) 70%);
      z-index: -1;
      pointer-events: none;
    }

    .ambient-glow-2 {
      position: fixed;
      bottom: -10%;
      right: -10%;
      width: 50vw;
      height: 50vw;
      background: radial-gradient(circle, rgba(234, 179, 8, var(--glow-opacity)) 0%, rgba(254, 253, 250, 0) 70%);
      z-index: -1;
      pointer-events: none;
    }

    h1, h2, h3, h4, h5, .font-heading {
      font-family: 'Space Grotesk', sans-serif;
      letter-spacing: -0.5px;
      color: var(--text-main);
    }

    /* Yellow Class Utilities */
    .text-yellow { color: var(--brand-yellow-hover) !important; }
    [data-bs-theme="dark"] .text-yellow { color: var(--brand-yellow) !important; }
    
    .bg-light-yellow { background-color: var(--light-yellow-accent) !important; }
    .bg-subtle-yellow { background-color: var(--light-yellow-subtle) !important; }

    /* Modern Card Style */
    .modern-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .modern-card:hover {
      border-color: var(--brand-yellow);
      box-shadow: 0 12px 30px rgba(234, 179, 8, 0.15);
      transform: translateY(-4px);
    }

    /* Buttons */
    .btn-yellow-primary {
      background: var(--brand-yellow);
      color: var(--brand-yellow-btn-text) !important;
      font-weight: 600;
      border: none;
      box-shadow: 0 4px 14px rgba(234, 179, 8, 0.3);
      transition: all 0.3s ease;
    }

    .btn-yellow-primary:hover {
      background: var(--brand-yellow-hover);
      color: #ffffff !important;
      box-shadow: 0 6px 20px rgba(234, 179, 8, 0.4);
      transform: translateY(-1px);
    }

    .btn-yellow-outline {
      background: var(--bg-card);
      color: var(--brand-yellow-hover);
      border: 1px solid var(--border-color);
      font-weight: 600;
      transition: all 0.3s ease;
    }

    [data-bs-theme="dark"] .btn-yellow-outline {
      color: var(--brand-yellow);
    }

    .btn-yellow-outline:hover {
      background: var(--light-yellow-subtle);
      border-color: var(--brand-yellow);
      color: var(--brand-yellow-hover);
      transform: translateY(-1px);
    }

    .btn-portal {
      background: var(--light-yellow-subtle);
      border: 1px solid var(--border-color);
      color: var(--brand-yellow-hover);
      font-weight: 600;
      transition: all 0.3s ease;
    }

    [data-bs-theme="dark"] .btn-portal {
      color: var(--brand-yellow);
    }

    .btn-portal:hover {
      background: var(--brand-yellow);
      color: var(--brand-yellow-btn-text) !important;
      border-color: var(--brand-yellow);
    }

    /* Section Spacing */
    section {
      padding: 60px 0;
    }

    /* ==========================================================================
       2. NAVIGATION & MOBILE COLLAPSE FIXES
       ========================================================================== */
    .navbar {
      background: var(--bg-card);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border-color);
      transition: all 0.3s ease;
      z-index: 1030;
    }

    .navbar-brand {
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 700;
      font-size: 1.4rem;
      color: var(--text-main) !important;
    }

    .brand-mark {
      display: inline-block;
      width: 12px;
      height: 12px;
      background-color: var(--brand-yellow);
      border-radius: 3px;
      margin-right: 8px;
    }

    .nav-link {
      color: var(--text-muted) !important;
      font-weight: 500;
      margin: 0 8px;
      transition: color 0.3s ease;
      cursor: pointer !important;
      pointer-events: auto !important;
    }

    .nav-link:hover, .nav-link.active {
      color: var(--brand-yellow-hover) !important;
    }

    [data-bs-theme="dark"] .nav-link:hover, 
    [data-bs-theme="dark"] .nav-link.active {
      color: var(--brand-yellow) !important;
    }

    .theme-toggle-btn {
      background: var(--light-yellow-subtle);
      border: 1px solid var(--border-color);
      color: var(--brand-yellow-hover);
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    [data-bs-theme="dark"] .theme-toggle-btn {
      color: var(--brand-yellow);
    }

    .theme-toggle-btn:hover {
      transform: rotate(20deg) scale(1.05);
      border-color: var(--brand-yellow);
    }

    /* Mobile Sidebar Menu */
    @media (max-width: 991.98px) {
      #navbarContent.navbar-collapse {
        display: block;
        position: fixed;
        top: 0;
        right: 0;
        height: 100vh;
        width: min(300px, 80vw);
        margin-top: 0;
        background: var(--bg-card);
        border: none;
        border-left: 1px solid var(--border-color);
        border-radius: 0;
        padding: 100px 24px 24px;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
        transform: translateX(100%);
        transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 1045;
        overflow-y: auto;
      }

      #navbarContent.navbar-collapse.sidebar-open {
        transform: translateX(0);
      }

      /* Hide the sidebar logo on mobile */
      #navbarContent .navbar-brand {
        display: none !important;
      }

      .navbar-nav .nav-link {
        padding: 12px 0;
        border-bottom: 1px dashed var(--border-color);
        cursor: pointer !important;
        pointer-events: auto !important;
        display: block;
        width: 100%;
      }
      
      .navbar-nav .nav-item:last-child .nav-link {
        border-bottom: none;
      }

      .navbar-toggler {
        z-index: 1050;
      }

      /* Dimmed backdrop behind the sidebar */
      .sidebar-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.35s ease;
        z-index: 20;
      }

      .sidebar-overlay.show {
        opacity: 1;
        pointer-events: auto;
      }

      body.sidebar-no-scroll {
        overflow: hidden;
      }

      /* Hide desktop portal button on mobile */
      .navbar .btn-portal.d-none.d-sm-inline-flex {
        display: none !important;
      }
    }

    /* Show portal button in sidebar on mobile */
    @media (max-width: 991.98px) {
      .sidebar-portal-btn {
        display: flex !important;
        margin-top: 20px;
        width: 100%;
        justify-content: center;
      }
    }

    @media (min-width: 992px) {
      .sidebar-portal-btn {
        display: none !important;
      }
    }

    /* ==========================================================================
       3. HERO SECTION & PROGRESS BARS
       ========================================================================== */
    #home {
      padding-top: 140px;
    }

    @media (min-width: 992px) {
      #home {
        padding-top: 120px;
      }
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 16px;
      border-radius: 50px;
      background: var(--light-yellow-subtle);
      border: 1px solid var(--border-color);
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--brand-yellow-hover);
      margin-bottom: 24px;
    }

    [data-bs-theme="dark"] .hero-badge {
      color: var(--brand-yellow);
    }

    .hero-badge-dot {
      width: 8px;
      height: 8px;
      background-color: var(--brand-yellow);
      border-radius: 50%;
    }

    .hero-title {
      font-size: 3.5rem;
      font-weight: 700;
      line-height: 1.15;
      margin-bottom: 24px;
    }

    .hero-dashboard-preview {
      position: relative;
      border-radius: 20px;
      padding: 24px;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
    }

    .progress-bar-yellow {
      background-color: var(--brand-yellow) !important;
    }

    .progress-bar-animated {
      width: 0%;
      transition: width 1.8s cubic-bezier(0.1, 0.8, 0.2, 1);
    }

    /* ==========================================================================
       4. ANIMATIONS & RECRUITMENT
       ========================================================================== */
    .animate-reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .animate-reveal.revealed {
      opacity: 1;
      transform: translateY(0);
    }

    .delay-1 { transition-delay: 0.1s; }
    .delay-2 { transition-delay: 0.2s; }
    .delay-3 { transition-delay: 0.3s; }
    .delay-4 { transition-delay: 0.4s; }

    .icon-box {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 20px;
      background: var(--light-yellow-accent);
      color: var(--brand-yellow-hover);
    }

    [data-bs-theme="dark"] .icon-box {
      color: var(--brand-yellow);
    }

    .job-card {
      position: relative;
      overflow: hidden;
    }

    .job-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: var(--brand-yellow);
    }

    .job-badge {
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-color);
      font-size: 0.75rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 20px;
      color: var(--text-muted);
    }

    .job-badge-yellow {
      background: var(--light-yellow-subtle);
      border: 1px solid var(--border-color);
      color: var(--brand-yellow-hover);
    }

    [data-bs-theme="dark"] .job-badge-yellow {
      color: var(--brand-yellow);
    }

    @media (max-width: 991.98px) {
      .hero-title { font-size: 2.25rem; }
      section { padding: 60px 0; }
    }
  </style>
</head>
<body>

  <!-- Ambient Background Glows -->
  <div class="ambient-glow-1"></div>
  <div class="ambient-glow-2"></div>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="#home">
        <span class="brand-mark"></span>
        Shelf<span class="text-yellow">Sense</span>
      </a>

      <div class="d-flex align-items-center gap-2 order-lg-3">
        <!-- Dark Mode Toggle Button -->
        <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle Dark Mode" title="Toggle theme">
          <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        </button>

        <!-- Staff Portal - Desktop only -->
        <a href="#portal" class="btn btn-portal px-3 py-2 rounded-pill btn-sm d-none d-sm-inline-flex align-items-center">
          <i class="bi bi-shield-lock me-2"></i>Staff Portal
        </a>

        <!-- Hamburger Button -->
        <button class="navbar-toggler border-0 p-1 ms-1 shadow-none" type="button" id="hamburgerBtn" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
          <i class="bi bi-list fs-1 text-yellow" id="hamburgerIcon"></i>
        </button>
      </div>

      <div class="collapse navbar-collapse order-lg-2" id="navbarContent">

        <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
          <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
          <li class="nav-item"><a class="nav-link" href="#join">Join Us</a></li>
          <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
        </ul>
        <!-- Staff Portal - Mobile sidebar only -->
        <a href="#portal" class="btn btn-portal px-3 py-2 rounded-pill btn-sm sidebar-portal-btn align-items-center">
          <i class="bi bi-shield-lock me-2"></i>Staff Portal
        </a>
      </div>
    </div>
  </nav>

  <!-- Mobile Sidebar Backdrop -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- SECTION 1: HERO -->
  <section id="home" class="d-flex align-items-center min-vh-100">
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
            <a href="#services" class="btn btn-yellow-primary px-4 py-3 rounded-3">
              Explore Platform <i class="bi bi-arrow-right ms-2"></i>
            </a>
            <a href="#join" class="btn btn-yellow-outline px-4 py-3 rounded-3">
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

  <!-- SECTION 2: ABOUT -->
  <section id="about" class="position-relative">
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

  <!-- SECTION 3: SERVICES -->
  <section id="services" class="bg-subtle-yellow border-top border-bottom">
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

  <!-- SECTION 4: JOIN US -->
  <section id="join" class="position-relative">
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
        <div class="col-lg-10 animate-reveal delay-1">
          <div class="modern-card job-card p-4">
            <div class="row align-items-center gy-3">
              <div class="col-md-7">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="job-badge">Engineering</span>
                  <span class="job-badge job-badge-yellow">Full-Time / Remote</span>
                </div>
                <h4 class="mb-2">Senior Full-Stack Engineer (Node.js & React)</h4>
                <p class="text-muted small mb-0">
                  Lead the development of our high-concurrency POS sync engine and backend role-based inventory microservices.
                </p>
              </div>
              <div class="col-md-5 text-md-end">
                <a href="https://careers.shelfsense-example.com/apply/fullstack-dev" target="_blank" rel="noopener noreferrer" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                  Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-10 animate-reveal delay-2">
          <div class="modern-card job-card p-4">
            <div class="row align-items-center gy-3">
              <div class="col-md-7">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="job-badge">HR Operations</span>
                  <span class="job-badge job-badge-yellow">Hybrid / On-site</span>
                </div>
                <h4 class="mb-2">Retail HR & Payroll Systems Specialist</h4>
                <p class="text-muted small mb-0">
                  Work directly with retail clients to configure automated shift workflows, attendance engines, and compliance modules.
                </p>
              </div>
              <div class="col-md-5 text-md-end">
                <a href="https://careers.shelfsense-example.com/apply/hr-specialist" target="_blank" rel="noopener noreferrer" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                  Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-10 animate-reveal delay-3">
          <div class="modern-card job-card p-4">
            <div class="row align-items-center gy-3">
              <div class="col-md-7">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="job-badge">Product Operations</span>
                  <span class="job-badge job-badge-yellow">Full-Time / Remote</span>
                </div>
                <h4 class="mb-2">Inventory Operations Analyst</h4>
                <p class="text-muted small mb-0">
                  Analyze SKU tracking workflows across bookstore chains and school supply vendors to optimize automated reordering.
                </p>
              </div>
              <div class="col-md-5 text-md-end">
                <a href="https://careers.shelfsense-example.com/apply/ops-analyst" target="_blank" rel="noopener noreferrer" class="btn btn-yellow-primary px-4 py-2 rounded-2">
                  Apply Now <i class="bi bi-box-arrow-up-right ms-2"></i>
                </a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- SECTION 5: CONTACT -->
  <section id="contact" class="bg-subtle-yellow border-top">
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

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>

  <!-- JavaScript Logic -->
  <script>
    // 1. DARK MODE TOGGLE LOGIC
    const themeToggleBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;

    const savedTheme = localStorage.getItem('theme') || 
      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    function setTheme(theme) {
      htmlElement.setAttribute('data-bs-theme', theme);
      if (theme === 'dark') {
        themeIcon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
      } else {
        themeIcon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
      }
      localStorage.setItem('theme', theme);
    }

    setTheme(savedTheme);

    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = htmlElement.getAttribute('data-bs-theme');
      setTheme(currentTheme === 'dark' ? 'light' : 'dark');
    });

    // 2. PROGRESS BAR ANIMATION ON LOAD
    window.addEventListener('load', () => {
      setTimeout(() => {
        document.querySelectorAll('.progress-bar-animated').forEach(bar => {
          const targetWidth = bar.getAttribute('data-progress');
          if (targetWidth) {
            bar.style.width = targetWidth;
          }
        });
      }, 300);
    });

    // 3. INTERSECTION OBSERVER FOR REVEAL ANIMATIONS
    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.15
    };

    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    document.querySelectorAll('.animate-reveal').forEach(element => {
      revealObserver.observe(element);
    });

    // 4. MOBILE SIDEBAR TOGGLE
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const hamburgerIcon = document.getElementById('hamburgerIcon');
    const navbarContent = document.getElementById('navbarContent');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
      navbarContent.classList.add('sidebar-open');
      sidebarOverlay.classList.add('show');
      document.body.classList.add('sidebar-no-scroll');
      hamburgerBtn.setAttribute('aria-expanded', 'true');
      hamburgerIcon.classList.replace('bi-list', 'bi-x-lg');
    }

    function closeSidebar() {
      navbarContent.classList.remove('sidebar-open');
      sidebarOverlay.classList.remove('show');
      document.body.classList.remove('sidebar-no-scroll');
      hamburgerBtn.setAttribute('aria-expanded', 'false');
      hamburgerIcon.classList.replace('bi-x-lg', 'bi-list');
    }

    hamburgerBtn.addEventListener('click', () => {
      navbarContent.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
    });

    sidebarOverlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeSidebar();
    });

    // Close the sidebar automatically if the viewport grows back to desktop size
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 992 && navbarContent.classList.contains('sidebar-open')) {
        closeSidebar();
      }
    });

    // 5. SMOOTH SCROLL WITH SIDEBAR CLOSING FOR MOBILE
    // This now handles both desktop and mobile nav links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          // Close mobile sidebar if open
          if (navbarContent.classList.contains('sidebar-open')) {
            closeSidebar();
          }

          // For desktop nav links, prevent default and smooth scroll
          if (!this.closest('.navbar-collapse') || window.innerWidth >= 992) {
            e.preventDefault();
          }

          // For mobile sidebar links, prevent default
          if (this.closest('#navbarContent')) {
            e.preventDefault();
          }

          const navbarHeight = document.querySelector('.navbar').offsetHeight;
          const elementPosition = targetElement.getBoundingClientRect().top;
          const offsetPosition = elementPosition + window.pageYOffset - navbarHeight - 10;

          // Small delay to allow sidebar to close before scrolling
          setTimeout(() => {
            window.scrollTo({
              top: offsetPosition,
              behavior: 'smooth'
            });
          }, 100);
        }
      });
    });

    // 6. NAVBAR ACTIVE HIGHLIGHT ON SCROLL
    window.addEventListener('scroll', () => {
      let scrollPosition = window.scrollY + 200;
      const sections = document.querySelectorAll('section');
      const navLinks = document.querySelectorAll('.nav-link');

      sections.forEach(section => {
        const top = section.offsetTop;
        const height = section.offsetHeight;
        const id = section.getAttribute('id');

        if (scrollPosition >= top && scrollPosition < top + height) {
          navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + id) {
              link.classList.add('active');
            }
          });
        }
      });
    });
  </script>
</body>
</html>