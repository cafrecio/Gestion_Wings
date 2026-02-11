<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Wings')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background-color: #07060A;
            color: #F4F5F7;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }

        .wings-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(34, 48, 74, 0.55) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(92, 52, 92, 0.35) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(34, 38, 64, 0.30) 0%, transparent 50%),
                #07060A;
        }

        .wings-hills {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1;
            pointer-events: none;
        }

        .wings-noise {
            position: fixed;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            opacity: 0.035;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 128px 128px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 20px;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .glass-card-sm {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
        }

        .wings-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 12px;
            color: #F4F5F7;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .wings-input:focus {
            outline: none;
            border-color: #E6252F;
            box-shadow: 0 0 0 3px rgba(230, 37, 47, 0.15);
        }
        .wings-input::placeholder {
            color: rgba(244, 245, 247, 0.35);
        }

        .wings-btn {
            background-color: #E6252F;
            border-radius: 12px;
            transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 8px rgba(230, 37, 47, 0.25);
        }
        .wings-btn:hover {
            background-color: #C61E27;
            box-shadow: 0 4px 16px rgba(230, 37, 47, 0.35);
        }
        .wings-btn:active { transform: scale(0.98); }
        .wings-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .wings-header {
            background: rgba(7, 6, 10, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .text-wings       { color: #F4F5F7; }
        .text-wings-muted { color: rgba(244, 245, 247, 0.55); }
        .text-wings-soft  { color: rgba(244, 245, 247, 0.70); }
    </style>
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="wings-bg"></div>

    <div class="wings-hills">
        <svg viewBox="0 0 1440 320" preserveAspectRatio="none" style="width: 100%; height: 220px; display: block;">
            <path d="M0,280 C180,180 360,220 540,200 C720,180 900,240 1080,210 C1200,190 1350,250 1440,230 L1440,320 L0,320 Z"
                  fill="rgba(34, 48, 74, 0.18)" />
            <path d="M0,290 C200,230 400,260 600,240 C800,220 1000,270 1200,250 C1320,240 1400,260 1440,255 L1440,320 L0,320 Z"
                  fill="rgba(62, 42, 72, 0.15)" />
            <path d="M0,305 C240,270 480,290 720,275 C960,260 1200,295 1440,280 L1440,320 L0,320 Z"
                  fill="rgba(7, 6, 10, 0.5)" />
        </svg>
    </div>

    <div class="wings-noise"></div>

    <div class="relative z-10 min-h-screen">
        @yield('content')
    </div>
</body>
</html>
