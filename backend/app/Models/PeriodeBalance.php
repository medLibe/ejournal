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

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function getTotalBalanceSheet($date_periode)
    {
        $formattedPeriode = Carbon::parse($date_periode)->format('Ym');

        $balances =  DB::table('periode_balances as pb')
            ->join('accounts as a', 'pb.account_id', '=', 'a.id')
            ->join('account_types as at', 'a.account_type_id', '=', 'at.id')
            ->join('account_groups as ag', 'at.account_group_id', '=', 'ag.id')
            ->where('pb.periode', '<=', $formattedPeriode)
            ->whereIn('ag.id', [1, 2, 3])
            ->groupBy('ag.account_group_name', 'at.account_type_name')
            ->select(
                'ag.account_group_name',
                'at.account_type_name',
                DB::raw('SUM(pb.closing_balance) as total_balance')
            )
            ->orderBy('ag.account_group_name')
            ->orderBy('at.account_type_name')
            ->get();

        // convert query result to hierarchy format
        $hierarchicalData = [];

        foreach ($balances as $balance) {
            $group = $balance->account_group_name;
            $type = $balance->account_type_name;

            if (!isset($hierarchicalData[$group])) {
                $hierarchicalData[$group] = [];
            }

            $hierarchicalData[$group][] = [
                'account_type_name' => $type,
                'total_balance' => $balance->total_balance
            ];
        }

        return $hierarchicalData;
    }

    public function getDetailedBalanceSheet($date_periode, $view_children)
    {
        $formattedPeriode = Carbon::parse($date_periode)->format('Ym');

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

        // recursive function to calculate balance from its children
        function calculateBalance($account)
        {
            // get closing_balance from account itself
            $selfBalance = $account->balances->sum('closing_balance') ?? 0;

            // if children exist add balance from its children
            $childrenBalance = $account->children->sum(fn($child) => calculateBalance($child));

            // return balance total account + child
            return $selfBalance + $childrenBalance;
        }

        // recursive function to filter out zero balance accounts
        function filterZeroBalance($accounts, $view_children)
        {
            return $accounts->map(function ($account) use ($view_children) {
                $calculatedBalance = calculateBalance($account);
                $filteredChildren = $view_children ? filterZeroBalance($account->children, $view_children) : [];

                return [
                    'id' => $account->id,
                    'name' => $account->account_name,
                    'balance' => $calculatedBalance,
                    'children' => $filteredChildren
                ];
            })
            ->filter(function ($account) {
                return $account['balance'] != 0 || count($account['children']) > 0;
            })->values();
        }

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
        return $groupedData->map(function ($group) use ($view_children) {
            $filteredAccounts = filterZeroBalance($group, $view_children);
            $totalBalanceGroup = collect($filteredAccounts)->sum('balance');

            if ($filteredAccounts->isEmpty()) {
                return null;
            }

            return [
                'account_group_name' => $group->first()->accountType->accountGroup->account_group_name ?? 'Tanpa Kelompok',
                'total_balance' => $totalBalanceGroup,
                'account_types' => $group->groupBy('accountType.account_type_name')->map(function ($typeAccounts, $typeName) use ($view_children) {
                    $filteredAccounts = filterZeroBalance($typeAccounts, $view_children);

                    if ($filteredAccounts->isEmpty()) {
                        return null;
                    }

                    return [
                        'account_type_name' => $typeName,
                        'accounts' => $filteredAccounts,
                    ];
                })->filter()->values(),
            ];
        })->filter()->values();
    }

    public function getTotalIncomeStatement($start_date, $end_date, $division)
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

    // public function getDetailedIncomeStatement($start_date, $end_date, $view_children)
    // {
    //     $query = DB::table('general_ledgers as gl')
    //         ->join('accounts as a', 'gl.account_id', '=', 'a.id')
    //         ->join('account_types as at', 'a.account_type_id', '=', 'at.id')
    //         ->where('gl.periode', '=', $date_periode)
    //         ->select(
    //             'at.account_type_name as kategori',
    //             'a.account_name as akun',
    //             DB::raw('SUM(gl.amount) as total')
    //         )
    //         ->groupBy('at.account_type_name', 'a.account_name');

    //     if (!$view_children) {
    //         $query->selectRaw('SUM(gl.amount) as total')->groupBy('at.account_type_name');
    //     }

    //     return $query->get();
    // }
}
