<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SKSUScholarSync is the scholarship processing, verification, certificate generation, and monitoring portal for Sultan Kudarat State University.">

    <title>SKSUScholarSync — Sultan Kudarat State University</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    @endif

    <style>
        :root {
            --primary: #008a45;
            --primary-dark: #034b2e;
            --primary-hover: #007338;
            --primary-light: #ecfdf5;
            --primary-border: #a7f3d0;
            --gold: #d97706;
            --gold-light: #fef3c7;
            --text-main: #0f172a;
            --text-muted: #475569;
            --text-subtle: #64748b;
            --bg-white: #ffffff;
            --bg-subtle: #f8fafc;
            --bg-surface: #ffffff;
            --border-light: #e2e8f0;
            --border-subtle: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.03);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-full: 9999px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Figtree", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--bg-white);
            color: var(--text-main);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .container {
            width: min(1140px, calc(100% - 2.5rem));
            margin-inline: auto;
        }

        /* Skip link for a11y */
        .skip-link {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            background: var(--primary);
            color: #ffffff;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            transform: translateY(-200%);
            transition: transform 0.2s ease;
        }
        .skip-link:focus {
            transform: translateY(0);
            outline: 3px solid var(--gold);
        }

        /* Site Header */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-light);
            transition: all 0.2s ease;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 72px;
            gap: 1.5rem;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: var(--radius-full);
            background: #ffffff;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
        }

        .brand-name {
            font-size: 1.125rem;
            font-weight: 800;
            color: var(--primary-dark);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .brand-tag {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-subtle);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            padding: 0.5rem 0.85rem;
            font-size: 0.925rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: var(--radius-sm);
            transition: color 0.15s ease, background 0.15s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            background: var(--bg-subtle);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.15rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.18s ease;
            white-space: nowrap;
        }

        .btn:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 138, 69, 0.15);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 138, 69, 0.22);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--text-main);
            border-color: var(--border-light);
        }

        .btn-outline:hover {
            background-color: var(--bg-subtle);
            border-color: #cbd5e1;
            color: var(--primary);
        }

        .btn-secondary {
            background-color: var(--bg-subtle);
            color: var(--text-main);
            border-color: var(--border-light);
        }

        .btn-secondary:hover {
            background-color: #f1f5f9;
            color: var(--primary);
        }

        .btn-lg {
            padding: 0.8rem 1.6rem;
            font-size: 1rem;
            border-radius: var(--radius-md);
        }

        /* Hero Section */
        .hero {
            padding: 4.5rem 0 3.5rem;
            background: var(--bg-white);
            border-bottom: 1px solid var(--border-subtle);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 3.5rem;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            background-color: var(--primary-light);
            border: 1px solid var(--primary-border);
            border-radius: var(--radius-full);
            color: var(--primary-dark);
            font-size: 0.825rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            background-color: var(--primary);
            border-radius: var(--radius-full);
        }

        .hero-title {
            font-size: clamp(2.25rem, 4vw, 3.25rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.025em;
            color: var(--text-main);
            margin-bottom: 1.25rem;
        }

        .hero-title span {
            color: var(--primary);
        }

        .hero-desc {
            font-size: 1.125rem;
            line-height: 1.65;
            color: var(--text-muted);
            margin-bottom: 2rem;
            max-width: 580px;
        }

        .hero-cta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 2rem;
        }

        .hero-note {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            color: var(--text-subtle);
        }

        .hero-note i {
            color: var(--primary);
        }

        /* Hero Preview Card (White, Clean, Elegant) */
        .hero-visual {
            display: flex;
            justify-content: center;
        }

        .hero-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
            position: relative;
        }

        .hero-seal-wrapper {
            width: 96px;
            height: 96px;
            margin: 0 auto 1.25rem;
            padding: 6px;
            background: #ffffff;
            border-radius: var(--radius-full);
            border: 2px solid var(--primary-border);
            box-shadow: var(--shadow-sm);
        }

        .hero-seal-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .hero-card-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.35rem;
        }

        .hero-card-subtitle {
            font-size: 0.875rem;
            color: var(--text-subtle);
            margin-bottom: 1.5rem;
        }

        .hero-card-pills {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            text-align: left;
        }

        .card-pill {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.9rem;
            background: var(--bg-subtle);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .card-pill i {
            color: var(--primary);
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .section-header {
            text-align: center;
            max-width: 640px;
            margin: 0 auto 2.5rem;
        }

        .section-title {
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
            line-height: 1.25;
            margin-bottom: 0.65rem;
        }

        .section-desc {
            font-size: 1rem;
            color: var(--text-muted);
        }

        /* Core Services / Modules Section */
        .features-section {
            padding: 4.5rem 0;
            background: var(--bg-subtle);
            border-bottom: 1px solid var(--border-light);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-border);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: var(--primary-light);
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .feature-card:nth-child(2) .feature-icon {
            background: #e0f2fe;
            color: #0284c7;
        }

        .feature-card:nth-child(3) .feature-icon {
            background: #fef3c7;
            color: #d97706;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.65rem;
        }

        .feature-text {
            font-size: 0.925rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Workflow Section (Simple 4-Step Process) */
        .workflow-section {
            padding: 4.5rem 0;
            background: var(--bg-white);
            border-bottom: 1px solid var(--border-subtle);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            position: relative;
        }

        .step-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 1.75rem 1.25rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .step-num {
            width: 40px;
            height: 40px;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-full);
            background: var(--primary);
            color: #ffffff;
            font-weight: 800;
            font-size: 1rem;
        }

        .step-card:nth-child(2) .step-num {
            background: #0284c7;
        }
        .step-card:nth-child(3) .step-num {
            background: #0d9488;
        }
        .step-card:nth-child(4) .step-num {
            background: #d97706;
        }

        .step-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .step-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Roles / User Access Section */
        .roles-section {
            padding: 4.5rem 0;
            background: var(--bg-subtle);
            border-bottom: 1px solid var(--border-light);
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .role-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .role-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .role-icon {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: var(--primary-light);
            color: var(--primary);
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .role-card:nth-child(2) .role-icon {
            background: #e0f2fe;
            color: #0284c7;
        }

        .role-card:nth-child(3) .role-icon {
            background: #fef3c7;
            color: #d97706;
        }

        .role-name {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .role-desc {
            font-size: 0.825rem;
            color: var(--text-subtle);
        }

        .role-points {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .role-points li {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .role-points li i {
            color: var(--primary);
            font-size: 0.75rem;
            margin-top: 0.35rem;
            flex-shrink: 0;
        }

        /* Simple CTA Box (White, Clean) */
        .cta-section {
            padding: 4.5rem 0;
            background: #ffffff;
        }

        .cta-box {
            background: #ffffff;
            border: 1px solid var(--primary-border);
            border-radius: var(--radius-lg);
            padding: 3rem 2.5rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            max-width: 840px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .cta-text {
            font-size: 1.05rem;
            color: var(--text-muted);
            max-width: 580px;
            margin: 0 auto 1.75rem;
        }

        .cta-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Site Footer (Clean, White) */
        .site-footer {
            background: #ffffff;
            border-top: 1px solid var(--border-light);
            padding: 2.5rem 0 2rem;
            font-size: 0.875rem;
            color: var(--text-subtle);
        }

        .footer-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .footer-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .footer-copy {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .footer-copy strong {
            color: var(--text-main);
            font-weight: 700;
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 2.5rem;
                text-align: center;
            }

            .hero-content {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .hero-desc {
                margin-inline: auto;
            }

            .hero-cta {
                justify-content: center;
            }

            .features-grid,
            .roles-grid {
                grid-template-columns: 1fr;
            }

            .steps-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .brand-tag {
                display: none;
            }

            .hero {
                padding: 3rem 0 2.5rem;
            }

            .steps-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .footer-brand {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .hero-cta {
                flex-direction: column;
                width: 100%;
            }

            .hero-cta .btn {
                width: 100%;
            }

            .cta-actions {
                flex-direction: column;
                width: 100%;
            }

            .cta-actions .btn {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <!-- Top Navigation -->
    <header class="site-header">
        <div class="container nav" aria-label="Main navigation">
            <a class="brand" href="{{ url('/') }}" aria-label="SKSUScholarSync Home">
                <img class="brand-logo" src="{{ asset('images/sksu-seal.png') }}" alt="Sultan Kudarat State University seal" width="44" height="44">
                <div class="brand-text">
                    <span class="brand-name">SKSUScholarSync</span>
                    <span class="brand-tag">Sultan Kudarat State University</span>
                </div>
            </a>

            <nav class="nav-menu" aria-label="Site sections">
                <a class="nav-link" href="#services">Services</a>
                <a class="nav-link" href="#workflow">Workflow</a>
                <a class="nav-link" href="#roles">Portals</a>
            </nav>

            <div class="nav-actions">
                @if (Route::has('login'))
                    @auth
                        <a class="btn btn-primary" href="{{ url('/dashboard') }}">
                            <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                            <span>Dashboard</span>
                        </a>
                    @else
                        <a class="btn btn-outline" href="{{ route('login') }}">
                            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                            <span>Sign in</span>
                        </a>
                        @if (Route::has('register'))
                            <a class="btn btn-primary" href="{{ route('register') }}">
                                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                                <span>Register</span>
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <main id="main-content">
        <!-- Hero Section -->
        <section class="hero" aria-labelledby="hero-heading">
            <div class="container hero-grid">
                <div class="hero-content">
                    <div class="hero-badge">
                        <span class="badge-dot" aria-hidden="true"></span>
                        <span>SKSU Scholarship Operations Portal</span>
                    </div>

                    <h1 id="hero-heading" class="hero-title">
                        Simplifying Scholarship Services for <span>SKSU</span> Scholars
                    </h1>

                    <p class="hero-desc">
                        A clean, centralized portal for student certificate requests, official receipt verification, agency masterlist validation, and institutional scholarship tracking.
                    </p>

                    <div class="hero-cta" aria-label="Primary actions">
                        @if (Route::has('login'))
                            @auth
                                <a class="btn btn-primary btn-lg" href="{{ url('/dashboard') }}">
                                    <span>Open Dashboard</span>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            @else
                                <a class="btn btn-primary btn-lg" href="{{ route('login') }}">
                                    <span>Access Portal</span>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                                @if (Route::has('register'))
                                    <a class="btn btn-secondary btn-lg" href="{{ route('register') }}">
                                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                                        <span>Create Account</span>
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>

                    <div class="hero-note">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>Unified system supporting all campuses of Sultan Kudarat State University</span>
                    </div>
                </div>

                <div class="hero-visual" aria-hidden="true">
                    <div class="hero-card">
                        <div class="hero-seal-wrapper">
                            <img class="hero-seal-img" src="{{ asset('images/sksu-seal.png') }}" alt="" width="96" height="96">
                        </div>
                        <h2 class="hero-card-title">Sultan Kudarat State University</h2>
                        <p class="hero-card-subtitle">Scholarship Information & Verification System</p>

                        <div class="hero-card-pills">
                            <div class="card-pill">
                                <i class="fa-solid fa-file-circle-check" aria-hidden="true"></i>
                                <span>Certificate of No Scholarship Requests</span>
                            </div>
                            <div class="card-pill">
                                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                <span>Official Receipt (OR) Upload & Review</span>
                            </div>
                            <div class="card-pill">
                                <i class="fa-solid fa-users-viewfinder" aria-hidden="true"></i>
                                <span>Automated Masterlist Enrollment Matching</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Core Services -->
        <section id="services" class="features-section" aria-labelledby="services-heading">
            <div class="container">
                <div class="section-header">
                    <h2 id="services-heading" class="section-title">Core Services</h2>
                    <p class="section-desc">Essential modules designed to make scholarship management seamless and accountable.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <h3 class="feature-title">Certificate Requests</h3>
                        <p class="feature-text">
                            Students can easily request a Certificate of No Scholarship, upload their payment receipt, and track verification in real-time.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <h3 class="feature-title">Masterlist Validation</h3>
                        <p class="feature-text">
                            Validates scholarship awardee masterlists against registrar enrollment data to prevent duplicate or ineligible grants.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <h3 class="feature-title">Monitoring & Reports</h3>
                        <p class="feature-text">
                            Exportable PDF, Excel, and CSV reports provide complete transparency for university administrators and partner agencies.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works / Workflow -->
        <section id="workflow" class="workflow-section" aria-labelledby="workflow-heading">
            <div class="container">
                <div class="section-header">
                    <h2 id="workflow-heading" class="section-title">How It Works</h2>
                    <p class="section-desc">A straightforward 4-step process from initial submission to final certificate release.</p>
                </div>

                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-num">1</div>
                        <h3 class="step-title">Submit Request</h3>
                        <p class="step-desc">Students apply online and attach their Official Receipt for verification.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-num">2</div>
                        <h3 class="step-title">Verify Payment</h3>
                        <p class="step-desc">Administrators review receipt details and approve valid transaction records.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-num">3</div>
                        <h3 class="step-title">Validate Enrollment</h3>
                        <p class="step-desc">Campus registrars and coordinators match records with enrolled students.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-num">4</div>
                        <h3 class="step-title">Certificate Release</h3>
                        <p class="step-desc">Official certificates are generated and ready for immediate secure download.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Portals / Roles Section -->
        <section id="roles" class="roles-section" aria-labelledby="roles-heading">
            <div class="container">
                <div class="section-header">
                    <h2 id="roles-heading" class="section-title">Role-Based Portals</h2>
                    <p class="section-desc">Dedicated dashboards tailored to each stakeholder's responsibilities.</p>
                </div>

                <div class="roles-grid">
                    <div class="role-card">
                        <div class="role-header">
                            <div class="role-icon" aria-hidden="true">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                            <div>
                                <h3 class="role-name">Students</h3>
                                <p class="role-desc">Applicants & Scholars</p>
                            </div>
                        </div>
                        <ul class="role-points">
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Request Certificates of No Scholarship</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Upload Official Receipt proofs</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Track review and approval status</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Download verified PDF certificates</li>
                        </ul>
                    </div>

                    <div class="role-card">
                        <div class="role-header">
                            <div class="role-icon" aria-hidden="true">
                                <i class="fa-solid fa-building-columns"></i>
                            </div>
                            <div>
                                <h3 class="role-name">Registrar & Coordinators</h3>
                                <p class="role-desc">Campus Validation Team</p>
                            </div>
                        </div>
                        <ul class="role-points">
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Access campus-scoped enrollment records</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Verify unmatched or inconsistent scholars</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Add validation remarks and decisions</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Forward validated batches to chairman</li>
                        </ul>
                    </div>

                    <div class="role-card">
                        <div class="role-header">
                            <div class="role-icon" aria-hidden="true">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div>
                                <h3 class="role-name">Admin & Chairman</h3>
                                <p class="role-desc">Approval & Oversight</p>
                            </div>
                        </div>
                        <ul class="role-points">
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Verify official receipt submissions</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Upload and approve agency masterlists</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Review audit trails and system activity</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Generate and export operational reports</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action (Clean White Card) -->
        <section class="cta-section" aria-labelledby="cta-heading">
            <div class="container">
                <div class="cta-box">
                    <h2 id="cta-heading" class="cta-title">Ready to access your scholarship portal?</h2>
                    <p class="cta-text">
                        Sign in with your university credentials or register a new student account to submit your certificate requests.
                    </p>
                    <div class="cta-actions">
                        @if (Route::has('login'))
                            @auth
                                <a class="btn btn-primary btn-lg" href="{{ url('/dashboard') }}">
                                    <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                                    <span>Go to Dashboard</span>
                                </a>
                            @else
                                <a class="btn btn-primary btn-lg" href="{{ route('login') }}">
                                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                                    <span>Sign in to Account</span>
                                </a>
                                @if (Route::has('register'))
                                    <a class="btn btn-secondary btn-lg" href="{{ route('register') }}">
                                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                                        <span>Create Account</span>
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Clean White Footer -->
    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <img class="footer-logo" src="{{ asset('images/sksu-seal.png') }}" alt="" width="32" height="32">
                <div class="footer-copy">
                    <strong>SKSUScholarSync</strong>
                    <span>Sultan Kudarat State University &bull; Province of Sultan Kudarat</span>
                </div>
            </div>
            <span>Integrated Scholarship Processing, Verification, and Monitoring System</span>
        </div>
    </footer>
</body>
</html>
