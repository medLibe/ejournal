<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Buku Besar</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background-color: #f3f4f6;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .font-semibold {
            font-weight: bold;
        }

        .bg-total {
            background-color: #e5e7eb;
        }

        .mb-2 {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <h2 class="mb-2">Laporan Buku Besar</h2>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No. Akun</th>
                <th>Nama Akun</th>
                <th>Keterangan</th>
                <th>Ref</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Kredit</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ledgers as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->transaction_date)->format('d-m-Y') }}</td>
                    <td>{{ $item->account_code }}</td>
                    <td>{{ $item->account_name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->reference_no }}</td>
                    <td class="text-right">{{ $item->formatted_debit }}</td>
                    <td class="text-right">{{ $item->formatted_credit }}</td>
                    <td class="text-right font-semibold">{{ $item->formatted_balance }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-total font-semibold">
                <td colspan="5" class="text-right">Total</td>
                <td class="text-right">{{ $grandTotalDebit }}</td>
                <td class="text-right">{{ $grandTotalCredit }}</td>
                <td></td>
            </tr>
        </tfoot>

    </table>

</body>

</html>
