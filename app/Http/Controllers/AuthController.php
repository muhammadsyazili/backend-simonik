<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class AuthController extends ApiController
{
    /**
     * Login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $attributes = [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        if ($validator->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        $user = User::with(['role', 'unit.level'])->firstWhere(['username' => $input['username']]);

        if (is_null($user)) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNAUTHORIZED,
                Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                null,
                ['user' => ['Username or Password is wrong']],
            );
        }

        $user->makeVisible(['password']);

        if (!Hash::check($input['password'], $user->password)) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNAUTHORIZED,
                Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                null,
                ['user' => ['Username or Password is wrong']],
            );
        }

        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role->name,
            'level' => is_null($user->unit) ? null : $user->unit->level->slug,
            'unit' => is_null($user->unit) ? null : $user->unit->slug,
            'actived' => $user->actived,
            'token' => $user->createToken('token_name')->plainTextToken,
        ];

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Login successfully",
            $data,
            null,
        );
    }

    /**
     * Logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Logout successfully",
            null,
            null,
        );
    }
}