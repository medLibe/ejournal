<?php

namespace App\Imports;

use App\Models\Account;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportGeneralLedgers implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    
    public function collection(Collection $collection)
    {
        $dataArray = [];
        $errors = [];

        // check if necessary columns exist
        $requiredColumns = [
            'tanggal',
            'nomor_akun',
            'department',
            'nomor_sumber',
            'tipe_sumber',
            'debit',
            'kredit',
            'keterangan',
        ];
        $headers = $collection->first(); // get first row (header)

        // check if required columns exist
        foreach ($requiredColumns as $column) {
            if (!array_key_exists($column, $headers)) {
                $errors[] = "Kolom '$column' tidak ditemukan dalam file.";
            }
        }

        // if there are missing columns, throw an exception
        if (!empty($errors)) {
            throw new Exception("Format file tidak sesuai. " . implode(' ', $errors), 422);
        }

        // initialize variables to store total debit and credit
        $totalDebit = 0;
        $totalCredit = 0;

        // process the collection and add rows to the data array
        foreach ($collection as $row) {
            if (empty(array_filter($row))) {
                continue; // skip row if all elements are empty
            }

            // convert excel serial date to carbon instance if its numeric
            $transactionDate = $this->convertExcelSerialToDate($row['tanggal']);

            // find the account by account code and account_name
            $account = Account::where('account_code', $row['nomor_akun'])
                            ->first();

            if (!$account) {
                throw new Exception(
                    "Terdapat nomor akun '{$row['nomor_akun']}' yang tidak cocok dengan database.",
                    409
                );
            }

            // check if account is parent account (has no balance)
            if ($account->is_parent) {
                $errors[] = "Akun '{$row['nomor_akun']}' adalah akun parent dan tidak dapat digunakan.";
                continue;
            }

            // determine transaction type and nominal
            $nominal = 0;
            $transactionType = 0;

            if (!empty($row['debit']) && $row['debit'] != 0) {
                $nominal = $row['debit'];
                $transactionType = 1; // debit
                $totalDebit += $nominal;
            } elseif (!empty($row['kredit']) && $row['kredit'] != 0) {
                $nominal = $row['kredit'];
                $transactionType = 2; // kredit
                $totalCredit += $nominal;
            } else {
                $errors[] = "Baris dengan nomor akun '{$row['nomor_akun']}' tidak memiliki nilai debit atau kredit.";
                continue;
            }

            $processedRow = [
                'tanggal'           => $transactionDate->format('Y-m-d'),
                'nomor_akun'        => $row['nomor_akun'],
                'nomor_sumber'      => $row['nomor_sumber'],
                'tipe_sumber'       => $row['tipe_sumber'],
                'department'        => $row['department'],
                'nominal'           => $nominal,
                'keterangan'        => $row['keterangan'],
                'tipe_transaksi'    => $transactionType,
                'id_akun'           => $account->id,
            ];

            // add processed row to the data array
            $dataArray[] = $processedRow;
        }

        // after processing all rows , check if total debit and credit are balance
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new Exception("Total Debit dan Kredit tidak balance! Total Debit: $totalDebit, Total Kredit: $totalCredit.", 409);
        }

        // return processed data if no errors
        return [
            'data'          => $dataArray,
            'total_nominal' => $totalDebit,
        ];
    }

    public function convertExcelSerialToDate($serial)
    {
        if (is_string($serial)) {
            return Carbon::parse($serial); // directly parse the string date
        }

        // if its numeric (excel serieal), convert it to carbon date
        if (is_numeric($serial)) {
            return Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($serial - 2);
        }

        // return a default value or handle invalid date formats
        throw new Exception("Tanggal tidak valid: $serial", 400);
    }
}
