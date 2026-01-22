@extends('layouts.staff')

@section('title', '勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endpush

@section('content')
<div class="attendance-list">
    <div class="attendance-list__container">
        <h2 class="attendance-list__title">勤怠一覧</h2>
        
        <div class="attendance-list__header">
            <form method="GET" action="{{ route('staff.attendance.list') }}" class="attendance-list__nav">
                <input type="hidden" name="month" value="{{ $prevMonth }}">
                <button type="submit" class="attendance-list__nav-btn">&lt; 前月</button>
            </form>
            
            <div class="attendance-list__month">
                <span class="attendance-list__month-icon">📅</span>
                <span class="attendance-list__month-text">{{ $displayMonth }}</span>
            </div>
            
            <form method="GET" action="{{ route('staff.attendance.list') }}" class="attendance-list__nav">
                <input type="hidden" name="month" value="{{ $nextMonth }}">
                <button type="submit" class="attendance-list__nav-btn">翌月 &gt;</button>
            </form>
        </div>
        
        <div class="attendance-list__table-wrapper">
            <table class="attendance-list__table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr class="{{ $attendance->attendance ? 'attendance-list__row--has-data' : '' }}">
                            <td class="attendance-list__date">{{ $attendance->date_formatted }}</td>
                            <td>{{ $attendance->clock_in_time ?? '' }}</td>
                            <td>{{ $attendance->clock_out_time ?? '' }}</td>
                            <td>{{ $attendance->break_duration ?? '' }}</td>
                            <td>{{ $attendance->total_work_time ?? '' }}</td>
                            <td>
                                @if($attendance->attendance)
                                    <a href="{{ route('staff.attendance.detail', $attendance->attendance->id) }}" class="attendance-list__detail-btn">詳細</a>
                                @else
                                    <span class="attendance-list__detail-empty">詳細</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="attendance-list__empty">勤怠データがありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
