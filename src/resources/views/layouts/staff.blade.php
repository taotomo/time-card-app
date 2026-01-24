<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Time Card App</title>
    <link rel="stylesheet" href="{{ asset('css/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-nav.css') }}">
    @stack('styles')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <a href="{{ route('staff.attendance') }}">
                <img src="{{ asset('img/COACHTECHヘッダーロゴ .png') }}" alt="COACHTECH" class="header__logo">
            </a>
            @auth
                @include('components.staff-nav')
            @endauth
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
