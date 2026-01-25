<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            $extname = '';
            if($user->extname){
                $extname = ' '.$user->extname;
            }

            $middlename = '';
            if ($user->middlename) {
                $middlename = ' ' . strtoupper($user->middlename[0]) . '.';
            }

            $photo = $user->photo ? asset("storage/$user->photo") : asset('images/no-image-icon.png');

            return response()->json([
                'message' => 'success',
                'userId' => $user->id,
                'userRole' => $user->role_id, 
                'userName' => $user->lastname.', '.$user->firstname.$extname.$middlename,
                'userPhoto' => $photo,
                'token' => $token
            ]);
        }

        return response()->json(['message' => 'Invalid credentials']);
    }

    // public function register(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:6',
    //     ]);

    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //     ]);

    //     $token = $user->createToken('authToken')->plainTextToken;

    //     return response()->json(['user' => $user, 'token' => $token]);
    // }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout()
    {
        // Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
