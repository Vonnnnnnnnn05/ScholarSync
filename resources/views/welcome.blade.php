<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SKSUScholarSync is an integrated scholarship processing, verification, certificate generation, monitoring, and reporting system for Sultan Kudarat State University.">

    <title>SKSUScholarSync</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --sksu-green: #008a45;
            --sksu-green-dark: #034b2e;
            --sksu-mint: #dff7e9;
            --sksu-gold: #f4c430;
            --sksu-navy: #123c73;
            --sksu-maroon: #8c1f28;
            --ink: #102018;
            --muted: #5d6b64;
            --line: #d9e4dc;
            --surface: #ffffff;
            --wash: #f5f8f4;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100dvh;
            color: var(--ink);
            background: var(--wash);
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            display: block;
            max-width: 100%;
        }

        .skip-link {
            position: fixed;
            left: 1rem;
            top: 1rem;
            z-index: 100;
            transform: translateY(-160%);
            border-radius: 6px;
            background: var(--sksu-gold);
            color: #172015;
            padding: .75rem 1rem;
            font-weight: 700;
            transition: transform .18s ease;
        }

        .skip-link:focus {
            transform: translateY(0);
            outline: 3px solid #ffffff;
            outline-offset: 3px;
        }

        .page-shell {
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, 0) 24rem),
                var(--wash);
        }

        .container {
            width: min(1120px, calc(100% - 2rem));
            margin-inline: auto;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 40;
            border-bottom: 1px solid rgba(255, 255, 255, .18);
            background: rgba(3, 75, 46, .94);
            color: #ffffff;
            backdrop-filter: blur(14px);
        }

        .nav {
            display: flex;
            min-height: 72px;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            min-width: 0;
            font-weight: 800;
        }

        .brand-mark {
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            border-radius: 50%;
            background: transparent;
            object-fit: contain;
            object-position: center;
            box-shadow: 0 0 0 2px rgba(244, 196, 48, .62);
        }

        .brand-text {
            display: grid;
            gap: .05rem;
        }

        .brand-name {
            font-size: 1rem;
            letter-spacing: 0;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, .72);
            font-size: .78rem;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .nav-link,
        .button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border-radius: 8px;
            font-weight: 700;
            transition: background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
        }

        .nav-link {
            padding: .65rem .85rem;
            color: rgba(255, 255, 255, .86);
            font-size: .94rem;
        }

        .nav-link:hover,
        .nav-link:focus-visible {
            background: rgba(255, 255, 255, .12);
            color: #ffffff;
        }

        .button {
            border: 1px solid transparent;
            padding: .75rem 1rem;
            cursor: pointer;
        }

        .button:focus-visible,
        .nav-link:focus-visible {
            outline: 3px solid rgba(244, 196, 48, .85);
            outline-offset: 3px;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            background: var(--sksu-gold);
            color: #132015;
            box-shadow: 0 12px 26px rgba(244, 196, 48, .24);
        }

        .button-primary:hover,
        .button-primary:focus-visible {
            background: #ffd95a;
        }

        .button-secondary {
            border-color: rgba(255, 255, 255, .28);
            color: #ffffff;
            background: rgba(255, 255, 255, .08);
        }

        .button-secondary:hover,
        .button-secondary:focus-visible {
            border-color: rgba(255, 255, 255, .52);
            background: rgba(255, 255, 255, .14);
        }

        .button-light {
            border-color: var(--line);
            color: var(--sksu-green-dark);
            background: #ffffff;
        }

        .button-light:hover,
        .button-light:focus-visible {
            border-color: rgba(0, 138, 69, .34);
            background: var(--sksu-mint);
        }

        .hero {
            position: relative;
            isolation: isolate;
            min-height: 720px;
            color: #ffffff;
            background:
                linear-gradient(110deg, rgba(3, 75, 46, .98) 0%, rgba(4, 83, 54, .94) 48%, rgba(18, 60, 115, .9) 100%);
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background:
                linear-gradient(90deg, rgba(3, 75, 46, .96) 0%, rgba(3, 75, 46, .86) 48%, rgba(3, 75, 46, .34) 100%),
                repeating-linear-gradient(135deg, rgba(255, 255, 255, .08) 0 1px, transparent 1px 26px);
        }

        .hero::after {
            content: "";
            position: absolute;
            right: -10rem;
            bottom: -16rem;
            z-index: -1;
            width: 42rem;
            height: 42rem;
            border: 1px solid rgba(244, 196, 48, .18);
            border-radius: 50%;
            box-shadow: inset 0 0 0 4rem rgba(244, 196, 48, .05);
        }

        .hero-seal-visual {
            position: relative;
            z-index: 0;
            justify-self: end;
            width: clamp(280px, 29vw, 430px);
            aspect-ratio: 1;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, .12) 0%, rgba(255, 255, 255, .04) 52%, rgba(255, 255, 255, 0) 72%);
        }

        .hero-seal-visual::before {
            content: "";
            position: absolute;
            inset: -1.1rem;
            border: 1px solid rgba(244, 196, 48, .28);
            border-radius: 50%;
        }

        .hero-seal-visual img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            opacity: .88;
            filter: drop-shadow(0 30px 45px rgba(0, 0, 0, .28));
        }

        .hero-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.12fr) minmax(280px, .72fr);
            gap: clamp(2rem, 5vw, 5.5rem);
            min-height: calc(720px - 72px);
            align-items: center;
            padding: 5rem 0 4rem;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 760px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.2rem;
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 999px;
            background: rgba(255, 255, 255, .08);
            padding: .45rem .7rem;
            color: rgba(255, 255, 255, .9);
            font-size: .86rem;
            font-weight: 700;
        }

        .eyebrow-dot {
            width: .55rem;
            height: .55rem;
            border-radius: 50%;
            background: var(--sksu-gold);
            box-shadow: 0 0 0 .3rem rgba(244, 196, 48, .18);
        }

        h1 {
            margin: 0;
            max-width: 760px;
            font-size: clamp(3.1rem, 5.4vw, 5.35rem);
            line-height: .95;
            letter-spacing: 0;
        }

        h1 span {
            display: block;
        }

        h1 span + span {
            font-size: .9em;
        }

        .hero-copy {
            max-width: 640px;
            margin: 1.4rem 0 0;
            color: rgba(255, 255, 255, .84);
            font-size: clamp(1.05rem, 2vw, 1.3rem);
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 2rem;
        }

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1px;
            max-width: 780px;
            margin-top: 3.25rem;
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .18);
        }

        .metric {
            min-height: 104px;
            background: rgba(2, 43, 29, .56);
            padding: 1rem;
        }

        .metric strong {
            display: block;
            color: #ffffff;
            font-size: 1.6rem;
            line-height: 1.1;
        }

        .metric span {
            display: block;
            margin-top: .45rem;
            color: rgba(255, 255, 255, .74);
            font-size: .88rem;
        }

        .section {
            padding: 5rem 0;
        }

        .section-tight {
            padding-top: 3.5rem;
        }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section-kicker {
            margin: 0 0 .55rem;
            color: var(--sksu-green);
            font-size: .82rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        h2 {
            margin: 0;
            max-width: 760px;
            color: var(--ink);
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .section-note {
            max-width: 360px;
            margin: 0;
            color: var(--muted);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .feature-card,
        .role-card,
        .monitoring-panel {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: 0 18px 45px rgba(24, 48, 34, .08);
        }

        .feature-card {
            padding: 1.25rem;
        }

        .icon-box {
            display: grid;
            width: 48px;
            height: 48px;
            place-items: center;
            border-radius: 8px;
            color: var(--sksu-green-dark);
            background: var(--sksu-mint);
            font-size: 1.15rem;
        }

        .feature-card:nth-child(2) .icon-box {
            color: #4e3310;
            background: #fff3c4;
        }

        .feature-card:nth-child(3) .icon-box {
            color: var(--sksu-navy);
            background: #e5efff;
        }

        h3 {
            margin: 1.1rem 0 .55rem;
            font-size: 1.2rem;
            line-height: 1.25;
        }

        .feature-card p,
        .role-card p,
        .monitoring-panel p {
            margin: 0;
            color: var(--muted);
        }

        .workflow {
            background: #ffffff;
            border-block: 1px solid var(--line);
        }

        .timeline {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 1px;
            border: 1px solid var(--line);
            background: var(--line);
        }

        .step {
            min-height: 170px;
            background: #ffffff;
            padding: 1.15rem;
        }

        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--sksu-green-dark);
            color: #ffffff;
            font-weight: 800;
        }

        .step h3 {
            margin-top: 1.25rem;
            font-size: 1.05rem;
        }

        .step p {
            margin: 0;
            color: var(--muted);
            font-size: .95rem;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .85rem;
        }

        .role-card {
            padding: 1rem;
        }

        .role-card strong {
            display: block;
            color: var(--sksu-green-dark);
            font-size: 1rem;
        }

        .role-card p {
            margin-top: .4rem;
            font-size: .9rem;
        }

        .monitoring-layout {
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 1rem;
            align-items: stretch;
        }

        .monitoring-panel {
            padding: 1.35rem;
        }

        .report-list {
            display: grid;
            gap: .7rem;
            margin: 1.2rem 0 0;
            padding: 0;
            list-style: none;
        }

        .report-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: .8rem .9rem;
            color: var(--muted);
            background: #fbfdfb;
        }

        .status-pill {
            flex: 0 0 auto;
            border-radius: 999px;
            background: var(--sksu-mint);
            color: var(--sksu-green-dark);
            padding: .28rem .6rem;
            font-size: .75rem;
            font-weight: 800;
        }

        .dashboard-preview {
            display: grid;
            gap: .8rem;
            border: 1px solid #123c73;
            border-radius: 8px;
            background: #10263f;
            padding: 1rem;
            color: #ffffff;
            box-shadow: 0 22px 60px rgba(18, 60, 115, .18);
        }

        .preview-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, .14);
            padding-bottom: .85rem;
        }

        .preview-title {
            font-weight: 800;
        }

        .preview-badge {
            border-radius: 999px;
            background: rgba(244, 196, 48, .18);
            color: #ffe28a;
            padding: .35rem .7rem;
            font-size: .8rem;
            font-weight: 800;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .preview-stat {
            min-height: 112px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 8px;
            background: rgba(255, 255, 255, .07);
            padding: .9rem;
        }

        .preview-stat span {
            color: rgba(255, 255, 255, .68);
            font-size: .82rem;
        }

        .preview-stat strong {
            display: block;
            margin-top: .7rem;
            font-size: 1.8rem;
        }

        .preview-progress {
            height: 8px;
            margin-top: 1rem;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
        }

        .preview-progress i {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--sksu-gold);
        }

        .cta-band {
            color: #ffffff;
            background:
                linear-gradient(110deg, rgba(3, 75, 46, .98), rgba(18, 60, 115, .94)),
                url("{{ asset('images/sksu-seal.png') }}") left 8vw center / 260px no-repeat;
        }

        .cta-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            padding: 3rem 0;
        }

        .cta-inner h2 {
            color: #ffffff;
        }

        .cta-inner p {
            max-width: 620px;
            margin: .85rem 0 0;
            color: rgba(255, 255, 255, .78);
        }

        .site-footer {
            border-top: 1px solid var(--line);
            background: #ffffff;
            padding: 1.4rem 0;
            color: var(--muted);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .footer-inner strong {
            color: var(--ink);
        }

        @media (max-width: 960px) {
            .nav-links .nav-link {
                display: none;
            }

            .hero {
                min-height: auto;
                background: linear-gradient(155deg, rgba(3, 75, 46, .98), rgba(18, 60, 115, .92));
            }

            .hero::before {
                background:
                    linear-gradient(180deg, rgba(3, 75, 46, .9), rgba(3, 75, 46, .72)),
                    repeating-linear-gradient(135deg, rgba(255, 255, 255, .08) 0 1px, transparent 1px 24px);
            }

            .hero-layout {
                grid-template-columns: 1fr;
                min-height: auto;
                padding: 4rem 0 3rem;
            }

            .hero-seal-visual {
                position: absolute;
                right: -4rem;
                bottom: 2rem;
                width: 300px;
                opacity: .16;
            }

            .hero-metrics,
            .feature-grid,
            .timeline,
            .roles-grid,
            .monitoring-layout {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .section-heading,
            .cta-inner,
            .footer-inner {
                align-items: start;
                flex-direction: column;
            }

            .section-note {
                max-width: 680px;
            }
        }

        @media (max-width: 640px) {
            .container {
                width: min(100% - 1rem, 1120px);
            }

            .brand-subtitle {
                display: none;
            }

            .brand-mark {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
            }

            .nav {
                min-height: 66px;
            }

            .nav-links {
                gap: .4rem;
            }

            .nav-links .button-secondary {
                display: none;
            }

            .button {
                width: 100%;
            }

            .nav-links .button,
            .hero-actions .button {
                width: auto;
            }

            h1 {
                max-width: 100%;
                font-size: clamp(2.65rem, 13vw, 4rem);
            }

            .hero-seal-visual {
                right: -5rem;
                bottom: 3rem;
                width: 240px;
                opacity: .12;
            }

            .hero-copy {
                font-size: 1rem;
            }

            .hero-metrics,
            .feature-grid,
            .timeline,
            .roles-grid,
            .monitoring-layout,
            .preview-grid {
                grid-template-columns: 1fr;
            }

            .hero-metrics {
                margin-top: 2rem;
            }

            .metric {
                min-height: auto;
            }

            .section {
                padding: 3.5rem 0;
            }

            .cta-band {
                background:
                    linear-gradient(155deg, rgba(3, 75, 46, .98), rgba(18, 60, 115, .94)),
                    url("{{ asset('images/sksu-seal.png') }}") right -4rem bottom -4rem / 240px no-repeat;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div class="page-shell">
        <header class="site-header">
            <div class="container nav" aria-label="Main navigation">
                <a class="brand" href="{{ url('/') }}" aria-label="SKSUScholarSync home">
                    <img class="brand-mark" src="{{ asset('images/sksu-seal.png') }}" alt="Sultan Kudarat State University seal" width="54" height="54">
                    <span class="brand-text">
                        <span class="brand-name">SKSUScholarSync</span>
                        <span class="brand-subtitle">Sultan Kudarat State University</span>
                    </span>
                </a>

                <nav class="nav-links" aria-label="Landing page sections">
                    <a class="nav-link" href="#features">Features</a>
                    <a class="nav-link" href="#workflow">Workflow</a>
                    <a class="nav-link" href="#roles">Roles</a>
                    @if (Route::has('login'))
                        @auth
                            <a class="button button-primary" href="{{ url('/dashboard') }}">
                                <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                                <span>Dashboard</span>
                            </a>
                        @else
                            <a class="button button-secondary" href="{{ route('login') }}">
                                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                                <span>Sign in</span>
                            </a>
                            @if (Route::has('register'))
                                <a class="button button-primary" href="{{ route('register') }}">
                                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                                    <span>Register</span>
                                </a>
                            @endif
                        @endauth
                    @endif
                </nav>
            </div>
        </header>

        <main id="main-content">
            <section class="hero" aria-labelledby="hero-title">
                <div class="container hero-layout">
                    <div class="hero-content">
                        <div class="eyebrow">
                            <span class="eyebrow-dot" aria-hidden="true"></span>
                            Integrated scholarship operations
                        </div>
                        <h1 id="hero-title">
                            <span>SKSU</span>
                            <span>ScholarSync</span>
                        </h1>
                        <p class="hero-copy">
                            A unified portal for certificate requests, official receipt verification, agency masterlists, microservice-based scholar validation, continuing scholarship evaluation, and administrative reporting.
                        </p>

                        <div class="hero-actions" aria-label="Primary actions">
                            @if (Route::has('login'))
                                @auth
                                    <a class="button button-primary" href="{{ url('/dashboard') }}">
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                        <span>Open Dashboard</span>
                                    </a>
                                @else
                                    <a class="button button-primary" href="{{ route('login') }}">
                                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                                        <span>Access Portal</span>
                                    </a>
                                    @if (Route::has('register'))
                                        <a class="button button-secondary" href="{{ route('register') }}">
                                            <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                                            <span>Create Account</span>
                                        </a>
                                    @endif
                                @endauth
                            @endif
                            <a class="button button-secondary" href="#workflow">
                                <i class="fa-solid fa-diagram-project" aria-hidden="true"></i>
                                <span>View Workflow</span>
                            </a>
                        </div>

                        <div class="hero-metrics" aria-label="System highlights">
                            <div class="metric">
                                <strong>5</strong>
                                <span>role-based portals</span>
                            </div>
                            <div class="metric">
                                <strong>8</strong>
                                <span>report categories</span>
                            </div>
                            <div class="metric">
                                <strong>PDF</strong>
                                <span>certificate generation</span>
                            </div>
                            <div class="metric">
                                <strong>API</strong>
                                <span>masterlist validation</span>
                            </div>
                        </div>
                    </div>

                    <div class="hero-seal-visual" aria-hidden="true">
                        <img src="{{ asset('images/sksu-seal.png') }}" alt="" width="1024" height="1024">
                    </div>
                </div>
            </section>

            <section id="features" class="section section-tight">
                <div class="container">
                    <div class="section-heading">
                        <div>
                            <p class="section-kicker">Core modules</p>
                            <h2>Scholarship processing with every step recorded.</h2>
                        </div>
                        <p class="section-note">
                            SKSUScholarSync connects student requests, administrator verification, agency submissions, and leadership approval in one traceable system.
                        </p>
                    </div>

                    <div class="feature-grid">
                        <article class="feature-card">
                            <div class="icon-box" aria-hidden="true">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <h3>Certificate Requests</h3>
                            <p>Students submit requests, upload official receipts, track status, and download approved Certificates of No Scholarship.</p>
                        </article>

                        <article class="feature-card">
                            <div class="icon-box" aria-hidden="true">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <h3>Verification Flow</h3>
                            <p>Administrators review receipts, coordinators validate masterlists, and the chairman approves final scholar records.</p>
                        </article>

                        <article class="feature-card">
                            <div class="icon-box" aria-hidden="true">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <h3>Monitoring and Reports</h3>
                            <p>Dashboards and exports summarize requests, scholars, masterlists, evaluations, fund sources, and decisions.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="workflow" class="section workflow">
                <div class="container">
                    <div class="section-heading">
                        <div>
                            <p class="section-kicker">Process map</p>
                            <h2>A clearer path from request to release.</h2>
                        </div>
                        <p class="section-note">
                            Each phase has a defined owner, status, and output so scholarship transactions are easier to review.
                        </p>
                    </div>

                    <div class="timeline" aria-label="Scholarship workflow">
                        <article class="step">
                            <span class="step-number">1</span>
                            <h3>Submit</h3>
                            <p>Students request certificates or upload continuing scholarship requirements.</p>
                        </article>
                        <article class="step">
                            <span class="step-number">2</span>
                            <h3>Verify</h3>
                            <p>Administrators check official receipts and update request decisions.</p>
                        </article>
                        <article class="step">
                            <span class="step-number">3</span>
                            <h3>Validate</h3>
                            <p>Agency masterlists are compared against enrolled student records.</p>
                        </article>
                        <article class="step">
                            <span class="step-number">4</span>
                            <h3>Approve</h3>
                            <p>Coordinators forward validated results for chairman approval.</p>
                        </article>
                        <article class="step">
                            <span class="step-number">5</span>
                            <h3>Release</h3>
                            <p>Certificates, final records, notifications, and reports become available.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="roles" class="section">
                <div class="container">
                    <div class="section-heading">
                        <div>
                            <p class="section-kicker">Role portals</p>
                            <h2>Built around the people who keep scholarships moving.</h2>
                        </div>
                    </div>

                    <div class="roles-grid">
                        <article class="role-card">
                            <strong>Student</strong>
                            <p>Request certificates, upload files, track status, and receive results.</p>
                        </article>
                        <article class="role-card">
                            <strong>Administrator</strong>
                            <p>Manage users, verify receipts, monitor transactions, and generate reports.</p>
                        </article>
                        <article class="role-card">
                            <strong>Agency</strong>
                            <p>Upload scholar masterlists and receive validated scholar records.</p>
                        </article>
                        <article class="role-card">
                            <strong>Coordinator</strong>
                            <p>Review validation results and prepare records for final approval.</p>
                        </article>
                        <article class="role-card">
                            <strong>Chairman</strong>
                            <p>Approve, reject, and release validated scholarship records.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="section workflow" aria-labelledby="monitoring-title">
                <div class="container monitoring-layout">
                    <div class="monitoring-panel">
                        <p class="section-kicker">Reports</p>
                        <h2 id="monitoring-title">Decision-ready records for scholarship operations.</h2>
                        <p>
                            Export-ready reporting supports scholar information, certificate requests, OR verification, masterlists, evaluations, fund sources, and approved or rejected transactions.
                        </p>

                        <ul class="report-list">
                            <li>
                                <span>Certificate Request Reports</span>
                                <span class="status-pill">PDF</span>
                            </li>
                            <li>
                                <span>Scholarship Masterlist Reports</span>
                                <span class="status-pill">Excel</span>
                            </li>
                            <li>
                                <span>Approved and Rejected Requests</span>
                                <span class="status-pill">CSV</span>
                            </li>
                        </ul>
                    </div>

                    <div class="dashboard-preview" aria-label="Monitoring dashboard preview">
                        <div class="preview-topbar">
                            <span class="preview-title">Monitoring Overview</span>
                            <span class="preview-badge">Live status</span>
                        </div>
                        <div class="preview-grid">
                            <div class="preview-stat">
                                <span>Pending certificate requests</span>
                                <strong>24</strong>
                                <div class="preview-progress" aria-hidden="true"><i style="width: 58%"></i></div>
                            </div>
                            <div class="preview-stat">
                                <span>Verified official receipts</span>
                                <strong>86</strong>
                                <div class="preview-progress" aria-hidden="true"><i style="width: 82%"></i></div>
                            </div>
                            <div class="preview-stat">
                                <span>Uploaded masterlists</span>
                                <strong>12</strong>
                                <div class="preview-progress" aria-hidden="true"><i style="width: 46%"></i></div>
                            </div>
                            <div class="preview-stat">
                                <span>Approved records</span>
                                <strong>148</strong>
                                <div class="preview-progress" aria-hidden="true"><i style="width: 74%"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cta-band" aria-labelledby="cta-title">
                <div class="container cta-inner">
                    <div>
                        <p class="section-kicker" style="color: #ffe28a;">Ready for implementation</p>
                        <h2 id="cta-title">Start with role-based access, then build each module phase by phase.</h2>
                        <p>Phase tracking can follow the project plan from setup, certificates, verification, masterlists, microservice validation, approvals, evaluations, monitoring, and reports.</p>
                    </div>
                    @if (Route::has('login'))
                        @auth
                            <a class="button button-primary" href="{{ url('/dashboard') }}">
                                <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                                <span>Go to Dashboard</span>
                            </a>
                        @else
                            <a class="button button-primary" href="{{ route('login') }}">
                                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                                <span>Enter Portal</span>
                            </a>
                        @endauth
                    @endif
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="container footer-inner">
                <div>
                    <strong>SKSUScholarSync</strong>
                    <span>for Sultan Kudarat State University scholarship operations.</span>
                </div>
                <span>Integrated Scholarship Processing, Verification, and Monitoring System</span>
            </div>
        </footer>
    </div>
</body>
</html>
