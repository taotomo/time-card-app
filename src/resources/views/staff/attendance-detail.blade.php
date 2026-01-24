@extends('layouts.staff')

@section('title', '勤怠詳細')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-attendance-detail.css') }}">
@endpush

@section('content')
<div class="attendance-detail">
    <div class="attendance-detail__container">
        <h2 class="attendance-detail__title">勤怠詳細</h2>
        
        @if($attendance->approval_status == 1)
            <p class="attendance-detail__warning">* 承認待ちのため修正はできません。</p>
        @endif
        
        <form method="POST" action="{{ route('staff.attendance.update', $attendance->id) }}" class="attendance-detail__form" novalidate>
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label class="form-group__label">名前</label>
                <div class="form-group__value">{{ $attendance->user->name }}</div>
            </div>
            
            <div class="form-group">
                <label class="form-group__label">日付</label>
                <div class="form-group__value">
                    {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('Y年') : '' }}
                    <span class="form-group__separator"></span>
                    {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('n月j日') : '' }}
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-group__label">出勤 ~ 退勤</label>
                <div class="form-group__inputs">
                    <input 
                        type="time" 
                        name="clock_in" 
                        value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}"
                        class="form-group__input @error('clock_in') form-group__input--error @enderror"
                        {{ $attendance->approval_status == 1 ? 'readonly' : '' }}
                    >
                    <span class="form-group__separator">~</span>
                    <input 
                        type="time" 
                        name="clock_out" 
                        value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}"
                        class="form-group__input @error('clock_out') form-group__input--error @enderror"
                        {{ $attendance->approval_status == 1 ? 'readonly' : '' }}
                    >
                </div>
                @error('clock_in')
                    <span class="form-group__error">{{ $message }}</span>
                @enderror
                @error('clock_out')
                    <span class="form-group__error">{{ $message }}</span>
                @enderror
            </div>
            
            @php
                $breakTimes = $attendance->break_times ?? [];
                $breakCount = max(count($breakTimes) + 1, 2); // 最低2行表示（既存+1行追加）
            @endphp
            
            @for($i = 0; $i < $breakCount; $i++)
                <div class="form-group">
                    <label class="form-group__label">休憩{{ $i > 0 ? ($i + 1) : '' }}</label>
                    <div class="form-group__inputs">
                        <input 
                            type="time" 
                            name="break_times[{{ $i }}][start]" 
                            value="{{ old('break_times.' . $i . '.start', isset($breakTimes[$i]['start']) ? $breakTimes[$i]['start'] : '') }}"
                            class="form-group__input @error('break_times.' . $i . '.start') form-group__input--error @enderror"
                            {{ $attendance->approval_status == 1 ? 'readonly' : '' }}
                        >
                        <span class="form-group__separator">~</span>
                        <input 
                            type="time" 
                            name="break_times[{{ $i }}][end]" 
                            value="{{ old('break_times.' . $i . '.end', isset($breakTimes[$i]['end']) ? $breakTimes[$i]['end'] : '') }}"
                            class="form-group__input @error('break_times.' . $i . '.end') form-group__input--error @enderror"
                            {{ $attendance->approval_status == 1 ? 'readonly' : '' }}
                        >
                    </div>
                    @error('break_times.' . $i . '.start')
                        <span class="form-group__error">{{ $message }}</span>
                    @enderror
                    @error('break_times.' . $i . '.end')
                        <span class="form-group__error">{{ $message }}</span>
                    @enderror
                </div>
            @endfor
            
            <div class="form-group">
                <label class="form-group__label">備考</label>
                <textarea 
                    name="remarks" 
                    rows="3"
                    class="form-group__textarea @error('remarks') form-group__textarea--error @enderror"
                    {{ $attendance->approval_status == 1 ? 'readonly' : '' }}
                >{{ old('remarks', $attendance->remarks) }}</textarea>
                @error('remarks')
                    <span class="form-group__error">{{ $message }}</span>
                @enderror
            </div>
            
            @if($attendance->approval_status != 1)
                <div class="form-actions">
                    <button type="submit" class="form-actions__submit">修正</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection
