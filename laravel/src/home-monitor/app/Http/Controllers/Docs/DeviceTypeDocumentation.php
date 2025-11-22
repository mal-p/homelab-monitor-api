<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Attributes as OA;

class DeviceTypeDocumentation
{
    /**
     * List device types.
     */
    #[OA\Get(
        path: '/api/device-types',
        tags: ['device-types'],
        operationId: 'deviceTypeIndex',
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
            required: ['device_types', 'links'],
            properties: [
                new OA\Property(
                    property: 'device_types',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['id', 'name', 'description'],
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Bluetooth device'),
                            new OA\Property(property: 'description', type: 'string', example: 'Govee H5075 Bluetooth temperature/humidity'),
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
                            new OA\Property(property: 'href', type: 'string', example: 'http://localhost/api/device-types?page=2'),
                            new OA\Property(property: 'rel', type: 'string', enum: ['next', 'prev'], example: 'next'),
                            new OA\Property(property: 'method', type: 'string', example: 'GET'),
                        ]
                    ),
                    description: 'Pagination links for navigating between pages'
                ),
            ],
            description: 'Returns all device types if no page parameter is provided, or a paginated subset if page is specified'
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function index() {}

    /**
     * Store a newly created device type.
     */
    #[OA\Post(
        path: '/api/device-types',
        tags: ['device-types'],
        operationId: 'deviceTypeStore',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 255, example: 'Bluetooth device'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Govee H5075 Bluetooth temperature/humidity'),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Created',
        content: new OA\JsonContent(
            type: 'object',
            required: ['device_type'],
            properties: [
                new OA\Property(
                    property: 'device_type',
                    type: 'object',
                    required: ['id', 'name', 'description'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Bluetooth device'),
                        new OA\Property(property: 'description', type: 'string', example: 'Govee H5075 Bluetooth temperature/humidity'),
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
     * Show all information for the specified device type.
     */
    #[OA\Get(
        path: '/api/device-types/{device_type_id}',
        tags: ['device-types'],
        operationId: 'deviceTypeShow',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'device_type_id',
        in: 'path',
        required: true,
        description: 'The device type ID',
        schema: new OA\Schema(type: 'integer', minimum: 1),
        example: 1
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
            type: 'object',
            required: ['device_type'],
            properties: [
                new OA\Property(
                    property: 'device_type',
                    type: 'object',
                    required: ['id', 'name', 'description'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Bluetooth device'),
                        new OA\Property(property: 'description', type: 'string', example: 'Govee H5075 Bluetooth temperature/humidity'),
                    ],
                ),
            ],
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing device')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function show() {}

    /**
     * Update the specified device type.
     */
    #[OA\Put(
        path: '/api/device-types/{device_type_id}',
        tags: ['device-types'],
        operationId: 'deviceTypeUpdate',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'device_type_id',
        in: 'path',
        required: true,
        description: 'The device type ID',
        schema: new OA\Schema(type: 'integer', minimum: 1),
        example: 1
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 255, example: 'Bluetooth device'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Govee H5075 Bluetooth temperature/humidity'),
            ],
        ),
    )]
    #[OA\Response(response: 204, description: 'No Content')]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing device parameter')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function update() {}

    /**
     * Remove the specified device type from storage.
     */
    #[OA\Delete(
        path: '/api/device-types/{device_type_id}',
        tags: ['device-types'],
        operationId: 'deviceTypeDestroy',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'device_type_id',
        in: 'path',
        required: true,
        description: 'The device type ID',
        schema: new OA\Schema(type: 'integer', minimum: 1),
        example: 1
    )]
    #[OA\Response(response: 204, description: 'No Content')]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing device parameter')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function destroy() {}
}