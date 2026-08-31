<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ShelfSense - Auth' ?></title>
    <link rel="icon" type="image/png" href="/ShelfSense/public/assets/images/logo-black.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css?v=20260831450000">
    <style>
        /* ==========================================================================
           DESIGN SYSTEM & THEME MATCHING
           ========================================================================== */
        :root {
            /* Light Mode Palette -- matches the internal dashboards (white / orange / black) */
            --bg-body: #f4f4f2;
            --bg-card: #ffffff;
            --bg-card-subtle: #f8f8f7;
            --border-color: #ebebe7;

            --brand-yellow: #f45b35;
            --brand-yellow-hover: #df4d29;
            --brand-yellow-btn-text: #ffffff;
            --light-yellow-accent: #fde8e2;
            --light-yellow-subtle: #fdf1ee;

            --text-main: #20201d;
            --text-muted: #73736f;
            --grid-opacity: 0.05;
            --glow-opacity: 0.18;
        }

        [data-bs-theme="dark"] {
            /* Dark Mode Palette */
            --bg-body: #161615;
            --bg-card: #201f1d;
            --bg-card-subtle: #262523;
            --border-color: #35342f;

            --brand-yellow: #f45b35;
            --brand-yellow-hover: #ff6d45;
            --brand-yellow-btn-text: #ffffff;
            --light-yellow-accent: #33241d;
            --light-yellow-subtle: #241c18;

            --text-main: #f4f4f2;
            --text-muted: #a8a8a2;
            --grid-opacity: 0.08;
            --glow-opacity: 0.14;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Background Grid */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image:
                linear-gradient(to right, rgba(244, 91, 53, var(--grid-opacity)) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(244, 91, 53, var(--grid-opacity)) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -2;
            pointer-events: none;
        }

        /* Ambient Glows */
        .ambient-glow-1 {
            position: fixed;
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(244, 91, 53, var(--glow-opacity)) 0%, rgba(244, 244, 242, 0) 70%);
            z-index: -1;
            pointer-events: none;
        }

        .ambient-glow-2 {
            position: fixed;
            bottom: -10%;
            right: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(223, 77, 41, var(--glow-opacity)) 0%, rgba(244, 244, 242, 0) 70%);
            z-index: -1;
            pointer-events: none;
        }

        /* Auth Container & Card */
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            max-width: 420px;
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .auth-card .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-card .brand-mark {
            display: inline-block;
            width: 12px;
            height: 12px;
            background-color: var(--brand-yellow);
            border-radius: 3px;
            margin-right: 6px;
        }

        .auth-card .brand h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-card .brand h1 span {
            color: var(--brand-yellow-hover);
        }

        [data-bs-theme="dark"] .auth-card .brand h1 span {
            color: var(--brand-yellow);
        }

        .auth-card .brand small {
            color: var(--text-muted);
            display: block;
            margin-top: 4px;
        }

        /* Form Inputs, Selects & Buttons */
        .form-control, 
        .form-select, 
        .input-group-text, 
        .btn-outline-secondary {
            background-color: var(--bg-card-subtle) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        /* Focus States */
        .form-control:focus, 
        .form-select:focus {
            background-color: var(--bg-card-subtle) !important;
            color: var(--text-main) !important;
            border-color: var(--brand-yellow) !important;
            box-shadow: 0 0 0 0.2rem rgba(244, 91, 53, 0.25) !important;
        }

        /* Select Dropdown Options */
        .form-select option {
            background-color: var(--bg-card) !important;
            color: var(--text-main) !important;
        }

        /* Custom File Input Button */
        .custom-file-input::file-selector-button {
            background-color: var(--light-yellow-subtle) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--border-color) !important;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-right: 12px;
        }

        .custom-file-input::file-selector-button:hover {
            background-color: var(--brand-yellow-hover) !important;
            border-color: var(--brand-yellow-hover) !important;
            color: #ffffff !important;
        }

        .custom-file-input::file-selector-button:active,
        .custom-file-input:focus::file-selector-button {
            background-color: var(--brand-yellow-hover) !important;
            border-color: var(--brand-yellow-hover) !important;
            color: #ffffff !important;
        }

        /* Primary Button */
        .btn-yellow-primary {
            background: var(--brand-yellow);
            color: var(--brand-yellow-btn-text) !important;
            font-weight: 600;
            border: none;
            padding: 10px;
            box-shadow: 0 4px 14px rgba(244, 91, 53, 0.3);
            transition: all 0.3s ease;
        }

        .btn-yellow-primary:hover {
            background: var(--brand-yellow-hover);
            color: #ffffff !important;
            box-shadow: 0 6px 20px rgba(244, 91, 53, 0.4);
            transform: translateY(-1px);
        }

        .auth-link {
            color: var(--brand-yellow-hover);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        [data-bs-theme="dark"] .auth-link {
            color: var(--brand-yellow);
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        /* Back navigation — given its own visible pill/container so it
           does not get lost against the page, unlike plain .auth-link text */
        .back-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background: var(--bg-card-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none !important;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        [data-bs-theme="dark"] .back-nav-btn {
            color: var(--brand-yellow);
        }

        .back-nav-btn:hover {
            background: var(--brand-yellow);
            border-color: var(--brand-yellow);
            color: #ffffff;
        }

        /* Flash Messages */
        .flash-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .flash-message.success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .flash-message.error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c6cb;
        }

        .flash-message.warning {
            background: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
        }

        .flash-message.info {
            background: #cff4fc;
            color: #055160;
            border: 1px solid #b6effb;
        }
        
        /* Dark Mode Theme Toggle */
        .theme-toggle-btn-auth {
            position: fixed;
            top: 20px;
            right: 20px;
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
            z-index: 1000;
        }

        [data-bs-theme="dark"] .theme-toggle-btn-auth {
            color: var(--brand-yellow);
        }

        .theme-toggle-btn-auth:hover {
            transform: rotate(20deg) scale(1.05);
            border-color: var(--brand-yellow);
        }
    </style>
</head>
<body>
    <!-- Ambient Background Glows -->
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <!-- Dark Mode Toggle Button (Auth Pages) -->
    <button class="theme-toggle-btn-auth" id="themeToggleAuth" aria-label="Toggle Dark Mode" title="Toggle theme">
        <i class="bi bi-moon-stars-fill" id="themeIconAuth"></i>
    </button>

    <div class="auth-container">
        <div class="auth-card">
            

            <!-- Flash Messages -->
            <?php $flash = getFlash(); ?>
            <?php if ($flash): ?>
                <div class="flash-message <?= $flash['type'] ?>">
                    <?= escape($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/ShelfSense/public/assets/js/app.js?v=20260831460000"></script>
</body>
</html>