<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Attributes as OA;

class UserDocumentation
{
    /**
     * Create a user.
     */
    #[OA\Post(
        path: '/api/users/register',
        tags: ['users'],
        operationId: 'userRegister',
        description: "Register a new user and return an API token with all abilities.",
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation', 'device_name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane.doe@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'secretpassword'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'secretpassword'),
                new OA\Property(property: 'device_name', type: 'string', maxLength: 100, example: 'My Device'),
            ],
        ),
    )]
    #[OA\Schema(
        schema: 'ApiToken',
        type: 'object',
        required: ['user', 'token', 'token_type'],
        properties: [
            new OA\Property(property: 'user', type: 'integer', example: 15),
            new OA\Property(property: 'token', type: 'string', example: '1|VG5I1lX11p7T7IoWr5wzgUNu66suSmm2V2dIen0V0189d453'),
            new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiToken'),
    )]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function create() {}

    /**
     * Login a user.
     */
    #[OA\Post(
        path: '/api/users/login',
        tags: ['users'],
        operationId: 'userLogin',
        description: "Return an API token for the user with all abilities.",
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password', 'device_name'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane.doe@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'secretpassword'),
                new OA\Property(property: 'device_name', type: 'string', maxLength: 100, example: 'My Device'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiToken'),
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function login() {}

    /**
     * Logout the current user.
     */
    #[OA\Post(
        path: '/api/users/logout',
        tags: ['users'],
        operationId: 'userLogout',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
            type: 'object',
            required: ['message'],
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
            ],
        )
    )]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function logout() {}

    /**
     * Get details for the currently logged-in user.
     */
    #[OA\Get(
        path: '/api/users',
        tags: ['users'],
        operationId: 'userGet',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
            type: 'object',
            required: ['user'],
            properties: [
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    required: [],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 15),
                        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane.doe@example.com'),
                        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', example: '2023-01-30T13:00:00Z'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2023-01-30T13:00:00Z'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2023-01-30T13:00:00Z'),
                    ],
                ),
            ]
        ),
    )]
    #[OA\Response(response: 422, description: 'Validation exception')]
    public static function show() {}
}