<?php

namespace App\Http\Controllers;

use App\Imports\ImportGeneralLedgers;
use App\Models\Account;
use App\Models\GeneralLedger;
use App\Models\GeneralLedgerImport;
use App\Models\PeriodeBalance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class GeneralLedgerImportController extends Controller
{
    protected $generalLedger;
    protected $generalLedgerImport;
    protected $account;
    protected $periodeBalance;

    public function __construct()
    {
        $this->generalLedger = new GeneralLedger();
        $this->generalLedgerImport = new GeneralLedgerImport();
        $this->periodeBalance = new PeriodeBalance();
        $this->account = new Account();
    }

    public function importGeneralLedger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_general_ledger' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'    => false,
                'message'   => 'File is invalid or exceeds size limit.',
            ], 400);
        }

        $file = $request->file('file_general_ledger');

        // preview array
        $previewResult = [];

        try {
            // use excel maatwebsite to import data
            $import = Excel::toArray(new ImportGeneralLedgers, $file);

            // array to collect
            $importCollection = collect($import[0]);

            // process the data and check for conflicts
            $processedData = (new ImportGeneralLedgers)->collection($importCollection);

            $previewResult = $processedData;
            return response()->json([
                'status'  => true,
                'preview' => $previewResult,
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

    public function storeGeneralLedger(Request $request)
    {
        try {
            $generalLedgerData = $request->generalLedger;

            // ger user login
            $user = auth('sanctum')->user();

            if (!$user) {
                throw new Exception("User is not authenticated.");
            }

            $errors = [];
            $validatedData = [];

            foreach ($generalLedgerData as $row) {
                try {
                    $validatedData[] = $this->validateAccountAndPrepareData($row);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            if (!empty($errors)) {
                return response()->json([
                    'status'    => false,
                    'errors'    => $errors,
                    'message'   => 'Beberapa data gagal divalidasi.',
                ], 422);
            }

            // generate import no
            $importDate = now();
            $importNo = 'TRX' . $importDate->format('YmdHis');

            $import = $this->generalLedgerImport->create([
                'import_no'     => $importNo,
                'import_date'   => $importDate,
                'created_by'    => $user->name,
                'updated_by'    => $user->name
            ]);

            $importId = $import->id;

            $periode = $validatedData[0]['periode']; //get periode from first data
            $prevPeriode = date('Ym', strtotime('-1 month', strtotime($periode . '01')));

            // get all active account
            $allAccounts = $this->account->where('is_active', 1)->get();

            foreach ($allAccounts as $account) {
                // check if account exist in periode balance now
                $periodBalance = $this->periodeBalance
                    ->where('account_id', $account->id)
                    ->where('periode', $periode)
                    ->first();

                // if accocunt is not exist, add with balance from prev balance
                if (!$periodBalance) {
                    $prevBalance = $this->periodeBalance
                        ->where('account_id', $account->id)
                        ->where('periode', $prevPeriode)
                        ->first();

                    $openingBalance = $prevBalance ? $prevBalance->closing_balance : $account->opening_balance;

                    $this->periodeBalance->create([
                        'account_id'        => $account->id,
                        'periode'           => $periode,
                        'opening_balance'   => $openingBalance,
                        'closing_balance'   => $openingBalance, // same with opening balance if transaction is not exist
                    ]);
                }
            }

            foreach ($validatedData as $row) {
                // deterimine opening balance for the period
                $periodBalance = $this->periodeBalance
                    ->where('account_id', $row['id_akun'])
                    ->where('periode', $row['periode'])
                    ->first();

                $openingBalance = $periodBalance ? $periodBalance->closing_balance : $row['opening_balance'];

                // set new balance based on transaction type (debit / credit)
                $newBalance = $this->updateAccountBalance($row, $openingBalance);

                // store transaction to general ledger
                $this->generalLedger->create([
                    'import_id'         => $importId,
                    'transaction_date'  => $row['tanggal'],
                    'periode'           => $row['periode'],
                    'reference'         => $row['tipe_sumber'],
                    'reference_no'      => $row['nomor_sumber'],
                    'department'        => $row['department'],
                    'account_id'        => $row['id_akun'],
                    'amount'            => $row['amount'],
                    'transaction_type'  => $row['tipe_transaksi'], // 1 = Debit, 2 = Kredit
                    'description'       => $row['keterangan'],
                ]);

                // Update account balance
                $this->account->where('id', $row['id_akun'])->update(['opening_balance' => $newBalance]);

                if ($periodBalance) {
                    $periodBalance->update([
                        'closing_balance' => $newBalance,
                    ]);
                } else {
                    $this->periodeBalance->create([
                        'account_id'        => $row['id_akun'],
                        'periode'           => $row['periode'],
                        'opening_balance'   => $openingBalance,
                        'closing_balance'   => $newBalance,
                    ]);
                }
            }

            return response()->json([
                'status'    => true,
                'message'   => 'Jurnal Harian berhasil ditambahkan.',
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    // to validate account and prepare data for general ledger import
    private function validateAccountAndPrepareData($row)
    {
        $account = $this->account->where('account_code', $row['nomor_akun'])
            ->with('accountType.accountGroup')
            ->first();

        if (!$account) {
            throw new Exception("Akun dengan nomor {$row['nomor_akun']} tidak ditemukan.");
        }

        // set periode based on transaction date
        $periode = date('Ym', strtotime($row['tanggal']));

        return [
            'tanggal'           => $row['tanggal'],
            'tipe_sumber'       => $row['tipe_sumber'],
            'nomor_sumber'      => $row['nomor_sumber'],
            'department'        => $row['department'],
            'nomor_akun'        => $row['nomor_akun'],
            'id_akun'           => $account->id,
            'amount'            => $row['nominal'],
            'tipe_transaksi'    => $row['tipe_transaksi'], // 1 = Debit, 2 = Kredit
            'keterangan'        => $row['keterangan'],
            'normal_balance'    => $account->accountType->accountGroup->normal_balance, // (1 = Debit, 2 = Kredit)
            'opening_balance'   => $account->opening_balance,
            'periode'           => $periode,
        ];
    }

    // to update balance account based on transaction type
    private function updateAccountBalance($row, $openingBalance, $reverse = false)
    {
        if ($row['normal_balance'] == 1) { // Normal Balance Debit
            if ($row['tipe_transaksi'] === 1) {
                return $reverse ? $openingBalance - $row['amount'] : $openingBalance + $row['amount'];
            } else {
                return $reverse ? $openingBalance + $row['amount'] : $openingBalance - $row['amount'];
            }
        } elseif ($row['normal_balance'] == 2) { // Normal Balance Kredit
            if ($row['tipe_transaksi'] === 2) {
                return $reverse ? $openingBalance - $row['amount'] : $openingBalance + $row['amount'];
            } else {
                return $reverse ? $openingBalance + $row['amount'] : $openingBalance - $row['amount'];
            }
        }
    }
}
