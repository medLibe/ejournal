<?php

namespace App\Imports;

use App\Models\Account;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportAccounts implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $dataArray = [];
        $errors = [];

        // check if necessarry column exist
        $requiredColumns = [
            'tipe_akun',
            'kode_akun',
            'nama_akun',
            'sub_akun_dari',
            'saldo_awal',
        ];

        $headers = $collection->first(); // get first row (header)

        // check if required column exist
        foreach ($requiredColumns as $column) {
            if (!array_key_exists($column, $headers)) {
                $errors[] = "Kolom '$column' tidak ditemukan dalam file.";
            }
        }

        // if there are missing columns, throw an exception
        if (!empty($errors)) {
            throw new Exception("Format file tidak sesuai. " . implode(' ', $errors), 422);
        }

        // convert collection to an array
        $rows = $collection->toArray();

        // group rows by kode_akun (parent detection)
        $parents = [];
        foreach ($rows as $row) {
            // skip empty rows
            if(empty(array_filter($row))) {
                continue;
            }

            // check if this is a parent account
            if (empty($row['sub_akun_dari'])) {
                $parents[$row['kode_akun']] = [
                    'tipe_akun' => $row['tipe_akun'],
                    'kode_akun' => $row['kode_akun'],
                    'nama_akun' => $row['nama_akun'],
                    'saldo_awal'=> 0
                ];
            }
        }

        // calculate saldo_awal for each parent based on sub_akun
        foreach ($rows as $row) {
            if (!empty($row['sub_akun_dari']) && isset($parents[$row['sub_akun_dari']])) {
                // add saldo_awal of sub_akun to its parent
                $parents[$row['sub_akun_dari']]['saldo_awal'] += (float) $row['saldo_awal'];
            }
        }

        // process the collection and add rows to the data array
        foreach ($collection as $row) {
            if (empty(array_filter($row))) {
                continue; // skip row if all elements are empty
            }

            // map the row to the correct fields
            $processedRow = [
                'tipe_akun'         => $row['tipe_akun'],
                'kode_akun'         => $row['kode_akun'],
                'nama_akun'         => $row['nama_akun'],
                'sub_akun_dari'     => $row['sub_akun_dari'],
                'saldo_awal'        => $row['saldo_awal'],
            ];

            // check if the account code already exists in the database
            $existingAccount = Account::where('account_code', $processedRow['kode_akun'])->exists();

            if ($existingAccount) {
                // if duplicate account code is found throw an exception
                throw new Exception("Terdapat satu atau lebih akun telah terdaftar.", 409);
            }

            // add processed row to the data array
            $dataArray[] = $processedRow;
        }

        return $dataArray;
    }
}
