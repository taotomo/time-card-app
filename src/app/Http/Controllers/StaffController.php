<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Http\Requests\StaffAttendanceUpdateRequest;
use Carbon\Carbon;

class StaffController extends Controller
{
    /**
     * スタッフ一覧画面を表示（管理者用）
     */
    public function index()
    {
        // 管理者以外の全ユーザーを取得（管理者のメールアドレスを除外）
        $users = User::where('email', '!=', 'admin@example.com')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('attendance.staff-list', [
            'users' => $users,
        ]);
    }

    /**
     * 勤怠打刻画面を表示
     */
    public function attendance()
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->first();
        
        // ステータスを判定
        $status = $this->determineStatus($attendance);
        
        return view('staff.attendance', [
            'attendance' => $attendance,
            'status' => $status,
            'attendanceStatus' => $status,
        ]);
    }

    /**
     * 出勤処理
     */
    public function clockIn(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // 今日既に出勤しているかチェック
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->first();
        
        if ($existing) {
            return redirect()->route('staff.attendance')->withErrors(['message' => '既に出勤済みです']);
        }
        
        // 出勤記録を作成
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now(),
            'break_times' => null,
            'remarks' => null,
            'approval_status' => 0,
        ]);
        
        return redirect()->route('staff.attendance');
    }

    /**
     * 休憩開始処理
     */
    public function breakStart(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->first();
        
        if (!$attendance) {
            return redirect()->route('staff.attendance')->withErrors(['message' => '出勤記録がありません']);
        }
        
        // 休憩時間配列を取得
        $breakTimes = $attendance->break_times ?? [];
        
        // 新しい休憩開始を追加
        $breakTimes[] = [
            'start' => Carbon::now()->format('H:i:s'),
            'end' => null,
        ];
        
        $attendance->update(['break_times' => $breakTimes]);
        
        return redirect()->route('staff.attendance');
    }

    /**
     * 休憩終了処理
     */
    public function breakEnd(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->first();
        
        if (!$attendance) {
            return redirect()->route('staff.attendance')->withErrors(['message' => '出勤記録がありません']);
        }
        
        $breakTimes = $attendance->break_times ?? [];
        
        // 最後の休憩の終了時刻を設定
        if (!empty($breakTimes)) {
            $lastIndex = count($breakTimes) - 1;
            if ($breakTimes[$lastIndex]['end'] === null) {
                $breakTimes[$lastIndex]['end'] = Carbon::now()->format('H:i:s');
            }
        }
        
        $attendance->update(['break_times' => $breakTimes]);
        
        return redirect()->route('staff.attendance');
    }

    /**
     * 退勤処理
     */
    public function clockOut(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->first();
        
        if (!$attendance) {
            return redirect()->route('staff.attendance')->withErrors(['message' => '出勤記録がありません']);
        }
        
        if ($attendance->clock_out) {
            return redirect()->route('staff.attendance')->withErrors(['message' => '既に退勤済みです']);
        }
        
        $attendance->update([
            'clock_out' => Carbon::now(),
        ]);
        
        return redirect()->route('staff.attendance');
    }

    /**
     * ステータスを判定
     */
    private function determineStatus($attendance)
    {
        if (!$attendance) {
            return 'before'; // 勤務外
        }
        
        if ($attendance->clock_out) {
            return 'finished'; // 退勤済
        }
        
        $breakTimes = $attendance->break_times ?? [];
        
        // 最後の休憩が終了していないか確認
        if (!empty($breakTimes)) {
            $lastBreak = end($breakTimes);
            if ($lastBreak['end'] === null) {
                return 'break'; // 休憩中
            }
        }
        
        return 'working'; // 出勤中
    }

    /**
     * 勤怠一覧画面を表示
     */
    public function attendanceList(Request $request)
    {
        $user = auth()->user();
        
        // 表示月を取得（指定がなければ当月）
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $targetMonth = Carbon::parse($month . '-01');
        
        // 前月・翌月
        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');
        
        // 月の開始日と終了日
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();
        
        // 月のすべての日付を生成
        $attendances = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // その日の勤怠データを取得
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('clock_in', $dateStr)
                ->first();
            
            $attendanceData = [
                'date' => $currentDate->copy(),
                'date_formatted' => $currentDate->format('m/d') . '(' . $this->getJapaneseDayOfWeek($currentDate) . ')',
                'attendance' => $attendance,
                'clock_in_time' => null,
                'clock_out_time' => null,
                'break_duration' => null,
                'total_work_time' => null,
            ];
            
            if ($attendance) {
                // 出勤時刻
                $attendanceData['clock_in_time'] = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
                
                // 退勤時刻
                $attendanceData['clock_out_time'] = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';
                
                // 休憩時間を計算
                $breakMinutes = 0;
                if ($attendance->break_times) {
                    foreach ($attendance->break_times as $breakTime) {
                        if (isset($breakTime['start']) && isset($breakTime['end'])) {
                            $start = Carbon::parse($dateStr . ' ' . $breakTime['start']);
                            $end = Carbon::parse($dateStr . ' ' . $breakTime['end']);
                            $breakMinutes += $end->diffInMinutes($start);
                        }
                    }
                }
                
                if ($breakMinutes > 0) {
                    $hours = floor($breakMinutes / 60);
                    $minutes = $breakMinutes % 60;
                    $attendanceData['break_duration'] = sprintf('%d:%02d', $hours, $minutes);
                }
                
                // 合計勤務時間を計算
                if ($attendance->clock_in && $attendance->clock_out) {
                    $clockIn = Carbon::parse($attendance->clock_in);
                    $clockOut = Carbon::parse($attendance->clock_out);
                    $totalMinutes = $clockOut->diffInMinutes($clockIn) - $breakMinutes;
                    
                    if ($totalMinutes > 0) {
                        $hours = floor($totalMinutes / 60);
                        $minutes = $totalMinutes % 60;
                        $attendanceData['total_work_time'] = sprintf('%d:%02d', $hours, $minutes);
                    }
                }
            }
            
            $attendances[] = (object) $attendanceData;
            $currentDate->addDay();
        }
        
        return view('staff.attendance-list', [
            'attendances' => $attendances,
            'displayMonth' => $targetMonth->format('Y/m'),
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'attendanceStatus' => 'before', // ヘッダーナビゲーション用
        ]);
    }

    /**
     * 日本語の曜日を取得
     */
    private function getJapaneseDayOfWeek($date)
    {
        $daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];
        return $daysOfWeek[$date->dayOfWeek];
    }

    /**
     * 勤怠詳細画面を表示
     */
    public function attendanceDetail($id)
    {
        $user = auth()->user();
        
        // 自分の勤怠データのみ取得
        $attendance = Attendance::with('user')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        return view('staff.attendance-detail', [
            'attendance' => $attendance,
            'attendanceStatus' => 'before', // ヘッダーナビゲーション用
        ]);
    }

    /**
     * 勤怠詳細を更新（修正申請）
     */
    public function updateAttendance(StaffAttendanceUpdateRequest $request, $id)
    {
        $user = auth()->user();
        
        // 自分の勤怠データのみ取得
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // 承認待ちの場合は更新不可
        if ($attendance->approval_status == 1) {
            return redirect()->back()->withErrors(['message' => '承認待ちのため修正はできません。']);
        }
        
        // 出勤日の日付を取得
        $date = Carbon::parse($attendance->clock_in)->format('Y-m-d');
        
        // 勤怠データを更新
        $clockIn = Carbon::parse($date . ' ' . $request->clock_in);
        $clockOut = Carbon::parse($date . ' ' . $request->clock_out);
        
        // 休憩時間の整形（空の休憩時間を除外）
        $breakTimes = [];
        foreach ($request->break_times ?? [] as $breakTime) {
            if (!empty($breakTime['start']) && !empty($breakTime['end'])) {
                $breakTimes[] = [
                    'start' => $breakTime['start'],
                    'end' => $breakTime['end'],
                ];
            }
        }
        
        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_times' => $breakTimes ?: null,
            'remarks' => $request->remarks,
            'approval_status' => 1, // 承認待ちに設定
        ]);
        
        return redirect()->route('staff.attendance.list')->with('success', '修正申請を送信しました');
    }

    /**
     * 申請一覧画面を表示
     */
    public function requestsList(Request $request)
    {
        $user = auth()->user();
        $activeTab = $request->input('tab', 'pending'); // デフォルトは承認待ち
        
        // タブに応じて申請を取得
        if ($activeTab === 'approved') {
            // 承認済み（approval_status = 2）
            $requests = Attendance::with('user')
                ->where('user_id', $user->id)
                ->where('approval_status', 2)
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            // 承認待ち（approval_status = 1）
            $requests = Attendance::with('user')
                ->where('user_id', $user->id)
                ->where('approval_status', 1)
                ->orderBy('updated_at', 'desc')
                ->get();
        }
        
        return view('staff.requests', [
            'requests' => $requests,
            'activeTab' => $activeTab,
            'attendanceStatus' => 'before', // ヘッダーナビゲーション用
        ]);
    }
}
