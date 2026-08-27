<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    @php
        $seoTitle = $portfolio->site_title ?: ($portfolio->about?->title ?: 'Portfolio');
        $seoDescription = $portfolio->meta_description ?: ($portfolio->about?->bio ?: '');
        $seoImage = $portfolio->about?->profile_image_url ?: asset('img/shakeel1.webp');
        // Dedicated 1200x630 (1.91:1) share image — social scrapers crop
        // square/portrait photos awkwardly, so og:image/twitter:image use
        // this instead of the raw profile photo used for JSON-LD/Person.
        $ogImage = asset('img/og-default.jpg');
        // On a verified tenant custom domain the portfolio is served at "/"
        // regardless of slug, so the canonical URL should be the domain root.
        $isDefaultPortfolioForNav = $portfolio->isDefault() || request()->attributes->has('resolvedPortfolio');
        $canonicalUrl = $isDefaultPortfolioForNav
            ? url('/')
            : route('portfolio.show', $portfolio);
    @endphp

    <title>{{ $seoTitle }}</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $seoDescription }}" />
    <meta name="author" content="{{ $portfolio->user?->name }}" />
    <meta name="robots" content="index, follow" />
    <meta name="language" content="en" />

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $canonicalUrl }}" />

    <!-- Open Graph for Social Media -->
    <meta property="og:title" content="{{ $seoTitle }}" />
    <meta property="og:description" content="{{ $seoDescription }}" />
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:url" content="{{ $canonicalUrl }}" />
    <meta property="og:type" content="profile" />
    <meta property="og:site_name" content="{{ $portfolio->user?->name ?: $seoTitle }}" />
    @if ($portfolio->user?->name)
        <meta property="profile:first_name" content="{{ explode(' ', $portfolio->user->name)[0] }}" />
    @endif

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $seoTitle }}" />
    <meta name="twitter:description" content="{{ $seoDescription }}" />
    <meta name="twitter:image" content="{{ $ogImage }}" />

    <!-- llms.txt (AI crawler discovery) -->
    <link rel="llms.txt" href="{{ route('llms-txt') }}" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('img/logo/slogo.png') }}" type="image/png" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/base.css') }}?ver=3" />
    <link rel="stylesheet" href="{{ asset('css/owl-carousel.css') }}?ver=3" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?ver=3" />

    <!-- Static Header Styles -->
    <style>
        .static-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: #0a0e14;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            height: 64px;
            display: flex;
            align-items: center;
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0 6.7%; /* 12% of 56vw left-panel ≈ 6.72% of viewport */
        }
        .header-logo {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        .header-logo img {
            height: 38px;
            width: auto;
            display: block;
        }
        .header-nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 24px;
        }
        .header-nav ul li a {
            color: #999;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            font-family: 'Jost', sans-serif;
            transition: color 0.25s;
        }
        .header-nav ul li a:hover {
            color: #5eead4;
        }
        .header-cta {
            background: #14b8a6;
            color: #04161a !important;
            padding: 8px 20px;
            border-radius: 100px;
            text-decoration: none !important;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            font-family: 'Jost', sans-serif;
            transition: background 0.25s;
            flex-shrink: 0;
        }
        .header-cta:hover {
            background: #0d9488;
        }
        .static-header a.glow_button {
            display: inline-block !important;
            padding: 9px 22px !important;
            background: linear-gradient(135deg, #14b8a6 0%, #5eead4 100%) !important;
            border-radius: 50px !important;
            color: #04161a !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            animation: buttonGlow 3s ease-in-out infinite;
            transition: transform 0.3s ease;
            border: none !important;
            white-space: nowrap !important;
        }
        .static-header a.glow_button:hover {
            transform: translateY(-2px);
        }
        .static-header a.glow_button .circle {
            display: none;
        }
        .static-header a.glow_button .text {
            color: #04161a !important;
        }
        .static-header a.glow_button:before {
            display: none !important;
        }
        .resumo_fn_content {
            padding-top: 64px !important;
        }
        /* Compensate for 64px header — reduce #home top padding by 64px (was 200px) */
        #home { padding-top: 136px; }
        @media (max-width: 1040px) {
            /* Respect the theme's mobile #home reset (min-height/padding) instead of clobbering it */
            #home { padding-top: 114px; }
        }

        /* Mobile burger toggle */
        .header-burger {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            width: 40px;
            height: 40px;
            padding: 0;
            background: transparent;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
            z-index: 10001;
        }
        .header-burger span {
            display: block;
            width: 22px;
            height: 2px;
            background: #e6edf3;
            border-radius: 2px;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .header-burger.is-active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .header-burger.is-active span:nth-child(2) { opacity: 0; }
        .header-burger.is-active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* Mobile nav overlay */
        .mobile-nav-overlay {
            position: fixed;
            inset: 0;
            top: 64px;
            z-index: 9998;
            background: #0a0e14;
            background-image: radial-gradient(circle at 85% 0%, rgba(94,234,212,0.10) 0%, transparent 55%);
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.3s ease, visibility 0.3s;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .mobile-nav-overlay.is-open {
            visibility: visible;
            opacity: 1;
        }
        .mobile-nav {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 8px 7% 40px;
        }
        .mobile-nav ol {
            list-style: none;
            margin: 0;
            padding: 12px 0 0;
            display: flex;
            flex-direction: column;
        }
        .mobile-nav ol li {
            opacity: 0;
            transform: translateY(14px);
            transition: opacity 0.4s ease, transform 0.4s ease;
            transition-delay: calc(var(--i, 0) * 45ms);
        }
        .mobile-nav-overlay.is-open ol li {
            opacity: 1;
            transform: translateY(0);
        }
        .mobile-nav ol li a {
            display: flex;
            align-items: baseline;
            gap: 16px;
            padding: 17px 2px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            color: var(--text-hi, #e6edf3);
            text-decoration: none;
            font-family: 'Fraunces', 'Jost', serif;
            font-size: 24px;
            font-weight: 500;
            letter-spacing: -0.01em;
            transition: color 0.25s ease, padding-left 0.25s ease;
        }
        .mobile-nav ol li a .mobile-nav-index {
            font-family: 'Jost', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            color: #5eead4;
            flex-shrink: 0;
        }
        .mobile-nav ol li a:hover,
        .mobile-nav ol li a:active {
            color: #5eead4;
            padding-left: 10px;
        }
        .mobile-nav-cta {
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 32px;
            padding: 17px 24px;
            border-radius: 100px;
            background: linear-gradient(135deg, #14b8a6 0%, #5eead4 100%);
            color: #04161a !important;
            text-decoration: none !important;
            font-family: 'Jost', sans-serif;
            font-size: 13.5px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            box-shadow: 0 14px 32px -12px rgba(94,234,212,0.55);
            opacity: 0;
            transform: translateY(14px);
            transition: opacity 0.4s ease 0.32s, transform 0.4s ease 0.32s, box-shadow 0.25s ease;
        }
        .mobile-nav-overlay.is-open .mobile-nav-cta {
            opacity: 1;
            transform: translateY(0);
        }
        .mobile-nav-cta:active {
            box-shadow: 0 8px 20px -10px rgba(94,234,212,0.55);
        }
        body.mobile-nav-open { overflow: hidden; }

        @media (max-width: 1040px) {
            /* left panel becomes 100vw, container padding is 8% → match header */
            .header-inner { padding: 0 8%; }
        }
        @media (max-width: 900px) {
            .header-nav { display: none; }
            .header-burger { display: flex; }
        }
        @media (min-width: 901px) {
            .mobile-nav-overlay { display: none !important; }
        }
        @media (max-width: 480px) {
            .header-cta,
            .static-header a.glow_button { display: none !important; }
            .header-inner { padding: 0 10px; gap: 10px; }
            .mobile-nav-overlay { top: 64px; }
        }
    </style>

    <!-- Premium Design System -->
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,400&family=Inter:wght@400;500;600&family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-0: #0a0e14;
            --bg-1: #0d1218;
            --bg-2: #11161f;
            --surface: #141922;
            --surface-hi: #1a2029;
            --border: rgba(255,255,255,0.06);
            --border-hi: rgba(94,234,212,0.30);
            --gold: #5eead4;
            --gold-bright: #99f6e4;
            --gold-deep: #14b8a6;
            --cta-ink: #04161a;
            --text-hi: #e6edf3;
            --text: #b8bfc7;
            --text-muted: #7d8590;
            --serif: 'Fraunces', 'Jost', serif;
            --sans: 'Jost', 'Inter', sans-serif;
        }

        /* Global text polish */
        body { background: var(--bg-0); }
        .resumo_fn_page { color: var(--text); }
        .resumo_fn_page h1, .resumo_fn_page h2, .resumo_fn_page h3, .resumo_fn_page h4, .resumo_fn_page h5 { color: var(--text-hi); }

        /* Section rhythm */
        section { position: relative; }
        .resumo_fn_main_title { position: relative; }
        .resumo_fn_main_title .subtitle {
            display: inline-flex !important;
            align-items: center;
            gap: 12px;
            font-size: 12px !important;
            letter-spacing: 3px !important;
            color: var(--gold) !important;
            text-transform: uppercase;
            font-weight: 600 !important;
            font-family: var(--sans);
        }
        .resumo_fn_main_title .subtitle::before {
            content: "";
            width: 32px;
            height: 1px;
            background: linear-gradient(90deg, var(--gold), transparent);
        }
        .resumo_fn_main_title .title {
            font-family: var(--serif) !important;
            font-size: 46px !important;
            line-height: 1.08 !important;
            font-weight: 500 !important;
            color: var(--text-hi) !important;
            letter-spacing: -0.02em !important;
            margin: 18px 0 22px !important;
        }
        .resumo_fn_main_title .desc {
            font-size: 15.5px !important;
            line-height: 1.8 !important;
            color: var(--text) !important;
            max-width: 620px;
        }
        @media (max-width: 768px) {
            .resumo_fn_main_title .title { font-size: 32px !important; }
        }

        /* ============ HEADER ============ */
        .static-header {
            background: rgba(11,10,9,0.72) !important;
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            border-bottom: 1px solid var(--border) !important;
        }
        .header-nav ul li a {
            color: var(--text-muted) !important;
            font-family: var(--sans) !important;
            font-size: 12px !important;
            letter-spacing: 2px !important;
            position: relative;
        }
        .header-nav ul li a::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: -6px;
            height: 1px;
            background: var(--gold);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .header-nav ul li a:hover { color: var(--gold) !important; }
        .header-nav ul li a:hover::after { transform: scaleX(1); }
        .static-header a.glow_button {
            background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold) 100%) !important;
            box-shadow: 0 8px 24px -8px rgba(94,234,212,0.5) !important;
            animation: none !important;
            letter-spacing: 1.2px !important;
        }
        .static-header a.glow_button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px -8px rgba(94,234,212,0.7) !important;
        }

        /* ============ HERO ============ */
        #home { overflow: hidden; padding-bottom: 60px; }
        @media (max-width: 1040px) {
            #home { padding-bottom: 0; min-height: 0; }
        }
        #home::before, #home::after {
            content: ""; position: absolute; pointer-events: none; z-index: 0;
            border-radius: 50%; filter: blur(80px);
        }
        #home::before {
            top: -10%; right: -10%;
            width: 520px; height: 520px;
            background: radial-gradient(circle, rgba(94,234,212,0.22) 0%, transparent 70%);
            animation: heroGlow 14s ease-in-out infinite;
        }
        #home::after {
            bottom: -20%; left: -15%;
            width: 480px; height: 480px;
            background: radial-gradient(circle, rgba(89,61,37,0.30) 0%, transparent 70%);
            animation: heroGlow 18s ease-in-out infinite reverse;
        }
        @keyframes heroGlow {
            0%, 100% { transform: translate(0,0) scale(1); opacity: .9; }
            50% { transform: translate(40px,-30px) scale(1.15); opacity: 1; }
        }
        #home .container { position: relative; z-index: 2; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 8px 18px;
            background: rgba(94,234,212,0.08);
            border: 1px solid var(--border-hi);
            border-radius: 100px;
            color: var(--gold-bright);
            font-size: 11.5px; font-weight: 600;
            letter-spacing: 1.5px; text-transform: uppercase;
            font-family: var(--sans);
            margin-bottom: 28px;
        }
        .hero-badge .pulse-dot {
            width: 8px; height: 8px; background: #4ade80;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(74,222,128,0.7);
            animation: pulseDot 2s infinite;
        }
        @keyframes pulseDot {
            0% { box-shadow: 0 0 0 0 rgba(74,222,128,0.7); }
            70% { box-shadow: 0 0 0 10px rgba(74,222,128,0); }
            100% { box-shadow: 0 0 0 0 rgba(74,222,128,0); }
        }

        #home .resumo_fn_main_title .subtitle::before { display: none; }
        #home .resumo_fn_main_title .subtitle {
            color: var(--gold-bright) !important;
            padding-left: 0;
        }
        #home .resumo_fn_main_title .title {
            font-family: var(--serif) !important;
            font-size: 64px !important;
            line-height: 1.02 !important;
            letter-spacing: -0.03em !important;
            font-weight: 500 !important;
        }
        #home .resumo_fn_main_title .title .accent {
            background: linear-gradient(135deg, var(--gold-bright) 0%, var(--gold-deep) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-style: italic;
            font-weight: 400;
        }
        @media (max-width: 1100px) {
            #home .resumo_fn_main_title .title { font-size: 48px !important; }
        }
        @media (max-width: 640px) {
            #home .resumo_fn_main_title .title { font-size: 36px !important; }
        }
        #home .resumo_fn_main_title .desc {
            font-size: 17px !important;
            color: var(--text) !important;
            max-width: 640px;
        }
        #home .resumo_fn_main_title .desc strong { color: var(--gold-bright); font-weight: 500; }

        .hero-cta-row { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 36px; }
        .hero-cta-primary, .hero-cta-secondary {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 16px 30px; border-radius: 100px;
            font-family: var(--sans); font-size: 13.5px;
            font-weight: 600; letter-spacing: 1px; text-transform: uppercase;
            text-decoration: none !important;
            transition: all 0.3s cubic-bezier(.2,.8,.2,1);
        }
        .hero-cta-primary {
            background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold) 100%);
            color: var(--cta-ink) !important;
            box-shadow: 0 14px 40px -12px rgba(94,234,212,0.6);
        }
        .hero-cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 50px -12px rgba(94,234,212,0.8);
        }
        .hero-cta-secondary {
            background: rgba(255,255,255,0.03);
            color: var(--text-hi) !important;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .hero-cta-secondary:hover {
            border-color: var(--gold);
            color: var(--gold) !important;
            background: rgba(94,234,212,0.06);
        }

        .hero-stats {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 8px; margin-top: 54px;
            padding: 28px 0 0;
            border-top: 1px solid var(--border);
        }
        @media (max-width: 600px) { .hero-stats { grid-template-columns: repeat(2, 1fr); row-gap: 28px; } }
        .hero-stat .stat-num {
            font-family: var(--serif);
            font-size: 40px; font-weight: 500; color: var(--gold-bright);
            line-height: 1; letter-spacing: -0.02em;
        }
        .hero-stat .stat-label {
            font-size: 10.5px; color: var(--text-muted);
            letter-spacing: 2px; text-transform: uppercase;
            margin-top: 8px; font-weight: 600; font-family: var(--sans);
        }

        .hero-trust {
            margin-top: 42px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .hero-trust .trust-label {
            font-size: 10.5px; color: var(--text-muted);
            letter-spacing: 2px; text-transform: uppercase; font-weight: 600;
        }
        .hero-trust .flags { display: flex; gap: 10px; font-size: 22px; filter: saturate(1.15); }

        .hero-reassure {
            display: flex; flex-wrap: wrap; gap: 20px;
            margin-top: 18px;
        }
        .hero-reassure span {
            font-size: 12.5px;
            color: var(--text-muted);
            font-family: var(--sans);
            font-weight: 500;
            letter-spacing: 0.2px;
        }
        .hero-reassure span::first-letter { color: var(--gold); }

        /* ============ ABOUT ============ */
        #about .resumo_fn_about_info {
            display: block !important;
            margin-top: 40px;
            padding: 0 !important;
        }
        #about .about_left { width: 100% !important; }
        #about .about_right {
            width: 100% !important;
            margin-top: 24px !important;
            display: flex !important;
            justify-content: flex-start !important;
        }
        .about-info-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;
        }
        @media (max-width: 640px) { .about-info-grid { grid-template-columns: 1fr; } }
        .about-info-grid .info-card {
            background: linear-gradient(180deg, rgba(42,37,30,0.5), rgba(32,29,24,0.4));
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 20px;
            transition: border-color 0.3s, transform 0.3s;
        }
        .about-info-grid .info-card:hover {
            border-color: var(--border-hi);
            transform: translateY(-2px);
        }
        .about-info-grid .info-card .label {
            font-size: 10.5px; color: var(--gold);
            letter-spacing: 2px; text-transform: uppercase;
            font-weight: 600; margin-bottom: 8px; font-family: var(--sans);
        }
        .about-info-grid .info-card .val {
            color: var(--text-hi); font-size: 14.5px; line-height: 1.55;
            font-family: var(--sans); font-weight: 500;
        }
        .about-info-grid .info-card .val a { color: var(--text-hi); }
        .about-info-grid .info-card .val a:hover { color: var(--gold); }

        /* Kill the legacy template cv btn entirely so nothing leaks through */
        .resumo_fn_cv_btn { display: none !important; }

        /* About CTA bar */
        .about-cta-bar {
            width: 100%;
            margin-top: 8px;
            padding: 24px 28px;
            background: linear-gradient(135deg, rgba(94,234,212,0.08) 0%, rgba(20,184,166,0.03) 100%);
            border: 1px solid var(--border-hi);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }
        .about-cta-text { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
        .about-cta-text strong {
            font-family: var(--serif);
            font-size: 19px;
            font-weight: 500;
            color: var(--text-hi);
            letter-spacing: -0.01em;
        }
        .about-cta-text span {
            font-size: 13.5px;
            color: var(--text);
            font-family: var(--sans);
        }
        .about-cta-actions { display: flex; gap: 12px; flex-wrap: wrap; }

        .btn-primary-inline, .btn-ghost-inline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 100px;
            font-family: var(--sans);
            font-size: 12.5px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, background 0.3s ease;
            line-height: 1;
            white-space: nowrap;
        }
        .btn-primary-inline {
            background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold) 100%);
            color: var(--cta-ink) !important;
            box-shadow: 0 10px 30px -12px rgba(94,234,212,0.5);
        }
        .btn-primary-inline:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 40px -12px rgba(94,234,212,0.7);
        }
        .btn-ghost-inline {
            background: transparent;
            color: var(--text-hi) !important;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .btn-ghost-inline:hover {
            border-color: var(--gold);
            color: var(--gold) !important;
            background: rgba(94,234,212,0.05);
        }
        .btn-primary-inline svg, .btn-ghost-inline svg { flex-shrink: 0; }

        /* Tabs */
        .resumo_fn_tabs { margin-top: 54px; }
        .tab_header ul {
            border-bottom: 1px solid var(--border) !important;
            gap: 32px;
        }
        .tab_header ul li a {
            font-family: var(--sans) !important;
            font-size: 12px !important;
            letter-spacing: 2px !important;
            text-transform: uppercase;
            color: var(--text-muted) !important;
            padding-bottom: 16px !important;
            position: relative;
            font-weight: 600 !important;
        }
        .tab_header ul li.active a { color: var(--gold-bright) !important; }
        .tab_header ul li a::after {
            content: ""; position: absolute; left: 0; right: 0; bottom: -1px;
            height: 2px; background: var(--gold); transform: scaleX(0);
            transition: transform 0.3s;
        }
        .tab_header ul li.active a::after, .tab_header ul li a:hover::after { transform: scaleX(1); }

        /* Experience / Education cards */
        .resumo_fn_boxed_list ul { display: grid; gap: 16px; }
        .resumo_fn_boxed_list ul li .item {
            background: linear-gradient(180deg, rgba(42,37,30,0.45), rgba(32,29,24,0.3));
            border: 1px solid var(--border) !important;
            border-radius: 14px !important;
            padding: 24px 26px !important;
            transition: border-color 0.3s, transform 0.3s;
            position: relative;
        }
        .resumo_fn_boxed_list ul li .item::before {
            content: ""; position: absolute; left: 0; top: 24px; bottom: 24px;
            width: 3px; background: linear-gradient(180deg, var(--gold), transparent);
            border-radius: 2px;
        }
        .resumo_fn_boxed_list ul li .item:hover {
            border-color: var(--border-hi) !important;
            transform: translateY(-2px);
        }
        .resumo_fn_boxed_list .item_top h5 {
            color: var(--gold) !important;
            font-size: 12px !important;
            letter-spacing: 2px !important;
            text-transform: uppercase;
            font-family: var(--sans) !important;
            font-weight: 600 !important;
        }
        .resumo_fn_boxed_list .item_top span {
            color: var(--text-muted) !important;
            font-size: 12px !important; letter-spacing: 1.5px !important;
        }
        .resumo_fn_boxed_list .item h3 {
            font-family: var(--serif) !important;
            font-size: 22px !important; font-weight: 500 !important;
            color: var(--text-hi) !important; margin-top: 6px !important;
            letter-spacing: -0.01em !important;
        }
        .resumo_fn_boxed_list .item p {
            color: var(--text) !important; line-height: 1.75 !important;
            font-size: 14.5px !important;
        }
        .resumo_fn_boxed_list .item strong { color: var(--gold-bright); }

        /* Skills chip grid */
        .skills-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
        }
        @media (max-width: 900px) { .skills-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 520px) { .skills-grid { grid-template-columns: 1fr; } }
        .skill-chip {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 18px;
            background: linear-gradient(180deg, rgba(42,37,30,0.5), rgba(32,29,24,0.3));
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: all 0.3s;
        }
        .skill-chip:hover {
            border-color: var(--border-hi);
            transform: translateY(-2px);
            background: linear-gradient(180deg, rgba(42,37,30,0.7), rgba(32,29,24,0.5));
        }
        .skill-chip .skill-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-bright), var(--gold-deep));
            box-shadow: 0 0 12px rgba(94,234,212,0.6);
            flex-shrink: 0;
        }
        .skill-chip .skill-name {
            color: var(--text-hi); font-size: 14px;
            font-weight: 500; font-family: var(--sans);
        }

        /* ============ WHY HIRE ME ============ */
        #why { padding: 90px 0 70px; position: relative; }
        #why::before {
            content: ""; position: absolute; top: 0; left: 10%; right: 10%;
            height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
        }
        .why-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 14px !important;
            margin-top: 44px;
            width: 100%;
        }
        @media (max-width: 620px) { .why-grid { grid-template-columns: 1fr !important; } }
        .why-card {
            padding: 28px 26px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            transition: border-color 0.4s, transform 0.4s, background 0.4s;
            position: relative;
            min-width: 0;
        }
        .why-card:hover {
            border-color: var(--border-hi);
            transform: translateY(-3px);
            background: var(--surface-hi);
        }
        .why-card .why-num {
            display: inline-block;
            font-family: var(--sans);
            font-size: 11px; font-weight: 600;
            letter-spacing: 3px; text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-hi);
        }
        .why-card h4 {
            font-family: var(--serif);
            font-size: 19px; font-weight: 500;
            color: var(--text-hi); margin: 0 0 10px;
            letter-spacing: -0.01em;
            line-height: 1.3;
        }
        .why-card p {
            font-size: 14px; line-height: 1.7; color: var(--text);
            margin: 0;
        }

        /* ============ PORTFOLIO — FILTERS ============ */
        .portfolio-filters {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            margin: 32px 0 8px !important;
            width: 100% !important;
            min-width: 0;
        }
        .filter-btn {
            flex: 0 0 auto;
            min-width: max-content !important;
            width: auto !important;
            max-width: none !important;
            display: inline-flex !important;
            align-items: center;
            padding: 11px 20px;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-family: var(--sans);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.6px;
            border-radius: 100px;
            cursor: pointer;
            transition: color 0.25s, background 0.25s, border-color 0.25s;
            line-height: 1;
            white-space: nowrap !important;
            writing-mode: horizontal-tb !important;
        }
        .filter-btn:hover {
            color: var(--text-hi);
            border-color: rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.03);
        }
        .filter-btn.active {
            background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold) 100%);
            color: var(--cta-ink);
            border-color: transparent;
            box-shadow: 0 8px 24px -10px rgba(94,234,212,0.5);
        }

        /* ============ PORTFOLIO — GRID / CARDS ============ */
        #portfolio .container.noright {
            padding-right: 8% !important;
        }
        #portfolio .my__nav { display: none; }
        #portfolio .resumo_fn_main_title { margin-bottom: 10px; }

        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-top: 28px;
        }
        @media (max-width: 1200px) { .portfolio-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .portfolio-grid { grid-template-columns: 1fr; } }

        .portfolio-grid .item {
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 14px 14px 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 22px;
            cursor: pointer;
            overflow: hidden;
            transition: transform 0.45s cubic-bezier(.2,.8,.2,1), box-shadow 0.45s ease, border-color 0.4s, opacity 0.35s ease;
            opacity: 1;
        }
        .portfolio-grid .item:hover {
            transform: translateY(-6px);
            border-color: var(--border-hi);
            box-shadow: 0 30px 60px -20px rgba(0,0,0,0.65);
        }
        .portfolio-grid .item.filtering-out {
            opacity: 0;
            transform: scale(0.94);
            pointer-events: none;
        }
        .portfolio-grid .item.is-hidden {
            display: none !important;
        }

        .portfolio-grid .item .img_holder {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 10;
            border-radius: 14px;
            overflow: hidden;
            background: var(--bg-1);
            flex-shrink: 0;
        }
        .portfolio-grid .item .img_holder img { width: 100%; display: block; opacity: 0; }
        .portfolio-grid .item .img_holder .abs_img {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: top center;
            transition: transform 1s cubic-bezier(.2,.8,.2,1);
        }
        .portfolio-grid .item:hover .abs_img { transform: scale(1.06); }
        .portfolio-grid .item .img_holder::after { display: none; }

        .portfolio-grid .item .title_holder {
            position: static !important;
            padding: 22px 10px 0 !important;
            background: none !important;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .portfolio-grid .item .title_holder p {
            order: 0;
            display: inline-block;
            align-self: flex-start;
            max-width: 100%;
            font-size: 10.5px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 600;
            margin: 0 !important;
            padding: 5px 11px;
            background: rgba(94,234,212,0.08);
            border: 1px solid rgba(94,234,212,0.22);
            border-radius: 100px;
            font-family: var(--sans);
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .portfolio-grid .item .title_holder h3 {
            order: 1;
            font-family: var(--serif) !important;
            font-size: 22px !important;
            font-weight: 500 !important;
            color: var(--text-hi) !important;
            margin: 0 !important;
            line-height: 1.25 !important;
            letter-spacing: -0.01em !important;
        }
        .portfolio-grid .item .title_holder h3 a {
            color: var(--text-hi) !important;
            text-decoration: none !important;
        }
        .portfolio-grid .item .title_holder .venture-badge {
            order: -1;
            align-self: flex-start;
            display: inline-block;
            padding: 4px 11px;
            font-family: var(--sans);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gold-bright);
            background: rgba(94,234,212,0.10);
            border: 1px solid var(--border-hi);
            border-radius: 100px;
            line-height: 1.4;
        }
        .portfolio-grid .item .card-desc {
            order: 2;
            margin: 0 10px 0 !important;
            padding: 0 !important;
            color: var(--text) !important;
            font-family: var(--sans) !important;
            font-size: 14px !important;
            line-height: 1.6 !important;
        }

        .venture-grid {
            display: grid;
            width: 100%;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-top: 40px;
        }
        @media (max-width: 700px) {
            .venture-grid { grid-template-columns: minmax(0, 1fr); gap: 20px; }
        }
        .venture-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            transition: border-color 0.2s ease, transform 0.2s ease;
        }
        .venture-card:hover {
            border-color: var(--border-hi);
            transform: translateY(-3px);
        }
        .venture-card-img {
            width: 100%;
            height: 170px;
            flex-shrink: 0;
            background-size: cover;
            background-position: center;
            background-color: var(--surface-hi);
        }
        .venture-card-body {
            padding: 22px 24px 26px;
            display: flex;
            flex-direction: column;
        }
        .venture-card-body .venture-badge {
            align-self: flex-start;
            display: inline-block;
            margin-bottom: 12px;
            padding: 4px 11px;
            font-family: var(--sans);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gold-bright);
            background: rgba(94,234,212,0.10);
            border: 1px solid var(--border-hi);
            border-radius: 100px;
            line-height: 1.4;
        }
        .venture-card-body h4 {
            margin: 0 0 4px !important;
            font-family: var(--serif) !important;
            font-size: 20px !important;
            color: var(--text-hi) !important;
            overflow-wrap: break-word;
        }
        .venture-your-title {
            margin: 0 0 12px !important;
            color: var(--gold) !important;
            font-family: var(--sans) !important;
            font-size: 13px !important;
            font-weight: 500;
        }
        .venture-desc {
            margin: 0 0 16px !important;
            color: var(--text) !important;
            font-family: var(--sans) !important;
            font-size: 14px !important;
            line-height: 1.6 !important;
        }
        .venture-link {
            margin-top: auto;
            color: var(--gold-bright) !important;
            font-family: var(--sans) !important;
            font-size: 13px !important;
            font-weight: 600;
            text-decoration: none !important;
        }
        .venture-link:hover {
            color: var(--gold) !important;
        }

        .portfolio-grid .item .card-arrow {
            position: absolute;
            right: 22px;
            bottom: 22px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--surface-hi);
            border: 1px solid var(--border);
            color: var(--text-hi);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, transform 0.3s, color 0.3s, border-color 0.3s;
        }
        .portfolio-grid .item:hover .card-arrow {
            background: linear-gradient(135deg, var(--gold-deep), var(--gold));
            color: var(--cta-ink);
            border-color: transparent;
            transform: rotate(-45deg);
        }
        .portfolio-grid .item .card-arrow svg {
            width: 16px;
            height: 16px;
            transition: transform 0.3s;
        }

        /* ============ SERVICES ============ */
        #services { padding-top: 40px; }
        .resumo_fn_service_list ul {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px !important;
            list-style: none !important;
            padding: 0 !important;
            margin: 40px 0 0 !important;
        }
        @media (max-width: 900px) { .resumo_fn_service_list ul { grid-template-columns: 1fr; } }
        .resumo_fn_service_list ul li { list-style: none !important; margin: 0 !important; padding: 0 !important; }
        .resumo_fn_service_list ul li::before { display: none !important; }
        .resumo_fn_service_list ul li .item {
            padding: 28px 28px !important;
            background: linear-gradient(180deg, rgba(42,37,30,0.5), rgba(32,29,24,0.25)) !important;
            border: 1px solid var(--border) !important;
            border-radius: 16px !important;
            transition: all 0.4s cubic-bezier(.2,.8,.2,1);
            height: 100%;
            position: relative; overflow: hidden;
        }
        .resumo_fn_service_list ul li .item::after {
            content: "→";
            position: absolute; right: 24px; top: 28px;
            color: var(--gold); font-size: 20px;
            opacity: 0; transform: translateX(-10px);
            transition: all 0.4s;
        }
        .resumo_fn_service_list ul li .item:hover {
            border-color: var(--border-hi) !important;
            transform: translateY(-4px);
            background: linear-gradient(180deg, rgba(42,37,30,0.7), rgba(32,29,24,0.4)) !important;
        }
        .resumo_fn_service_list ul li .item:hover::after { opacity: 1; transform: translateX(0); }
        .resumo_fn_service_list ul li .item_left { padding: 0 !important; }
        .resumo_fn_service_list ul li .item h3 {
            font-family: var(--serif) !important;
            font-size: 21px !important; font-weight: 500 !important;
            color: var(--text-hi) !important;
            margin-bottom: 10px !important;
            letter-spacing: -0.01em !important;
        }
        .resumo_fn_service_list ul li .item p {
            font-size: 14.5px !important; line-height: 1.75 !important;
            color: var(--text) !important; margin: 0 !important;
        }

        /* ============ TESTIMONIALS ============ */
        #customers { padding: 60px 0; position: relative; }
        .testimonials-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;
            margin-top: 40px;
        }
        @media (max-width: 900px) { .testimonials-grid { grid-template-columns: 1fr; } }
        .testimonials-grid .item {
            padding: 32px 30px 28px;
            background: linear-gradient(180deg, rgba(42,37,30,0.5), rgba(32,29,24,0.25));
            border: 1px solid var(--border);
            border-radius: 16px;
            transition: all 0.4s;
            position: relative;
        }
        .testimonials-grid .item::before {
            content: "\201C";
            position: absolute;
            top: 10px; left: 22px;
            font-family: var(--serif);
            font-size: 90px; font-weight: 500;
            color: var(--gold);
            line-height: 1;
            opacity: 0.25;
        }
        .testimonials-grid .item:hover {
            border-color: var(--border-hi);
            transform: translateY(-3px);
        }
        .testimonials-grid .item .title_holder { position: relative; z-index: 1; }
        .testimonials-grid .item .desc {
            font-family: var(--serif) !important;
            font-size: 17px !important;
            line-height: 1.65 !important;
            color: var(--text-hi) !important;
            font-style: italic;
            margin: 18px 0 24px !important;
            font-weight: 400 !important;
        }
        .testimonials-grid .item .title {
            font-family: var(--sans) !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: var(--text-hi) !important;
            margin: 0 !important;
        }
        .testimonials-grid .item .subtitle {
            font-size: 12px !important;
            color: var(--gold) !important;
            letter-spacing: 1.2px !important;
            text-transform: uppercase;
            margin-top: 4px !important;
            font-weight: 600 !important;
        }
        #customers .my__nav { display: none !important; }

        /* ============ CONTACT ============ */
        #contact { padding: 80px 0 60px; position: relative; }
        #contact::before {
            content: ""; position: absolute; top: 0; left: 5%; right: 5%;
            height: 1px; background: linear-gradient(90deg, transparent, var(--border-hi), transparent);
        }
        .resumo_fn_contact {
            background: linear-gradient(180deg, rgba(42,37,30,0.4), rgba(32,29,24,0.2));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 48px 44px !important;
            position: relative; overflow: hidden;
        }
        .resumo_fn_contact::after {
            content: ""; position: absolute;
            top: -30%; right: -20%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(94,234,212,0.12), transparent 70%);
            pointer-events: none;
        }
        @media (max-width: 768px) { .resumo_fn_contact { padding: 32px 24px !important; } }
        .contact_form .input_wrapper input,
        .contact_form .input_wrapper textarea {
            background: rgba(11,10,9,0.4) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            color: var(--text-hi) !important;
            font-family: var(--sans) !important;
            transition: border-color 0.3s;
        }
        .contact_form .input_wrapper input:focus,
        .contact_form .input_wrapper textarea:focus {
            border-color: var(--gold) !important;
            outline: none !important;
        }
        #send_message {
            background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold) 100%) !important;
            color: var(--cta-ink) !important;
            border-radius: 100px !important;
            padding: 16px 36px !important;
            font-weight: 600 !important;
            letter-spacing: 1.2px !important;
            text-transform: uppercase;
            box-shadow: 0 14px 40px -12px rgba(94,234,212,0.55);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none !important;
        }
        #send_message:hover { transform: translateY(-3px); box-shadow: 0 20px 50px -12px rgba(94,234,212,0.75); }

        .resumo_fn_contact_info h3 {
            font-family: var(--sans) !important;
            color: var(--text-hi) !important;
            font-size: 17px !important;
            font-weight: 500 !important;
        }
        .resumo_fn_contact_info p {
            color: var(--gold) !important;
            font-size: 11px !important;
            letter-spacing: 2px !important;
            text-transform: uppercase;
            font-weight: 600 !important;
        }
        .resumo_fn_contact_info a.fn__link { color: var(--gold-bright) !important; font-size: 15px !important; letter-spacing: 0 !important; text-transform: none !important; }

        /* ============ RIGHT PANEL ============ */
        /* Original: padding: 100px 16%. +64px top to compensate for the fixed 64px header (right panel is position:fixed, outside .resumo_fn_content which already gets the 64px bump). */
        .resumo_fn_right .right_in {
            background: linear-gradient(180deg, var(--bg-1) 0%, var(--bg-0) 100%) !important;
            padding: 164px 16% 100px !important;
        }
        @media (max-width: 1040px) {
            .resumo_fn_right .right_in { padding: 164px 20% 100px !important; }
        }
        @media (max-width: 768px) {
            .resumo_fn_right .right_in { padding: 164px 20px 100px !important; }
        }
        .right_top { position: relative; }
        .right_top .img_holder {
            position: relative !important;
        }
        .right_top .title_holder {
            text-align: center !important;
            background: none !important;
        }
        .right_top .title_holder h5 {
            color: var(--gold) !important;
            font-size: 10.5px !important;
            letter-spacing: 3px !important;
            text-transform: uppercase !important;
            font-weight: 600 !important;
            font-family: var(--sans) !important;
            margin: 0 0 12px !important;
            opacity: 0.85;
        }
        .right_top .title_holder h3 {
            font-family: var(--serif) !important;
            color: var(--text-hi) !important;
            font-weight: 500 !important;
            font-size: 26px !important;
            letter-spacing: -0.015em !important;
            line-height: 1.2 !important;
            margin: 0 !important;
        }
        .right_bottom:empty { display: none !important; }
        .right_bottom {
            padding: 32px 0 0 !important;
            margin-top: 32px;
            border-top: 1px solid var(--border);
        }

        /* Footer */
        .footer_top .resumo_fn_totop {
            background: linear-gradient(135deg, var(--gold-deep), var(--gold)) !important;
        }

        /* Whatsapp float */
        a.float {
            box-shadow: 0 10px 30px -5px rgba(37,211,102,0.5) !important;
        }

        /* Scrollbar polish */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--bg-0); }
        ::-webkit-scrollbar-thumb { background: var(--surface-hi); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--gold-deep); }

        /* Body base font refinement */
        .resumo_fn_page p { font-family: var(--sans); }

        /* Hide legacy progress bars if they linger */
        .resumo_fn_progress_bar { display: none !important; }

        /* CTA glow (header) softer */
        @keyframes buttonGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 1; }
        }
    </style>

    <!-- Structured Data with JSON-LD -->
    @php
        // Deliberately no Review/AggregateRating markup: testimonials here
        // are curated text quotes with no genuine star-rating mechanism
        // behind them, and Google's structured-data policy treats that as
        // manipulative — a manual action would hurt ranking, not help it.
        $personSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $portfolio->user?->name,
            'url' => $canonicalUrl,
            'image' => $seoImage,
            'jobTitle' => $portfolio->hero_subtitle,
            'sameAs' => array_values(array_filter([
                $portfolio->contact_email ? 'mailto:'.$portfolio->contact_email : null,
            ])),
            'description' => $seoDescription,
        ];

        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $seoTitle,
            'url' => $canonicalUrl,
            'inLanguage' => 'en',
        ];

        $serviceSchemas = $portfolio->services->map(fn ($service) => [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service->title,
            'description' => $service->description,
            'provider' => [
                '@type' => 'Person',
                'name' => $portfolio->user?->name,
            ],
            'areaServed' => 'Worldwide',
            'serviceType' => $service->title,
        ])->values()->all();
    @endphp
    <script type="application/ld+json">{!! json_encode($personSchema, JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($websiteSchema, JSON_UNESCAPED_SLASHES) !!}</script>
    @foreach ($serviceSchemas as $serviceSchema)
        <script type="application/ld+json">{!! json_encode($serviceSchema, JSON_UNESCAPED_SLASHES) !!}</script>
    @endforeach
</head>

<body>

    <script>
        window.portfolioLogVisitorUrl = @json(route('portfolio.contact.log', $portfolio->slug));
        window.portfolioDurationUrl = @json(route('portfolio.contact.duration', $portfolio->slug));
    </script>

    <!-- Wrapper All -->
    <div class="resumo_fn_wrapper">

        <!-- Static Header -->
        <header class="static-header">
            <div class="header-inner">
                <div class="header-logo">
                    <a href="#home">
                        <img src="{{ asset('img/logo/logo.png') }}" alt="{{ $portfolio->user?->name }}" width="70" height="76" />
                    </a>
                </div>
                <nav class="header-nav">
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        @if ($portfolio->projects->contains(fn ($project) => $project->isVenture()))
                            <li><a href="#ventures">Ventures</a></li>
                        @endif
                        <li><a href="#portfolio">Portfolio</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#customers">Testimonials</a></li>
                        @if ($portfolio->posts()->published()->exists())
                            <li><a href="{{ route('portfolio.blog.index', $portfolio) }}">Blog</a></li>
                        @endif
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </nav>
                <a href="#contact" class="glow_button">
                    <span class="circle"></span>
                    <span class="text">Book a Free Consultation</span>
                </a>
                <button type="button" class="header-burger" aria-label="Open menu" aria-expanded="false" aria-controls="mobileNavMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>
        <!-- /Static Header -->

        <!-- Mobile Nav Overlay -->
        @php
            $mobileNavLinks = collect([
                ['href' => '#home', 'label' => 'Home'],
                ['href' => '#about', 'label' => 'About'],
                $portfolio->projects->contains(fn ($project) => $project->isVenture())
                    ? ['href' => '#ventures', 'label' => 'Ventures']
                    : null,
                ['href' => '#portfolio', 'label' => 'Portfolio'],
                ['href' => '#services', 'label' => 'Services'],
                ['href' => '#customers', 'label' => 'Testimonials'],
                $portfolio->posts()->published()->exists()
                    ? ['href' => route('portfolio.blog.index', $portfolio), 'label' => 'Blog']
                    : null,
                ['href' => '#contact', 'label' => 'Contact'],
            ])->filter()->values();
        @endphp
        <div class="mobile-nav-overlay" id="mobileNavMenu">
            <nav class="mobile-nav">
                <ol>
                    @foreach ($mobileNavLinks as $link)
                        <li style="--i: {{ $loop->index }}">
                            <a href="{{ $link['href'] }}">
                                <span class="mobile-nav-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="mobile-nav-label">{{ $link['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
                <a href="#contact" class="mobile-nav-cta">
                    <span>Book a Free Consultation</span>
                    <span aria-hidden="true">&nbsp;→</span>
                </a>
            </nav>
        </div>
        <!-- /Mobile Nav Overlay -->

        <!-- MODALBOX -->
        <div class="resumo_fn_modalbox">
            <a class="extra_closer" href="#"></a>
            <div class="box_inner">
                <a class="closer" href="#"><span></span></a>
                <div class="modal_content">

                    <div class="modal_in">
                        <!-- Content comes from JS -->
                    </div>

                    <div class="fn__nav" data-from="" data-index="">
                        <a href="#" class="prev">
                            <span class="text">Prev</span>
                            <span class="arrow_wrapper"><span class="arrow"></span></span>
                        </a>
                        <a href="#" class="next">
                            <span class="text">Next</span>
                            <span class="arrow_wrapper"><span class="arrow"></span></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /MODALBOX -->

        <main class="resumo_fn_content">

            <!-- Main Left Part -->
            <div class="resumo_fn_left">

                <!-- Page -->
                <div class="resumo_fn_page">


                    <!-- Home Section -->
                    <section id="home">
                        <div class="container">
                            <div class="roww">
                                <div class="resumo_fn_main_title">
                                    @if ($portfolio->hero_badge_text)
                                        <span class="hero-badge">
                                            <span class="pulse-dot"></span>
                                            {{ $portfolio->hero_badge_text }}
                                        </span>
                                    @endif
                                    @if ($portfolio->hero_subtitle)
                                        <p class="subtitle">{{ $portfolio->hero_subtitle }}</p>
                                    @endif
                                    <h1 class="title">
                                        @if ($portfolio->hero_title_accent && str_contains((string) $portfolio->hero_title, $portfolio->hero_title_accent))
                                            {!! str_replace(
                                                $portfolio->hero_title_accent,
                                                '<span class="accent">'.e($portfolio->hero_title_accent).'</span>',
                                                e($portfolio->hero_title)
                                            ) !!}
                                        @else
                                            {{ $portfolio->hero_title }}
                                        @endif
                                    </h1>
                                    @if ($portfolio->hero_description)
                                        <p class="desc">
                                            {{ $portfolio->hero_description }}
                                        </p>
                                    @endif

                                    <div class="hero-cta-row">
                                        @if ($portfolio->hero_cta_primary_label)
                                            <a href="{{ $portfolio->hero_cta_primary_href ?: '#contact' }}" class="hero-cta-primary">
                                                {{ $portfolio->hero_cta_primary_label }} &nbsp;→
                                            </a>
                                        @endif
                                        @if ($portfolio->hero_cta_secondary_label)
                                            <a href="{{ $portfolio->hero_cta_secondary_href ?: '#portfolio' }}" class="hero-cta-secondary">
                                                {{ $portfolio->hero_cta_secondary_label }}
                                            </a>
                                        @endif
                                    </div>
                                    @if (!empty($portfolio->hero_reassurance_items))
                                        <div class="hero-reassure">
                                            @foreach ($portfolio->hero_reassurance_items as $item)
                                                <span>✓ {{ $item }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (!empty($portfolio->hero_stats))
                                        <div class="hero-stats">
                                            @foreach ($portfolio->hero_stats as $stat)
                                                <div class="hero-stat">
                                                    <div class="stat-num">{{ $stat['value'] ?? '' }}</div>
                                                    <div class="stat-label">{{ $stat['label'] ?? '' }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($portfolio->trust_label || $portfolio->trust_flags)
                                        <div class="hero-trust">
                                            @if ($portfolio->trust_label)
                                                <span class="trust-label">{{ $portfolio->trust_label }}</span>
                                            @endif
                                            @if ($portfolio->trust_flags)
                                                <span class="flags">{{ $portfolio->trust_flags }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- /Home Section -->


                    <!-- /Home Section -->


                    <!-- About Section -->
                    <section id="about">
                        <div class="container">
                            <div class="roww">

                                <!-- Main Title -->
                                @php
                                    $about = $portfolio->about;
                                @endphp
                                <div class="resumo_fn_main_title">
                                    <h2 class="subtitle">{{ $about?->heading ?: 'About Me' }}</h2>
                                    @if ($about?->title)
                                        <h3 class="title">{{ $about->title }}</h3>
                                    @endif
                                    @if ($about?->bio)
                                        <p class="desc">
                                            {{ $about->bio }}
                                        </p>
                                    @endif
                                </div>
                                <!-- /Main Title -->

                                <!-- About Information -->
                                <div class="resumo_fn_about_info">
                                    <div class="about_left">
                                        <div class="about-info-grid">
                                            @foreach (($about?->info_cards ?: []) as $card)
                                                <div class="info-card">
                                                    <div class="label">{{ $card['label'] ?? '' }}</div>
                                                    <div class="val">{{ $card['value'] ?? '' }}@if (!empty($card['subvalue']))<br><span style="color:var(--text-muted); font-size:13px;">{{ $card['subvalue'] }}</span>@endif</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="about_right">
                                        <div class="about-cta-bar">
                                            <div class="about-cta-text">
                                                <strong>{{ $about?->cta_heading }}</strong>
                                                <span>{{ $about?->cta_text }}</span>
                                            </div>
                                            <div class="about-cta-actions">
                                                <a href="#contact" class="btn-primary-inline">
                                                    Book a Free Call
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                                </a>
                                                @if ($about?->resume_url)
                                                    <a href="{{ $about->resume_url }}" download class="btn-ghost-inline">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                        Download CV
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /About Information -->


                                <!-- Tabs Shortcode -->
                                <div class="resumo_fn_tabs">

                                    <!-- Tab: Header -->
                                    <div class="tab_header">
                                        <ul>
                                            <li class="active"><a href="#tab1">Experience</a></li>
                                            <li><a href="#tab2">Education</a></li>
                                            <li><a href="#tab3">Skills</a></li>
                                        </ul>
                                    </div>
                                    <!-- /Tab: Header -->

                                    <!-- Tab: Content -->
                                    <div class="tab_content">

                                        <!-- #1 tab content (EXPERIENCE) -->
                                        <div id="tab1" class="tab_item active">
                                            <div class="resumo_fn_boxed_list">
                                                <ul>
                                                    @foreach ($portfolio->experiences as $experience)
                                                        <li>
                                                            <div class="item">
                                                                <div class="item_top">
                                                                    <p class="company">{{ $experience->company }}</p>
                                                                    <span>({{ $experience->date_range }})</span>
                                                                </div>
                                                                <h3>{{ $experience->role }}</h3>
                                                                @if ($experience->project_name)
                                                                    <strong>{{ $experience->project_name }}</strong>
                                                                @endif
                                                                <p>
                                                                    {!! nl2br(e($experience->description)) !!}
                                                                </p>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- /#1 tab content -->

                                        <!-- #2 tab content (EDUCATION) -->
                                        <div id="tab2" class="tab_item">
                                            <div class="resumo_fn_boxed_list">
                                                <ul>
                                                    @foreach ($portfolio->educations as $education)
                                                        <li>
                                                            <div class="item">
                                                                <div class="item_top">
                                                                    <p class="company">{{ $education->institution }}</p>
                                                                    <span>({{ $education->date_range }})</span>
                                                                </div>
                                                                <h3>{{ $education->degree }}</h3>
                                                                <p>{{ $education->description }}</p>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- /#2 tab content -->

                                        <!-- #3 tab content (SKILLS) -->
                                        <div id="tab3" class="tab_item">

                                            <div class="skills-grid">
                                                @foreach ($portfolio->skills as $skill)
                                                    <div class="skill-chip"><span class="skill-dot"></span><span class="skill-name">{{ $skill->name }}</span></div>
                                                @endforeach
                                            </div>

                                            <!-- Legacy progress bar (hidden via CSS) -->
                                            <div class="resumo_fn_progress_bar">

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">Laravel (PHP Framework)</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">Database Design (MySQL & PostgreSQL)
                                                        </h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">RESTful API Development</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">CodeIgniter</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">Vue.js (Frontend Integration)</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">AJAX, jQuery</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">HTML5 & CSS3 (Responsive UI)</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">OOP & MVC Architecture</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">Docker</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">Git & Version Control</h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress_item" data-value="100">
                                                    <div class="item_in">
                                                        <h3 class="progress_title">Stored Procedures & SQL Optimization
                                                        </h3>
                                                        <div class="bg_wrap">
                                                            <div class="progress_bg"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <!-- /Progress Bar -->

                                        </div>
                                        <!-- /#3 tab content -->


                                    </div>
                                    <!-- /Tab: Content -->
                                </div>
                                <!-- /Tabs Shortcode -->



                            </div>
                        </div>
                    </section>
                    <!-- /About Section -->


                    @php
                        $ventureProjects = $portfolio->projects->filter(fn ($project) => $project->isVenture());
                    @endphp
                    @if ($ventureProjects->isNotEmpty())
                        <!-- Ventures Section -->
                        <section id="ventures">
                            <div class="container">
                                <div class="roww">
                                    <div class="resumo_fn_main_title">
                                        <h2 class="subtitle">Beyond Client Work</h2>
                                        <h3 class="title">Products &amp; Companies I've Built</h3>
                                        <p class="desc">These aren't client projects — they're products I've founded, co-founded, or hold a stake in.</p>
                                    </div>

                                    <div class="venture-grid">
                                        @foreach ($ventureProjects as $venture)
                                            @php
                                                $ventureImageUrl = $venture->image_url ?: asset('img/thumb/square.jpg');
                                            @endphp
                                            <div class="venture-card">
                                                <div class="venture-card-img" style="background-image: url('{{ $ventureImageUrl }}');"></div>
                                                <div class="venture-card-body">
                                                    <span class="venture-badge">{{ $venture->role->label() }}</span>
                                                    <h4>{{ $venture->company_name ?: $venture->title }}</h4>
                                                    @if ($venture->your_title)
                                                        <p class="venture-your-title">{{ $venture->your_title }}</p>
                                                    @endif
                                                    @if ($venture->description)
                                                        <p class="venture-desc">{{ $venture->description }}</p>
                                                    @endif
                                                    @if ($venture->external_link)
                                                        <a rel="nofollow" target="_blank" href="{{ $venture->external_link }}" class="venture-link">
                                                            Visit {{ $venture->company_name ?: $venture->title }} →
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>
                        <!-- /Ventures Section -->
                    @endif


                    <!-- Why Work With Me Section -->
                    <section id="why">
                        <div class="container">
                            <div class="roww">
                                <div class="resumo_fn_main_title">
                                    <h2 class="subtitle">Why Clients Hire Me</h2>
                                    <h3 class="title">Work with someone who gets it done.</h3>
                                    <p class="desc">I bring senior-level engineering, clear communication, and startup-grade pace to every project — so you ship with confidence.</p>
                                </div>

                                <div class="why-grid">
                                    <div class="why-card">
                                        <span class="why-num">01 — Craft</span>
                                        <h4>Senior-level quality</h4>
                                        <p>6+ years shipping production Laravel in healthcare, finance, and SaaS. Clean code, tested and documented — no handoff surprises.</p>
                                    </div>
                                    <div class="why-card">
                                        <span class="why-num">02 — Mindset</span>
                                        <h4>Business-first thinking</h4>
                                        <p>I ask "why" before "how." Every feature is measured against your goals, not a spec sheet.</p>
                                    </div>
                                    <div class="why-card">
                                        <span class="why-num">03 — Communication</span>
                                        <h4>Clear and consistent</h4>
                                        <p>Async updates, zero jargon, timezone-flexible. You'll always know what's shipping, what's blocked, and when.</p>
                                    </div>
                                    <div class="why-card">
                                        <span class="why-num">04 — Longevity</span>
                                        <h4>Built to scale</h4>
                                        <p>Secure APIs, optimized queries, sensible architecture. Today's code won't be tomorrow's tech debt.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- /Why Work With Me Section -->


                    <!-- Portfolio Section -->
                    <section id="portfolio">

                        <div class="container">
                            <div class="roww">
                                <!-- Main Title -->
                                <div class="resumo_fn_main_title">
                                    <div class="my__nav">
                                        <a href="#" class="prev"><span></span></a>
                                        <a href="#" class="next"><span></span></a>
                                    </div>
                                    <h2 class="subtitle">Portfolio</h2>
                                    <h3 class="title">Featured Projects</h3>
                                </div>
                                <!-- /Main Title -->

                                <!-- Filter Tabs -->
                                @php
                                    $clientProjects = $portfolio->projects->reject(fn ($project) => $project->isVenture());
                                    $projectTags = $clientProjects
                                        ->flatMap(fn ($project) => $project->tags ?: [])
                                        ->filter()
                                        ->unique()
                                        ->values();
                                @endphp
                                <div class="portfolio-filters" id="portfolioFilters">
                                    <button type="button" class="filter-btn active" data-filter="all">All Projects</button>
                                    @foreach ($projectTags as $tag)
                                        <button type="button" class="filter-btn" data-filter="{{ $tag }}">{{ ucfirst($tag) }}</button>
                                    @endforeach
                                </div>
                                <!-- /Filter Tabs -->
                            </div>
                        </div>

                        <div class="container noright">
                            <div class="roww">

                                <div class="portfolio-grid modal_items" data-from="portfolio" data-count="{{ $clientProjects->count() }}">

                                    @foreach ($clientProjects as $project)
                                        @php
                                            $categoryAttr = implode(' ', $project->tags ?: []);
                                            $imageUrl = $project->image_url ?: asset('img/thumb/square.jpg');
                                            $imageAlt = $project->image_alt ?: ($project->title.' — screenshot');
                                            $primaryTag = $project->tags[0] ?? null;
                                            $caseStudyUrl = $isDefaultPortfolioForNav
                                                ? route('work.show', $project->slug)
                                                : route('portfolio.work.show', [$portfolio, $project->slug]);
                                        @endphp
                                        <div class="item modal_item" data-index="{{ $loop->iteration }}" data-category="{{ $categoryAttr }}">
                                            <div class="img_holder">
                                                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" loading="lazy">
                                                <div class="abs_img" data-bg-img="{{ $imageUrl }}"></div>
                                            </div>
                                            <div class="title_holder">
                                                <h3>
                                                    <a href="{{ $caseStudyUrl }}">{{ $project->title }}</a>
                                                </h3>
                                                @if ($project->description)
                                                    <p class="card-desc">{{ $project->description }}</p>
                                                @endif
                                                <p class="card-desc"><a href="{{ $caseStudyUrl }}" style="color: var(--gold, #5eead4); font-size: 13px;">Read full case study →</a></p>
                                            </div>
                                            <div class="fn__hidden">
                                                <p class="fn__cat">{{ $primaryTag ? ucfirst($primaryTag) : $project->title }}
                                                    @if ($project->external_link)
                                                        <small> (<a rel="nofollow" target="_blank" style="color: rgb(92 92 66);" href="{{ $project->external_link }}">Click Here To Visit Website</a>)</small>
                                                    @endif
                                                </p>
                                                <h3 class="fn__title">{{ $project->title }}</h3>
                                                <div class="img_holder">
                                                    <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" loading="lazy">
                                                    <div class="abs_img" data-bg-img="{{ $imageUrl }}"></div>
                                                </div>

                                                <p class="fn__desc">
                                                    {!! $project->details_html !!}
                                                </p>

                                                @if ($project->external_link)
                                                    <p class="fn__desc">
                                                    <p><a rel="nofollow" target="_blank" style="color: rgb(146, 146, 69);" href="{{ $project->external_link }}">Click Here To Visit Website</a></p>
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                </div>


                            </div>
                        </div>

                    </section>
                    <!-- /Portfolio Section -->



                    <!-- Services Section -->
                    <section id="services">
                        <div class="container">
                            <div class="roww">

                                <!-- Main Title -->
                                <div class="resumo_fn_main_title">
                                    <h2 class="subtitle">Services I Offer</h2>
                                    <h3 class="title">Turn Ideas Into Powerful Web Solutions</h3>
                                    <p class="desc">
                                        Looking to hire a Laravel developer to build a custom website, a management
                                        system, or a full SaaS platform? I help businesses and startups build modern,
                                        high-performing web applications and management systems using the Laravel
                                        framework. Whether you're launching a new product, digitizing operations with
                                        a custom management system, or scaling an existing platform, I deliver custom
                                        solutions that drive real results.
                                    </p>
                                </div>
                                <!-- /Main Title -->

                                <!-- /Services List -->
                                <div class="resumo_fn_service_list">
                                    <ul>
                                        @foreach ($portfolio->services as $service)
                                            <li>
                                                <div class="item">
                                                    <div class="item_left">
                                                        <h3>{{ $service->title }}</h3>
                                                        <p>{{ $service->description }}</p>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- /Services Section -->




                    <!-- Customers Section -->
                    <!-- Customers Section -->
                    <section id="customers">
                        <div class="container">
                            <div class="roww">

                                <!-- Main Title -->
                                <div class="resumo_fn_main_title">
                                    <h2 class="subtitle">Testimonials</h2>
                                    <h3 class="title">What People Say About My Work</h3>
                                </div>
                                <!-- /Main Title -->

                                <!-- Testimonials -->
                                <div class="resumo_fn_testimonials">
                                    <div class="my__nav" style="display:none">
                                        <a href="#" class="prev"><span></span></a>
                                        <a href="#" class="next"><span></span></a>
                                    </div>
                                    <div class="testimonials-grid">

                                        @foreach ($portfolio->testimonials as $testimonial)
                                            <div class="item">
                                                <div class="title_holder">
                                                    <p class="desc">&ldquo;{{ $testimonial->quote }}&rdquo;</p>
                                                    <p class="title">{{ $testimonial->name }}</p>
                                                    <p class="subtitle">{{ $testimonial->role_company }}</p>
                                                </div>
                                            </div>
                                        @endforeach

                                    </div>
                                </div>
                                <!-- /Testimonials -->

                            </div>
                        </div>
                    </section>
                    <!-- /Customers Section -->

                    <!-- /Customers Section -->




                    <!-- News Section -->

                    <!-- /News Section -->


                    <!-- Contact Section -->
                    <section id="contact">
                        <div class="container">
                            <div class="roww resumo_fn_contact">

                                <!-- Main Title -->
                                <div class="resumo_fn_main_title">
                                    <h2 class="subtitle">Let’s Connect</h2>
                                    <h3 class="title">Book a Free Consultation</h3>
                                    <p class="desc">Let’s discuss your project goals and challenges in a quick consultation. I’ll review your requirements, share insights, and guide you on the best technical approach for your Laravel or web application project. Fill out the form below to schedule your free consultation. I typically respond within a few hours.</p>
                                </div>
                                <!-- /Main Title -->

                                <!-- Contact Form -->
                                <form class="contact_form" id="contactForm" action="/" method="post" autocomplete="off"
                                    data-email="{{ $portfolio->contact_email }}"
                                    data-action-url="{{ route('portfolio.contact.send', $portfolio->slug) }}">

                                    <!--
                                    Don't remove below code in avoid to work contact form properly.
                                    You can chance dat-success value with your one. It will be used when user will try to contact via contact form and will get success message.
                                -->
                                    <div class="success"
                                        data-success="Your message has been received, I will contact you soon."></div>
                                    <div class="empty_notice"><span>Please Fill Required Fields!</span></div>
                                    <!-- -->

                                    <div class="items_wrap">
                                        <div class="items">
                                            <div class="item half">
                                                <div class="input_wrapper">
                                                    <input id="name" type="text" aria-label="Name" />
                                                    <span class="moving_placeholder">Name *</span>
                                                </div>
                                            </div>
                                            <div class="item half">
                                                <div class="input_wrapper">
                                                    <input id="email" type="email" aria-label="Email" />
                                                    <span class="moving_placeholder">Email *</span>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <div class="input_wrapper">
                                                    <input id="phone" type="number" aria-label="Phone" />
                                                    <span class="moving_placeholder">Phone *</span>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <div class="input_wrapper">
                                                    <textarea id="message" aria-label="Message"></textarea>
                                                    <span class="moving_placeholder">Message *</span>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <a id="send_message" href="#">Send Message</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <div id="formStatus" style="margin-top:10px; font-weight:bold;"></div>
                                <!-- /Contact Form -->

                                <!-- Contact Info -->
                                <div class="resumo_fn_contact_info">
                                    @if ($portfolio->whatsapp_number)
                                        <p>Phone/Whatsapp</p>
                                        <h3><a href="tel:{{ $portfolio->whatsapp_number }}">{{ $portfolio->whatsapp_number }}</a></h3>
                                    @endif
                                    @if ($portfolio->contact_email)
                                        <p><a class="fn__link"
                                                href="mailto:{{ $portfolio->contact_email }}">{{ $portfolio->contact_email }}</a></p>
                                    @endif
                                </div>
                                <!-- /Contact Info -->

                            </div>
                        </div>
                    </section>
                    <!-- /Contact Section -->



                </div>
                <!-- /Page -->


                <footer id="footer">
                    <div class="footer_top">
                        <a href="#" class="resumo_fn_totop" aria-label="Back to top"><span></span></a>
                    </div>
                    <div class="footer_content">
                        <div class="container">
                            <!-- <p>Copyright © 2021. All rights reserved. <br /> Designed &amp; Developed by <a class="fn__link" href="https://boransic.com/" target="_blank">Boransic Technologies</a></p> -->
                        </div>
                    </div>
                </footer>


            </div>
            <!-- /Main Left Part -->

            <!-- Main Right Part -->
            <div class="resumo_fn_right">

                <!-- Panel Content -->
                <div class="right_in">
                    <div class="right_top">
                        <div class="border1"></div>
                        <div class="border2"></div>

                        @php
                            $profileImageUrl = $randomProfileImage?->image_url ?: ($portfolio->about?->profile_image_url ?: asset('img/shakeel1.webp'));
                            $profileImageAlt = $randomProfileImage?->alt_text ?: ($portfolio->about?->alt_text ?: trim(($portfolio->user?->name ?: '').($portfolio->hero_subtitle ? ' — '.$portfolio->hero_subtitle : '')));
                        @endphp
                        <div class="img_holder">
                            <img src="{{ $profileImageUrl }}" alt="{{ $profileImageAlt }}" fetchpriority="high">
                            <div class="abs_img" id="background-img" data-bg-img="{{ $profileImageUrl }}" style="background-image: url('{{ $profileImageUrl }}');"></div>
                        </div>
                        <div class="title_holder">
                            <p class="eyebrow">Hi There! I am</p>
                            <h3>
                                <span class="animated_title">
                                    <span class="title_in">{{ $portfolio->user?->name }}</span>
                                    @if ($portfolio->hero_subtitle)
                                        <span class="title_in">{{ $portfolio->hero_subtitle }}</span>
                                    @endif
                                </span>
                            </h3>
                        </div>
                    </div>
                    <div class="right_bottom"></div>


                    @if ($portfolio->whatsapp_number)
                        <a href="https://api.whatsapp.com/send?phone={{ urlencode($portfolio->whatsapp_number) }}&text=Hello! I would like to inquire about your services."
                            class="float" target="_blank" aria-label="Chat on WhatsApp">
                            <svg class="my-float" viewBox="0 0 24 24" width="30" height="30" fill="currentColor" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12.001 2C6.478 2 2 6.477 2 12c0 1.986.579 3.836 1.578 5.396L2 22l4.735-1.552A9.953 9.953 0 0 0 12.001 22c5.523 0 10-4.477 10-10S17.524 2 12.001 2zm0 18.13a8.107 8.107 0 0 1-4.13-1.128l-.296-.176-3.06 1.004 1.02-2.987-.192-.306A8.107 8.107 0 0 1 3.87 12c0-4.484 3.647-8.13 8.131-8.13 4.483 0 8.13 3.646 8.13 8.13 0 4.483-3.647 8.13-8.13 8.13z"/>
                            </svg>
                        </a>
                    @endif


                </div>

                <!-- /Panel Content -->

            </div>
            <!-- /Main Right Part -->

        </main>



        <div class="frenify-cursor cursor-outer" data-default="yes" data-link="yes" data-slider="yes"><span
                class="fn-cursor"></span></div>
        <div class="frenify-cursor cursor-inner" data-default="yes" data-link="yes" data-slider="yes"><span
                class="fn-cursor"><span class="fn-left"></span><span class="fn-right"></span></span>
        </div>

    </div>
    <!-- /Wrapper All -->
    <!-- Profile image background is applied generically by js/init.js's BgImg() handler via the [data-bg-img] attribute. -->

    <!-- calculate my age start -->
    <script>
        // Set the birthdate to 01 July 1995
        const birthdate = new Date('1995-07-01');

        // Calculate the age in years
        const ageInMilliseconds = Date.now() - birthdate.getTime();
        const ageInYears = Math.floor(ageInMilliseconds / (1000 * 60 * 60 * 24 * 365.25));

        // Display the age on the webpage
        document.getElementById('age').textContent = ageInYears;
    </script>
    <!-- calculate my age end -->
    <!-- <script src="https://platform.linkedin.com/badges/js/profile.js" async defer type="text/javascript"></script> -->
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let page = document.title.split('|')[0].trim() || 'portfolio';
        let screenRes = window.screen.width + 'x' + window.screen.height;

        const sessionToken = (window.crypto && typeof window.crypto.randomUUID === 'function')
            ? window.crypto.randomUUID()
            : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });

        fetch(window.portfolioLogVisitorUrl + '?page=' + encodeURIComponent(page) + '&screen=' + screenRes + '&session_token=' + encodeURIComponent(sessionToken));

        const pageLoadTime = Date.now();
        let durationSent = false;

        function sendDuration() {
            const elapsedSeconds = Math.round((Date.now() - pageLoadTime) / 1000);

            if (navigator.sendBeacon) {
                navigator.sendBeacon(window.portfolioDurationUrl, new URLSearchParams({
                    session_token: sessionToken,
                    duration_seconds: elapsedSeconds,
                }));
            }

            durationSent = true;
        }

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                sendDuration();
            }
        });

        window.addEventListener('beforeunload', function() {
            if (!durationSent) {
                sendDuration();
            }
        });

        // Mobile nav toggle
        const burger = document.querySelector('.header-burger');
        const mobileNav = document.getElementById('mobileNavMenu');
        if (burger && mobileNav) {
            const closeMobileNav = () => {
                burger.classList.remove('is-active');
                burger.setAttribute('aria-expanded', 'false');
                burger.setAttribute('aria-label', 'Open menu');
                mobileNav.classList.remove('is-open');
                document.body.classList.remove('mobile-nav-open');
            };
            const openMobileNav = () => {
                burger.classList.add('is-active');
                burger.setAttribute('aria-expanded', 'true');
                burger.setAttribute('aria-label', 'Close menu');
                mobileNav.classList.add('is-open');
                document.body.classList.add('mobile-nav-open');
            };
            burger.addEventListener('click', function () {
                if (mobileNav.classList.contains('is-open')) {
                    closeMobileNav();
                } else {
                    openMobileNav();
                }
            });
            mobileNav.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', closeMobileNav);
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth > 900) closeMobileNav();
            });
        }

        // Portfolio: inject arrow button (card descriptions are now rendered server-side)
        const arrowSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>';

        const portfolioItems = document.querySelectorAll('.portfolio-grid .item');
        portfolioItems.forEach(item => {
            if (!item.querySelector('.card-arrow')) {
                const a = document.createElement('span');
                a.className = 'card-arrow';
                a.innerHTML = arrowSvg;
                item.appendChild(a);
            }
        });

        // Portfolio: smooth category filter (fade-out → reflow → staggered fade-in)
        const filterBtns = document.querySelectorAll('.filter-btn');
        let filtering = false;
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                if (filtering || this.classList.contains('active')) return;
                filtering = true;
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const filter = this.dataset.filter;

                // Phase 1: fade out all currently visible items
                const visibleNow = Array.from(portfolioItems).filter(i => !i.classList.contains('is-hidden'));
                visibleNow.forEach(i => i.classList.add('filtering-out'));

                setTimeout(() => {
                    // Phase 2: toggle visibility based on filter
                    const survivors = [];
                    portfolioItems.forEach(item => {
                        const cats = (item.dataset.category || '').split(/\s+/);
                        const match = (filter === 'all') || cats.includes(filter);
                        if (match) {
                            item.classList.remove('is-hidden');
                            survivors.push(item);
                        } else {
                            item.classList.add('is-hidden');
                        }
                        item.classList.remove('filtering-out');
                    });

                    // Phase 3: stagger-in survivors
                    survivors.forEach((item, i) => {
                        item.classList.add('filtering-out');
                        setTimeout(() => {
                            item.classList.remove('filtering-out');
                        }, 30 + i * 45);
                    });

                    setTimeout(() => { filtering = false; }, 30 + survivors.length * 45 + 400);
                }, 280);
            });
        });
    });
    </script>
    <!-- Scripts -->
    <script src="{{ asset('js/jquery.js') }}?ver=3"></script>
    <script src="{{ asset('js/typed.js') }}?ver=3"></script>
    <script src="{{ asset('js/owl-carousel.js') }}?ver=3"></script>
    <script src="{{ asset('js/waypoints.js') }}?ver=3"></script>
    <script src="{{ asset('js/init.js') }}?ver=3"></script>
    <!-- /Scripts -->
<script src="{{ asset('js/send-email.js') }}?ver=3"></script>
</body>

</html>
