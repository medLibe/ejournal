<?php

namespace App\Exports;

use App\Models\AccountType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class ExportAccountTypes implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // mapping null with n/a
        return AccountType::
            all(['account_type_name', 'specific_account_type_number', 'start_account_type_number', 'end_account_type_number', 'is_single'])
            ->map(function ($item) {
            $item->specific_account_type_number = $item->specific_account_type_number ?: 'N/A';
            $item->start_account_type_number = $item->start_account_type_number ?: 'N/A';
            $item->end_account_type_number = $item->end_account_type_number ?: 'N/A';
            $item->is_single = $item->is_single === 0 ? 'Tidak' : 'Ya';

            return $item;
        });
    }

    // defined heading
    public function headings(): array
    {
        return [
            'Tipe Akun',
            'Nomor Spesifik',
            'Nomor Awal',
            'Nomor Akhir',
            'Berdiri Sendiri',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $cellRange = 'A1:E1'; // headers
                $event->sheet->getDelegate()
                    ->getStyle($cellRange)
                    ->getFont()
                    ->setSize(12)
                    ->setBold(true);

                // count total data rows
                $totalRows = count($this->collection()) + 1;

                // style border
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ];

                // implement border to all data
                $event->sheet->getStyle("A1:E{$totalRows}")->applyFromArray($styleArray);

                // implement alignment to all data
                $event->sheet->getStyle("A1:E{$totalRows}")
                        ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
