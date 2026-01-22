@php
    $isFinished = isset($attendanceStatus) && $attendanceStatus === 'finished';
@endphp

<nav class="header__nav staff-nav">
    @if($isFinished)
        <a href="{{ route('staff.attendance.list') }}" class="staff-nav__link">今月の出勤一覧</a>
        <a href="{{ route('staff.requests') }}" class="staff-nav__link">申請一覧</a>
    @else
        <a href="{{ route('staff.attendance') }}" class="staff-nav__link">勤怠</a>
        <a href="{{ route('staff.attendance.list') }}" class="staff-nav__link">勤怠一覧</a>
        <a href="{{ route('staff.requests') }}" class="staff-nav__link">申請</a>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="staff-nav__logout-form">
        @csrf
        <button type="submit" class="staff-nav__link staff-nav__link--logout">ログアウト</button>
    </form>
</nav>
