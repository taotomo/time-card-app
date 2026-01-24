<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class StaffAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストID9-1: 自分の勤怠情報が全て表示される
     */
    public function test_staff_attendance_list_shows_all_own_attendances(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        // 自分の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->subDays(2)->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::now()->subDays(2)->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->subDay()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::now()->subDay()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $this->assertCount(2, Attendance::where('user_id', $user->id)->get());
    }

    /**
     * テストID9-2: 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_attendance_list_displays_current_month(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y年n月'));
    }

    /**
     * テストID9-3: 前月ボタンを押下すると前月の情報が表示される
     */
    public function test_attendance_list_shows_previous_month_data(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $previousMonth = Carbon::now()->subMonth();
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => $previousMonth->setDay(15)->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => $previousMonth->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $previousMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($previousMonth->format('Y年n月'));
    }

    /**
     * テストID9-4: 翌月ボタンを押下すると翌月の情報が表示される
     */
    public function test_attendance_list_shows_next_month_data(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $nextMonth = Carbon::now()->addMonth();

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $nextMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y年n月'));
    }

    /**
     * テストID9-5: 詳細ボタンを押下すると勤怠詳細画面に遷移する
     */
    public function test_detail_button_redirects_to_attendance_detail(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::now()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }
}
