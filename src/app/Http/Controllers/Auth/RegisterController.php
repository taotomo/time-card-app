<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * 会員登録画面を表示
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * 会員登録処理
     */
    public function store(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // ログイン後、メール認証誘導画面へリダイレクト
        auth()->login($user);
        
        return redirect()->route('verification.notice');
    }
}
