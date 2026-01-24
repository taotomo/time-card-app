<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;
use App\Models\User;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストID16-1: 会員登録後、認証メールが送信される
     */
    public function test_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertNotNull($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * テストID16-2: メール認証誘導画面で認証サイトへのリンクが表示される
     */
    public function test_verification_notice_page_shows_verification_link(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('認証はこちらから');
    }

    /**
     * テストID16-3: メール認証を完了すると、勤務登録画面に遷移する
     */
    public function test_email_verification_redirects_to_attendance_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/attendance');
        
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }
}
