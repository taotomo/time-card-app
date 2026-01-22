@extends('layouts.admin')

@section('title', 'スタッフ別勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-detail.css') }}">
@endpush

@section('content')
<div class="staff-detail">
    <div class="staff-detail__container">
        <h2 class="staff-detail__title">{{ $user->name }}さんの勤怠</h2>
        
        <div class="staff-detail__controls">
            <div class="staff-detail__month-selector">
                <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="month-selector__button">
                    ＜ 前月
                </a>
                <div class="month-selector__current">
                    <span class="month-selector__icon">📅</span>
                    <span class="month-selector__text">{{ $month->format('Y/m') }}</span>
                </div>
                <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="month-selector__button">
                    翌月 ＞
                </a>
            </div>
            
            <a href="{{ route('admin.attendance.csv', ['id' => $user->id, 'month' => $month->format('Y-m')]) }}" class="staff-detail__csv-button">
                CSV出力
            </a>
        </div>

        <div class="staff-detail__table-wrapper">
            <table class="staff-detail__table">
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
                    @foreach($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance['date'] }}</td>
                        <td>{{ $attendance['clock_in'] }}</td>
                        <td>{{ $attendance['clock_out'] }}</td>
                        <td>{{ $attendance['break_time'] }}</td>
                        <td>{{ $attendance['total_time'] }}</td>
                        <td>
                            @if($attendance['clock_in'])
                                <a href="#" class="staff-detail__detail-link">詳細</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
