<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GeneralLedger extends Model
{
    protected $fillable = [
        'import_id',
        'transaction_date',
        'periode',
        'reference',
        'reference_no',
        'department',
        'account_id',
        'amount',
        'transaction_type',
        'description',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public static function getGeneralLedgers($params = [], $withTotalAmount = false)
    {
        try {
            $startDate = $params['start_date'] ?? null;
            $endDate   = $params['end_date'] ?? null;
            $importId  = $params['import_id'] ?? null;
            $perPage   = $params['perPage'] ?? 50;
            $page      = $params['page'] ?? 1;
            $search    = $params['search'] ?? null;

            $baseQuery = DB::table('general_ledgers as gl')
                ->select([
                    'gl.reference_no',
                    'gl.reference',
                    DB::raw("MAX(gl.transaction_date) as transaction_date"),
                    DB::raw("SUM(CASE WHEN gl.transaction_type = 1 THEN gl.amount ELSE 0 END) AS total_debit"),
                    DB::raw("SUM(CASE WHEN gl.transaction_type = 2 THEN gl.amount ELSE 0 END) AS total_credit"),
                    DB::raw("SUM(CASE WHEN gl.transaction_type = 1 THEN gl.amount ELSE 0 END) AS total_amount")
                ])
                ->groupBy('gl.reference_no', 'gl.reference');

            // filter date or import id
            if ($startDate && $endDate) {
                $baseQuery->whereBetween('gl.transaction_date', [$startDate, $endDate]);
            } elseif ($importId) {
                $baseQuery->where('gl.import_id', $importId);
            } else {
                throw new InvalidArgumentException('Parameter tidak valid. Harus menyertakan start_date & end_date atau import_id.');
            }

            // filter search if exist
            if (!empty($search)) {
                $baseQuery->where(function($q) use ($search) {
                    $q->where('gl.reference_no', 'like', "%{$search}%")
                      ->orWhere('gl.reference', 'like', "%{$search}%")
                      ->orWhere('gl.transaction_date', 'like', "%{$search}%");
                });
            }

            // order by
            $baseQuery->orderBy(DB::raw('MAX(gl.transaction_date)'), 'desc');

            // get pagination result
            $paginated = (clone $baseQuery)->paginate($perPage, ['*'], 'page', $page);

            // count total amount all of filter result, not only active page
            $totalAmount = 0;

            if ($withTotalAmount) {
                $totalAmount = (clone $baseQuery)->get()->sum('total_amount');
            }

            return [
                'data'          => $paginated,
                'total_amount'  => $totalAmount,
            ];
        } catch (Exception $error) {
            throw new Exception($error->getMessage());
        }
    }

    public static function getGeneralLedgersForPrint($params = [])
    {
        $startDate = $params['start_date'] ?? null;
        $endDate   = $params['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            throw new InvalidArgumentException('Tanggal tidak valid.');
        }

        return DB::table('general_ledgers as gl')
            ->select([
                'gl.reference_no',
                'gl.reference',
                DB::raw("MAX(gl.transaction_date) as transaction_date"),
                DB::raw("SUM(CASE WHEN gl.transaction_type = 1 THEN gl.amount ELSE 0 END) AS total_debit"),
                DB::raw("SUM(CASE WHEN gl.transaction_type = 2 THEN gl.amount ELSE 0 END) AS total_credit"),
                DB::raw("SUM(CASE WHEN gl.transaction_type = 1 THEN gl.amount ELSE 0 END) AS total_amount")
            ])
            ->whereBetween('gl.transaction_date', [$startDate, $endDate])
            ->groupBy('gl.reference_no', 'gl.reference')
            ->orderBy(DB::raw('MAX(gl.transaction_date)'), 'desc')
            ->get();
    }

    public function getLedgers($account_id, $start_date, $end_date)
    {
        $periode = date('Ym', strtotime($start_date));

        // fist, get first transaction
        $first_transaction_date = DB::table('general_ledgers')
            ->where('account_id', $account_id)
            ->orderBy('transaction_date', 'asc')
            ->value('transaction_date');

        $first_periode = date('Ym', strtotime($first_transaction_date));

        // check if selected periode is further than last transaction
        $last_transaction_date = DB::table('general_ledgers')
            ->where('account_id', $account_id)
            ->orderBy('transaction_date', 'desc')
            ->value('transaction_date');

        $last_periode = date('Ym', strtotime($last_transaction_date));

        // if is not exist transaction before, directly get balance from opening-balances accounts
        if (is_null($last_transaction_date)) {
            $opening_balance = DB::table('accounts')
                ->where('id', $account_id)
                ->value('opening_balance');
        } else {
            // if selected periode is further than last selected period
            if ($periode > $last_periode) {
                $opening_balance = DB::table('accounts')
                    ->where('id', $account_id)
                    ->value('opening_balance');
            } else {
                 // check if selected period < first transaction period
                if ($periode < $first_periode) {
                    // if selected period < is less than first transaction period
                    $opening_balance = DB::table('accounts')
                        ->where('id', $account_id)
                        ->value('previous_balance');
                } elseif ($periode == $first_periode) {
                    // if selected period same with first transaction period
                    $opening_balance = DB::table('periode_balances')
                        ->where('account_id', $account_id)
                        ->where('periode', $periode)
                        ->value('opening_balance');
                } elseif ($periode > $first_periode) {
                    // if selected period is greater than first transaction period
                    $closing_balance = DB::table('periode_balances')
                        ->where('account_id', $account_id)
                        ->where('periode', $periode)
                        ->value('opening_balance');
            
                    if (is_null($closing_balance)) {
                        // get balance from closing balance in previous period
                        $prev_periode = date('Ym', strtotime($start_date . ' -1 month'));
                        $opening_balance = DB::table('periode_balances')
                            ->where('account_id', $account_id)
                            ->where('periode', $prev_periode)
                            ->value('closing_balance');
                    } else {
                        $opening_balance = $closing_balance;
                    }
                }
            }
        }

        // get transaction in specified periode
        $transactions = DB::table('general_ledgers')
                ->where('account_id', $account_id)
                ->whereBetween('transaction_date', [$start_date, $end_date])
                ->join('accounts', 'general_ledgers.account_id', '=', 'accounts.id')
                ->orderBy('general_ledgers.transaction_date', 'asc')
                ->orderBy('general_ledgers.reference_no', 'asc')
                ->get([
                    'general_ledgers.id',
                    'general_ledgers.transaction_date',
                    'general_ledgers.description',
                    'general_ledgers.reference_no',
                    'general_ledgers.transaction_type',
                    'general_ledgers.amount',
                    'accounts.account_code',
                    'accounts.account_name',
                ]);

        $opening_balance_entry = (object) [
            'id' => null,
            'transaction_date' => date('Y-m-d', strtotime($start_date . ' -1 day')), // -1day before start date
            'description' => 'Saldo Awal',
            'reference_no' => '',
            'debit' => 0,
            'credit' => 0,
            'balance' => $opening_balance,
            'account_code' => '',
            'account_name' => ''
        ];

        // format transaction with current balance
        $balance = $opening_balance;
        $formattedTransactions = [$opening_balance_entry];

        foreach ($transactions as $trx) {
            $debit = $trx->transaction_type == 1 ? $trx->amount : 0;
            $credit = $trx->transaction_type == 2 ? $trx->amount : 0;
            $balance += $debit - $credit;

            $formattedTransactions[] = (object) [
                'id' => $trx->id,
                'transaction_date' => $trx->transaction_date,
                'description' => $trx->description,
                'reference_no' => $trx->reference_no,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
                'account_code' => $trx->account_code,
                'account_name' => $trx->account_name
            ];
        }

        return $formattedTransactions;
    }

    public function countAmountGeneralLedger($year, $month)
    {
        return DB::table('general_ledgers as gl')
                ->where('transaction_type', 1)
                ->whereYear('gl.transaction_date', 2025)
                ->whereMonth('gl.transaction_date', 01)
                ->selectRaw('sum(amount) as total_amount')
                ->first();
    }

    public function countAmountGeneralLedgerByParentAccount($parentName, $year, $month)
    {
        $parentId = DB::table('accounts')
                    ->where('account_type_id', 1)
                    ->where('account_name', strtoupper($parentName))
                    ->value('account_code');

        if (!$parentId) return $parentId;

        $childIds = DB::table('accounts')->where('parent_id', $parentId)->pluck('id');

        return DB::table('general_ledgers')
                ->whereIn('account_id', $childIds)
                ->whereYear('transaction_date', 2025)
                ->whereMonth('transaction_date', 01)
                ->where('transaction_type', 1)
                ->sum('amount');
    }

    public function countAmountGeneralLedgerByNominalAccount($accountGroupId, $year, $month)
    {
        $accountTypeIds = DB::table('account_types')
                    ->where('account_group_id', $accountGroupId)
                    ->pluck('id');

        if ($accountTypeIds->isEmpty()) return 0;

        $accountIds = DB::table('accounts')
                        ->whereIn('account_type_id', $accountTypeIds)
                        ->pluck('id');

        if ($accountIds->isEmpty()) return 0;

        return DB::table('general_ledgers')
                ->whereIn('account_id', $accountIds)
                ->whereYear('transaction_date', 2025)
                ->whereMonth('transaction_date', 01)
                ->sum('amount');
    }
}
