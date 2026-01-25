<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Cdev</title>
        
        <link rel="icon" type="image/png" href="{{ asset('images/clstldev2-removebg.png') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/print.css') }}">
        <link rel="stylesheet" href="{{ asset('play-tailwind/assets/css/swiper-bundle.min.css') }}" />
        <link rel="stylesheet" href="{{ asset('play-tailwind/assets/css/animate.css') }}" />
        <link rel="stylesheet" href="{{ asset('play-tailwind/src/css/tailwind.css') }}" />

        <!-- ==== WOW JS ==== -->
        <script src="{{ asset('play-tailwind/assets/js/wow.min.js') }}"></script>
        <script>
        new WOW().init();
        </script>

        @viteReactRefresh
        @vite(['resources/css/app.css','resources/js/app.jsx'])
        
    </head>
    <body class="text-blueGray-700 antialiased">
        <div id="app"></div>
    </body>
</html>