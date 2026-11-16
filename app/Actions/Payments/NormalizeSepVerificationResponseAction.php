<?php

namespace App\Actions\Payments;

use RuntimeException;

class NormalizeSepVerificationResponseAction
{
    /**
     * Normalize the SEP verification payload before settlement logic uses it.
     *
     * @return array{
     *     result_code:mixed,
     *     result_message:string,
     *     transaction_detail:array,
     *     verified_amount:mixed
     * }
     */
    public function handle(mixed $response): array
    {
        if (
            ! is_array($response) ||
            ! array_key_exists('ResultCode', $response)
        ) {
            throw new RuntimeException('پاسخ استعلام سامان‌کیش معتبر نیست.');
        }

        $detail = $response['TransactionDetail'] ?? [];

        return [
            'result_code' => $response['ResultCode'],
            'result_message' => (string) (
                $response['ResultDescription']
                ?? $response['ResultMessage']
                ?? $response['Message']
                ?? ''
            ),
            'transaction_detail' => is_array($detail) ? $detail : [],
            'verified_amount' => is_array($detail)
                ? ($detail['OrginalAmount'] ?? null)
                : null,
        ];
    }
}
