<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $fillable = [
        'account_group_id',
        'account_type_name',
    ];

    public function accountGroup()
    {
        return $this->belongsTo(AccountGroup::class, 'account_group_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'account_type_id');
    }
}
