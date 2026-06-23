<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Client\StoreClientRequest;
use App\Http\Requests\Web\Client\UpdateClientRequest;
use App\Http\Resources\Web\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(private readonly ClientService $clientService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_CLIENTS->value)) {
            return $response;
        }

        return $this->successResponse(ClientResource::collection($this->clientService->list($request)));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($request->input('branch_id'))) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::CREATE_CLIENTS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $client = $this->clientService->create($data);

            return $this->successResponse(new ClientResource($client), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Client $client): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($client)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_CLIENTS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new ClientResource($this->clientService->show($client)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($client)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::UPDATE_CLIENTS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $client = $this->clientService->update($client, $data);

            return $this->successResponse(new ClientResource($client));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($client)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::DELETE_CLIENTS->value)) {
            return $response;
        }

        try {
            $this->clientService->delete($client);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
