<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', '勤怠管理システム')</title>
    <link rel="stylesheet" href="{{ asset('css/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    @stack('styles')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <img src="{{ asset('img/COACHTECHヘッダーロゴ .png') }}" alt="COACHTECH" class="header__logo">
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
