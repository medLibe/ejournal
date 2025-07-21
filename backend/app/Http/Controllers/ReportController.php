<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\GeneralLedger;
use App\Models\PeriodeBalance;
use Exception;
use Illuminate\Http\Request;

class ReportController extends Controller
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

    public function getBalanceSheets(Request $request)
    {
        try {
            $date_periode = $request->query('datePeriode');
            $view_total = $request->query('viewTotal') === 'true';
            $view_parent = $request->query('viewParent') === 'true';
            $view_children = $request->query('viewChildren') === 'true';
            $division = $request->query('division');

            if(!$date_periode) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tanggal wajib diisi.'
                ], 400);
            }

            if (
                $view_total && ($view_parent || $view_children) || //view total must send as single param
                !$view_total && !$view_parent && !$view_children || //at least one parameter choosed
                !$view_total && $view_parent === false && $view_children === true //view children can't true if view parent false
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter tidak sesuai.'
                ], 400);
            }

            if ($view_total) {
                $result = $this->periodeBalance->getTotalBalanceSheet($date_periode, $division);
            } else {
                $result = $this->periodeBalance->getDetailedBalanceSheet($date_periode, $view_children, $division);
            }

            return response()->json([
                'status'    => true,
                'data'      => $result,
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getTrialBalances(Request $request)
    {
        try {
            $start_date = $request->query('startDate');
            $end_date = $request->query('endDate');
            $division = $request->query('division');

            if(!$start_date || !$end_date) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tanggal wajib diisi.'
                ], 400);
            }

            $result = $this->periodeBalance->getTrialBalance($start_date, $end_date, $division);

            return response()->json([
                'status'    => true,
                'data'      => $result,
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getIncomeStatements(Request $request)
    {
        try {
            $start_date = $request->query('startDate');
            $end_date = $request->query('endDate');
            $view_total = $request->query('viewTotal') === 'true';
            $view_parent = $request->query('viewParent') === 'true';
            $view_children = $request->query('viewChildren') === 'true';
            $division = $request->query('division');

            if (!$start_date || !$end_date) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tanggal wajib diisi.'
                ], 400);
            }

            if (
                $view_total && ($view_parent || $view_children) || // view total must be a single param
                !$view_total && !$view_parent && !$view_children || // at least one parameter must be chosen
                !$view_total && $view_parent === false && $view_children === true // view children can't be true if view parent is false
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter tidak sesuai.'
                ], 400);
            }

            if ($view_total) {
                $result = $this->periodeBalance->getTotalIncomeStatement($start_date, $end_date, $division);
            } else {
                $result = $this->periodeBalance->getDetailedIncomeStatement($start_date, $end_date, $view_children);
            }

            return response()->json([
                'status'    => true,
                'data'      => $result,
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getAccounts()
    {
        try {
            $accounts = $this->account->getSelectableAccounts();

            return response()->json([
                'status'    => true,
                'data'      => $accounts
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getLedgers(Request $request)
    {
        try {
            $start_date = $request->query('startDate');
            $end_date = $request->query('endDate');
            $account_id = $request->query('accountId');
            $division = $request->query('division');

            if(!$start_date && !$end_date) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tanggal wajib diisi.'
                ], 400);
            }

            if(!$account_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akun wajib dipilih.'
                ], 400);
            }

            $result = $this->generalLedger->getLedgers($account_id, $start_date, $end_date, $division);

            return response()->json([
                'status'    => true,
                'data'      => $result,
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
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
}
