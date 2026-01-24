@extends('layouts.admin')

@section('title', '勤怠詳細')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-request-detail.css') }}">
@endpush

@section('content')
<div class="request-detail">
    <div class="request-detail__container">
        <h2 class="request-detail__title">勤怠詳細</h2>
        
        <div class="request-detail__content">
            <div class="request-detail__row">
                <div class="request-detail__label">名前</div>
                <div class="request-detail__value">{{ $attendance->user->name }}</div>
            </div>
            
            <div class="request-detail__row">
                <div class="request-detail__label">日付</div>
                <div class="request-detail__value">
                    {{ \Carbon\Carbon::parse($attendance->clock_in)->format('Y年') }}
                    {{ \Carbon\Carbon::parse($attendance->clock_in)->format('n月j日') }}
                </div>
            </div>
            
            <div class="request-detail__row">
                <div class="request-detail__label">出勤・退勤</div>
                <div class="request-detail__value-group">
                    <div class="request-detail__time-group">
                        <span class="request-detail__time">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}</span>
                        <span class="request-detail__separator">〜</span>
                        <span class="request-detail__time">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</span>
                    </div>
                </div>
            </div>
            
            <div class="request-detail__row">
                <div class="request-detail__label">休憩</div>
                <div class="request-detail__value-group">
                    @if($attendance->break_times && count($attendance->break_times) > 0)
                        @foreach($attendance->break_times as $index => $break)
                            <div class="request-detail__time-group">
                                <span class="request-detail__time">{{ $break['start'] }}</span>
                                <span class="request-detail__separator">〜</span>
                                <span class="request-detail__time">{{ $break['end'] }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="request-detail__row">
                <div class="request-detail__label">休憩2</div>
                <div class="request-detail__value"></div>
            </div>
            
            <div class="request-detail__row">
                <div class="request-detail__label">備考</div>
                <div class="request-detail__value">{{ $attendance->remarks ?? '' }}</div>
            </div>
        </div>
        
        @if($attendance->approval_status == 1)
        <form method="POST" action="{{ route('admin.request.approve', $attendance->id) }}" class="request-detail__form">
            @csrf
            @method('PUT')
            <button type="submit" class="request-detail__approve-btn">承認</button>
        </form>
        @elseif($attendance->approval_status == 2)
        <div class="request-detail__approved-message">承認済み</div>
        @endif
    </div>
</div>
@endsection
