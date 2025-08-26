<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Laporan Neraca Saldo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h2 {
            margin-bottom: 5px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 4px 6px;
        }

        th {
            background-color: #f5f5f5;
            text-align: left;
        }

        td.text-right {
            text-align: right;
        }

        tr.group-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        tfoot td {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .account-code {
            width: 8%;
        }
        
        .account-name {
            width: 20%;
        }

        .level-1 {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>Trial Balance</h2>
    <p>
        Periode: {{ $startDate }} s/d {{ $endDate }}<br>
        @if (!empty($division))
            Divisi: {{ $division }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th class="account-code">No. Akun</th>
                <th>Nama Akun</th>
                <th class="text-right">Saldo Awal Debet</th>
                <th class="text-right">Saldo Awal Kredit</th>
                <th class="text-right">Perubahan Debet</th>
                <th class="text-right">Perubahan Kredit</th>
                <th class="text-right">Saldo Akhir Debet</th>
                <th class="text-right">Saldo Akhir Kredit</th>
            </tr>
        </thead>
        
        <tbody>
            @foreach($data as $group)
                {{-- Level 1 --}}
                <tr>
                    <td class="account-code">{{ $group['group_code'] ?? $group['account_code'] ?? '' }}</td>
                    <td class="account-name level-1">{{ $group['group_name'] ?? $group['account_name'] ?? '' }}</td>
                    <td class="text-right">{{ $group['opening_debit'] ?? '' }}</td>
                    <td class="text-right">{{ $group['opening_credit'] ?? '' }}</td>
                    <td class="text-right">{{ $group['mutation_debit'] ?? '' }}</td>
                    <td class="text-right">{{ $group['mutation_credit'] ?? '' }}</td>
                    <td class="text-right">{{ $group['closing_debit'] ?? '' }}</td>
                    <td class="text-right">{{ $group['closing_credit'] ?? '' }}</td>
                </tr>

                @if(!empty($group['children']))
                    @foreach($group['children'] as $sub)
                        {{-- Level 2 --}}
                        <tr>
                            <td class="account-code">{{ $sub['account_code'] ?? '' }}</td>
                            <td class="account-name" style="padding-left: 20px;">{{ $sub['account_name'] ?? '' }}</td>
                            <td class="text-right">{{ $sub['opening_debit'] ?? '' }}</td>
                            <td class="text-right">{{ $sub['opening_credit'] ?? '' }}</td>
                            <td class="text-right">{{ $sub['mutation_debit'] ?? '' }}</td>
                            <td class="text-right">{{ $sub['mutation_credit'] ?? '' }}</td>
                            <td class="text-right">{{ $sub['closing_debit'] ?? '' }}</td>
                            <td class="text-right">{{ $sub['closing_credit'] ?? '' }}</td>
                        </tr>

                        @if(!empty($sub['children']))
                            @foreach($sub['children'] as $acc)
                                {{-- Level 3 --}}
                                <tr>
                                    <td class="account-code">{{ $acc['account_code'] ?? '' }}</td>
                                    <td class="account-name" style="padding-left: 40px;">{{ $acc['account_name'] ?? '' }}</td>
                                    <td class="text-right">{{ $acc['opening_debit'] ?? '' }}</td>
                                    <td class="text-right">{{ $acc['opening_credit'] ?? '' }}</td>
                                    <td class="text-right">{{ $acc['mutation_debit'] ?? '' }}</td>
                                    <td class="text-right">{{ $acc['mutation_credit'] ?? '' }}</td>
                                    <td class="text-right">{{ $acc['closing_debit'] ?? '' }}</td>
                                    <td class="text-right">{{ $acc['closing_credit'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                @endif
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="2" class="text-right">Total</td>
                <td class="text-right">{{ $totals['opening_debit'] ?? 0 }}</td>
                <td class="text-right">{{ $totals['opening_credit'] ?? 0 }}</td>
                <td class="text-right">{{ $totals['mutation_debit'] ?? 0 }}</td>
                <td class="text-right">{{ $totals['mutation_credit'] ?? 0 }}</td>
                <td class="text-right">{{ $totals['closing_debit'] ?? 0 }}</td>
                <td class="text-right">{{ $totals['closing_credit'] ?? 0 }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
