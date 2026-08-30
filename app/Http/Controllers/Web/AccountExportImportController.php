<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Account\ExportAccountRequest;
use App\Http\Requests\Web\Account\ImportAccountRequest;
use App\Models\Account;
use App\Services\AccountExportService;
use App\Services\AccountImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountExportImportController extends Controller
{
    public function __construct(
        private readonly AccountExportService $accountExportService,
        private readonly AccountImportService $accountImportService,
    ) {}

    public function exportRegistrations(ExportAccountRequest $request): StreamedResponse|JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ACCOUNTS->value)) {
            return $response;
        }

        $account = Account::findOrFail($request->integer('account_id'));

        $branchIds = $request->filled('branch_ids')
            ? array_map('intval', (array) $request->input('branch_ids'))
            : $this->getUserBranchIds();

        if (! empty($branchIds)) {
            if ($response = $this->authorizeBranchAccess($branchIds)) {
                return $response;
            }
        }

        return $this->accountExportService->exportRegistrations(
            $account,
            $request->string('date')->toString() ?: null,
            $branchIds,
        );
    }

    public function exportCancellations(ExportAccountRequest $request): StreamedResponse|JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ACCOUNTS->value)) {
            return $response;
        }

        $account = Account::findOrFail($request->integer('account_id'));

        $branchIds = $request->filled('branch_ids')
            ? array_map('intval', (array) $request->input('branch_ids'))
            : $this->getUserBranchIds();

        if (! empty($branchIds)) {
            if ($response = $this->authorizeBranchAccess($branchIds)) {
                return $response;
            }
        }

        return $this->accountExportService->exportCancellations(
            $account,
            $request->string('date')->toString() ?: null,
            $branchIds,
        );
    }

    public function import(ImportAccountRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ACCOUNTS->value)) {
            return $response;
        }

        $account = Account::findOrFail($request->integer('account_id'));

        try {
            $items = $this->accountImportService->import($request->file('file'), $account->draw_day);
            $result = $this->accountImportService->process($items);

            return $this->successResponse([
                'account' => $account->only(['id', 'name', 'ccp_number', 'ccp_key', 'draw_day']),
                'items'   => $items,
                'count'   => count($items),
                'result'  => $result,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
