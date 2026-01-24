<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者ユーザー
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 一般ユーザー
        $users = [
            [
                'name' => '山田太郎',
                'email' => 'yamada@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => '佐藤花子',
                'email' => 'sato@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => '鈴木一郎',
                'email' => 'suzuki@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => '田中美咲',
                'email' => 'tanaka@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => '高橋健太',
                'email' => 'takahashi@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $createdUsers[] = User::create($userData);
        }

        // 今日の勤怠データ
        foreach ($createdUsers as $index => $user) {
            if ($index < 3) {
                // 出勤済み（休憩中）
                Attendance::create([
                    'user_id' => $user->id,
                    'clock_in' => Carbon::today()->setHour(9)->setMinute(0),
                    'clock_out' => null,
                    'break_times' => [
                        ['start' => '12:00', 'end' => '13:00'],
                    ],
                    'remarks' => null,
                    'approval_status' => Attendance::STATUS_NORMAL,
                ]);
            } elseif ($index < 4) {
                // 出勤済み（勤務中）
                Attendance::create([
                    'user_id' => $user->id,
                    'clock_in' => Carbon::today()->setHour(9)->setMinute(30),
                    'clock_out' => null,
                    'break_times' => [],
                    'remarks' => null,
                    'approval_status' => Attendance::STATUS_NORMAL,
                ]);
            }
        }

        // 昨日の勤怠データ（退勤済み）
        foreach ($createdUsers as $user) {
            Attendance::create([
                'user_id' => $user->id,
                'clock_in' => Carbon::yesterday()->setHour(9)->setMinute(0),
                'clock_out' => Carbon::yesterday()->setHour(18)->setMinute(0),
                'break_times' => [
                    ['start' => '12:00', 'end' => '13:00'],
                ],
                'remarks' => null,
                'approval_status' => Attendance::STATUS_NORMAL,
            ]);
        }

        // 先週の勤怠データ（5日分）
        for ($i = 1; $i <= 5; $i++) {
            $date = Carbon::today()->subDays($i + 2);
            
            foreach ($createdUsers as $user) {
                $clockIn = $date->copy()->setHour(9)->setMinute(rand(0, 30));
                $clockOut = $date->copy()->setHour(18)->setMinute(rand(0, 30));
                
                Attendance::create([
                    'user_id' => $user->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'break_times' => [
                        ['start' => '12:00', 'end' => '13:00'],
                    ],
                    'remarks' => null,
                    'approval_status' => Attendance::STATUS_NORMAL,
                ]);
            }
        }

        // 修正申請データ（承認待ち）
        Attendance::create([
            'user_id' => $createdUsers[0]->id,
            'clock_in' => Carbon::today()->subDays(3)->setHour(9)->setMinute(0),
            'clock_out' => Carbon::today()->subDays(3)->setHour(18)->setMinute(0),
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '交通機関の遅延により遅刻しました',
            'approval_status' => Attendance::STATUS_PENDING,
        ]);

        Attendance::create([
            'user_id' => $createdUsers[1]->id,
            'clock_in' => Carbon::today()->subDays(4)->setHour(9)->setMinute(0),
            'clock_out' => Carbon::today()->subDays(4)->setHour(18)->setMinute(0),
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '体調不良のため早退しました',
            'approval_status' => Attendance::STATUS_PENDING,
        ]);

        // 承認済みデータ
        Attendance::create([
            'user_id' => $createdUsers[2]->id,
            'clock_in' => Carbon::today()->subDays(5)->setHour(9)->setMinute(0),
            'clock_out' => Carbon::today()->subDays(5)->setHour(18)->setMinute(0),
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'remarks' => '打刻忘れがありました',
            'approval_status' => Attendance::STATUS_APPROVED,
        ]);

        // 先月のデータ
        $lastMonth = Carbon::today()->subMonth();
        foreach ($createdUsers as $user) {
            for ($day = 1; $day <= 20; $day++) {
                $date = $lastMonth->copy()->setDay($day);
                
                // 週末をスキップ
                if ($date->isWeekend()) {
                    continue;
                }
                
                $clockIn = $date->copy()->setHour(9)->setMinute(rand(0, 30));
                $clockOut = $date->copy()->setHour(18)->setMinute(rand(0, 30));
                
                Attendance::create([
                    'user_id' => $user->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'break_times' => [
                        ['start' => '12:00', 'end' => '13:00'],
                    ],
                    'remarks' => null,
                    'approval_status' => Attendance::STATUS_NORMAL,
                ]);
            }
        }

        $this->command->info('ダミーデータの作成が完了しました！');
        $this->command->info('');
        $this->command->info('=== ログイン情報 ===');
        $this->command->info('管理者:');
        $this->command->info('  Email: admin@example.com');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('一般ユーザー:');
        foreach ($users as $userData) {
            $this->command->info('  Email: ' . $userData['email']);
            $this->command->info('  Password: password');
        }
        $this->command->info('');
        $this->command->info('合計ユーザー数: ' . (count($users) + 1));
        $this->command->info('合計勤怠データ数: ' . Attendance::count());
    }
}
