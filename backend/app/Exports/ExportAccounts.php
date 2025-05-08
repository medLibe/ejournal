<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class ExportAccounts implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // mapping null with n/a
        $accounts = Account::with(['subAccounts.subAccounts'])->whereNull('parent_id')->get();

        $result = collect();

         foreach ($accounts as $account) {
            // Proses akun dan sub-akun menggunakan fungsi getAccountData
            $result = $result->merge($this->getAccountData($account, 0));
        }

        return $result;
    }

    // recursive funtion to get account data and sub account data with indentation
    private function getAccountData($account, $indentLevel)
    {
        $accountData = collect([[
            'account_code' => str_repeat('    ', $indentLevel) . $account->account_code,
            'account_name' => str_repeat('    ', $indentLevel) . $account->account_name,
            'account_type' => $account->accountType->account_type_name ?? '-',
            'balance' => $account->beginning_balance ? 'Rp ' . number_format($account->beginning_balance, 2, ',', '.') : '-'
        ]]);

        // check if sub account exists
        $subAccountsData = collect();
        foreach ($account->subAccounts as $subAccount) {
            $subAccountsData = $subAccountsData->merge($this->getAccountData($subAccount, $indentLevel + 1));
        }

        return $accountData->merge($subAccountsData);
    }

    public function headings(): array
    {
        return [
            'No. Akun',
            'Nama Akun',
            'Tipe Akun',
            'Saldo',
        ];
    }

    // excel styling format
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $cellRange = 'A1:D1'; // headers
                $event->sheet->getDelegate()
                    ->getStyle($cellRange)
                    ->getFont()
                    ->setSize(12)
                    ->setBold(true);

                // count total data rows
                $totalRows = count($this->collection()) + 1;

                // Style border
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ];

                // apply border to all data
                $event->sheet->getStyle("A1:D{$totalRows}")->applyFromArray($styleArray);

                // align all data to center
                $event->sheet->getStyle("A1:D{$totalRows}")
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // set indentations for the account codes
                foreach (range(2, $totalRows) as $row) {
                    $indentLevel = substr_count($event->sheet->getCell("A{$row}")->getValue(), '    '); // count indent spaces
                    $event->sheet->getCell("A{$row}")->getStyle()->getAlignment()->setIndent($indentLevel); // set indent
                    $event->sheet->getCell("B{$row}")->getStyle()->getAlignment()->setIndent($indentLevel); // set indent for account name as well
                }
            },
        ];
    }
}
