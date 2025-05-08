<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountTypeSeeder extends Seeder
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

         DB::table('account_types')->insert([
            ['account_type_name' => 'Kas/Bank', 'account_group_id' => 1],
            ['account_type_name' => 'Akun Piutang', 'account_group_id' => 1],
            ['account_type_name' => 'Aktiva Lancar lainnya', 'account_group_id' => 1],
            ['account_type_name' => 'Persediaan', 'account_group_id' => 1],
            ['account_type_name' => 'Aktiva Tetap', 'account_group_id' => 1],
            ['account_type_name' => 'Akumulasi Penyusutan', 'account_group_id' => 1],
            ['account_type_name' => 'Akun Hutang', 'account_group_id' => 3],
            ['account_type_name' => 'Hutang lancar lainnya', 'account_group_id' => 3],
            ['account_type_name' => 'Ekuitas', 'account_group_id' => 2],
            ['account_type_name' => 'Pendapatan', 'account_group_id' => 4],
            ['account_type_name' => 'Harga Pokok Penjualan', 'account_group_id' => 5],
            ['account_type_name' => 'Beban', 'account_group_id' => 5],
            ['account_type_name' => 'Beban lain-lain', 'account_group_id' => 5],
            ['account_type_name' => 'Pendapatan lain', 'account_group_id' => 4],
        ]);
    }
}
