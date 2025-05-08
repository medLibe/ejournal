<?php

namespace App\Http\Controllers;

use App\Exports\ExportAccounts;
use App\Imports\ImportAccounts;
use App\Models\Account;
use App\Models\AccountType;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{
    protected $account;
    protected $accountType;

    public function __construct()
    {
        $this->account = new Account();
        $this->accountType = new AccountType();
    }

    public function getAccounts(Request $request)
    {
        try {
            $getAccounts = $this->account->getAccounts($request);

            return response()->json([
                'status'    => true,
                'accounts'  => $getAccounts,
                'total'     => count($getAccounts)
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage(),
            ], 500);
        }
    }

    public function printAccounts()
    {
        try {
            $accounts = $this->account->with(['accountType', 'subAccounts'])->get();

            foreach ($accounts as $account) {
                $account->opening_balance = $this->getTotalBalance($account);  // Total saldo akun induk
            }

            $htmlContent = view('account', ['accounts' => $accounts])->render();

            $pdf = Pdf::loadHTML($htmlContent);

            return $pdf->setPaper('a4', 'portrait')->download('Master_Data_Akun.pdf');
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function exportAccounts()
    {
        try {
            return Excel::download(new ExportAccounts, 'accounts.xlsx');
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function importAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_account' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'    => false,
                'message'   => 'File is invalid or exceeds size limit.',
            ], 400);
        }

        $file = $request->file('file_account');

        // preview array
        $previewResult = [];

        try {
            // use excel maatwebsite to import data
            $import = Excel::toArray(new ImportAccounts, $file);

            // array to collect
            $importCollection = collect($import[0]);

            // process the data and check for conflicts
            $processedData = (new ImportAccounts)->collection($importCollection);

            $previewResult = $processedData;
            return response()->json([
                'status'  => true,
                'preview' => $previewResult,
                'raw'     => $previewResult,
                'total'   => count($import[0]),
            ], 200);

        } catch (Exception $error) {
            $statusCode = $error->getCode() ?: 500;

            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage(),
            ], $statusCode);
        }
    }

    public function storeAccount(Request $request)
    {
        try {
            $accountsData = $request->account;

            $getAccountTypeByNames = $this->accountType->pluck('id', 'account_type_name');
            $normalizedAccountTypes = [];
            foreach ($getAccountTypeByNames as $key => $value) {
                $normalizedAccountTypes[strtolower(trim(preg_replace('/\s+/', ' ', $key)))] = $value;
            }

            $missingAccountTypes = [];
            foreach ($accountsData as $row) {
                $accountType = strtolower(trim(preg_replace('/\s+/', ' ', $row['tipe_akun'])));
                if (!isset($normalizedAccountTypes[$accountType])) {
                    $missingAccountTypes[] = $accountType;
                }
            }
            // remove duplication missing account types:
            $missingAccountTypes = array_unique($missingAccountTypes);

            if (!empty($missingAccountTypes)) {
                throw new Exception("Tipe akun berikut tidak ditemukan: " . implode(', ', array_unique($missingAccountTypes)));
            }

            $parentAccounts = array_filter($accountsData, fn($row) => empty($row['sub_akun_dari']));
            $childAccounts = array_filter($accountsData, fn($row) => !empty($row['sub_akun_dari']));

            // preparing parent account
            $mappedParentAccounts = array_map(function ($row) use ($normalizedAccountTypes) {
                $accountTypeId = $normalizedAccountTypes[strtolower(trim($row['tipe_akun']))] ?? null;
                if (!$accountTypeId) {
                    throw new Exception("Tipe akun '{$row['tipe_akun']}' tidak ditemukan.");
                }

                if (empty($row['nama_akun'])) {
                    throw new Exception("Akun dengan kode '{$row['kode_akun']}' memiliki `nama_akun` yang kosong atau null.");
                }

                return [
                    'account_type_id'   => $accountTypeId,
                    'account_code'      => $row['kode_akun'],
                    'account_name'      => $row['nama_akun'],
                    'parent_id'         => null, // Parent akun induk adalah null
                    'opening_balance'   => $row['saldo_awal'] ?? 0,
                    'previous_balance'  => $row['saldo_awal'] ?? 0,
                ];
            }, $parentAccounts);

            // insert parent account first
            $this->account->insert($mappedParentAccounts);


            // then insert child account, make sure parent id compatible
            $mappedChildAccounts = [];
            foreach ($childAccounts as $row) {
                $accountTypeId = $normalizedAccountTypes[strtolower(trim(preg_replace('/\s+/', ' ', $row['tipe_akun'])))] ?? null;
            if (!$accountTypeId) {
                throw new Exception("Tipe akun '{$row['tipe_akun']}' tidak ditemukan.");
            }

            $parentAccountCode = $this->findParentAccountCode($row['sub_akun_dari'], $accountsData);
            if (!$parentAccountCode) {
                throw new Exception("Parent account '{$row['sub_akun_dari']}' tidak ditemukan untuk akun '{$row['kode_akun']}' - '{$row['nama_akun']}'.");
            }

            $mappedChildAccounts[] = [
                'account_type_id'   => $accountTypeId,
                'account_code'      => $row['kode_akun'],
                'account_name'      => $row['nama_akun'],
                'parent_id'         => $parentAccountCode,
                'opening_balance'   => $row['saldo_awal'] ?? 0,
                'previous_balance'  => $row['saldo_awal'] ?? 0,
            ];
        }

            // insert child account after parent account
            $this->account->insert($mappedChildAccounts);

            return response()->json([
                'status'    => true,
                'message'   => 'Akun berhasil disimpan'
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    // helper function to find parent account by code recursively
    private function findParentAccountCode($subAccountCode, $accountsData)
    {
        foreach ($accountsData as $account) {
            if (strtolower(trim($account['kode_akun'])) === strtolower(trim($subAccountCode))) {
                return $account['kode_akun'];
            }
        }
        return null;
    }

    // recursive to count total balance
    private function getTotalBalance($account)
    {
        $totalSaldo = $account->opening_balance ?? 0;

        foreach ($account->subAccounts as $subAccount) {
            $totalSaldo += $this->getTotalBalance($subAccount);
        }

        return $totalSaldo;
    }
}
