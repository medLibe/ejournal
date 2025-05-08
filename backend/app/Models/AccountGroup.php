<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountGroup extends Model
{
    protected $fillable = [
        'account_group_name',
        'normal_balance',
    ];

    public function accountTypes()
    {
        return $this->hasMany(AccountType::class, 'account_group_id');
    }
}
