<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
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
    public function update(Request $request, $id)
    {
        $attendance = \App\Models\Attendance::findOrFail($id);

        // 承認待ちの場合は修正不可
        if ($attendance->approval_status === 1) {
            return back()->withErrors(['message' => '承認待ちのため修正はできません。']);
        }

        // バリデーション
        $validated = $request->validate([
            'clock_in_month' => 'required|integer|min:1|max:12',
            'clock_in_day' => 'required|integer|min:1|max:31',
            'clock_in_time' => 'required',
            'clock_out_time' => 'nullable',
            'break_start_1' => 'nullable',
            'break_end_1' => 'nullable',
            'break_start_2' => 'nullable',
            'break_end_2' => 'nullable',
            'remarks' => 'required',
        ], [
            'remarks.required' => '備考を記入してください',
        ]);

        // 年月日を組み立て
        $year = Carbon::parse($attendance->clock_in)->format('Y');
        $clockInDate = sprintf('%s-%02d-%02d', $year, $validated['clock_in_month'], $validated['clock_in_day']);

        // 出勤・退勤時刻の結合
        $clockInDateTime = $clockInDate . ' ' . $validated['clock_in_time'];
        $clockOutDateTime = $validated['clock_out_time'] 
            ? $clockInDate . ' ' . $validated['clock_out_time']
            : null;

        // 出勤・退勤時刻のバリデーション
        if ($clockOutDateTime && Carbon::parse($clockInDateTime)->gte(Carbon::parse($clockOutDateTime))) {
            return back()->withErrors(['time_error' => '出勤時間もしくは退勤時間が不適切な値です'])->withInput();
        }

        // 休憩時間の設定と検証
        $breakTimes = [];
        
        // 休憩1
        if ($validated['break_start_1'] && $validated['break_end_1']) {
            $breakStart1 = Carbon::parse($clockInDate . ' ' . $validated['break_start_1']);
            $breakEnd1 = Carbon::parse($clockInDate . ' ' . $validated['break_end_1']);
            
            // 休憩開始が出勤時刻より前、または退勤時刻より後
            if ($breakStart1->lt(Carbon::parse($clockInDateTime)) || 
                ($clockOutDateTime && $breakStart1->gt(Carbon::parse($clockOutDateTime)))) {
                return back()->withErrors(['break_error' => '休憩時間が不適切な値です'])->withInput();
            }
            
            // 休憩終了が退勤時刻より後
            if ($clockOutDateTime && $breakEnd1->gt(Carbon::parse($clockOutDateTime))) {
                return back()->withErrors(['break_error' => '休憩時間もしくは退勤時間が不適切な値です'])->withInput();
            }
            
            $breakTimes[] = [
                'start' => $validated['break_start_1'],
                'end' => $validated['break_end_1'],
            ];
        }

        // 休憩2
        if ($validated['break_start_2'] && $validated['break_end_2']) {
            $breakStart2 = Carbon::parse($clockInDate . ' ' . $validated['break_start_2']);
            $breakEnd2 = Carbon::parse($clockInDate . ' ' . $validated['break_end_2']);
            
            // 休憩開始が出勤時刻より前、または退勤時刻より後
            if ($breakStart2->lt(Carbon::parse($clockInDateTime)) || 
                ($clockOutDateTime && $breakStart2->gt(Carbon::parse($clockOutDateTime)))) {
                return back()->withErrors(['break_error' => '休憩時間が不適切な値です'])->withInput();
            }
            
            // 休憩終了が退勤時刻より後
            if ($clockOutDateTime && $breakEnd2->gt(Carbon::parse($clockOutDateTime))) {
                return back()->withErrors(['break_error' => '休憩時間もしくは退勤時間が不適切な値です'])->withInput();
            }
            
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
     * 申請一覧画面を表示
     */
    public function requestsList(Request $request)
    {
        $activeTab = $request->input('tab', 'pending'); // デフォルトは承認待ち
        
        // タブに応じて申請を取得
        if ($activeTab === 'approved') {
            // 承認済み（approval_status = 2）
            $requests = \App\Models\Attendance::with('user')
                ->where('approval_status', 2)
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            // 承認待ち（approval_status = 1）
            $requests = \App\Models\Attendance::with('user')
                ->where('approval_status', 1)
                ->orderBy('updated_at', 'desc')
                ->get();
        }
        
        return view('attendance.requests', [
            'requests' => $requests,
            'activeTab' => $activeTab,
        ]);
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
        if ($attendance->approval_status != 1) {
            return redirect()->back()->withErrors(['message' => 'この申請は承認できません。']);
        }
        
        // approval_status を承認済み(2)に変更
        $attendance->update([
            'approval_status' => 2,
        ]);
        
        return redirect()->route('admin.requests', ['tab' => 'approved'])
            ->with('success', '申請を承認しました');
    }
}
