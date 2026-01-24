<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面を表示
     */
    public function index(Request $request)
    {
        // URLパラメータから日付を取得、なければ今日の日付
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $targetDate = Carbon::parse($date);

        // 全ユーザーを取得し、その日の勤怠情報を結合
        $users = User::with(['attendances' => function ($query) use ($date) {
            $query->whereDate('clock_in', $date);
        }])->get();

        // 各ユーザーの勤怠情報を整形
        $attendanceData = $users->map(function ($user) {
            $attendance = $user->attendances->first();
            
            return [
                'id' => $attendance ? $attendance->id : null,
                'name' => $user->name,
                'clock_in' => $attendance ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                'clock_out' => $attendance && $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                'break_time' => $attendance ? $this->formatBreakTimes($attendance->break_times) : '',
                'total_time' => $attendance ? $this->calculateTotalTime($attendance) : '',
                'status' => $attendance && $attendance->clock_out ? '詳細' : '詳細',
            ];
        });

        return view('attendance.index', [
            'attendances' => $attendanceData,
            'date' => $targetDate,
        ]);
    }

    /**
     * 分を時間:分形式に変換
     */
    private function formatMinutesToTime($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%d:%02d', $hours, $mins);
    }

    /**
     * 休憩時間を表示形式に変換
     */
    private function formatBreakTimes($breakTimes)
    {
        if (!$breakTimes || empty($breakTimes)) {
            return '';
        }

        $totalMinutes = 0;
        foreach ($breakTimes as $break) {
            if (isset($break['start']) && isset($break['end'])) {
                $start = Carbon::parse($break['start']);
                $end = Carbon::parse($break['end']);
                $totalMinutes += $end->diffInMinutes($start);
            }
        }

        return $this->formatMinutesToTime($totalMinutes);
    }

    /**
     * 合計勤務時間を計算
     */
    private function calculateTotalTime($attendance)
    {
        if (!$attendance->clock_out) {
            return '';
        }

        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::parse($attendance->clock_out);
        $totalMinutes = $clockOut->diffInMinutes($clockIn);
        
        // 休憩時間を差し引く
        if ($attendance->break_times && !empty($attendance->break_times)) {
            foreach ($attendance->break_times as $break) {
                if (isset($break['start']) && isset($break['end'])) {
                    $start = Carbon::parse($break['start']);
                    $end = Carbon::parse($break['end']);
                    $totalMinutes -= $end->diffInMinutes($start);
                }
            }
        }

        return $this->formatMinutesToTime($totalMinutes);
    }

    /**
     * スタッフ別月次勤怠一覧画面を表示
     */
    public function staffDetail(Request $request, $userId)
    {
        // ユーザー情報を取得
        $user = User::findOrFail($userId);

        // URLパラメータから年月を取得、なければ今月
        $yearMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $targetMonth = Carbon::parse($yearMonth . '-01');

        // 選択した月の全日付を取得
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        // 該当月の勤怠情報を取得
        $attendances = $user->attendances()
            ->whereDate('clock_in', '>=', $startDate)
            ->whereDate('clock_in', '<=', $endDate)
            ->orderBy('clock_in', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->clock_in)->format('Y-m-d');
            });

        // 月の全日付分のデータを作成
        $attendanceData = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $attendance = $attendances->get($dateStr);

            $attendanceData->push([
                'date' => $date->format('m/d') . '(' . $this->getDayOfWeekJa($date) . ')',
                'clock_in' => $attendance ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                'clock_out' => $attendance && $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                'break_time' => $attendance && $attendance->break_time ? $this->formatMinutesToTime($attendance->break_time) : '',
                'total_time' => $attendance ? $this->calculateTotalTime($attendance) : '',
            ]);
        }

        return view('attendance.staff-detail', [
            'user' => $user,
            'attendances' => $attendanceData,
            'month' => $targetMonth,
        ]);
    }

    /**
     * CSV出力
     */
    public function exportCsv(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $yearMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $targetMonth = Carbon::parse($yearMonth . '-01');

        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        $attendances = $user->attendances()
            ->whereDate('clock_in', '>=', $startDate)
            ->whereDate('clock_in', '<=', $endDate)
            ->orderBy('clock_in', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->clock_in)->format('Y-m-d');
            });

        // CSVデータを作成
        $csvData = [];
        $csvData[] = ['日付', '出勤', '退勤', '休憩', '合計'];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $attendance = $attendances->get($dateStr);

            $csvData[] = [
                $date->format('m/d') . '(' . $this->getDayOfWeekJa($date) . ')',
                $attendance ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                $attendance && $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                $attendance && $attendance->break_time ? $this->formatMinutesToTime($attendance->break_time) : '',
                $attendance ? $this->calculateTotalTime($attendance) : '',
            ];
        }

        // CSVファイルを生成
        $filename = $user->name . '_' . $targetMonth->format('Y年m月') . '_勤怠一覧.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            // BOM追加（Excel対応）
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * 曜日を日本語で取得
     */
    private function getDayOfWeekJa($date)
    {
        $daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];
        return $daysOfWeek[$date->dayOfWeek];
    }

    /**
     * 勤怠詳細画面を表示
     */
    public function detail($id)
    {
        $attendance = \App\Models\Attendance::with('user')->findOrFail($id);
        
        return view('attendance.detail', [
            'attendance' => $attendance,
        ]);
    }

    /**
     * 勤怠詳細の更新処理
     */
    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        $attendance = \App\Models\Attendance::findOrFail($id);

        // 承認待ちの場合は修正不可
        if ($attendance->isPending()) {
            return back()->withErrors(['message' => '承認待ちのため修正はできません。']);
        }

        // バリデーション済みデータを取得
        $validated = $request->validated();

        // 年月日を組み立て
        $year = Carbon::parse($attendance->clock_in)->format('Y');
        $clockInDate = sprintf('%s-%02d-%02d', $year, $validated['clock_in_month'], $validated['clock_in_day']);

        // 出勤・退勤時刻の結合
        $clockInDateTime = $clockInDate . ' ' . $validated['clock_in_time'];
        $clockOutDateTime = $validated['clock_out_time'] 
            ? $clockInDate . ' ' . $validated['clock_out_time']
            : null;

        // 休憩時間の設定
        $breakTimes = [];
        
        // 休憩1
        if ($validated['break_start_1'] && $validated['break_end_1']) {
            $breakTimes[] = [
                'start' => $validated['break_start_1'],
                'end' => $validated['break_end_1'],
            ];
        }

        // 休憩2
        if ($validated['break_start_2'] && $validated['break_end_2']) {
            $breakTimes[] = [
                'start' => $validated['break_start_2'],
                'end' => $validated['break_end_2'],
            ];
        }

        // データ更新
        $attendance->update([
            'clock_in' => $clockInDateTime,
            'clock_out' => $clockOutDateTime,
            'break_times' => !empty($breakTimes) ? $breakTimes : null,
            'remarks' => $validated['remarks'],
        ]);

        return redirect()->route('admin.attendance.list', ['date' => $clockInDate])
            ->with('success', '勤怠情報を修正しました');
    }

    /**
     * 申請一覧画面を表示（管理者用）
     */
    public function requestsList(Request $request)
    {
        $activeTab = $request->input('tab', 'pending'); // デフォルトは承認待ち
        
        // タブに応じて申請を取得
        if ($activeTab === 'approved') {
            $requests = \App\Models\Attendance::with('user')
                ->where('approval_status', \App\Models\Attendance::STATUS_APPROVED)
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            $requests = \App\Models\Attendance::with('user')
                ->where('approval_status', \App\Models\Attendance::STATUS_PENDING)
                ->orderBy('updated_at', 'desc')
                ->get();
        }
        
        return view('attendance.requests', [
            'requests' => $requests,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * 申請一覧画面を表示（一般ユーザー・管理者共通）
     * ミドルウェアで認証を区別し、同じパスを使用
     */
    public function requestsListUnified(Request $request)
    {
        $user = auth()->user();
        $activeTab = $request->input('tab', 'pending');
        
        // 管理者判定
        $isAdmin = ($user->email === 'admin@example.com');
        
        if ($isAdmin) {
            // 管理者：全ユーザーの申請を取得
            if ($activeTab === 'approved') {
                $requests = \App\Models\Attendance::with('user')
                    ->where('approval_status', \App\Models\Attendance::STATUS_APPROVED)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            } else {
                $requests = \App\Models\Attendance::with('user')
                    ->where('approval_status', \App\Models\Attendance::STATUS_PENDING)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }
            
            return view('attendance.requests', [
                'requests' => $requests,
                'activeTab' => $activeTab,
            ]);
        } else {
            // 一般ユーザー：自分の申請のみ取得
            if ($activeTab === 'approved') {
                $requests = \App\Models\Attendance::with('user')
                    ->where('user_id', $user->id)
                    ->where('approval_status', \App\Models\Attendance::STATUS_APPROVED)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            } else {
                $requests = \App\Models\Attendance::with('user')
                    ->where('user_id', $user->id)
                    ->where('approval_status', \App\Models\Attendance::STATUS_PENDING)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }
            
            return view('staff.requests', [
                'requests' => $requests,
                'activeTab' => $activeTab,
                'attendanceStatus' => 'before',
            ]);
        }
    }

    /**
     * 申請詳細画面を表示
     */
    public function requestDetail($id)
    {
        $attendance = \App\Models\Attendance::with('user')->findOrFail($id);
        
        return view('attendance.request-detail', [
            'attendance' => $attendance,
        ]);
    }

    /**
     * 申請を承認
     */
    public function approveRequest($id)
    {
        $attendance = \App\Models\Attendance::findOrFail($id);
        
        // 承認待ちの場合のみ承認可能
        if (!$attendance->isPending()) {
            return redirect()->back()->withErrors(['message' => 'この申請は承認できません。']);
        }
        
        $attendance->update([
            'approval_status' => \App\Models\Attendance::STATUS_APPROVED,
        ]);
        
        return redirect()->route('requests.list', ['tab' => 'approved'])
            ->with('success', '申請を承認しました');
    }
}
