<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function login()
    {
        $attributes = [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

        $input = request()->only(array_keys($attributes));

        $validator = Validator::make($input, $attributes);

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
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                ['username' => ['Invalid Username']],
            );
        }

        $user = $user->makeVisible(['password']);

        if (!Hash::check($input['password'], $user->password)) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                ['password' => ['Invalid Password']],
            );
        }

        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role->name,
            'level' => $user->unit == null ? null : $user->unit->level->slug,
            'unit' => $user->unit == null ? null : $user->unit->slug,
        ];

        $userId = $data['id'];

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "User ID : $userId",
            $data,
            null,
        );
    }
}
