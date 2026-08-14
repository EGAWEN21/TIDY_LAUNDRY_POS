<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if(isRTL() == true) dir="rtl" @endif>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="icon" type="image/png" href="{{ getFavIcon() }}" sizes="16x16">
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/bootstrap.min.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('assets/css/lib/apexcharts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor-katex.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor.atom-one-dark.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor.quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/full-calendar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/jquery-jvectormap-2.0.5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/prism.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/file-upload.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/audioplayer.css') }}"> -->
    <link href="{{asset('assets/plugins/toastr.min.css')}}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @vite('resources/css/app.css')
    
    <!-- PWA Manifest & Theme Color -->
    <meta name="theme-color" content="#1b2a47">
    <link rel="manifest" href="{{ url('/manifest.json') }}">
    
    <!-- iOS / Apple Meta Tags -->
    @php
        $logo192Version = file_exists(public_path('assets/images/logo-192.png')) ? filemtime(public_path('assets/images/logo-192.png')) : time();
        $logo512Version = file_exists(public_path('assets/images/logo-512.png')) ? filemtime(public_path('assets/images/logo-512.png')) : time();
        $appleTouchVersion = file_exists(public_path('assets/images/apple-touch-icon.png')) ? filemtime(public_path('assets/images/apple-touch-icon.png')) : time();
    @endphp
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/apple-touch-icon.png') }}?v={{ $appleTouchVersion }}">
    <link rel="apple-touch-startup-image" href="{{ asset('assets/images/logo-512.png') }}?v={{ $logo512Version }}">
    
    <style>
        #pwa-splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 99999;
            display: none; /* Hidden by default on website */
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease-out;
        }
        
        /* Only show splash screen when installed as PWA (standalone) */
        @media all and (display-mode: standalone) {
            #pwa-splash:not(.splash-hidden) {
                display: flex;
            }
        }
        
        #pwa-splash img {
            width: 120px;
            height: 120px;
            animation: pulse 2s infinite;
        }
        #pwa-splash h2 {
            margin-top: 20px;
            font-family: 'Inter', sans-serif;
            color: #333;
            font-weight: 600;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
        [data-theme="dark"] #pwa-splash {
            background-color: rgba(17, 24, 39, 0.75);
        }
        [data-theme="dark"] #pwa-splash h2 {
            color: #f3f4f6;
        }
        @media (prefers-reduced-motion: reduce) {
            #pwa-splash,
            #pwa-splash img {
                animation: none;
                transition: none;
            }
        }
    </style>
    
    <title>Offline POS</title>
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>
    <x-theme-component/>
    
    @php
        $user = auth()->user();
        $isValid = false;
        
        // Ensure the token exists in session AND is mathematically valid in the database
        if (session()->has('pos_api_token')) {
            $plainTextToken = session()->get('pos_api_token');
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($plainTextToken);
            if ($tokenModel && (!$tokenModel->expires_at || $tokenModel->expires_at->isFuture())) {
                $isValid = true;
            }
        }
        
        // If the token is missing, corrupted, or expired, instantly regenerate a fresh 12-hour token
        if (!$isValid) {
            $user->tokens()->where('name', 'pos-pwa')->delete();
            $token = $user->createToken('pos-pwa', ['pos:access'], now()->addHours(12))->plainTextToken;
            session()->put('pos_api_token', $token);
        }
        
        $posToken = session()->get('pos_api_token');
    @endphp

    <script>
        window.PosConfig = {
            apiToken: @json($posToken),
            user: @json($user),
            permissions: @json($user->user_type == 1 ? ['all'] : ($user->role ? $user->role->permissions->pluck('permission_name')->toArray() : []))
        };

    </script>
</head>

<body>
    <!-- Splash Screen -->
    <div id="pwa-splash">
        <img src="{{ asset('assets/images/logo-192.png') }}?v={{ $logo192Version }}" alt="Logo">
        <h2>{{ getApplicationName() }}</h2>
    </div>

    <div id="pos-app" class="tw-font-sans"></div>
    
    <script>
        window.hidePwaSplash = function() {
            var splash = document.getElementById('pwa-splash');
            if (splash && !splash.classList.contains('splash-hidden')) {
                splash.style.display = 'none'; // Instant hide for speed
                splash.classList.add('splash-hidden');
            }
        };
        // Fallback in case Vue catastrophically fails
        window.addEventListener('load', function() {
            setTimeout(window.hidePwaSplash, 5000); 
        });
    </script>
    <script src="{{ asset('assets/js/lib/jquery-3.7.1.min.js') }}"></script>
    <!-- Bootstrap js -->
    <script src="{{ asset('assets/js/lib/bootstrap.bundle.min.js') }}"></script>
    <!-- Apex Chart js -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> -->
    <!-- Data Table js -->
    <!-- <script src="{{ asset('assets/js/lib/dataTables.min.js') }}"></script> -->
    <script src="{{asset('assets/plugins/toastr.min.js')}}"></script>
    <!-- Iconify Font js -->
    <script src="{{ asset('assets/js/lib/iconify-icon.min.js') }}"></script>
    <!-- jQuery UI js -->
    <script src="{{ asset('assets/js/lib/jquery-ui.min.js') }}"></script>
    <!-- Vector Map js -->
    <!-- <script src="{{ asset('assets/js/lib/jquery-jvectormap-2.0.5.min.js') }}"></script>
    <script src="{{ asset('assets/js/lib/jquery-jvectormap-world-mill-en.js') }}"></script> -->
    <!-- Popup js -->
    <!-- <script src="{{ asset('assets/js/lib/magnific-popup.min.js') }}"></script> -->
    <!-- Slick Slider js -->
    <!-- <script src="{{ asset('assets/js/lib/slick.min.js') }}"></script> -->
    <!-- prism js -->
    <!-- <script src="{{ asset('assets/js/lib/prism.js') }}"></script> -->
    <!-- file upload js -->
    <!-- <script src="{{ asset('assets/js/lib/file-upload.js') }}"></script> -->
    <!-- audioplayer -->
    <!-- <script src="{{ asset('assets/js/lib/audioplayer.js') }}"></script> -->

    <!-- main js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    
    <!-- Vite Vue App -->
    @vite(['resources/js/pos-app.js'])
</body>
</html>
