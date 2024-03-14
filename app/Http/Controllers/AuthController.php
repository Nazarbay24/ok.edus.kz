<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\Student;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            "login" => "required|min:2|max:20",
            "password" => "required|min:2|max:20",
        ]);

        $user = Student::where('login', trim($request->login))
            ->where('pass', trim($request->password))
            ->first();

        if( !$user ) {
            return response()->json(['message' => __('Неправильный логин или пароль')], 400);
        }
        if($user->status == 0) {
            return response()->json(['message' => __('Аккаунт заблокирован')], 401);
        }

        $user->firebase_token = $request->firebase_token;
        $user->mobile_lang = $request->language;
        $user->last_visit = date("Y-m-d H:i:s");
        $user->device = 'mobile';
        $user->remote_ip = $request->ip();
        $user->save();

        $access_token = $user->createToken('access', ['student', 'access']);
        $refresh_token = $user->createToken('refresh', ['student', 'refresh'], config('sanctum.rt_expiration'));


        return response()->json([
            'access_token' => $access_token->plainTextToken,
            'refresh_token' => $refresh_token->plainTextToken,
            'profile' => [/*$user->only(['id', 'id_mektep', 'id_class', 'name', 'surname', 'lastname', 'iin', 'birthday', 'pol']*/
                'smart' => (bool)$user->mektep->smart,
                'id' => $user->id,
                'id_mektep' => $user->id_mektep,
                'id_class' => $user->id_class,
                'class' => $user->class->class,
                'class_group' => $user->class->group,
                'name' => $user->name,
                'surname' => $user->surname,
                'lastname' => $user->lastname,
                'iin' => $user->iin,
                'birthday' => $user->birthday,
                'pol' => $user->pol
            ]
        ], 200);
    }

    public function logout(Request $request) {
        if($request->user()->currentAccessToken()->delete()) {
            $user = $request->user();
            $user->firebase_token = null;
            $user->save();

            return response()->json([
                'message' => 'success',
            ], 200);
        }
        else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    public function refreshToken(Request $request) {
        $user = $request->user();

        if($user->status == 0) {
            return response()->json(['message' => __('Аккаунт заблокирован')], 401);
        }

        $request->user()->currentAccessToken()->delete();
        $access_token = $user->createToken('access', ['student', 'access'], config('sanctum.expiration'));
        $refresh_token = $user->createToken('refresh', ['student', 'refresh'], config('sanctum.rt_expiration'));

        $user->last_visit = date("Y-m-d H:i:s");
        $user->device = 'mobile';
        $user->remote_ip = $request->ip();
        $user->save();

        return response()->json([
            'access_token' => $access_token->plainTextToken,
            'refresh_token' => $refresh_token->plainTextToken,
        ], 200);
    }
}
