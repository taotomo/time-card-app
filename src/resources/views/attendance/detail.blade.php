@extends('layouts.admin')

@section('title', '勤怠詳細')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endpush

@section('content')
<div class="attendance-detail">
    <div class="attendance-detail__container">
        <h2 class="attendance-detail__title">勤怠詳細</h2>

        @if($errors->has('message'))
            <div class="attendance-detail__error-message">
                {{ $errors->first('message') }}
            </div>
        @endif

        @if($errors->has('time_error') || $errors->has('break_error'))
            <div class="attendance-detail__error-message">
                @if($errors->has('time_error'))
                    {{ $errors->first('time_error') }}<br>
                @endif
                @if($errors->has('break_error'))
                    {{ $errors->first('break_error') }}<br>
                @endif
                @if($errors->has('remarks'))
                    {{ $errors->first('remarks') }}
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}" class="attendance-detail__form">
            @csrf
            @method('PUT')

            <div class="attendance-detail__info">
                <div class="info-item">
                    <div class="info-item__label">名前</div>
                    <div class="info-item__value">{{ $attendance->user->name }}</div>
                </div>

                <div class="info-item">
                    <div class="info-item__label">日付</div>
                    <div class="info-item__value info-item__value--date">
                        <span class="date-field__year">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('Y年') }}</span>
                        <input 
                            type="number" 
                            name="clock_in_month" 
                            value="{{ old('clock_in_month', \Carbon\Carbon::parse($attendance->clock_in)->format('n')) }}"
                            class="date-field__input"
                            min="1"
                            max="12"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >
                        <span class="date-field__unit">月</span>
                        <input 
                            type="number" 
                            name="clock_in_day" 
                            value="{{ old('clock_in_day', \Carbon\Carbon::parse($attendance->clock_in)->format('j')) }}"
                            class="date-field__input"
                            min="1"
                            max="31"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >
                        <span class="date-field__unit">日</span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-item__label">出勤・退勤</div>
                    <div class="info-item__value info-item__value--time">
                        <input 
                            type="time" 
                            name="clock_in_time" 
                            value="{{ old('clock_in_time', \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')) }}"
                            class="info-item__time-input"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >
                        <span class="info-item__separator">～</span>
                        <input 
                            type="time" 
                            name="clock_out_time" 
                            value="{{ old('clock_out_time', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}"
                            class="info-item__time-input"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-item__label">休憩</div>
                    <div class="info-item__value info-item__value--time">
                        @php
                            $breakTimes = $attendance->break_times ?? [];
                            $break1 = $breakTimes[0] ?? null;
                        @endphp
                        <input 
                            type="time" 
                            name="break_start_1" 
                            value="{{ old('break_start_1', $break1['start'] ?? '') }}"
                            class="info-item__time-input"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >
                        <span class="info-item__separator">～</span>
                        <input 
                            type="time" 
                            name="break_end_1" 
                            value="{{ old('break_end_1', $break1['end'] ?? '') }}"
                            class="info-item__time-input"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-item__label">休憩2</div>
                    <div class="info-item__value info-item__value--time">
                        @php
                            $break2 = $breakTimes[1] ?? null;
                        @endphp
                        <input 
                            type="time" 
                            name="break_start_2" 
                            value="{{ old('break_start_2', $break2['start'] ?? '') }}"
                            class="info-item__time-input"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >
                        <span class="info-item__separator">～</span>
                        <input 
                            type="time" 
                            name="break_end_2" 
                            value="{{ old('break_end_2', $break2['end'] ?? '') }}"
                            class="info-item__time-input"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >
                    </div>
                </div>

                <div class="info-item info-item--remarks">
                    <div class="info-item__label">備考</div>
                    <div class="info-item__value">
                        <textarea 
                            name="remarks" 
                            class="info-item__textarea"
                            {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                        >{{ old('remarks', $attendance->remarks ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="attendance-detail__submit">
                <button 
                    type="submit" 
                    class="attendance-detail__submit-btn"
                    {{ $attendance->approval_status === 1 ? 'disabled' : '' }}
                >修正</button>
            </div>
        </form>
    </div>
</div>
@endsection
