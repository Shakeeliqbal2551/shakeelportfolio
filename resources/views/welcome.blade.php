<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Portfolio SaaS — Build your developer portfolio</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <style>
            :root {
                color-scheme: light dark;
                --bg: #0a0e14;
                --surface: #141922;
                --border: rgba(255,255,255,0.08);
                --text-hi: #e6edf3;
                --text: #b8bfc7;
                --text-muted: #7d8590;
                --accent: #5eead4;
                --accent-deep: #14b8a6;
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                background: var(--bg);
                color: var(--text);
                font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            }
            header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 24px 6%;
                border-bottom: 1px solid var(--border);
            }
            .brand {
                font-weight: 700;
                font-size: 18px;
                color: var(--text-hi);
                letter-spacing: -0.02em;
            }
            nav a {
                color: var(--text);
                text-decoration: none;
                font-size: 14px;
                margin-left: 20px;
                padding: 8px 18px;
                border-radius: 100px;
                border: 1px solid transparent;
                transition: all 0.2s ease;
            }
            nav a.primary {
                background: linear-gradient(135deg, var(--accent-deep) 0%, var(--accent) 100%);
                color: #04161a;
                font-weight: 600;
            }
            nav a.secondary {
                border-color: var(--border);
                color: var(--text-hi);
            }
            nav a:hover { opacity: 0.85; }
            main {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 60px 6%;
            }
            .hero { max-width: 640px; }
            .badge {
                display: inline-block;
                font-size: 12px;
                letter-spacing: 1.5px;
                text-transform: uppercase;
                color: var(--accent);
                border: 1px solid rgba(94,234,212,0.3);
                background: rgba(94,234,212,0.08);
                padding: 6px 16px;
                border-radius: 100px;
                margin-bottom: 24px;
            }
            h1 {
                font-size: 42px;
                line-height: 1.15;
                color: var(--text-hi);
                margin: 0 0 18px;
                letter-spacing: -0.02em;
            }
            p.lead {
                font-size: 16px;
                line-height: 1.7;
                color: var(--text);
                margin: 0 0 34px;
            }
            .cta-row {
                display: flex;
                gap: 14px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .cta-row a {
                text-decoration: none;
                padding: 12px 26px;
                border-radius: 100px;
                font-weight: 600;
                font-size: 14px;
            }
            .cta-row a.primary {
                background: linear-gradient(135deg, var(--accent-deep) 0%, var(--accent) 100%);
                color: #04161a;
            }
            .cta-row a.secondary {
                border: 1px solid var(--border);
                color: var(--text-hi);
            }
            footer {
                text-align: center;
                padding: 24px;
                font-size: 13px;
                color: var(--text-muted);
                border-top: 1px solid var(--border);
            }
            @media (max-width: 640px) {
                h1 { font-size: 30px; }
                header { flex-direction: column; gap: 16px; }
            }
        </style>
    </head>
    <body>
        <header>
            <span class="brand">Portfolio SaaS</span>
            <nav>
                @auth
                    <a href="{{ url('/dashboard') }}" class="primary">Go to Dashboard</a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="secondary">Log in</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="primary">Get Started</a>
                    @endif
                @endauth
            </nav>
        </header>

        <main>
            <div class="hero">
                <span class="badge">Coming Soon</span>
                <h1>Build and host your developer portfolio in minutes.</h1>
                <p class="lead">
                    This platform is being built out as a multi-tenant portfolio builder —
                    sign up, fill in your experience, projects and services, and get a
                    shareable public page instantly.
                </p>
                <div class="cta-row">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="primary">Go to Dashboard</a>
                    @else
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="primary">Create your portfolio</a>
                        @endif
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="secondary">Log in</a>
                        @endif
                    @endauth
                </div>
            </div>
        </main>

        <footer>
            &copy; {{ date('Y') }} Portfolio SaaS. All rights reserved.
        </footer>
    </body>
</html>
