@extends('layouts.staff')

@section('title', '勤怠')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-attendance.css') }}">
@endpush

@section('content')
<div class="attendance">
    <div class="attendance__container">
        <!-- ステータスバッジ -->
        <div class="attendance__status">
            @if($status === 'before')
                <span class="status-badge status-badge--before">勤務外</span>
            @elseif($status === 'working')
                <span class="status-badge status-badge--working">出勤中</span>
            @elseif($status === 'break')
                <span class="status-badge status-badge--break">休憩中</span>
            @elseif($status === 'finished')
                <span class="status-badge status-badge--finished">退勤済</span>
            @endif
        </div>

        <!-- 日付表示 -->
        <div class="attendance__date" id="currentDate"></div>

        <!-- 時刻表示 -->
        <div class="attendance__time" id="currentTime">00:00</div>

        <!-- 退勤後のメッセージ -->
        @if($status === 'finished')
            <p class="attendance__message">お疲れ様でした。</p>
        @endif

        <!-- アクションボタン -->
        <div class="attendance__actions">
            @if($status === 'before')
                <!-- 出勤ボタン -->
                <form method="POST" action="{{ route('staff.clock-in') }}">
                    @csrf
                    <button type="submit" class="attendance__button attendance__button--primary">出勤</button>
                </form>
            @elseif($status === 'working')
                <!-- 退勤 & 休憩入ボタン -->
                <form method="POST" action="{{ route('staff.clock-out') }}">
                    @csrf
                    <button type="submit" class="attendance__button attendance__button--primary">退勤</button>
                </form>
                <form method="POST" action="{{ route('staff.break-start') }}">
                    @csrf
                    <button type="submit" class="attendance__button attendance__button--secondary">休憩入</button>
                </form>
            @elseif($status === 'break')
                <!-- 休憩戻ボタン -->
                <form method="POST" action="{{ route('staff.break-end') }}">
                    @csrf
                    <button type="submit" class="attendance__button attendance__button--secondary">休憩戻</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // 現在時刻を表示
    function updateTime() {
        const now = new Date();
        
        // 時刻表示（HH:MM）
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('currentTime').textContent = hours + ':' + minutes;
        
        // 日付表示（YYYY年M月D日(曜日)）
        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        const date = now.getDate();
        const days = ['日', '月', '火', '水', '木', '金', '土'];
        const day = days[now.getDay()];
        
        document.getElementById('currentDate').textContent = 
            year + '年' + month + '月' + date + '日(' + day + ')';
    }
    
    // 初回実行
    updateTime();
    
    // 1秒ごとに更新
    setInterval(updateTime, 1000);
</script>
@endpush
