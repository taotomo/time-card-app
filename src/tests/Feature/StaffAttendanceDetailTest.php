<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class StaffAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストID10-1: 勤怠詳細画面の名前がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_shows_logged_in_user_name(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'name' => '山田太郎']);
        
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
        $response->assertSee('山田太郎');
    }

    /**
     * テストID10-2: 勤怠詳細画面の日付が選択した日付になっている
     */
    public function test_attendance_detail_shows_selected_date(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $date = Carbon::now()->subDays(3);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => $date->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => $date->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee($date->format('Y年'));
        $response->assertSee($date->format('n月j日'));
    }

    /**
     * テストID10-3: 出勤・退勤時刻がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_clock_in_out_times(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->setTime(9, 30)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::now()->setTime(18, 45)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('09:30');
        $response->assertSee('18:45');
    }

    /**
     * テストID10-4: 休憩時刻がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_break_times(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::now()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => [['start' => '12:00', 'end' => '13:00']],
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    /**
     * テストID11-1: 出勤時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    public function test_update_fails_when_clock_in_is_after_clock_out(): void
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

        $response = $this->actingAs($user)->put('/attendance/detail/' . $attendance->id, [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'break_times' => [],
            'remarks' => '修正',
        ]);

        $response->assertSessionHasErrors(['clock_in', 'clock_out']);
        $this->assertStringContainsString('出勤時間が不適切な値です', session('errors')->first('clock_in'));
    }

    /**
     * テストID11-2: 休憩開始時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    public function test_update_fails_when_break_start_is_after_clock_out(): void
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

        $response = $this->actingAs($user)->put('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_times' => [
                ['start' => '19:00', 'end' => '20:00']
            ],
            'remarks' => '修正',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * テストID11-3: 休憩終了時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    public function test_update_fails_when_break_end_is_after_clock_out(): void
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

        $response = $this->actingAs($user)->put('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_times' => [
                ['start' => '12:00', 'end' => '19:00']
            ],
            'remarks' => '修正',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * テストID11-4: 備考が未入力の場合、エラーメッセージが表示される
     */
    public function test_update_fails_when_remarks_is_missing(): void
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

        $response = $this->actingAs($user)->put('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_times' => [],
            'remarks' => '',
        ]);

        $response->assertSessionHasErrors(['remarks']);
        $this->assertEquals('備考を記入してください', session('errors')->first('remarks'));
    }

    /**
     * テストID11-5: 修正申請処理が実行される
     */
    public function test_update_request_is_submitted(): void
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

        $response = $this->actingAs($user)->put('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:30',
            'clock_out' => '18:30',
            'break_times' => [],
            'remarks' => '時刻修正',
        ]);

        $attendance->refresh();
        $this->assertEquals(1, $attendance->approval_status);
    }

    /**
     * テストID11-6: 承認待ちタブに自分の申請が全て表示される
     */
    public function test_pending_requests_show_all_user_requests(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::now()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200);
    }

    /**
     * テストID11-7: 承認済みタブに管理者が承認した申請が全て表示される
     */
    public function test_approved_requests_show_approved_requests(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::now()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
    }

    /**
     * テストID11-8: 詳細ボタンを押下すると勤怠詳細画面に遷移する
     */
    public function test_detail_button_in_requests_redirects_to_detail(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::now()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
    }
}
