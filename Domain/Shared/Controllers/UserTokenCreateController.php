<?php

namespace Domain\Shared\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserTokenCreateController
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ], 401);
        }

        $token = $request->user()->createToken('token');

        return response()->json(['token' => $token->plainTextToken], 201);
    }
}
