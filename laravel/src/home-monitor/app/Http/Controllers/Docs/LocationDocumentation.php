<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Attributes as OA;

class LocationDocumentation
{
    /**
     * List locations.
     */
    #[OA\Get(
        path: '/api/locations',
        tags: ['locations'],
        operationId: 'locationIndex',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'Optional pagination index',
        schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 1000),
        example: 1
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
            type: 'object',
            required: ['locations', 'links'],
            properties: [
                new OA\Property(
                    property: 'locations',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['id', 'name', 'description'],
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Bedroom'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Upstairs bedroom'),
                        ],
                    )
                ),
                new OA\Property(
                    property: 'links',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['href', 'rel', 'method'],
                        properties: [
                            new OA\Property(property: 'href', type: 'string', example: 'http://localhost/api/locations?page=2'),
                            new OA\Property(property: 'rel', type: 'string', enum: ['next', 'prev'], example: 'next'),
                            new OA\Property(property: 'method', type: 'string', example: 'GET'),
                        ]
                    ),
                    description: 'Pagination links for navigating between pages'
                ),
            ],
            description: 'Returns all locations if no page parameter is provided, or a paginated subset if page is specified'
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function index() {}

    /**
     * Store a newly created location.
     */
    #[OA\Post(
        path: '/api/locations',
        tags: ['locations'],
        operationId: 'locationStore',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 255, example: 'Bedroom'),
                new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 2000, example: 'Upstairs bedroom'),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Created',
        content: new OA\JsonContent(
            type: 'object',
            required: ['location'],
            properties: [
                new OA\Property(
                    property: 'location',
                    type: 'object',
                    required: ['id', 'name', 'description'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Bedroom'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Upstairs bedroom'),
                    ],
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function store() {}

    /**
     * Show details for the specified location, including its devices.
     */
    #[OA\Get(
        path: '/api/locations/{location_id}',
        tags: ['locations'],
        operationId: 'locationShow',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'location_id',
        in: 'path',
        required: true,
        description: 'The location ID',
        schema: new OA\Schema(type: 'integer', minimum: 1),
        example: 1
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
            type: 'object',
            required: ['location'],
            properties: [
                new OA\Property(
                    property: 'location',
                    type: 'object',
                    required: ['id', 'name', 'description', 'devices'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Bedroom'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Upstairs bedroom'),
                        new OA\Property(
                            property: 'devices',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                required: ['id', 'name', 'is_active'],
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 12),
                                    new OA\Property(property: 'name', type: 'string', example: 'Govee BT Bedroom'),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                ]
                            )
                        ),
                    ],
                ),
            ],
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing location')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function show() {}

    /**
     * Update the specified location.
     */
    #[OA\Put(
        path: '/api/locations/{location_id}',
        tags: ['locations'],
        operationId: 'locationUpdate',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'location_id',
        in: 'path',
        required: true,
        description: 'The location ID',
        schema: new OA\Schema(type: 'integer', minimum: 1),
        example: 1
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 255, example: 'Bedroom'),
                new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 2000, example: 'Upstairs bedroom'),
            ],
        ),
    )]
    #[OA\Response(response: 204, description: 'No Content')]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing location')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function update() {}

    /**
     * Remove the specified location from storage.
     */
    #[OA\Delete(
        path: '/api/locations/{location_id}',
        tags: ['locations'],
        operationId: 'locationDestroy',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'location_id',
        in: 'path',
        required: true,
        description: 'The location ID',
        schema: new OA\Schema(type: 'integer', minimum: 1),
        example: 1
    )]
    #[OA\Response(response: 204, description: 'No Content')]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing location')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function destroy() {}
}
