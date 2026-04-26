@php
    $isFloxcity = $brandConfig['app'] === 'floxcity';
    $token = data_get($requestModel->meta, 'token', $requestModel->id);
    $isPending = $requestModel->status === 'pending' && (!$requestModel->expires_at || $requestModel->expires_at->isFuture());
    $expiresAt = $requestModel->expires_at?->timezone(config('app.timezone'))->format('H:i');
@endphp
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandConfig['name'] }} — Підтвердіть статус майстра</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        :root {
            --primary: {{ $isFloxcity ? '#059669' : '#0284c7' }};
            --primary-dark: {{ $isFloxcity ? '#047857' : '#0369a1' }};
            --primary-50: {{ $isFloxcity ? '#ecfdf5' : '#f0f9ff' }};
            --primary-200: {{ $isFloxcity ? '#a7f3d0' : '#bae6fd' }};
            --primary-700: {{ $isFloxcity ? '#047857' : '#0369a1' }};
            --ink: #111827;
            --muted: #4b5563;
            --subtle: #6b7280;
            --line: #e5e7eb;
            --slate-900: #0f172a;
            --slate-300: #cbd5e1;
            --green: #16a34a;
            --green-dark: #15803d;
            --green-50: #f0fdf4;
            --green-200: #bbf7d0;
            --green-800: #166534;
            --red: #ef4444;
            --red-dark: #dc2626;
            --red-50: #fef2f2;
            --red-200: #fecaca;
            --red-800: #991b1b;
            --amber-50: #fffbeb;
            --amber-200: #fde68a;
            --amber-800: #92400e;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            font-family: 'Manrope', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(ellipse 70% 50% at 80% -10%, var(--primary-50), transparent 60%),
                #ffffff;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            line-height: 1.45;
        }

        .page {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 14px 16px;
            gap: 14px;
        }

        /* HEADER */
        .site-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--ink);
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: var(--primary);
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
        }

        .brand-name {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .nav-cta {
            display: inline-flex;
            align-items: center;
            padding: 7px 14px;
            border-radius: 999px;
            background: var(--primary);
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background .16s ease;
        }

        .nav-cta:hover { background: var(--primary-dark); }

        /* MAIN GRID */
        .main {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            align-content: center;
        }

        @media (min-width: 900px) {
            .main {
                grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
                gap: 20px;
                align-items: stretch;
            }
        }

        /* LEFT COLUMN */
        .primary-col {
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: 0;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            align-self: flex-start;
            padding: 5px 12px;
            border-radius: 999px;
            background: var(--primary-50);
            color: var(--primary-700);
            box-shadow: inset 0 0 0 1px var(--primary-200);
            font-size: 12px;
            font-weight: 600;
        }

        .title {
            margin: 0;
            font-size: 26px;
            line-height: 1.1;
            letter-spacing: -0.02em;
            font-weight: 800;
        }

        .title-accent { color: var(--primary); }

        @media (min-width: 640px) { .title { font-size: 30px; } }
        @media (min-width: 900px) { .title { font-size: 34px; } }

        .subtitle {
            margin: 0;
            font-size: 14px;
            line-height: 1.55;
            color: var(--muted);
        }

        .meta-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid var(--line);
            min-width: 0;
        }

        .meta-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            border-radius: 9px;
            background: var(--primary-50);
            color: var(--primary);
        }

        .meta-icon svg { width: 16px; height: 16px; }

        .meta-text { min-width: 0; }

        .meta-label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: var(--subtle);
            text-transform: uppercase;
            letter-spacing: .05em;
            line-height: 1.2;
        }

        .meta-value {
            display: block;
            margin-top: 1px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* STATUS CARD */
        .status-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
        }

        .status-heading {
            margin: 0;
            font-size: 18px;
            line-height: 1.2;
            letter-spacing: -0.01em;
            font-weight: 800;
        }

        .status-copy {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 0;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            color: #ffffff;
            transition: transform .14s ease, filter .14s ease, box-shadow .14s ease;
        }

        .btn:hover { transform: translateY(-1px); filter: brightness(1.04); }
        .btn:active { transform: translateY(0); filter: brightness(.96); }

        .btn svg { width: 16px; height: 16px; }

        .btn-free {
            background: var(--green);
            box-shadow: 0 6px 16px rgba(22, 163, 74, 0.22);
        }
        .btn-free:hover { background: var(--green-dark); }

        .btn-busy {
            background: var(--red);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.22);
        }
        .btn-busy:hover { background: var(--red-dark); }

        .hint {
            margin: 10px 0 0;
            text-align: center;
            font-size: 12px;
            color: var(--subtle);
        }

        /* STATE */
        .state {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.5;
        }

        .state-icon {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #ffffff;
        }

        .state-icon svg { width: 16px; height: 16px; }
        .state-body strong { font-weight: 800; }

        .state-free {
            background: var(--green-50);
            border: 1px solid var(--green-200);
            color: var(--green-800);
        }
        .state-free .state-icon { color: var(--green-dark); }

        .state-busy {
            background: var(--red-50);
            border: 1px solid var(--red-200);
            color: var(--red-800);
        }
        .state-busy .state-icon { color: var(--red-dark); }

        .state-expired {
            background: var(--amber-50);
            border: 1px solid var(--amber-200);
            color: var(--amber-800);
        }
        .state-expired .state-icon { color: var(--amber-800); }

        /* DOWNLOAD CARD (right column on desktop) */
        .download {
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--slate-900);
            color: #ffffff;
            border-radius: 18px;
            padding: 18px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
        }

        .download::before {
            content: '';
            position: absolute;
            inset: auto -40px -60px auto;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--primary), transparent 70%);
            opacity: .35;
            pointer-events: none;
        }

        .download-inner {
            position: relative;
            z-index: 1;
        }

        .download-eyebrow {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--slate-300);
            font-size: 11px;
            font-weight: 600;
        }

        .download-title {
            margin: 10px 0 0;
            font-size: 18px;
            line-height: 1.2;
            letter-spacing: -0.01em;
            font-weight: 800;
        }

        .download-copy {
            margin: 8px 0 0;
            color: var(--slate-300);
            font-size: 13px;
            line-height: 1.5;
        }

        .gplay {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
            padding: 10px 16px;
            border-radius: 12px;
            background: #ffffff;
            color: var(--slate-900);
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            transition: transform .14s ease;
        }

        .gplay:hover { transform: translateY(-1px); }
        .gplay svg { width: 22px; height: 22px; flex-shrink: 0; }

        .gplay-text { text-align: left; line-height: 1.1; }
        .gplay-label { display: block; font-size: 10px; color: var(--subtle); font-weight: 500; }
        .gplay-store { display: block; margin-top: 1px; font-size: 14px; font-weight: 800; letter-spacing: -0.01em; }

        /* FOOTER */
        .site-footer {
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            padding-top: 4px;
        }

        .footer-copy {
            font-size: 11px;
            color: var(--subtle);
        }
    </style>
