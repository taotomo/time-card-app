@extends('layouts.app')

@section('title', '管理者ログイン')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
<div class="login">
    <div class="login__container">
        <h2 class="login__title">管理者ログイン</h2>
        
        <form method="POST" action="{{ route('login') }}" class="login__form" novalidate>
            @csrf
            
            <div class="form__group">
                <label for="email" class="form__label">メールアドレス</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form__input @error('email') form__input--error @enderror" 
                    value="{{ old('email') }}" 
                    autofocus
                >
                @error('email')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>
            
            <div class="form__group">
                <label for="password" class="form__label">パスワード</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form__input @error('password') form__input--error @enderror"
                >
                @error('password')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>
            
            <div class="form__group">
                <button type="submit" class="form__button">管理者ログインする</button>
            </div>
        </form>
    </div>
</div>
@endsection
