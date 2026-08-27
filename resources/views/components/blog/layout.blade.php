<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $seoTitle }}</title>

    <meta name="description" content="{{ $seoDescription }}" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="{{ $canonicalUrl }}" />

    <meta property="og:title" content="{{ $seoTitle }}" />
    <meta property="og:description" content="{{ $seoDescription }}" />
    @if ($seoImage ?? null)
        <meta property="og:image" content="{{ $seoImage }}" />
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}" />
    <meta property="og:type" content="{{ $ogType ?? 'website' }}" />
    <meta property="og:site_name" content="{{ $portfolio->user?->name ?: $portfolio->site_title }}" />
    @if (($ogType ?? null) === 'article')
        @isset($publishedTime)
            <meta property="article:published_time" content="{{ $publishedTime }}" />
        @endisset
        @if ($portfolio->user?->name)
            <meta property="article:author" content="{{ $portfolio->user->name }}" />
        @endif
    @endif

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $seoTitle }}" />
    <meta name="twitter:description" content="{{ $seoDescription }}" />
    @if ($seoImage ?? null)
        <meta name="twitter:image" content="{{ $seoImage }}" />
    @endif

    <link rel="llms.txt" href="{{ route('llms-txt') }}" />

    <link rel="shortcut icon" href="{{ asset('img/logo/slogo.png') }}" type="image/png" />

    @isset($jsonLd)
        @foreach ((array) $jsonLd as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES) !!}</script>
        @endforeach
    @endisset

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,400&family=Inter:wght@400;500;600&family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/base.css') }}?ver=3" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?ver=3" />

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

        * { box-sizing: border-box; }
        body {
            background: var(--bg-0);
            color: var(--text);
            font-family: var(--sans);
            margin: 0;
            padding-top: 64px;
        }
        a { color: var(--gold); }
        h1, h2, h3, h4 { color: var(--text-hi); font-family: var(--serif); font-weight: 500; margin: 0 0 0.5em; }

        .static-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 9999;
            height: 64px;
            display: flex;
            align-items: center;
            background: rgba(11,10,9,0.92);
            backdrop-filter: blur(18px) saturate(140%);
            border-bottom: 1px solid var(--border);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .header-logo img { height: 34px; display: block; }
        .header-nav ul { list-style: none; display: flex; gap: 28px; margin: 0; padding: 0; }
        .header-nav ul li a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .header-nav ul li a:hover, .header-nav ul li a.active { color: var(--gold); }
        @media (max-width: 700px) { .header-nav { display: none; } }

        .blog-wrap { max-width: 780px; margin: 0 auto; padding: 64px 24px 96px; }
        .blog-list { max-width: 900px; margin: 0 auto; padding: 64px 24px 96px; }

        .blog-hero-title {
            font-size: 40px;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }
        .blog-hero-sub { color: var(--text-muted); margin-bottom: 40px; }

        .post-card {
            display: block;
            text-decoration: none;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--surface);
            padding: 24px;
            margin-bottom: 22px;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }
        .post-card:hover { transform: translateY(-3px); border-color: var(--border-hi); }
        .post-card img {
            width: 100%;
            border-radius: 12px;
            aspect-ratio: 16/9;
            object-fit: cover;
            margin-bottom: 16px;
        }
        .post-card h2 { font-size: 22px; margin-bottom: 8px; }
        .post-card .post-date { font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: var(--gold); margin-bottom: 10px; display: block; }
        .post-card p { color: var(--text); font-size: 14.5px; line-height: 1.7; margin: 0; }

        .post-detail-title { font-size: 38px; letter-spacing: -0.02em; line-height: 1.15; }
        .post-detail-meta { color: var(--text-muted); font-size: 13px; letter-spacing: 1px; text-transform: uppercase; margin: 12px 0 32px; }
        .post-detail-image { width: 100%; border-radius: 16px; margin-bottom: 32px; object-fit: cover; max-height: 420px; }
        .post-body { font-size: 16px; line-height: 1.85; color: var(--text); }
        .post-body h2, .post-body h3 { margin-top: 1.6em; }
        .post-body p { margin: 0 0 1.2em; }
        .post-body ul, .post-body ol { margin: 0 0 1.2em; padding-left: 1.5em; }
        .post-body a { color: var(--gold); }
        .post-body img { max-width: 100%; border-radius: 10px; }
        .back-link { display: inline-block; margin-bottom: 24px; color: var(--text-muted); text-decoration: none; font-size: 13px; }
        .back-link:hover { color: var(--gold); }

        footer.blog-footer {
            border-top: 1px solid var(--border);
            padding: 32px 24px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
        }
    </style>
</head>
@php
    $isDefaultPortfolio = $portfolio->isDefault() || request()->attributes->has('resolvedPortfolio');
    $homeUrl = $isDefaultPortfolio ? url('/') : route('portfolio.show', $portfolio);
    $blogIndexUrl = $isDefaultPortfolio ? route('blog.index') : route('portfolio.blog.index', $portfolio);
@endphp
<body>
    <header class="static-header">
        <div class="header-inner">
            <div class="header-logo">
                <a href="{{ $homeUrl }}">
                    <img src="{{ asset('img/logo/logo.png') }}" alt="{{ $portfolio->user?->name }}" />
                </a>
            </div>
            <nav class="header-nav">
                <ul>
                    <li><a href="{{ $homeUrl }}">Home</a></li>
                    <li><a href="{{ $blogIndexUrl }}" class="active">Blog</a></li>
                </ul>
            </nav>
        </div>
    </header>

    {{ $slot }}

    <footer class="blog-footer">
        &copy; {{ now()->year }} {{ $portfolio->user?->name }}. All rights reserved.
    </footer>
</body>
</html>
