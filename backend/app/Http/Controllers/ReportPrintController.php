<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\GeneralLedger;
use App\Models\PeriodeBalance;
use Exception;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class ReportPrintController extends Controller
{
    protected $periodeBalance;
    protected $generalLedger;
    protected $account;

    public function __construct()
    {
        $this->periodeBalance = new PeriodeBalance();
        $this->generalLedger = new GeneralLedger();
        $this->account = new Account();
    }

    public function getLedgerDetails(Request $request)
    {
        try {
            $start_date = $request->query('startDate');
            $end_date = $request->query('endDate');
            $division = $request->query('division');


            if (!$start_date || !$end_date) {
                return response()->json([
                    'status'    => false,
                    'message'   => 'Tanggal awal dan akhir wajib diisi.'
                ], 400);
            }

            $result = $this->generalLedger->getLedgerDetails($start_date, $end_date, $division);

            return response()->json([
                'status'    => true,
                'data'      => $result
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    // print controllers
    public function printLedgers(Request $request)
    {
        $start_date = $request->query('startDate');
        $end_date = $request->query('endDate');
        $account_id = $request->query('accountId');
        $division = $request->query('division');

        if (!$start_date || !$end_date || !$account_id) {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal dan akun wajib diisi.'
            ], 400);
        }

        $result = $this->generalLedger->getLedgers($account_id, $start_date, $end_date, $division);

        // count grand total
        $ledgers = $result['ledgers'];
        $grandTotalDebit = $result['totals']['debit'];
        $grandTotalCredit = $result['totals']['credit'];
        $openingBalance = $ledgers[0]->balance ?? 0;

        // format currency
        $formatCurrency = function ($value) {
            if ($value == 0) return '0';
            $abs = number_format(abs($value), 0, ',', '.');
            return $value < 0 ? '(' . $abs . ')' :  $abs;
        };

        // mapping by collection
        $ledgers = collect($ledgers)->map(function ($item) use ($formatCurrency) {
            $item->formatted_debit = $formatCurrency($item->debit ?? 0);
            $item->formatted_credit = $formatCurrency($item->credit ?? 0);
            $item->formatted_balance = $formatCurrency($item->balance ?? 0);
            return $item;
        });

        // generate pdf
        $pdf = Pdf::loadView('ledger', [
            'ledgers' => $ledgers,
            'grandTotalDebit' => $formatCurrency($grandTotalDebit),
            'grandTotalCredit' => $formatCurrency($grandTotalCredit),
            'openingBalance' => $openingBalance,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('ledger.pdf');
    }

    public function printTrialBalances(Request $request)
    {
        $start_date = $request->query('startDate');
        $end_date = $request->query('endDate');
        $division = $request->query('division');

        if (!$start_date || !$end_date) {
            return response()->json([
                'status'    => false,
                'message'   => 'Tanggal wajib diisi.'
            ], 400);
        }

        // get data from same model
        $result = $this->periodeBalance->getTrialBalance($start_date, $end_date, $division);

        $data = $result;
        $totals = $result['totals'];

        // number format
        $formatCurrency = function ($value) {
            if ($value == 0) return '0';
            $abs = number_format(abs($value), 0, ',', '.');
            return $value < 0 ? '(' . $abs . ')' : $abs;
        };

        $numericFields = [
            'opening_debit',
            'opening_credit',
            'mutation_debit',
            'mutation_credit',
            'closing_debit',
            'closing_credit'
        ];

        $data = collect($data)->map(function ($group) use ($formatCurrency, $numericFields) {
            // format level 1
            foreach ($numericFields as $field) {
                if (isset($group[$field])) {
                    $group[$field] = $formatCurrency($group[$field]);
                }
            }

            if (isset($group['children'])) {
                $group['children'] = collect($group['children'])->map(function ($sub) use ($formatCurrency, $numericFields) {
                    // format level 2
                    foreach ($numericFields as $field) {
                        if (isset($sub[$field])) {
                            $sub[$field] = $formatCurrency($sub[$field]);
                        }
                    }

                    if (isset($sub['children'])) {
                        $sub['children'] = collect($sub['children'])->map(function ($entry) use ($formatCurrency, $numericFields) {
                            // format level 3
                            foreach ($numericFields as $field) {
                                if (isset($entry[$field])) {
                                    $entry[$field] = $formatCurrency($entry[$field]);
                                }
                            }
                            return $entry;
                        })->toArray();
                    }
                    return $sub;
                })->toArray();
            }
            return $group;
        });

        foreach ($numericFields as $field) {
            if (isset($totals[$field])) {
                $totals[$field] = $formatCurrency($totals[$field]);
            }
        }

        // make PDF
        $pdf = Pdf::loadView('trial_balance', [
            'data' => $data,
            'totals' => $totals,
            'startDate' => $start_date,
            'endDate' => $end_date,
            'division' => $division
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('trial_balance.pdf');
    }

    public function printBalanceSheets(Request $request)
    {
        try {
            $date_periode = $request->query('datePeriode');
            $view_total   = $request->query('viewTotal') === 'true';
            $view_parent  = $request->query('viewParent') === 'true';
            $view_children = $request->query('viewChildren') === 'true';
            $division     = $request->query('division');

            if (!$date_periode) {
                return response()->json([
                    'status'    => false,
                    'message'   => 'Tanggal wajib diisi.'
                ], 400);
            }

            if
            (
                $view_total && ($view_parent || $view_children) ||
                !$view_total && !$view_parent && !$view_children ||
                !$view_total && $view_parent === false && $view_children === true
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter tidak sesuai.'
                ], 400);
            }

            if ($view_total) {
                $result = $this->periodeBalance->getTotalBalanceSheet($date_periode, $division);

                $data = collect($result)->map(function ($types, $group) {
                    return [
                        'account_group_name'    => $group,
                        'account_types'      => collect($types)->map(function ($type) {
                            return [
                                'account_type_name' => $type['account_type_name'],
                                'total_balance'     => $type['total_balance'],
                            ];
                        })->toArray(),
                        'closing_balance'       => 0,
                        'total_balance'      => collect($types)->sum('total_balance'),
                    ];
                })->values()->toArray();

                // blade for total
                $view = 'balance_sheet_total';
            } else {
                $rawResult = $this->periodeBalance->getDetailedBalanceSheet($date_periode, $view_children, $division);

                if (is_array($rawResult)) {
                    $result = $rawResult;
                } elseif ($rawResult instanceof Collection) {
                    $result = $rawResult->toArray();
                } else {
                    $result = [];
                }

                $data = collect($result)->map(function ($group) {
                    $totalBalanceGroup = collect($group['account_types'])->sum('total_balance');

                    return [
                        'account_group_name' => $group['account_group_name'] ?? 'Tanpa Kelompok',
                        'total_balance' => $totalBalanceGroup,
                        'account_types' => collect($group['account_types'] ?? [])->map(function ($type) {
                            return [
                                'account_type_name' => $type['account_type_name'] ?? '',
                                'accounts' => collect($type['accounts'] ?? [])->map(function ($account) {
                                    return [
                                        'account_name' => $account['name'] ?? $account['account_name'] ?? '',
                                        'total_balance' => $account['balance'] ?? $account['total_balance'] ?? 0,
                                        'children' => $account['children'] ?? [],
                                    ];
                                })->toArray(),
                            ];
                        })->toArray(),
                    ];
                })->toArray();

                $view = 'balance_sheet_detail';
            }

            // generate PDF
            $pdf = Pdf::loadView($view, [
                'periode'       => $date_periode,
                'division'      => $division,
                'data'          => $data,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('balance_sheet.pdf');
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function printIncomeStatements(Request $request)
    {
        $start_date = $request->query('startDate');
        $end_date = $request->query('endDate');
        $division = $request->query('division');
        $view_total = $request->query('viewTotal') === 'true';
        $view_parent = $request->query('viewParent') === 'true';
        $view_children = $request->query('viewChildren') === 'true';

        if (!$start_date || !$end_date) {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal wajib diisi.'
            ], 400);
        }

        if (
            $view_total && ($view_parent || $view_children) ||
            !$view_total && !$view_parent && !$view_children ||
            !$view_total && $view_parent === false && $view_children === true
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Parameter tidak sesuai.'
            ], 400);
        }

        $result = $this->periodeBalance->getTotalIncomeStatement($start_date, $end_date, $division);

        $data = $result['data'] ?? [];
        $summary = $result['summary'] ?? [];

        $pdf= Pdf::loadView('income_statement', [
            'data'          => $data,
            'summary'       => $summary,
            'startDate'     => $start_date,
            'endDate'       => $end_date,
            'division'      => $division,
            'viewTotal'     => $view_total,
            'viewParent'    => $view_parent,
            'viewChildren'  => $view_children,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('income_statement.pdf');
    }
}
