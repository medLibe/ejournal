<?php

namespace App\Imports;

use App\Models\Account;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportAccounts implements ToCollection, WithHeadingRow
{
    public function collection(Collection $collection)
    {
        $dataArray = [];
        $errors = [];

        // required column in excel
        $requiredColumns = [
            'tipe_akun',
            'kode_akun_internal',
            'nama_akun_internal',
            'kode_akun_eksternal',
            'nama_akun_eksternal',
            'sub_akun_dari',
            'saldo_awal',
        ];

        $headers = $collection->first();

        foreach ($requiredColumns as $column) {
            if (!array_key_exists($column, $headers)) {
                $errors[] = "Kolom '$column' tidak ditemukan.";
            }
        }

        if (!empty($errors)) {
            throw new Exception("Format file tidak sesuai. " . implode(' ', $errors), 422);
        }

        // count opening balance for parent account based on sub-account
        $parentBalances = [];

        foreach ($collection as $row) {
            if (empty(array_filter($row))) continue;

            $parentCode = $row['sub_akun_dari'];
            if ($parentCode) {
                if (!isset($parentBalances[$parentCode])) {
                    $parentBalances[$parentCode] = 0;
                }
                $parentBalances[$parentCode] += (float) $row['saldo_awal'];
            }
        }

        foreach ($collection as $row) {
            if (empty(array_filter($row))) continue;

            $accountCode = $row['kode_akun_eksternal'];

            // duplicate detection in database
            if (Account::where('account_code', $accountCode)->exists()) {
                throw new Exception("Akun dengan kode '$accountCode' sudah terdaftar.", 409);
            }

            $processedRow = [
                'account_type_id'     => $row['tipe_akun'],
                'account_code'        => $row['kode_akun_eksternal'],
                'account_name'        => $row['nama_akun_eksternal'],
                'account_code_alt'    => $row['kode_akun_internal'],
                'account_name_alt'    => $row['nama_akun_internal'],
                'parent_id'           => $row['sub_akun_dari'] ?? null,
                'opening_balance'     => isset($parentBalances[$accountCode])
                    ? $parentBalances[$accountCode]
                    : (float) $row['saldo_awal'],
                'is_active'           => true,
            ];

            $dataArray[] = $processedRow;
        }

        return $dataArray;
    }
}
