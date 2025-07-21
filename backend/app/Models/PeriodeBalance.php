<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PeriodeBalance extends Model
{
    protected $fillable = [
        'account_id',
        'periode',
        'opening_balance',
        'closing_balance'
    ];

    function account()
    {
        return $this->belongsTo(Account::class);
    }

    function getTotalBalanceSheet($date_periode, $division = null)
    {
        $startOfTime = '2000-01-01'; // or system start
        $endDate = Carbon::parse($date_periode)->endOfMonth()->toDateString();

        // $balances =  DB::table('periode_balances as pb')
        //     ->join('accounts as a', 'pb.account_id', '=', 'a.id')
        //     ->join('account_types as at', 'a.account_type_id', '=', 'at.id')
        //     ->join('account_groups as ag', 'at.account_group_id', '=', 'ag.id')
        //     ->where('pb.periode', '<=', $formattedPeriode)
        //     ->whereIn('ag.id', [1, 2, 3])
        //     ->groupBy('ag.account_group_name', 'at.account_type_name')
        //     ->select(
        //         'ag.account_group_name',
        //         'at.account_type_name',
        //         DB::raw('SUM(pb.closing_balance) as total_balance')
        //     )
        //     ->orderBy('ag.account_group_name')
        //     ->orderBy('at.account_type_name')
        //     ->get();

        $balances = DB::table('general_ledgers as gl')
                        ->join('accounts as a', 'gl.account_id', '=', 'a.id')
                        ->join('account_types as at', 'a.account_type_id', '=', 'at.id')
                        ->join('account_groups as ag', 'at.account_group_id', '=', 'ag.id')
                        ->whereBetween('gl.transaction_date', [$startOfTime, $endDate])
                        ->when($division, fn($q) => $q->where('gl.department', $division))
                        ->whereIn('ag.id', [1, 2, 3])
                        ->select(
                            'ag.account_group_name',
                            'at.account_type_name',
                            DB::raw('SUM(CASE WHEN gl.transaction_type = 1 THEN gl.amount ELSE 0 END) as debit'),
                            DB::raw('SUM(CASE WHEN gl.transaction_type = 2 THEN gl.amount ELSE 0 END) as credit')
                        )
                        ->groupBy('ag.account_group_name', 'at.account_type_name')
                        ->get();

        // convert query result to hierarchy format
        $hierarchicalData = [];

        foreach ($balances as $balance) {
            $group = $balance->account_group_name;
            $type = $balance->account_type_name;
            $total = $balance->debit - $balance->credit;

            if (!isset($hierarchicalData[$group])) {
                $hierarchicalData[$group] = [];
            }

            $hierarchicalData[$group][] = [
                'account_type_name' => $type,
                'total_balance' => $total
            ];
        }

        return $hierarchicalData;
    }

    function getDetailedBalanceSheet($date_periode, $view_children, $division = null)
    {
        $formattedPeriode = Carbon::parse($date_periode)->format('Ym');
        $endDate = Carbon::parse($date_periode)->endOfMonth()->toDateString();

        // get parent account
        $accounts = Account::whereNull('parent_id')
            ->whereHas('accountType.accountGroup', function ($query) {
                $query->whereIn('id', [1, 2, 3]);
            })
            ->with([
                'accountType.accountGroup',
                'balances' => function ($query) use ($formattedPeriode) {
                    $query->where('periode', '<=', $formattedPeriode);
                },
                'children' => function ($query) use ($formattedPeriode, $view_children) {
                    $query->with([
                        'balances' => function ($subQuery) use ($formattedPeriode) {
                            $subQuery->where('periode', '<=', $formattedPeriode);
                        }
                    ]);

                    if ($view_children) {
                        $query->with([
                            'children' => function ($subQuery) use ($formattedPeriode) {
                                $subQuery->with([
                                    'balances' => function ($subSubQuery) use ($formattedPeriode) {
                                        $subSubQuery->where('periode', '<=', $formattedPeriode);
                                    }
                                ]);
                            }
                        ]);
                    }
                }
            ])
            ->get();

        // get balance from general ledgers if division requested
        $ledgerBalances = collect();
        if ($division) {
            $ledgerQuery = DB::table('general_ledgers')
                ->select(
                    'account_id',
                    DB::raw("SUM(CASE WHEN transaction_type = 1 THEN amount ELSE 0 END) AS total_debit"),
                    DB::raw("SUM(CASE WHEN transaction_type = 2 THEN amount ELSE 0 END) AS total_credit")
                )
                ->whereDate('transaction_date', '<=', $endDate)
                ->where('department', $division)
                ->groupBy('account_id')
                ->get();

            $ledgerBalances = $ledgerQuery->mapWithKeys(function ($item) {
                return [$item->account_id => $item->total_debit - $item->total_credit];
            });
        }

        // calculated recursive balance
        $calculateBalance = function ($account) use (&$calculateBalance, $division, $ledgerBalances) {
            if ($division) {
                $selfBalance = $ledgerBalances[$account->id] ?? 0;
            } else {
                $selfBalance = $account->balances->sum('closing_balance') ?? 0;
            }

            $childrenBalance = $account->children->sum(fn($child) => $calculateBalance($child));

            return $selfBalance + $childrenBalance;
        };

        // recursive function to filter out zero balance accounts
        $filterZeroBalance = function ($accounts) use (&$filterZeroBalance, $calculateBalance, $view_children) {
            return $accounts->map(function ($account) use ($filterZeroBalance, $calculateBalance, $view_children) {
                $calculatedBalance = $calculateBalance($account);
                $filteredChildren = $view_children ? $filterZeroBalance($account->children) : [];

                return [
                    'id' => $account->id,
                    'name' => $account->account_name,
                    'balance' => $calculatedBalance,
                    'children' => $filteredChildren
                ];
            })->filter(function ($account) {
                return $account['balance'] != 0 || count($account['children']) > 0;
            })->values();
        };

        // grouping based on account groups
        $groupedData = $accounts->groupBy(function ($account) {
            return $account->accountType->accountGroup->account_group_name ?? 'Tanpa Kelompok';
        });

        $customOrder = [1, 3, 2];
        $groupedData = $groupedData->sortBy(function ($group) use ($customOrder) {
            $groupId = $group->first()->accountType->accountGroup->id ?? 999;
            $index = array_search($groupId, $customOrder);
            return $index !== false ? $index : 999;
        });

        // format result
        return $groupedData->map(function ($group) use ($filterZeroBalance, $view_children, $calculateBalance) {
            $filteredAccounts = $filterZeroBalance($group);
            $totalBalanceGroup = collect($filteredAccounts)->sum('balance');

            if ($filteredAccounts->isEmpty()) return null;

            return [
                'account_group_name' => $group->first()->accountType->accountGroup->account_group_name ?? 'Tanpa Kelompok',
                'total_balance' => $totalBalanceGroup,
                'account_types' => $group->groupBy('accountType.account_type_name')->map(function ($typeAccounts, $typeName) use ($filterZeroBalance) {
                    $filteredAccounts = $filterZeroBalance($typeAccounts);

                    if ($filteredAccounts->isEmpty()) return null;

                    return [
                        'account_type_name' => $typeName,
                        'accounts' => $filteredAccounts,
                    ];
                })->filter()->values(),
            ];
        })->filter()->values();
    }

    function getTotalIncomeStatement($start_date, $end_date, $division)
    {
        $formattedStart = Carbon::parse($start_date)->format('Ym');
        $formattedEnd = Carbon::parse($end_date)->format('Ym');

        $accountIds = DB::table('general_ledgers')
            ->select('account_id')
            ->when(trim($division) !== '', function ($query) use ($division) {
                return $query->where('department', $division);
            })
            ->distinct()
            ->pluck('account_id');
    
        $rawBalances = DB::table('periode_balances as pb')
                ->join('accounts as a', 'pb.account_id', '=', 'a.id')
                ->join('account_types as at', 'a.account_type_id', '=', 'at.id')
                ->join('account_groups as ag', 'at.account_group_id', '=', 'ag.id')
                ->whereBetween('pb.periode', [$formattedStart, $formattedEnd])
                ->whereIn('ag.id', [4, 5]) // 4 = Pendapatan, 5 = Beban
                ->when(trim($division) !== '', function ($query) use ($accountIds) {
                    return $query->whereIn('pb.account_id', $accountIds);
                })
                ->select(
                    'ag.account_group_name',
                    'at.account_type_name',
                    'at.id as account_type_id',
                    'a.id as account_id',
                    'a.account_name',
                    'a.account_code',
                    DB::raw('SUM(pb.closing_balance) as total_balance')
                )
                ->groupBy('ag.account_group_name', 'at.account_type_name', 'a.id', 'a.account_name', 'a.account_code')
                ->orderBy('ag.account_group_name')
                ->orderBy('at.account_type_name')
                ->orderBy('a.account_code')
                ->get();

        
        $hierarchicalData = [];
        $totalIncome = 0;
        $totalCost = 0;

        foreach ($rawBalances as $row) {
            $group = $row->account_type_id == 11 ? 'Harga Pokok Penjualan' : $row->account_group_name;
            $type = $row->account_type_name;
            $amount = floatval($row->total_balance);
    
            if (!isset($hierarchicalData[$group])) {
                $hierarchicalData[$group] = [];
            }

            // find index type, if not exist, add in
            // $typeIndex = array_search($type, array_column($hierarchicalData[$group], 'account_type_name'));
            // if($typeIndex === false) {
            //     $hierarchicalData[$group][] = [
            //         'account_type_name' => $type,
            //         'total_balance' => 0,
            //         'accounts' => []
            //     ];
            //     $typeIndex = array_key_last($hierarchicalData[$group]);
            // }
            $typeKey = array_search($type, array_map(function ($item) {
                return $item['account_type_name'];
            }, $hierarchicalData[$group] ?? []));

            if ($typeKey === false) {
                $hierarchicalData[$group][] = [
                    'account_type_name' => $type,
                    'total_balance' => 0,
                    'accounts' => []
                ];
                $typeKey = array_key_last($hierarchicalData[$group]);
            }

            if ($amount != 0) {
                // avoid duplicate data HPP
                $isHppMainAccount = $row->account_type_id == 11 &&
                                    $row->account_name == $row->account_type_name;

                // if HPP account equal with HPP account type -> skip from accounts[]
                if (!$isHppMainAccount) {
                    $hierarchicalData[$group][$typeKey]['accounts'][] = [
                        'account_id' => $row->account_id,
                        'account_name' => $row->account_name,
                        'account_code' => $row->account_code,
                        'total_balance' => $amount
                    ];
                }

                // add total_balance into account_type
                $hierarchicalData[$group][$typeKey]['total_balance'] += $amount;

                // count summary
                if ($group === "Pendapatan") {
                    $totalIncome += $amount;
                } elseif ($group === "Beban (Biaya)" || $group === "Harga Pokok Penjualan") {
                    $totalCost += $amount;
                }
            }
        }

        $profitBeforeTax = $totalIncome - $totalCost;
        $tax = $profitBeforeTax * 0.11; // tax 11%
        $profitAfterTax = $profitBeforeTax - $tax;    
        
        foreach ($hierarchicalData as $groupName => $types) {
            $types = array_filter($types, function ($type) {
                return $type['total_balance'] != 0 || count($type['accounts']) > 0;
            });

            if (count($types) === 0) {
                unset($hierarchicalData[$groupName]);
            }
        }

        // add: set order for beban and pendapatan
        $orderedData = [];
        $priorityOrder = ['Pendapatan', 'Harga Pokok Penjualan', 'Beban (Biaya)'];

        foreach ($priorityOrder as $groupName) {
            if (isset($hierarchicalData[$groupName])) {
                $orderedData[$groupName] = $hierarchicalData[$groupName];
            }
        }

         foreach ($hierarchicalData as $groupName => $value) {
            if (!in_array($groupName, $priorityOrder)) {
                $orderedData[$groupName] = $value;
            }
        }

        return [
            "status" => true,
            "data" => $orderedData,
            "summary" => [
                "total_income"      => $totalIncome,
                "total_cost"        => $totalCost,
                "profit_before_tax" => $profitBeforeTax,
                "tax"               => $tax,
                "profit_after_tax"  => $profitAfterTax
            ]
        ];    
    }

    function getTrialBalance($start_date, $end_date, $division = null)
    {
        $periodeStart = Carbon::parse($start_date)->format('Ym');
        $periodeEnd = Carbon::parse($end_date)->format('Ym');

        // step 1: get all accounts
        $accounts = DB::table('accounts')
                ->select('id', 'account_code', 'account_name')
                ->orderBy('account_code')
                ->get()
                ->keyBy('id');


        // step 2: get opening balances
        $opening = DB::table('periode_balances')
                ->where('periode', '<', $periodeStart)
                ->select('account_id', DB::raw('SUM(closing_balance) as opening_balance'))
                ->groupBy('account_id')
                ->pluck('opening_balance', 'account_id');

        // step 3: get mutation debit/credit
        $mutations = DB::table('general_ledgers')
                ->whereBetween('transaction_date', [$start_date, $end_date])
                ->when($division, fn($q) => $q->where('department', $division))
                ->select(
                    'account_id',
                    DB::raw('SUM(CASE WHEN transaction_type = 1 THEN amount ELSE 0 END) as debit'),
                    DB::raw('SUM(CASE WHEN transaction_type = 2 THEN amount ELSE 0 END) as credit')
                )
                ->groupBy('account_id')
                ->get()
                ->keyBy('account_id');

        // step 4: build tree structure
        $structured = [];
        $totals = [
            'opening_debit' => 0,
            'opening_credit' => 0,
            'mutation_debit' => 0,
            'mutation_credit' => 0,
            'closing_debit' => 0,
            'closing_credit' => 0,
        ];

        foreach ($accounts as $id => $acc) {
            $code = $acc->account_code;
            $segments = explode('.', $code);

            if (!isset($opening[$id]) && !isset($mutations[$id])) {
                continue;
            }

            $open = $opening[$id] ?? 0;
            $debit = $mutations[$id]->debit ?? 0;
            $credit = $mutations[$id]->credit ?? 0;
            $close = $open + $debit - $credit;

            $entry = [
                'account_code' => $code,
                'account_name' => $acc->account_name,
                'opening_debit' => $open >= 0 ? $open : 0,
                'opening_credit' => $open < 0 ? abs($open) : 0,
                'mutation_debit' => $debit,
                'mutation_credit' => $credit,
                'closing_debit' => $close >= 0 ? $close : 0,
                'closing_credit' => $close < 0 ? abs($close) : 0,
            ];

            $totals['opening_debit'] += $entry['opening_debit'];
            $totals['opening_credit'] += $entry['opening_credit'];
            $totals['mutation_debit'] += $entry['mutation_debit'];
            $totals['mutation_credit'] += $entry['mutation_credit'];
            $totals['closing_debit'] += $entry['closing_debit'];
            $totals['closing_credit'] += $entry['closing_credit'];

            if (count($segments) === 1) {
                $structured[$code] = [
                    'group_code' => $code,
                    'group_name' => $acc->account_name,
                    'children' => [$entry]
                ];
            } elseif (count($segments) === 2) {
                $p = $segments[0];
                $structured[$p]['children'][$code] = [
                    'account_code' => $code,
                    'account_name' => $acc->account_name,
                    'children' => [$entry]
                ];
            } else {
                $p = $segments[0];
                $s = $segments[0] . '.' . $segments[1];
                $structured[$p]['children'][$s]['children'][] = $entry;
            }
        }

        // reset array index (from assoc to numeric)
        foreach ($structured as &$g) {
            $g['children'] = array_values($g['children']);
            foreach ($g['children'] as &$sub) {
                if (isset($sub['children'])) {
                    $sub['children'] = array_values($sub['children']);
                }
            }
        }

        return [
            'status'    => true,
            'data'      => array_values($structured),
            'totals'    => $totals,
        ];
    }
}
