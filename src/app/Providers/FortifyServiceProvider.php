<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 登録後のリダイレクトをカスタマイズ
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                // 一般ユーザー登録後はメール認証誘導画面へ
                return redirect()->route('verification.notice');
            }
        });

        // ログイン後のリダイレクトをカスタマイズ
        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                $user = auth()->user();
                
                // 管理者の場合
                if ($user->email === 'admin@example.com') {
                    return redirect()->route('admin.attendance.list');
                }
                
                // 一般ユーザーの場合
                // メール認証が必要な場合は自動的にverifiedミドルウェアが処理
                return redirect()->route('staff.attendance');
            }
        });

        // メール認証後のリダイレクトをカスタマイズ
        $this->app->instance(VerifyEmailResponse::class, new class implements VerifyEmailResponse {
            public function toResponse($request)
            {
                // メール認証完了後は勤怠打刻画面へ
                return redirect()->route('staff.attendance');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // ビューの設定
        Fortify::loginView(function () {
            return view('staff.login');
        });

        Fortify::registerView(function () {
            return view('staff.register');
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // ログイン成功後のリダイレクト先を設定
        Fortify::authenticateUsing(function (Request $request) {
            // LoginRequestのバリデーションを使用
            $loginRequest = app(\App\Http\Requests\LoginRequest::class);
            $validator = validator($request->all(), $loginRequest->rules(), $loginRequest->messages());
            
            if ($validator->fails()) {
                return null;
            }
            
            $user = \App\Models\User::where('email', $request->email)->first();
            
            if ($user && \Hash::check($request->password, $user->password)) {
                return $user;
            }
            
            // ログイン情報が不正な場合
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['ログイン情報が登録されていません'],
            ]);
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
