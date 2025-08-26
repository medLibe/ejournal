<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Neraca</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { margin-bottom: 5px; text-align: center; }
        p { margin: 0 0 10px; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        /* th, td { border: 1px solid #ccc; padding: 4px 6px; } */
        th { background-color: #f5f5f5; text-align: left; }
        td.text-right { text-align: right; }
        tr.group-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h2>Laporan Neraca</h2>
    <p>
        Periode: {{ $periode }} <br>
        @if (!empty($division))
            Divisi: {{ $division }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $group)
                {{-- Group --}}
                <tr class="group-row">
                    <td colspan="2"><strong>{{ $group['account_group_name'] }}</strong></td>
                </tr>

                {{-- Account types --}}
                @foreach($group['account_types'] as $type)
                    <tr>
                        <td style="padding-left:20px;">{{ $type['account_type_name'] }}</td>
                        <td class="text-right">
                            {{ $type['total_balance'] < 0
                                ? '(' . number_format(abs($type['total_balance']), 0, ',', '.') . ')'
                                : number_format($type['total_balance'], 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach

                {{-- Total per group --}}
                <tr class="group-row">
                    <td>Total {{ $group['account_group_name'] }}</td>
                    <td class="text-right">
                        {{ $group['total_balance'] < 0
                            ? '(' . number_format(abs($group['total_balance']), 0, ',', '.') . ')'
                            : number_format($group['total_balance'], 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
