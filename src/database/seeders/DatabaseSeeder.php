<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. 管理者ユーザーを作成
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        // 2. 一般ユーザーを作成（3名）
        $users = [];
        
        $users[] = User::create([
            'name' => '西俊介',
            'email' => 'nishi@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $users[] = User::create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $users[] = User::create([
            'name' => '山田花子',
            'email' => 'hanako@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        // 3. 勤怠記録を作成（各ユーザーに対して過去1ヶ月分）
        foreach ($users as $user) {
            // 過去30日分のダミーデータを作成
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::today()->subDays($i);
                
                // 土日はスキップ（週末は勤務なし）
                if ($date->isWeekend()) {
                    continue;
                }

                // 出勤時刻: 9:00 ± ランダム0-30分
                $clockIn = $date->copy()->setTime(9, rand(0, 30));
                
                // 退勤時刻: 18:00 ± ランダム0-60分
                $clockOut = $date->copy()->setTime(18, rand(0, 60));
                
                // 休憩時間（12:00-13:00）
                $breakTimes = [
                    ['start' => '12:00', 'end' => '13:00']
                ];
                
                // たまに追加休憩を入れる
                if (rand(0, 3) === 0) {
                    $breakTimes[] = ['start' => '15:00', 'end' => '15:15'];
                }

                // 承認ステータス: 大半は通常(0)、たまに承認待ち(1)や承認済み(2)
                $approvalStatus = 0;
                if ($i < 3) {
                    $approvalStatus = 1; // 最近3日分は承認待ち
                } elseif ($i >= 3 && $i < 5) {
                    $approvalStatus = 2; // その前2日分は承認済み
                }

                Attendance::create([
                    'user_id' => $user->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'break_times' => $breakTimes,
                    'remarks' => $approvalStatus === 1 ? '遅刻のため修正申請' : null,
                    'approval_status' => $approvalStatus,
                ]);
            }
        }

        $this->command->info('✅ ダミーデータ作成完了！');
        $this->command->info('管理者: admin@example.com / password123');
        $this->command->info('一般ユーザー: nishi@example.com / password123');
        $this->command->info('一般ユーザー: yamada@example.com / password123');
        $this->command->info('一般ユーザー: hanako@example.com / password123');
    }
}
