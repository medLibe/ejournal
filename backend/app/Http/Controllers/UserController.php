<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\GeneralLedger;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $user;
    protected $generalLedger;
    protected $account;

    public function __construct()
    {
        $this->user = new User();
        $this->generalLedger = new GeneralLedger();
        $this->account = new Account();
    }

    public function login(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'username'  => 'required',
                'password'  => 'required',
            ], [
                'username.required' => 'Username tidak boleh kosong.',
                'password.required' => 'Password tidak boleh kosong.',
            ]);

            if($validation->fails()) {
                return response()->json([
                    'status'   => false,
                    'message'   => 'Terdapat error pada saat input data.',
                    'errors'    => $validation->errors()
                ], 400);
            }

            $credentials = request(['username', 'password']);

            if(!Auth::attempt($credentials)){
                return response()->json([
                    'status'   => false,
                    'message'   => 'Kredensial tidak cocok dengan database.'
                ], 401);
            }

            $user = $request->user();
            $generatedToken = $user->createToken('Authorized Token');
            $token = $generatedToken->plainTextToken;

            return response()->json([
                'status'        => true,
                'message'       => 'Login berhasil, halaman akan redirect dalam beberapa saat.',
                'accessToken'   => $token,
                'tokenType'     => 'Bearer'
            ]);

        } catch (Exception $error) {
            return response()->json([
                'status'   => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'status' => true,
                'data'   => $user,
            ]);

            if (!$user) {
                return response()->json(['message' => 'User tidak ditemukan'], 401);
            }

            $user->tokens()->delete();

            return response()->json([
                'status' => true,
            ]);

        } catch (Exception $error) {
            return response()->json([
                'status'   => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getUsers()
    {
        try {
            $getUsers = $this->user->get();

            return response()->json([
                'status'    => true,
                'users'     => $getUsers
            ]);

        } catch (Exception $error) {
            return response()->json([
                'status'   => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getUserById($userId)
    {
        try {
            $getUserById = $this->user->where('id', $userId)->first();

            return response()->json([
                'status'    => true,
                'user'      => $getUserById
            ]);

        } catch (Exception $error) {
            return response()->json([
                'status'   => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function storeUser(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'name'      => 'required',
                'username'  => 'required|unique:users,username',
                'password'  => 'required',
            ], [
                'name.required'     => 'Nama tidak boleh kosong.',
                'username.unique'   => 'Username telah terdaftar.',
                'username.required' => 'Username tidak boleh kosong.',
                'password.required' => 'Password tidak boleh kosong.',
            ]);

            if($validation->fails()) {
                return response()->json([
                    'status'   => false,
                    'message'   => 'Terdapat error pada saat input data.',
                    'errors'    => $validation->errors()
                ], 400);
            }

            $this->user->create([
                'name'      => $request->name,
                'username'  => $request->username,
                'password'  => $request->password,
            ]);

            return response()->json([
                'status'    => true,
                'message'   => 'User baru berhasil ditambahkan.'
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function updateUser(Request $request, $userId)
    {
        try {
            $getUser = $this->user->where('id', $userId)->first();

            if($getUser->username !== $request->username) {
                $this->user->where('id', $userId)->update([
                    'name'      => $request->name,
                    'username'  => $request->username,
                    'password'  => $request->password,
                ]);
            }

            $this->user->where('id', $userId)->update([
                'name'      => $request->name,
                'password'  => $request->password,
            ]);

            return response()->json([
                'status'    => true,
                'message'   => 'User berhasil diperbarui.'
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }

    public function getDashboard()
    {
        try {
            $now = Carbon::now();

            $generalLedger = $this->generalLedger->countAmountGeneralLedger($now->year, $now->month);

            $revenue = $this->generalLedger->countAmountGeneralLedgerByNominalAccount(4, $now->year, $now->month);
            $expense = $this->generalLedger->countAmountGeneralLedgerByNominalAccount(5, $now->year, $now->month);
            $cash = $this->generalLedger->countAmountGeneralLedgerByParentAccount('KAS', $now->year, $now->month);
            $bank = $this->generalLedger->countAmountGeneralLedgerByParentAccount('BANK', $now->year, $now->month);

            $activeAccount = $this->account->where('is_active', true)->count();

            $currentDB = DB::connection()->getDatabaseName();
            $department = strtoupper(str_replace('ejournal_', '', $currentDB));

            return response()->json([
                'active_account'    => $activeAccount,
                'revenue'           => (float) $revenue,
                'expense'           => (float) $expense,
                'cash'              => (float) $cash,
                'bank'              => (float) $bank,
                'general_ledger'    => (float) $generalLedger->total_amount,
                'department'        => $department,
            ]);
        } catch (Exception $error) {
            return response()->json([
                'status'    => false,
                'message'   => $error->getMessage()
            ], 500);
        }
    }
}
