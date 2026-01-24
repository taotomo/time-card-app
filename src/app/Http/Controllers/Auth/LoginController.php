<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Fortify;

class LoginController extends Controller
{
    /**
     * ログイン処理
     */
    public function login(LoginRequest $request)
    {
        // LoginRequestで既にバリデーション済み
        
        // 認証試行
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // FortifyのLoginResponseを使用してリダイレクト
            return app(LoginResponse::class);
        }

        // 認証失敗時
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }
}
