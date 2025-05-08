<?php

namespace App\Http\Controllers;

use App\Exports\ExportAccountTypes;
use App\Imports\ImportAccountTypes;
use App\Models\AccountType;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class AccountTypeController extends Controller
{
    protected $accountType;

    public function __construct()
    {
        $this->accountType = new AccountType();
    }

    public function getAccountTypes()
    {
        try {
            $getAccountTypes = $this->accountType->with('accountGroup')->get();

            return response()->json([
                'status'        => true,
                'accountTypes'  => $getAccountTypes,
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }
}
