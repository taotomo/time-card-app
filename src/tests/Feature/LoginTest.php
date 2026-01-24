<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストID2-1: 一般ユーザーログイン - メールアドレスが未入力の場合
     */
    public function test_staff_login_fails_when_email_is_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    /**
     * テストID2-2: 一般ユーザーログイン - パスワードが未入力の場合
     */
    public function test_staff_login_fails_when_password_is_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    /**
     * テストID2-3: 一般ユーザーログイン - 認証情報が一致しない場合
     */
    public function test_staff_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('ログイン情報が登録されていません', session('errors')->first('email'));
    }

    /**
     * テストID3-1: 管理者ログイン - メールアドレスが未入力の場合
     */
    public function test_admin_login_fails_when_email_is_missing(): void
    {
        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    /**
     * テストID3-2: 管理者ログイン - パスワードが未入力の場合
     */
    public function test_admin_login_fails_when_password_is_missing(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    /**
     * テストID3-3: 管理者ログイン - 認証情報が一致しない場合
     */
    public function test_admin_login_fails_with_invalid_credentials(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('ログイン情報が登録されていません', session('errors')->first('email'));
    }
}
