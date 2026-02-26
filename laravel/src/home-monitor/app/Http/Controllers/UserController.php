<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Concerns\GeneratesDatabaseErrorResponses;

use Illuminate\Database\QueryException;
use Illuminate\Http\{Request, Response};
use Illuminate\Support\Facades\{Hash, Validator};

class UserController extends Controller
{
    use GeneratesDatabaseErrorResponses;

    const API_TOKEN_NAME_PREFIX = 'homelab-';

    /**
     * Create a user.
     * @see \App\Http\Controllers\Docs\UserDocumentation::create() for API documentation
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email'=> $request->email,
                'password' => Hash::make($request->password),
            ]);
    
            $token = $user->createToken(UserController::API_TOKEN_NAME_PREFIX . $request->device_name)->plainTextToken;
    
            return response()->json(
                ['user' => $user->id, 'token' => $token, 'token_type' => 'Bearer'],
                Response::HTTP_OK,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__);
        }
    }

    /**
     * Login a user.
     * @see \App\Http\Controllers\Docs\UserDocumentation::login() for API documentation
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(
                    ['errors' => ['email' => ['The provided credentials are incorrect.']]],
                    Response::HTTP_UNAUTHORIZED,
                );
            }

            $token = $user->createToken(UserController::API_TOKEN_NAME_PREFIX . $request->device_name)->plainTextToken;

            return response()->json(
                ['user' => $user->id, 'token' => $token, 'token_type' => 'Bearer'],
                Response::HTTP_OK,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__);
        }
    }

    /**
     * Logout the current user.
     * @see \App\Http\Controllers\Docs\UserDocumentation::logout() for API documentation
     */
    public function logout(Request $request)
    {
        $userId = $request->user()?->id;

        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json(
                ['message' => 'Logged out successfully'],
                Response::HTTP_OK,
            );

        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__, ['user_id' => $userId,]);
        }
    }

    /**
     * Logout all other tokens for the current user.
     * @see \App\Http\Controllers\Docs\UserDocumentation::logoutOtherTokens() for API documentation
     */
    public function logoutOtherTokens(Request $request)
    {
        $user = $request->user();
        $userId = $user?->id;

        try {
            $currentAccessToken = $user->currentAccessToken();
            $revokedCount = $user->tokens()->whereNot('id', $currentAccessToken->id)->delete();

            return response()->json(
                [
                    'message' => 'Other tokens logged out successfully',
                    'revoked_count' => $revokedCount,
                ],
                Response::HTTP_OK,
            );
        } catch (QueryException $e) {
            return $this->databaseErrorResponse($e, __FUNCTION__, ['user_id' => $userId]);
        }
    }

    /**
     * Get details for the currently logged-in user.
     * @see \App\Http\Controllers\Docs\UserDocumentation::show() for API documentation
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->makeHidden(['created_at', 'updated_at', 'email_verified_at']);
        }

        return response()->json(
            ['user' => $user],
            Response::HTTP_OK,
        );
    }
}
