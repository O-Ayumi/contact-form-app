<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    public function __invoke(Request $request)
    {
        $credentials = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'password.required' => 'パスワードを入力してください。',
        ])->validate();

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            return Auth::user();
        }

        throw ValidationException::withMessages([
            'email' => ['ログイン情報が登録されていません。'],
        ]);
    }
}
