<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          /**
         * 1 = Aset (Aktiva/Harta)
         * 2 = Ekuitas (Modal)
         * 3 = Liabilitas (Pasiva/Kewajiban)
         * 4 = Pendapatan
         * 5 = Beban (Biaya)
         */

        DB::table('account_groups')->insert([
            [
                'account_group_name' => 'Aset (Aktiva/Harta)',
                'normal_balance' => 1
            ],
            [
                'account_group_name' => 'Ekuitas (Modal)',
                'normal_balance' => 2
            ],
            [
                'account_group_name' => 'Liabilitas (Pasiva/Kewajiban)',
                'normal_balance' => 2
            ],
            [
                'account_group_name' => 'Pendapatan',
                'normal_balance' => 2
            ],
            [
                'account_group_name' => 'Beban (Biaya)',
                'normal_balance' => 1
            ],
        ]);
    }
}
