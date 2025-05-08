<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralLedgerImport extends Model
{
    protected $fillable = [
        'import_no',
        'import_date',
        'created_by',
        'updated_by',
    ];
}
