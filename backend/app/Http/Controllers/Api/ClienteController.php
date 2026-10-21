<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClienteController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ClienteResource::collection(Cliente::orderBy('nombre')->get());
    }
}
