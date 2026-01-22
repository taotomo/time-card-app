@extends('layouts.admin')

@section('title', '申請一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-requests.css') }}">
@endpush

@section('content')
<div class="requests">
    <div class="requests__container">
        <h2 class="requests__title">申請一覧</h2>
        
        <div class="requests__tabs">
            <button 
                class="requests__tab {{ $activeTab === 'pending' ? 'requests__tab--active' : '' }}" 
                onclick="location.href='{{ route('admin.requests', ['tab' => 'pending']) }}'"
            >
                承認待ち
            </button>
            <button 
                class="requests__tab {{ $activeTab === 'approved' ? 'requests__tab--active' : '' }}"
                onclick="location.href='{{ route('admin.requests', ['tab' => 'approved']) }}'"
            >
                承認済み
            </button>
        </div>
        
        <div class="requests__table-wrapper">
            <table class="requests__table">
                <thead>
                    <tr>
                        <th>校舎</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    <tr>
                        <td>{{ $activeTab === 'pending' ? '承認待ち' : '承認済み' }}</td>
                        <td>{{ $request->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->clock_in)->format('Y/m/d') }}</td>
                        <td>{{ $request->remarks ?? '' }}</td>
                        <td>{{ $request->updated_at ? $request->updated_at->format('Y/m/d') : '' }}</td>
                        <td>
                            <a href="{{ route('admin.request.detail', $request->id) }}" class="requests__detail-link">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="requests__empty">{{ $activeTab === 'pending' ? '承認待ちの申請はありません' : '承認済みの申請はありません' }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
