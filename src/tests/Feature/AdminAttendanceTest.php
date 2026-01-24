<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストID12-1: その日の全ユーザーの勤怠情報が正確に表示される
     */
    public function test_admin_attendance_list_shows_all_users_data(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user1->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'clock_in' => Carbon::today()->setHour(10)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(19)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertSee($user2->name);
    }

    /**
     * テストID12-2: 遷移した際に現在の日付が表示される
     */
    public function test_admin_attendance_list_displays_current_date(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(Carbon::today()->format('Y-m-d'));
    }

    /**
     * テストID12-3: 前日ボタンを押下すると前日の勤怠情報が表示される
     */
    public function test_admin_attendance_list_shows_previous_day_data(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        
        $previousDay = Carbon::yesterday();

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $previousDay->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee($previousDay->format('Y-m-d'));
    }

    /**
     * テストID12-4: 翌日ボタンを押下すると翌日の勤怠情報が表示される
     */
    public function test_admin_attendance_list_shows_next_day_data(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        
        $nextDay = Carbon::tomorrow();

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $nextDay->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee($nextDay->format('Y-m-d'));
    }

    /**
     * テストID13-1: 勤怠詳細画面に選択したデータが表示される
     */
    public function test_admin_attendance_detail_shows_selected_data(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /**
     * テストID13-2: 出勤時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    public function test_admin_update_fails_when_clock_in_is_after_clock_out(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($admin)->put('/admin/attendance/' . $attendance->id, [
            'clock_in_month' => Carbon::today()->month,
            'clock_in_day' => Carbon::today()->day,
            'clock_in_time' => '19:00',
            'clock_out_time' => '18:00',
            'break_start_1' => null,
            'break_end_1' => null,
            'break_start_2' => null,
            'break_end_2' => null,
            'remarks' => '修正',
        ]);

        $response->assertSessionHasErrors(['clock_in_time', 'clock_out_time']);
    }

    /**
     * テストID13-3: 休憩開始時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    public function test_admin_update_fails_when_break_start_is_after_clock_out(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($admin)->put('/admin/attendance/' . $attendance->id, [
            'clock_in_month' => Carbon::today()->month,
            'clock_in_day' => Carbon::today()->day,
            'clock_in_time' => '09:00',
            'clock_out_time' => '18:00',
            'break_start_1' => '19:00',
            'break_end_1' => '20:00',
            'break_start_2' => null,
            'break_end_2' => null,
            'remarks' => '修正',
        ]);

        $response->assertSessionHasErrors(['break_start_1']);
    }

    /**
     * テストID13-4: 休憩終了時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    public function test_admin_update_fails_when_break_end_is_after_clock_out(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($admin)->put('/admin/attendance/' . $attendance->id, [
            'clock_in_month' => Carbon::today()->month,
            'clock_in_day' => Carbon::today()->day,
            'clock_in_time' => '09:00',
            'clock_out_time' => '18:00',
            'break_start_1' => '12:00',
            'break_end_1' => '19:00',
            'break_start_2' => null,
            'break_end_2' => null,
            'remarks' => '修正',
        ]);

        $response->assertSessionHasErrors(['break_end_1']);
    }

    /**
     * テストID13-5: 備考が未入力の場合、エラーメッセージが表示される
     */
    public function test_admin_update_fails_when_remarks_is_missing(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($admin)->put('/admin/attendance/' . $attendance->id, [
            'clock_in_month' => Carbon::today()->month,
            'clock_in_day' => Carbon::today()->day,
            'clock_in_time' => '09:00',
            'clock_out_time' => '18:00',
            'break_start_1' => null,
            'break_end_1' => null,
            'break_start_2' => null,
            'break_end_2' => null,
            'remarks' => '',
        ]);

        $response->assertSessionHasErrors(['remarks']);
        $this->assertEquals('備考を記入してください', session('errors')->first('remarks'));
    }

    /**
     * テストID14-1: 全ユーザーの氏名とメールアドレスが表示される
     */
    public function test_admin_staff_list_shows_all_users(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user1 = User::factory()->create(['name' => '山田太郎', 'email' => 'yamada@example.com', 'email_verified_at' => now()]);
        $user2 = User::factory()->create(['name' => '佐藤花子', 'email' => 'sato@example.com', 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('佐藤花子');
        $response->assertSee('sato@example.com');
    }

    /**
     * テストID14-2: ユーザーの勤怠情報が正しく表示される
     */
    public function test_admin_staff_detail_shows_user_attendance(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id);

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /**
     * テストID14-3: 前月ボタンを押下すると前月の情報が表示される
     */
    public function test_admin_staff_detail_shows_previous_month(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $previousMonth = Carbon::now()->subMonth();

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '?month=' . $previousMonth->format('Y-m'));

        $response->assertStatus(200);
    }

    /**
     * テストID14-4: 翌月ボタンを押下すると翌月の情報が表示される
     */
    public function test_admin_staff_detail_shows_next_month(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $nextMonth = Carbon::now()->addMonth();

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '?month=' . $nextMonth->format('Y-m'));

        $response->assertStatus(200);
    }

    /**
     * テストID14-5: 詳細ボタンを押下すると勤怠詳細画面に遷移する
     */
    public function test_admin_staff_detail_button_redirects_to_attendance_detail(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '通常勤務',
            'approval_status' => \App\Models\Attendance::STATUS_NORMAL,
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
    }

    /**
     * テストID15-1: 承認待ちの修正申請が全て表示される
     */
    public function test_admin_pending_requests_show_all_pending(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '修正申請',
            'approval_status' => \App\Models\Attendance::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200);
    }

    /**
     * テストID15-2: 承認済みの修正申請が全て表示される
     */
    public function test_admin_approved_requests_show_all_approved(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '承認済み',
            'approval_status' => \App\Models\Attendance::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
    }

    /**
     * テストID15-3: 修正申請の詳細内容が正しく表示される
     */
    public function test_admin_request_detail_shows_correct_data(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => null,
            'remarks' => '修正申請',
            'approval_status' => \App\Models\Attendance::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->get('/admin/request/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /**
     * テストID15-4: 修正申請の承認処理が正しく行われる
     */
    public function test_admin_approve_request_works_correctly(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::today()->setHour(9)->format('Y-m-d H:i:s'),
            'clock_out' => Carbon::today()->setHour(18)->format('Y-m-d H:i:s'),
            'break_times' => [],
            'remarks' => '修正申請',
            'approval_status' => \App\Models\Attendance::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->put('/admin/request/' . $attendance->id . '/approve');

        $response->assertRedirect();
        
        $attendance->refresh();
        $this->assertEquals(2, $attendance->approval_status);
    }
}
