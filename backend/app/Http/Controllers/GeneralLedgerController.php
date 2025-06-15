<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\GeneralLedger;
use App\Models\GeneralLedgerImport;
use App\Models\PeriodeBalance;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;

class GeneralLedgerController extends Controller
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
    
    public function getGeneralLedgers(Request $request)
    {
        try {
            $params = [
                'start_date'    => $request->input('startDate'),
                'end_date'      => $request->input('endDate'),
                'page'          => $request->input('page', 1),
                'perPage'       => $request->input('perPage', 50),
                'search'        => $request->input('search')
            ];

            $getGeneralLedgers = $this->generalLedger->getGeneralLedgers($params, true);

            return response()->json([
                'status'         => true,
                'generalLedgers' => $getGeneralLedgers['data']->items(),
                'total'          => $getGeneralLedgers['data']->total(),
                'total_amount'   => $getGeneralLedgers['total_amount'],
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getGeneralLedgersByImportNo(Request $request, $importNo)
    {
        try {
            $import = $this->generalLedgerImport->where('import_no', $importNo)->firstOrFail();

            $params = [
                'import_id' => $import->id,
                'page'      => $request->input('page', 1),
                'perPage'   => $request->input('perPage', 50),
                'search'        => $request->input('search')
            ];

            if (!$import) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data import tidak ditemukan.'
                ], 404);
            }

            $getGeneralLedgers = $this->generalLedger->getGeneralLedgers($params, true);

             return response()->json([
                'status'         => true,
                'generalLedgers' => $getGeneralLedgers['data']->items(),
                'total'          => $getGeneralLedgers['data']->total(),
                'total_amount'   => $getGeneralLedgers['total_amount'],
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getGeneralLedgerByReferenceNo(Request $request)
    {
        try {
            $referenceNo = $request->query('ref');
            $generalLedgers = $this->generalLedger
                ->where('reference_no', $referenceNo)
                ->join('accounts', 'general_ledgers.account_id', '=', 'accounts.id')
                ->select('general_ledgers.*', 'accounts.account_name', 'accounts.account_code')
                ->get();

            if (!$generalLedgers) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nomor sumber tidak ditemukan.'
                ], 404);
            }

            return response()->json([
                'status'         => true,
                'ref'            => $referenceNo,
                'generalLedgers' => $generalLedgers,
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getGeneralLedgerImports()
    {
        try {
            $getGeneralLedgerImports = $this->generalLedgerImport->get();

            return response()->json([
                'status'               => true,
                'generalLedgerImports' => $getGeneralLedgerImports,
                'total'                => count($getGeneralLedgerImports)
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function printGeneralLedgersVoucherList(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if (!$startDate || !$endDate) {
            return response()->json([
                'status'    => false,
                'message'   => 'Parameter tanggal tidak sesuai.',
            ], 422);
        }

        $params = [
            'start_date'    => $startDate,
            'end_date'      => $endDate,
        ];

        $startDateFixed = date('d-m-Y', strtotime($startDate));
        $endDateFixed = date('d-m-Y', strtotime($endDate));

        $data = $this->generalLedger->getGeneralLedgersForPrint($params);

        $pdf = Pdf::loadView('general_ledgers_voucher_detail', [
            'generalLedgers'    => $data
        ])->setPaper('A4', 'portrait');

        return $pdf->stream('Daftar Bukti Jurnal Periode ' . $startDateFixed . ' sd ' . $endDateFixed);
    }
}
