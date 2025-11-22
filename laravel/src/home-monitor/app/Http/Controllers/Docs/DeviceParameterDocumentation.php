<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Attributes as OA;

class DeviceParameterDocumentation
{
    /**
     * List device parameters.
     */
    #[OA\Get(
        path: '/api/device-parameters',
        tags: ['device-parameters'],
        operationId: 'deviceParameterIndex',
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
            required: ['device_parameters', 'links'],
            properties: [
                new OA\Property(
                    property: 'device_parameters',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['id', 'device_id', 'name', 'unit', 'alarm_active'],
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 3),
                            new OA\Property(property: 'device_id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Temperature'),
                            new OA\Property(property: 'unit', type: 'string', example: '°C'),
                            new OA\Property(property: 'alarm_active', type: 'boolean', example: false),
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
                            new OA\Property(property: 'href', type: 'string', example: 'http://localhost/api/device-parameters?page=2'),
                            new OA\Property(property: 'rel', type: 'string', enum: ['next', 'prev'], example: 'next'),
                            new OA\Property(property: 'method', type: 'string', example: 'GET'),
                        ]
                    ),
                    description: 'Pagination links for navigating between pages'
                ),
            ],
            description: 'Returns all device parameters if no page parameter is provided, or a paginated subset if page is specified'
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function index() {}

    /**
     * Store a newly created device parameter.
     */
    #[OA\Post(
        path: '/api/device-parameters',
        tags: ['device-parameters'],
        operationId: 'deviceParameterStore',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['device_id', 'name'],
            properties: [
                new OA\Property(property: 'device_id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 255, example: 'Temperature'),
                new OA\Property(property: 'unit', type: 'string', nullable: true, maxLength: 50, example: '°C'),
                new OA\Property(property: 'alarm_type', type: 'string', enum: ['none', 'low', 'high'], example: 'none'),
                new OA\Property(property: 'alarm_trigger', type: 'number', format: 'float', nullable: true, example: 30.1),
                new OA\Property(property: 'alarm_hysteresis', type: 'number', format: 'float', minimum: 0.0, nullable: true, example: 1.5),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Created',
        content: new OA\JsonContent(
            type: 'object',
            required: ['device_parameter'],
            properties: [
                new OA\Property(
                    property: 'device_parameter',
                    type: 'object',
                    required: ['id', 'device_id', 'name', 'unit', 'alarm_type', 'alarm_trigger', 'alarm_hysteresis'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 7),
                        new OA\Property(property: 'device_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Temperature'),
                        new OA\Property(property: 'unit', type: 'string', example: '°C'),
                        new OA\Property(property: 'alarm_type', type: 'string', example: 'none'),
                        new OA\Property(property: 'alarm_trigger', type: 'number', format: 'float', example: 30.1),
                        new OA\Property(property: 'alarm_hysteresis', type: 'number', format: 'float', example: 1.5),
                        new OA\Property(property: 'alarm_active', type: 'boolean', example: false),
                        new OA\Property(property: 'alarm_updated_at', type: 'string', format: 'date-time', example: '2023-01-30T13:00:00Z'),
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
     * Show all information for the specified device parameter.
     */
    #[OA\Get(
        path: '/api/device-parameters/{device_parameter_id}',
        tags: ['device-parameters'],
        operationId: 'deviceParameterShow',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'device_parameter_id',
        in: 'path',
        required: true,
        description: 'The device parameter ID',
        schema: new OA\Schema(type: 'integer', minimum: 1),
        example: 1
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
            type: 'object',
            required: ['device_parameter'],
            properties: [
                new OA\Property(
                    property: 'device_parameter',
                    type: 'object',
                    required: ['id', 'name', 'unit', 'alarm_type', 'alarm_trigger', 'alarm_hysteresis', 'alarm_active', 'alarm_updated_at', 'device'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 7),
                        new OA\Property(property: 'name', type: 'string', example: 'Temperature'),
                        new OA\Property(property: 'unit', type: 'string', example: '°C'),
                        new OA\Property(property: 'alarm_type', type: 'string', example: 'none'),
                        new OA\Property(property: 'alarm_trigger', type: 'number', format: 'float', example: 30.1),
                        new OA\Property(property: 'alarm_hysteresis', type: 'number', format: 'float', example: 1.5),
                        new OA\Property(property: 'alarm_active', type: 'boolean', example: false),
                        new OA\Property(property: 'alarm_updated_at', type: 'string', format: 'date-time', example: '2023-01-30T13:00:00Z'),
                        new OA\Property(
                            property: 'device',
                            type: 'object',
                            required: ['id', 'name', 'is_active', 'type'],
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Govee BT Bedroom'),
                                new OA\Property(property: 'serial_number', type: 'string', example: '56-79-A5-19-08-B7'),
                                new OA\Property(property: 'mpan', type: 'string', example: ''),
                                new OA\Property(property: 'location', type: 'string', example: 'Bedroom'),
                                new OA\Property(property: 'description', type: 'string', example: ''),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'type', type: 'string', example: 'Bluetooth device'),
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
                        ),
                    ],
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing device')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function show() {}

    /**
     * Update the specified device parameter.
     */
    #[OA\Put(
        path: '/api/device-parameters/{device_parameter_id}',
        tags: ['device-parameters'],
        operationId: 'deviceParameterUpdate',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'device_parameter_id',
        in: 'path',
        required: true,
        description: 'The device parameter ID',
        schema: new OA\Schema(type: 'integer', minimum: 1),
        example: 1
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['device_id', 'name'],
            properties: [
                new OA\Property(property: 'device_id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 255, example: 'Temperature'),
                new OA\Property(property: 'unit', type: 'string', nullable: true, maxLength: 50, example: '°C'),
                new OA\Property(property: 'alarm_type', type: 'string', enum: ['none', 'low', 'high'], example: 'none'),
                new OA\Property(property: 'alarm_trigger', type: 'number', format: 'float', nullable: true, example: 30.1),
                new OA\Property(property: 'alarm_hysteresis', type: 'number', format: 'float', minimum: 0.0, nullable: true, example: 1.5),
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
     * Remove the specified device parameter from storage.
     */
    #[OA\Delete(
        path: '/api/device-parameters/{device_parameter_id}',
        tags: ['device-parameters'],
        operationId: 'deviceParameterDestroy',
        security: [
            ['api_key' => []],
        ],
    )]
    #[OA\Parameter(
        name: 'device_parameter_id',
        in: 'path',
        required: true,
        description: 'The device parameter ID',
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