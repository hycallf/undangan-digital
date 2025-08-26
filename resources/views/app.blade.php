<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <!-- PWA Meta tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Prevent zoom on input focus (iOS) -->
    <meta name="format-detection" content="telephone=no">

    <!-- Force HTTPS untuk camera access -->
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <!-- Performance hints -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Noto+Naskh+Arabic:wght@400..700&family=Sacramento&display=swap"
        rel="stylesheet">

    <script async src="//cdn.iframe.ly/embed.js"></script>
    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead


    <style>
        /* Prevent iOS bounce effect */
        body {
            overscroll-behavior: none;
            -webkit-overflow-scrolling: touch;
        }

        /* Prevent text selection on mobile */
        * {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Allow text selection for input fields */
        input,
        textarea {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }

        /* Fix mobile viewport */
        @viewport {
            width: device-width;
            zoom: 1.0;
        }
    </style>
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
