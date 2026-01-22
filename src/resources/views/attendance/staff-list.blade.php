@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff.css') }}">
@endpush

@section('content')
<div class="staff">
    <div class="staff__container">
        <h2 class="staff__title">スタッフ一覧</h2>

        <div class="staff__table-wrapper">
            <table class="staff__table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <a href="{{ route('admin.attendance.staff', $user->id) }}" class="staff__detail-link">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="staff__empty">スタッフデータがありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
