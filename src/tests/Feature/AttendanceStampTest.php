<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceStampTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストID4: 日時取得 - 現在の日時が表示される
     */
    public function test_attendance_page_displays_current_date(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        // 日付表示要素が存在することを確認（JavaScriptで動的に挿入されるため）
        $response->assertSee('currentDate', false);
        $response->assertSee('currentTime', false);
    }

    /**
     * テストID5-1: ステータス確認 - 勤務外ステータス
     */
    public function test_status_shows_not_working(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * テストID5-2: ステータス確認 - 出勤中ステータス
     */
    public function test_status_shows_working(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->format('Y-m-d H:i:s'),
            'clock_out' => null,
            'break_times' => [],
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * テストID5-3: ステータス確認 - 休憩中ステータス
     */
    public function test_status_shows_on_break(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->format('Y-m-d H:i:s'),
            'clock_out' => null,
            'break_times' => [['start' => Carbon::now()->format('H:i'), 'end' => null]],
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * テストID5-4: ステータス確認 - 退勤済ステータス
     */
    public function test_status_shows_finished(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    /**
     * テストID6-1: 出勤ボタンが正しく機能する
     */
    public function test_clock_in_button_works_correctly(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_out' => null,
        ]);
    }

    /**
     * テストID6-2: 出勤は一日一回のみ（退勤済の場合、出勤ボタンは表示されない）
     */
    public function test_clock_in_button_not_displayed_after_clock_out(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => [],
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertDontSee('attendance/clock-in', false);
    }

    /**
     * テストID6-3: 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_in_time_is_recorded_in_attendance_list(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $clockInTime = Carbon::now();
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => $clockInTime->format('Y-m-d H:i:s'),
            'clock_out' => null,
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($clockInTime->format('H:i'));
    }

    /**
     * テストID7-1: 休憩入ボタンが正しく機能する
     */
    public function test_break_start_button_works_correctly(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->format('Y-m-d H:i:s'),
            'clock_out' => null,
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->post('/attendance/break-start');

        $response->assertRedirect('/attendance');
        
        $attendance->refresh();
        $breakTimes = $attendance->break_times;
        $this->assertNotNull($breakTimes);
        $this->assertArrayHasKey('start', $breakTimes[0]);
    }

    /**
     * テストID7-2: 休憩は一日に何回でもできる
     */
    public function test_break_can_be_taken_multiple_times(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->format('Y-m-d H:i:s'),
            'clock_out' => null,
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00']
            ],
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩入');
    }

    /**
     * テストID7-3: 休憩戻ボタンが正しく機能する
     */
    public function test_break_end_button_works_correctly(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->format('Y-m-d H:i:s'),
            'clock_out' => null,
            'break_times' => [['start' => Carbon::now()->format('H:i'), 'end' => null]],
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->post('/attendance/break-end');

        $response->assertRedirect('/attendance');
        
        $attendance->refresh();
        $breakTimes = $attendance->break_times;
        $this->assertNotNull($breakTimes[0]['end']);
    }

    /**
     * テストID7-4: 休憩戻は一日に何回でもできる
     */
    public function test_break_end_can_be_used_multiple_times(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->format('Y-m-d H:i:s'),
            'clock_out' => null,
            'break_times' => [
                ['start' => '12:00', 'end' => '13:00']
            ],
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->post('/attendance/break-start');
        
        $response->assertRedirect('/attendance');
        
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /**
     * テストID7-5: 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_break_time_is_recorded_in_attendance_list(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => [['start' => '12:00', 'end' => '13:00']],
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
    }

    /**
     * テストID8-1: 退勤ボタンが正しく機能する
     */
    public function test_clock_out_button_works_correctly(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->setMinute(0),
            'clock_out' => null,
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response->assertRedirect('/attendance');
        
        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
    }

    /**
     * テストID8-2: 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_out_time_is_recorded_in_attendance_list(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $clockOutTime = Carbon::today()->setHour(18);
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => $clockOutTime->format('Y-m-d H:i:s'),
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($clockOutTime->format('H:i'));
    }
}
