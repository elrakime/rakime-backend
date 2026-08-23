<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class AccountImportService
{
    /**
     * Parse a bank return .txt file into an array of items.
     *
     * Each non-empty line is decoded using fixed-width fields in the
     * following order:
     *
     *  | field                     | length |
     *  |---------------------------|--------|
     *  | client ccp number         | 10     |
     *  | client fullname           | 27     |
     *  | amount                    | 14     |
     *  | date                      | 10     |
     *  | account ccp number        | 10     |
     *  | status                    | 1      |
     *  | draw day                  | 2      |
     *  | subscription reference    | rest   |
     *
     * @return array<int, array{
     *     client_ccp_number: string,
     *     client_fullname: string,
     *     amount: string,
     *     date: string,
     *     account_ccp_number: string,
     *     status: string,
     *     draw_day: string,
     *     subscription_reference: string,
     * }>
     */
    public function import(UploadedFile $file): array
    {
        $content = $file->get();

        if ($content === false || trim($content) === '') {
            throw new RuntimeException(__('account_imports.empty_file'), 422);
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);

        if ($lines === false) {
            throw new RuntimeException(__('account_imports.unreadable_file'), 422);
        }

        $items = [];

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");

            if (trim($line) === '') {
                continue;
            }

            $items[] = $this->parseLine($line);
        }

        if ($items === []) {
            throw new RuntimeException(__('account_imports.empty_file'), 422);
        }

        return $items;
    }

    /**
     * Decode a single fixed-width line into its fields.
     *
     * @return array{
     *     client_ccp_number: string,
     *     client_fullname: string,
     *     amount: string,
     *     date: string,
     *     account_ccp_number: string,
     *     status: string,
     *     draw_day: string,
     *     subscription_reference: string,
     * }
     */
    private function parseLine(string $line): array
    {
        return [
            'client_ccp_number'      => trim(substr($line, 0, 10)),
            'client_fullname'        => trim(substr($line, 10, 27)),
            'amount'                 => trim(substr($line, 37, 14)),
            'date'                   => trim(substr($line, 51, 10)),
            'account_ccp_number'     => trim(substr($line, 61, 10)),
            'status'                 => trim(substr($line, 71, 1)),
            'draw_day'               => trim(substr($line, 72, 2)),
            'subscription_reference' => trim(substr($line, 74)),
        ];
    }
}
