<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Neraca</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2, p { margin: 0; padding: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td, th { padding: 6px; }
        th { text-align: left; background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .indent-1 { padding-left: 20px; }
        .indent-2 { padding-left: 40px; }
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
                <tr class="group-row">
                    <td colspan="2"><strong>{{ $group['account_group_name'] }}</strong></td>
                </tr>

                @foreach($group['account_types'] as $type)
                    <tr>
                        <td style="padding-left:20px;"><strong>{{ $type['account_type_name'] }}</strong></td>
                        <td class="text-right">
                        </td>
                    </tr>

                    @foreach($type['accounts'] as $account)
                        <tr>
                            <td style="padding-left:40px;">{{ $account['account_name'] ?? $account['name'] }}</td>
                            <td class="text-right">
                                {{ $account['total_balance'] < 0
                                    ? '(' . number_format(abs($account['total_balance']), 0, ',', '.') . ')'
                                    : number_format($account['total_balance'], 0, ',', '.') }}
                            </td>
                        </tr>

                        @if(!empty($account['children']))
                            @foreach($account['children'] as $child)
                                <tr>
                                    <td style="padding-left:60px;">{{ $child['name'] }}</td>
                                    <td class="text-right">
                                        {{ $child['balance'] < 0
                                            ? '(' . number_format(abs($child['balance']), 0, ',', '.') . ')'
                                            : number_format($child['balance'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach

                    @php
                        $totalBalance = collect($type['accounts'])->sum('total_balance');
                    @endphp
                    <tr>
                        <td style="padding-left:20px;"><strong>Total {{ $type['account_type_name'] }}</strong></td>
                        <td class="text-right">
                            <strong>
                                {{ $totalBalance < 0
                                ? '(' . number_format(abs($totalBalance), 0, ',', '.') . ')'
                                : number_format($totalBalance, 0, ',', '.') }}
                            </strong>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

</body>
</html>
