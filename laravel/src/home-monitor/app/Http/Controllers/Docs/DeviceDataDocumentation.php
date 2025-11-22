<?php

namespace App\Http\Controllers\Docs;

use \App\Http\Controllers\DeviceDataController;

use OpenApi\Attributes as OA;

class DeviceDataDocumentation
{
    /**
     * Fetch time-series data for a paramter in time buckets.
     */
    #[OA\Get(
        path: '/api/device-parameters/{device_parameter_id}/data',
        tags: ['parameter-data'],
        operationId: 'deviceParameterDataBucket',
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
    #[OA\Parameter(
        name: 'start',
        in: 'query',
        required: true,
        description: 'Time interval start',
        schema: new OA\Schema(type: 'string', format: 'date-time'),
        example: '2023-01-30T13:00:00Z'
    )]
    #[OA\Parameter(
        name: 'end',
        in: 'query',
        required: true,
        description: 'Time interval end',
        schema: new OA\Schema(type: 'string', format: 'date-time'),
        example: '2023-01-30T17:00:00Z'
    )]
    #[OA\Parameter(
        name: 'bucket_size',
        in: 'query',
        required: false,
        description: 'The bucket size in minutes',
        schema: new OA\Schema(type: 'integer', minimum: 5, maximum: 1440, default: 60),
        example: 60
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
            type: 'object',
            required: ['data'],
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['bucket_start', 'count', 'min_value', 'max_value', 'avg_value'],
                        properties: [
                            new OA\Property(property: 'bucket_start', type: 'string', format: 'date-time', example: '2023-01-30T13:00:00Z'),
                            new OA\Property(property: 'count', type: 'integer', example: 10),
                            new OA\Property(property: 'min_value', type: 'number', format: 'float', example: 20.5),
                            new OA\Property(property: 'max_value', type: 'number', format: 'float', example: 25.8),
                            new OA\Property(property: 'avg_value', type: 'number', format: 'float', example: 23.2),
                        ]
                    )
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing device parameter')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function bucket() {}

    /**
     * Store time-series data for a paramter.
     */
    #[OA\Post(
        path: '/api/device-parameters/{device_parameter_id}/data',
        tags: ['parameter-data'],
        operationId: 'deviceParameterDataStore',
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
            required: ['data'],
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    minItems: 1,
                    maxItems: DeviceDataController::MAX_STORAGE_READINGS,
                    items: new OA\Items(
                        type: 'object',
                        required: ['value', 'time'],
                        properties: [
                            new OA\Property(property: 'value', type: 'number', format: 'float', example: 25.8),
                            new OA\Property(property: 'time', type: 'string', format: 'date-time', example: '2023-01-30T13:00:00Z'),
                        ]
                    )
                ),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Created',
        content: new OA\JsonContent(
            type: 'object',
            required: ['message', 'count'],
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Data stored successfully'),
                new OA\Property(property: 'count', type: 'integer', example: 10),
            ],
        )
    )]
    #[OA\Response(response: 401, description: 'Authentication exception')]
    #[OA\Response(response: 404, description: 'Missing device parameter')]
    #[OA\Response(response: 422, description: 'Validation exception')]
    #[OA\Response(response: 500, description: 'Database exception')]
    public static function store() {}
}