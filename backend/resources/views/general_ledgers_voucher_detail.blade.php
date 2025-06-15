<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Bukti Jurnal</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h3>General Ledger - Voucher Detail</h3>
    <p>Periode: {{ request('startDate') }} s/d {{ request('endDate') }}</p>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No Sumber</th>
                <th>Tipe Sumber</th>
                <th class="right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($generalLedgers as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->transaction_date)->format('d-m-Y') }}</td>
                    <td>{{ $item->reference_no }}</td>
                    <td>{{ $item->reference }}</td>
                    <td class="right">{{ number_format($item->total_amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
