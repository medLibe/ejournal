<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'company_code',
        'company_name',
        'company_type',
    ];

    function getCompanies($params = [], $paginate = true)
    {
        $query = self::query();

        // search
        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function($q) use ($search) {
                $q->where('company_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('company_type', 'like', "%{$search}%");
            });
        }

        // pagination
        $page     = $params['page'] ?? 1;
        $perPage  = $params['perPage'] ?? 50;

        if ($paginate) {
            $data = $query->paginate($perPage, ['*'], 'page', $page);
        } else {
            $data = $query->get();
        }

        return [
            'data' => $data
        ];
    }
}
