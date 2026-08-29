<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="/sekolahku/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <!-- Legacy SB Admin 2 & Custom CSS (LOADED AFTER VITE TO OVERRIDE TAILWIND PREFLIGHT) -->
        <link href="/sekolahku/css/sb-admin-2.min.css" rel="stylesheet">
        <link href="/sekolahku/css/mycss.css" rel="stylesheet">
    </head>
    <body id="page-top" class="font-sans antialiased">
        @inertia

        <!-- Legacy Scripts -->
        <script src="/sekolahku/vendor/jquery/jquery.min.js"></script>
        <script src="/sekolahku/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="/sekolahku/vendor/jquery-easing/jquery.easing.min.js"></script>
        <script src="/sekolahku/js/sb-admin-2.min.js"></script>
    </body>
</html>
