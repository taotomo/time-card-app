@extends('layouts.app')

@section('title', '会員登録')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-register.css') }}">
@endpush

@section('content')
<div class="register">
    <div class="register__container">
        <h2 class="register__title">会員登録</h2>

        <form method="POST" action="{{ route('register') }}" class="register__form" novalidate>
            @csrf

            <div class="form-group">
                <label for="name" class="form-group__label">名前</label>
                <input 
                    id="name" 
                    type="text" 
                    name="name" 
                    value="{{ old('name') }}" 
                    class="form-group__input @error('name') form-group__input--error @enderror"
                    autofocus
                >
                @error('name')
                    <p class="form-group__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-group__label">メールアドレス</label>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    class="form-group__input @error('email') form-group__input--error @enderror"
                >
                @error('email')
                    <p class="form-group__error">{{ $message }}</p>
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

            <div class="form-group">
                <label for="password_confirmation" class="form-group__label">パスワード確認</label>
                <input 
                    id="password_confirmation" 
                    type="password" 
                    name="password_confirmation" 
                    class="form-group__input"
                >
            </div>

            <button type="submit" class="register__button">登録する</button>

            <div class="register__login-link">
                <a href="{{ route('login') }}">ログインはこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection
