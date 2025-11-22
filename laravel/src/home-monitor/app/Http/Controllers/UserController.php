<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Database\QueryException;
use Illuminate\Http\{Request, Response};
use Illuminate\Support\Facades\{Hash, Log, Validator};

class UserController extends Controller
{
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
            Log::error('Database insert failed', [
                'route' => 'UserController::register',
                'exception' => $e->getMessage(),
            ]);

            return response()->json(
                ['errors' => ['server' => ['Database error occurred']]],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
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
            Log::error('Database update failed', [
                'route' => 'UserController::login',
                'exception' => $e->getMessage(),
            ]);

            return response()->json(
                ['errors' => ['server' => ['Database error occurred']]],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Logout the current user.
     * @see \App\Http\Controllers\Docs\UserDocumentation::logout() for API documentation
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(
                ['message' => 'Logged out successfully'],
                Response::HTTP_OK,
            );

        } catch (QueryException $e) {
            Log::error('Database update failed', [
                'route' => 'UserController::logout',
                'user_id' => $request->user()->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json(
                ['errors' => ['server' => ['Database error occurred']]],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Get details for the currently logged-in user.
     * @see \App\Http\Controllers\Docs\UserDocumentation::show() for API documentation
     */
    public function show(Request $request)
    {
        return response()->json(
            ['user' => $request->user()],
            Response::HTTP_OK,
        );
    }
}
