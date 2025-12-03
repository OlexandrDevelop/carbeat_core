<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carbeat · Переходимо до застосунку</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            text-align: center;
        }
        .card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 32px;
            padding: 2.5rem 2rem;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.6);
        }
        .logo {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #38bdf8;
            display: grid;
            place-items: center;
            margin: 0 auto 1.5rem auto;
            font-weight: 700;
            font-size: 1.25rem;
            color: #0f172a;
        }
        .actions {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .btn {
            border: none;
            border-radius: 999px;
            padding: 0.85rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .btn-primary {
            background: #38bdf8;
            color: #0f172a;
        }
        .btn-secondary {
            background: transparent;
            border: 1px solid rgba(148, 163, 184, 0.4);
            color: #cbd5f5;
        }
        .btn:active {
            transform: scale(0.97);
        }
        .status {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: rgba(248, 250, 252, 0.8);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deepLink = @json($deepLink);
            const androidStore = @json($androidStoreUrl);
            const iosStore = @json($iosStoreUrl);

            const isAndroid = /Android/i.test(navigator.userAgent);
            const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
            const fallbackUrl = isIOS ? iosStore : androidStore;

            function openApp() {
                window.location.href = deepLink;
                setTimeout(function () {
                    window.location.href = fallbackUrl;
                }, 1500);
            }

            document.getElementById('open-app').addEventListener('click', openApp);
            document.getElementById('open-store').addEventListener('click', function () {
                window.location.href = fallbackUrl;
            });

            openApp();
        });
    </script>
</head>
<body>
    <div class="card">
        <div class="logo">CB</div>
        <h1>Відкриваємо Carbeat</h1>
        <p class="status">
            Зараз спробуємо запустити застосунок.
            Якщо його немає, ми автоматично відкриємо сторінку встановлення.
        </p>
        @if($master)
            <p class="status">Майстер: <strong>{{ $master->name }}</strong></p>
        @endif
        <div class="actions">
            <button id="open-app" class="btn btn-primary">Відкрити застосунок</button>
            <button id="open-store" class="btn btn-secondary">Відкрити сторінку встановлення</button>
        </div>
    </div>
</body>
</html>

