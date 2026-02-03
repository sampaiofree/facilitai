<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\ImageController as BaseImageController;

class ImageController extends BaseImageController
{
    protected string $imagesRouteBase = 'agencia.images';
    protected string $foldersRouteBase = 'agencia.folders';
    protected string $viewName = 'agencia.images.index';
}
