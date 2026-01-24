<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストID1-1: 名前が未入力の場合、バリデーションエラーが表示される
     */
    public function test_register_fails_when_name_is_missing(): void
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals('お名前を入力してください', session('errors')->first('name'));
    }

    /**
     * テストID1-2: メールアドレスが未入力の場合、バリデーションエラーが表示される
     */
    public function test_register_fails_when_email_is_missing(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    /**
     * テストID1-3: パスワードが8文字未満の場合、バリデーションエラーが表示される
     */
    public function test_register_fails_when_password_is_less_than_8_characters(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードは8文字以上で入力してください', session('errors')->first('password'));
    }

    /**
     * テストID1-4: パスワード確認が一致しない場合、バリデーションエラーが表示される
     */
    public function test_register_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードと一致しません', session('errors')->first('password'));
    }

    /**
     * テストID1-5: パスワードが未入力の場合、バリデーションエラーが表示される
     */
    public function test_register_fails_when_password_is_missing(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    /**
     * テストID1-6: 正しい情報で登録すると、データベースに保存される
     */
    public function test_register_succeeds_with_valid_data(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }
}
