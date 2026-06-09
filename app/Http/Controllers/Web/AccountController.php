<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Account\StoreAccountRequest;
use App\Http\Requests\Web\Account\UpdateAccountRequest;
use App\Http\Resources\Web\AccountResource;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accountService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ACCOUNTS->value)) {
            return $response;
        }

        return $this->successResponse(AccountResource::collection($this->accountService->list($request)));
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_ACCOUNTS->value)) {
            return $response;
        }

        try {
            $account = $this->accountService->create($this->validateRequest($request));

            return $this->successResponse(new AccountResource($account), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Account $account): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ACCOUNTS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new AccountResource($this->accountService->show($account)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_ACCOUNTS->value)) {
            return $response;
        }

        try {
            $account = $this->accountService->update($account, $this->validateRequest($request));

            return $this->successResponse(new AccountResource($account));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Account $account): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_ACCOUNTS->value)) {
            return $response;
        }

        try {
            $this->accountService->delete($account);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
