@extends('layouts.admin')

@section('title', '勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endpush

@section('content')
<div class="attendance">
    <div class="attendance__container">
        <h2 class="attendance__title">{{ $date->year }}年{{ $date->month }}月{{ $date->day }}日の勤怠</h2>
        
        <div class="attendance__date-selector">
            <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="date-selector__button">
                ＜ 前日
            </a>
            <div class="date-selector__current">
                <span class="date-selector__icon">📅</span>
                <span class="date-selector__text">{{ $date->format('Y/m/d') }}</span>
            </div>
            <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="date-selector__button">
                翌日 ＞
            </a>
        </div>

        <div class="attendance__table-wrapper">
            <table class="attendance__table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance['name'] }}</td>
                        <td>{{ $attendance['clock_in'] }}</td>
                        <td>{{ $attendance['clock_out'] }}</td>
                        <td>{{ $attendance['break_time'] }}</td>
                        <td>{{ $attendance['total_time'] }}</td>
                        <td>
                            @if($attendance['id'])
                                <a href="{{ route('admin.attendance.detail', $attendance['id']) }}" class="attendance__detail-link">{{ $attendance['status'] }}</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="attendance__empty">勤怠データがありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
