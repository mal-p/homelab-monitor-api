<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.1.0',
    title: 'Home-monitor API',
    description: 'A small home monitoring API for logging electricity usage, temperature etc.',
)]
abstract class Controller
{
    //
}
