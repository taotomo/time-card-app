@extends('layouts.staff')

@section('title', 'メール認証')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endpush

@section('content')
<div class="verify-email">
    <div class="verify-email__container">
        <p class="verify-email__message">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>
        
        @if (session('status') == 'verification-link-sent')
            <div class="verify-email__success">
                新しい認証リンクがメールアドレスに送信されました。
            </div>
        @endif
        
        <div class="verify-email__actions">
            <a href="http://localhost:8025" target="_blank" class="verify-email__note verify-email__note--link">認証はこちらから</a>
            
            <form method="POST" action="{{ route('verification.send') }}" class="verify-email__form">
                @csrf
                <button type="submit" class="verify-email__resend-link">
                    認証メールを再送する
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