</head>
<body>
<div class="page">
    <header class="site-header">
        <a class="brand" href="#">
            <span class="brand-logo">{{ $brandConfig['logo'] }}</span>
            <span class="brand-name">{{ $brandConfig['name'] }}</span>
        </a>
        <a class="nav-cta" href="{{ $brandConfig['store_url'] }}" target="_blank" rel="noopener noreferrer">
            Завантажити
        </a>
    </header>

    <main class="main">
        <section class="primary-col" aria-label="Запит статусу майстра">
            <span class="eyebrow">{{ $brandConfig['hero_badge'] }}</span>

            <h1 class="title">Підтвердіть <span class="title-accent">статус</span> зараз</h1>

            <p class="subtitle">{{ $brandConfig['request_copy'] }}</p>

            <div class="meta-list" aria-label="Інформація про запит">
                <div class="meta-item">
                    <span class="meta-icon" aria-hidden="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <span class="meta-text">
                        <span class="meta-label">Майстер</span>
                        <span class="meta-value">{{ $requestModel->master->name }}</span>
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon" aria-hidden="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2M12 22a10 10 0 110-20 10 10 0 010 20z"/>
                        </svg>
                    </span>
                    <span class="meta-text">
                        <span class="meta-label">Активний до</span>
                        <span class="meta-value">{{ $expiresAt ?? '—' }}</span>
                    </span>
                </div>
            </div>

            <div class="status-card">
                <h2 class="status-heading">Ви вільні зараз?</h2>
                <p class="status-copy">Одна відповідь миттєво оновить ваш статус і сповістить клієнта.</p>

                @if ($isPending)
                    <form method="POST" action="{{ route('status-request.respond', ['token' => $token]) }}">
                        @csrf
                        <div class="actions">
                            <button type="submit" class="btn btn-free" name="answer" value="free">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Вільний
                            </button>
                            <button type="submit" class="btn btn-busy" name="answer" value="busy">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Зайнятий
                            </button>
                        </div>
                        <p class="hint">Клієнт отримає сповіщення одразу.</p>
                    </form>
                @else
                    @if ($requestModel->status === 'answered' && $requestModel->answer === 'free')
                        <div class="state state-free" role="status">
                            <span class="state-icon" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <div class="state-body">
                                Статус підтверджено: <strong>Вільний</strong>. Клієнт уже отримав сповіщення.
                            </div>
                        </div>
                    @elseif ($requestModel->status === 'answered' && $requestModel->answer === 'busy')
                        <div class="state state-busy" role="status">
                            <span class="state-icon" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </span>
                            <div class="state-body">
                                Статус підтверджено: <strong>Зайнятий</strong>. Клієнт отримав повідомлення.
                            </div>
                        </div>
                    @elseif ($requestModel->status === 'expired')
                        <div class="state state-expired" role="status">
                            <span class="state-icon" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2M12 22a10 10 0 110-20 10 10 0 010 20z"/>
                                </svg>
                            </span>
                            <div class="state-body">Час відповіді минув. Запит більше неактивний.</div>
                        </div>
                    @else
                        <div class="state state-expired" role="status">
                            <span class="state-icon" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2M12 22a10 10 0 110-20 10 10 0 010 20z"/>
                                </svg>
                            </span>
                            <div class="state-body">Цей запит уже неактивний.</div>
                        </div>
                    @endif
                @endif
            </div>
        </section>

        <aside class="download" aria-label="Завантажити додаток">
            <div class="download-inner">
                <span class="download-eyebrow">{{ $brandConfig['cta'] }}</span>
                <h2 class="download-title">Відповідайте швидше в {{ $brandConfig['name'] }}</h2>
                <p class="download-copy">{{ $brandConfig['cta_text'] }}</p>

                <a class="gplay" href="{{ $brandConfig['store_url'] }}" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 289.789 289.789" aria-hidden="true">
                        <path style="fill: #349886" d="M13.424,13.689c-0.692,2.431-1.165,5.053-1.165,8.048v246.356c0,2.95,0.473,5.544,1.138,7.948 L151.664,145.047,13.424,13.689z"/>
                        <path style="fill: #3db39e" d="M205.621,93.921L44.185,4.121C37.749,0.207,31.413-0.785,26.06,0.58l138.85,131.931 C164.91,132.511,205.621,93.921,205.621,93.921z"/>
                        <path style="fill: #f4b459" d="M265.142,127.031l-43.088-23.97l-44.135,41.804l44.954,42.733l41.923-23.023 C285.261,152.913,277.796,134.141,265.142,127.031z"/>
                        <path style="fill: #e2574c" d="M25.65,289.095c5.435,1.52,11.926,0.61,18.526-3.405l161.928-88.907L164.655,157.4 L25.65,289.095z"/>
                    </svg>
                    <span class="gplay-text">
                        <span class="gplay-label">Завантажити в</span>
                        <span class="gplay-store">Google Play</span>
                    </span>
                </a>
            </div>
        </aside>
    </main>

    <footer class="site-footer">
        <p class="footer-copy">© {{ now()->year }} {{ $brandConfig['name'] }}</p>
    </footer>
</div>
</body>
</html>
