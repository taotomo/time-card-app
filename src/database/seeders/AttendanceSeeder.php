<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 2023年6月1日のテストデータ
        $date = '2023-06-01';
        
        // 山田 太郎（user_id: 2）
        Attendance::create([
            'user_id' => 2,
            'clock_in' => $date . ' 09:00:00',
            'clock_out' => $date . ' 20:00:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '',
            'approval_status' => 0,
        ]);

        // 西 怜音（user_id: 3）
        Attendance::create([
            'user_id' => 3,
            'clock_in' => $date . ' 09:00:00',
            'clock_out' => $date . ' 18:00:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '',
            'approval_status' => 0,
        ]);

        // 堀田 一世（user_id: 4）
        Attendance::create([
            'user_id' => 4,
            'clock_in' => $date . ' 09:00:00',
            'clock_out' => $date . ' 18:00:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '',
            'approval_status' => 0,
        ]);

        // 山本 晃吾（user_id: 5）
        Attendance::create([
            'user_id' => 5,
            'clock_in' => $date . ' 09:00:00',
            'clock_out' => $date . ' 18:00:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '',
            'approval_status' => 0,
        ]);

        // 秋田 麻美（user_id: 6）
        Attendance::create([
            'user_id' => 6,
            'clock_in' => $date . ' 09:00:00',
            'clock_out' => $date . ' 18:00:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '',
            'approval_status' => 0,
        ]);

        // 中垣 綾美（user_id: 7）
        Attendance::create([
            'user_id' => 7,
            'clock_in' => $date . ' 09:00:00',
            'clock_out' => $date . ' 18:00:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '',
            'approval_status' => 0,
        ]);

        // 本日のテストデータ（今日の日付）
        $today = Carbon::today()->format('Y-m-d');
        
        Attendance::create([
            'user_id' => 2,
            'clock_in' => $today . ' 09:00:00',
            'clock_out' => $today . ' 18:00:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '',
            'approval_status' => 0,
        ]);

        Attendance::create([
            'user_id' => 3,
            'clock_in' => $today . ' 09:30:00',
            'clock_out' => $today . ' 17:30:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '体調不良のため早退',
            'approval_status' => 0,
        ]);

        // 承認待ちのテストデータ
        Attendance::create([
            'user_id' => 4,
            'clock_in' => $today . ' 08:30:00',
            'clock_out' => $today . ' 19:00:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
                ['start' => '15:00', 'end' => '15:30'],
            ],
            'remarks' => '残業対応',
            'approval_status' => 1, // 承認待ち
        ]);
    }
}
