<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\GeneralLedger;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    protected $company;
    protected $generalLedgers;

    public function __construct()
    {
        $this->company = new Company();
        $this->generalLedgers = new GeneralLedger();
    }

    public function getCompanies(Request $request)
    {
        try {
            $params = [
                'page'          => $request->input('page', 1),
                'perPage'       => $request->input('perPage', 50),
                'search'        => $request->input('search')
            ];

            $getCompanies = $this->company->getCompanies($params, true);

            return response()->json([
                'status'    => true,
                'companies' => $getCompanies['data']->items(),
                'total'     => $getCompanies['data']->total(),
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function storeCompany(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_code'  => 'required|unique:companies,company_code',
                'company_name'  => 'required',
                'company_type'  => 'required'
            ], [
                'company_code.unique'   => 'Kode perusahaan sudah terdaftar.',
                'company_code.required' => 'Kode perusahaan wajib diisi.',
                'company_name.required' => 'Nama perusahaan wajib diisi.',
                'company_type.required' => 'Tipe perusahaan wajib diisi.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'    => false,
                    'error'     => $validator->errors(),
                    'message'   => 'Terdapat error pada input data.',
                ], 400);
            }

            $user = auth('sanctum')->user();

            $this->company->create([
                'company_code'  => $request->company_code,
                'company_name'  => $request->company_name,
                'company_type'  => $request->company_type,
                'created_by'    => $user->name,
            ]);

            return response()->json([
                'status'    => true,
                'message'   => 'Data perusahaan baru berhasil ditambahkan.'
            ]);

        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function deleteCompany($companyId)
    {
        try {
            $usedInLedgers = $this->generalLedgers->where('company_id', $companyId)->exists();

            if ($usedInLedgers) {
                return response()->json([
                    'status'    => false,
                    'message'   => 'Data perusahaan ini tidak dapat dihapus.',
                ], 400);
            }

            $getCompany = $this->company->find($companyId);
            if (!$getCompany) {
                return response()->json([
                    'status'    => false,
                    'message'   => 'Data perusahaan tidak ditemukan.',
                ], 404);
            }

            $getCompany->delete();

            return response()->json([
                'status'    => true,
                'message'   => 'Data perusahaan berhasil dihapus.'
            ]);

        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }
}
