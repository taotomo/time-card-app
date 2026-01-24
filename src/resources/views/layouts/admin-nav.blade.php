<nav class="admin-nav">
    <a href="{{ route('admin.attendance.list') }}" class="admin-nav__link {{ request()->routeIs('admin.attendance.list') ? 'admin-nav__link--active' : '' }}">
        勤怠一覧
    </a>
    <a href="{{ route('admin.staff.list') }}" class="admin-nav__link {{ request()->routeIs('admin.staff.list') ? 'admin-nav__link--active' : '' }}">
        スタッフ一覧
    </a>
    <a href="{{ route('requests.list') }}" class="admin-nav__link {{ request()->routeIs('requests.list') ? 'admin-nav__link--active' : '' }}">
        申請一覧
    </a>
    <form method="POST" action="{{ route('logout') }}" class="admin-nav__logout-form">
        @csrf
        <button type="submit" class="admin-nav__link admin-nav__link--logout">
            ログアウト
        </button>
    </form>
</nav>
