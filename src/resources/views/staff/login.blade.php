@extends('layouts.app')

@section('title', 'ログイン')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-login.css') }}">
@endpush

@section('content')
<div class="login">
    <div class="login__container">
        <h2 class="login__title">ログイン</h2>

        @if(session('status'))
            <div class="login__success-message">
                {{ session('status') }}
            </div>
        @endif

        @if(session('success'))
            <div class="login__success-message">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->has('email') && $errors->first('email') === 'ログイン情報が登録されていません')
            <div class="login__error-message">
                ログイン情報が登録されていません
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="login__form">
            @csrf

            <div class="form-group">
                <label for="email" class="form-group__label">メールアドレス</label>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    class="form-group__input @error('email') form-group__input--error @enderror"
                    autofocus
                >
                @error('email')
                    @if($message !== 'ログイン情報が登録されていません')
                        <p class="form-group__error">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-group__label">パスワード</label>
                <input 
                    id="password" 
                    type="password" 
                    name="password" 
                    class="form-group__input @error('password') form-group__input--error @enderror"
                >
                @error('password')
                    <p class="form-group__error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="login__button">ログインする</button>

            <div class="login__register-link">
                <a href="{{ route('register') }}">会員登録はこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection
