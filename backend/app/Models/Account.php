<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    protected $fillable = [
        'account_name',
        'account_type_id',
        'account_code',
        'account_code_alt',
        'account_name_alt',
        'parent_id', //parent code
        'opening_balance', //opening balance for first time
        'previous_balance',
        'is_active',
    ];

    // relation to account tpyes
    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

     public function subAccounts()
    {
        return $this->hasMany(Account::class, 'parent_id', 'account_code');
    }

    public function children()
    {
        return $this->subAccounts()->with('children');
    }

    public function balances()
    {
        return $this->hasMany(PeriodeBalance::class, 'account_id', 'id');
    }

    public static function getAccounts($request)
    {
        try {
            $accounts = self::with(['accountType'])->get();

            $accounts = $accounts->sortBy(function ($item) {
                $parts = explode('-', $item->account_code);
            
                // convert parts to integers to sort numerically
                $part1 = isset($parts[0]) ? (int)$parts[0] : 0;
                $part2 = isset($parts[1]) ? (int)$parts[1] : 0;
                $part3 = isset($parts[2]) ? (int)$parts[2] : 0;
            
                return sprintf('%02d-%05d-%05d-%05d', $item->accountType->id ?? 0, $part1, $part2, $part3);
            })->values();

            $accounts->transform(function ($item) {
                $item->beginning_balance = $item->beginning_balance ?? 0;

                if ($item->parent_id) {
                    $parentAccount = self::where('account_code', $item->parent_id)->first();
                    $item->parent_account_name = $parentAccount ? $parentAccount->account_name : '-';
                } else {
                    $item->parent_account_name = '-';
                }

                $item->account_type_name = $item->accountType->account_type_name;

                return $item;
            });

            return $accounts;
        } catch (Exception $error) {
            throw new \Exception($error->getMessage());
        }
    }

    public function getSelectableAccounts()
    {
        return self::whereDoesntHave('children')
            ->withCount('children')
            ->orderBy('account_name', 'asc')
            ->get()
            ->filter(function ($account) {
                return $account->children_count === 0 ||
                ($account->children_count > 0 && $account->children->every(fn($child) => $child->children->isEmpty()));
            })
            ->map(function ($account) {
                return [
                    'id'            => $account->id,
                    'account_code'  => $account->account_code,
                    'account_name'  => $account->account_name,
                ];
            })
            ->values();
    }
}
