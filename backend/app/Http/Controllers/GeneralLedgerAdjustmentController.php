<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\GeneralLedger;
use App\Models\GeneralLedgerImport;
use App\Models\PeriodeBalance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GeneralLedgerAdjustmentController extends Controller
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

    public function adjustmentGeneralLedgerByReferenceNo(Request $request)
    {
        try {
            $data = $request->data;

            $errors = [];
            $validatedData = [];

            foreach ($data as $row) {
                try {
                    $validatedData[] = $this->validateAccountAndPrepareAdjustment($row);
                } catch (ValidationException $ve) {
                    $errors = array_merge_recursive($errors, $ve->errors());
                } catch (Exception $e) {
                    $errors['general'][] = $e->getMessage();
                }
            }

            if (!empty($errors)) {
                return response()->json([
                    'status'    => false,
                    'errors'    => $errors,
                    'message'   => 'Beberapa data gagal divalidasi',
                ], 422);
            }

            if (empty($validatedData)) {
                return response()->json([
                    'status'    => false,
                    'message'   => 'Tidak ada data yang valid untuk disimpan.'
                ], 400);
            }

            // balance checker
            $totalDebit = collect($validatedData)
                ->where('tipe_transaksi', 1)
                ->sum('amount');

            $totalCredit = collect($validatedData)
                ->where('tipe_transaksi', 2)
                ->sum('amount');

            if ($totalDebit !== $totalCredit) {
                return response()->json([
                    'status'    => false,
                    'message'   => 'Total debit dan kredit harus sama. Harap periksa kembali.'
                ], 422);
            }

            $referenceNo = $validatedData[0]['nomor_sumber'];

            DB::beginTransaction();

            $oldEntries = $this->generalLedger->where('reference_no', $referenceNo)->get();

            foreach ($oldEntries as $entry) {
                // get account
                $account = $this->account->find($entry->account_id);

                // update saldo backward
                $prevPeriodeBalance = $this->periodeBalance
                    ->where('account_id', $account->id)
                    ->where('periode', $entry->periode)
                    ->first();

                if ($prevPeriodeBalance) {
                    // reverse effect of the old transactions from closing balance
                    $reverseRow = [
                        'tipe_transaksi'    => $entry->transaction_type,
                        'amount'            => $entry->amount,
                        'normal_balance'    => $account->accountType->accountGroup->normal_balance,
                    ];

                    $prevPeriodeBalance->closing_balance = $this->updateAccountBalance(
                        $reverseRow,
                        $prevPeriodeBalance->closing_balance,
                        true
                    );
                    $prevPeriodeBalance->save();
                }

                $reverseRow['opening_balance'] = $account->opening_balance;
                $account->opening_balance = $this->updateAccountBalance($reverseRow, $account->opening_balance, true);
                $account->save();
            }

            // delete old entries
            $this->generalLedger->where('reference_no', $referenceNo)->delete();

            foreach ($validatedData as $row) {
                $this->generalLedger->create([
                    'transaction_date'  => $row['tanggal'],
                    'periode'           => $row['periode'],
                    'reference_no'      => $row['nomor_sumber'],
                    'reference'         => $row['tipe_sumber'],
                    'department'        => $row['department'],
                    'account_id'        => $row['id_akun'],
                    'amount'            => $row['amount'],
                    'transaction_type'  => $row['tipe_transaksi'],
                    'description'       => $row['keterangan'],
                    'import_id'         => $row['id_import'],
                ]);

                $account = $this->account->find($row['id_akun']);

                // update account balance
                $newBalance = $this->updateAccountBalance($row, $account->opening_balance);
                $account->opening_balance = $newBalance;
                $account->save();

                // update periode balance
                $periodBalance = $this->periodeBalance
                    ->where('account_id', $account->id)
                    ->where('periode', $row['periode'])
                    ->first();

                if ($periodBalance) {
                    $periodBalance->closing_balance = $newBalance;
                    $periodBalance->save();
                } else {
                    $this->periodeBalance->create([
                        'account_id'      => $account->id,
                        'periode'         => $row['periode'],
                        'opening_balance' => $newBalance,
                        'closing_balance' => $newBalance,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'    => true,
                'message'   => 'Data adjustment berhasil diperbaharui.'
            ]);
        } catch (Exception $error) {
            DB::rollBack();
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage(),
            ], 500);
        }
    }

    public function adjustmentEntryGeneralLedger(Request $request)
    {
        try {
            $data = $request->data;
            $errors = [];
            $validatedData = [];

            foreach ($data as $row) {
                try {
                    $validatedData[] = $this->validateAccountAndPrepareAdjustment($row);
                } catch (ValidationException $ve) {
                    $errors = array_merge_recursive($errors, $ve->errors());
                } catch (Exception $e) {
                    $errors['general'][] = $e->getMessage();
                }
            }

            if (!empty($errors)) {
                return response()->json([
                    'status' => false,
                    'errors' => $errors,
                    'message' => 'Beberapa data gagal divalidasi',
                ], 422);
            }

            $totalDebit = collect($validatedData)->where('tipe_transaksi', 1)->sum('amount');
            $totalCredit = collect($validatedData)->where('tipe_transaksi', 2)->sum('amount');

            if ($totalDebit !== $totalCredit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Total debit dan kredit harus sama.',
                ], 422);
            }

            DB::beginTransaction();

            foreach ($validatedData as $row) {
                $this->generalLedger->create([
                    'transaction_date'  => $row['tanggal'],
                    'periode'           => $row['periode'],
                    'reference_no'      => $row['nomor_sumber'] ?? null,
                    'reference'         => $row['tipe_sumber'] ?? null,
                    'department'        => $row['department'],
                    'account_id'        => $row['id_akun'],
                    'amount'            => $row['amount'],
                    'transaction_type'  => $row['tipe_transaksi'],
                    'description'       => $row['keterangan'],
                    'import_id'         => null, // <– SET NULL
                ]);

                $account = $this->account->find($row['id_akun']);
                $newBalance = $this->updateAccountBalance($row, $account->opening_balance);
                $account->opening_balance = $newBalance;
                $account->save();

                $periodBalance = $this->periodeBalance
                    ->where('account_id', $account->id)
                    ->where('periode', $row['periode'])
                    ->first();

                if ($periodBalance) {
                    $periodBalance->closing_balance = $newBalance;
                    $periodBalance->save();
                } else {
                    $this->periodeBalance->create([
                        'account_id'      => $account->id,
                        'periode'         => $row['periode'],
                        'opening_balance' => $newBalance,
                        'closing_balance' => $newBalance,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data entry berhasil disimpan.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // to validate account and prepare data for general ledger entry
    private function validateAccountAndPrepareAdjustment(array $row)
    {
        $validator = Validator::make($row, [
            'transaction_date' => ['required', 'date'],
            'reference_no'     => ['required', 'string'],
            'reference'        => ['required'],
            'department'       => ['required', 'string'],
            'description'      => ['required', 'string'],
        ], [
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date'     => 'Tanggal transaksi tidak valid.',
            'reference_no.required'     => 'Nomor sumber wajib diisi.',
            'reference.required'        => 'Tipe sumber wajib dipilih.',
            'department.required'       => 'Departemen wajib diisi.',
            'description.required'      => 'Deskripsi wajib diisi.',
        ]);

        if ($validator->fails()) {
            // Kirim semua pesan sebagai array agar bisa ditampilkan per field
            throw new ValidationException($validator, response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()->toArray()
            ], 422));
        }

        return $this->normalizeAndValidateRow($row);
    }

    private function normalizeAndValidateRow(array $row)
    {
        if (!is_numeric($row['amount']) || $row['amount'] <= 0) {
            throw new Exception("Nominal harus angka lebih dari 0.");
        }

        if (!in_array($row['transaction_type'], [1, 2])) {
            throw new Exception("Tipe transaksi tidak valid.");
        }

        $account = $this->account
            ->where('id', $row['account_id'])
            ->with('accountType.accountGroup')
            ->first();

        if (!$account) {
            throw new Exception("Akun dengan ID {$row['account_id']} tidak ditemukan.");
        }

        if ($account->is_parent) {
            throw new Exception("Akun '{$account->account_code}' adalah akun parent dan tidak bisa digunakan.");
        }

        $periode = date('Ym', strtotime($row['transaction_date']));

        return [
            'id'                => $row['id'] ?? null,
            'tanggal'           => $row['transaction_date'],
            'periode'           => $periode,
            'department'        => $row['department'],
            'id_akun'           => $account->id,
            'amount'            => $row['amount'],
            'tipe_transaksi'    => $row['transaction_type'],
            'nomor_sumber'      => $row['reference_no'],
            'tipe_sumber'       => $row['reference'],
            'keterangan'        => $row['description'],
            'normal_balance'    => $account->accountType->accountGroup->normal_balance,
            'opening_balance'   => $account->opening_balance,
            'id_import'         => $row['import_id'] ?? null
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
