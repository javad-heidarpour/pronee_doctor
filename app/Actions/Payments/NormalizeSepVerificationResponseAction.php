<?php

namespace App\\Actions\\Payments;

use RuntimeException;

class NormalizeSepVerificationResponseAction
{
    /**
     * Normalize SEP's documented VerifyTransaction response.
     *
     * @return array{
     *     success:bool,
     *     result_code:mixed,
     *     result_message:string,
     *     transaction_detail:array,
     *     verified_amount:mixed,
     *     verified_ref_num:string,
     *     verified_terminal_number:mixed
     * }
     */
    public function handle(mixed $response): array
    {
        if (
            ! is_array($response) ||
            ! array_key_exists('ResultCode', $response) ||
            ! array_key_exists('Success', $response)
        ) {
            throw new RuntimeException('پاسخ استعلام سامان‌کیش معتبر نیست.');
        }

        $detail = $response['TransactionDetail'] ?? [];
        $detail = is_array($detail) ? $detail : [];

        return [
            'success' => $response['Success'] === true,
            'result_code' => $response['ResultCode'],
            'result_message' => (string) (
                $response['ResultDescription']
                ?? $response['ResultMessage']
                ?? $response['Message']
                ?? ''
            ),
            'transaction_detail' => $detail,
            'verified_amount' => $detail['OrginalAmount'] ?? null,
            'verified_ref_num' => trim((string) ($detail['RefNum'] ?? '')),
            'verified_terminal_number' => $detail['TerminalNumber'] ?? null,
        ];
    }
}
