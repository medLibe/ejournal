<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2, p { margin: 0; padding: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td, th { padding: 6px; }
        th { text-align: left; background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .indent-1 { padding-left: 20px; }
        .indent-2 { padding-left: 40px; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    @php
        function formatNumber($value) {
            if (empty($value)) return '0';
            $clean = str_replace(['.', ','], '', $value);
            if (!is_numeric($clean)) return '0';
            return number_format((float)$clean, 0, ',', '.');
        }
    @endphp

    <h2>Laporan Laba Rugi</h2>
    <p>Periode: {{ $startDate }} sampai {{ $endDate }}</p>
    @if($division)
        <p>Divisi: {{ $division }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $groupName => $types)
                {{-- Group Name (e.g. Pendapatan, Beban) --}}
                <tr>
                    <td class="bold">{{ $groupName }}</td>
                    <td></td>
                </tr>

                @foreach($types as $type)
                    {{-- type level --}}
                    <tr>
                        <td class="indent-1 bold">{{ $type['account_type_name'] }}</td>
                        <td></td>
                    </tr>

                    @foreach($type['accounts'] as $account)
                        {{-- account level --}}
                        <tr>
                            <td class="indent-2">{{ $account['account_name'] }}</td>
                            <td class="text-right">{{ formatNumber($account['total_balance']) }}</td>
                        </tr>
                    @endforeach

                    {{-- total account per group --}}
                    <tr>
                        <td class="indent-1 bold">Total {{ $type['account_type_name'] }}</td>
                        <td class="text-right bold">{{ formatNumber($type['total_balance']) }}</td>
                    </tr>
                @endforeach
            @endforeach

            {{-- summery --}}
            <tr><td colspan="2">&nbsp;</td></tr>
            <tr>
                <td><strong>Total Pendapatan</strong></td>
                <td class="text-right"><strong>{{ formatNumber($summary['total_income'] ?? 0) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Biaya & HPP</strong></td>
                <td class="text-right"><strong>{{ formatNumber($summary['total_cost'] ?? 0) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Laba Sebelum Pajak</strong></td>
                <td class="text-right"><strong>{{ formatNumber($summary['profit_before_tax'] ?? 0) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Pajak (11%)</strong></td>
                <td class="text-right"><strong>{{ formatNumber($summary['tax'] ?? 0) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Laba Setelah Pajak</strong></td>
                <td class="text-right"><strong>{{ formatNumber($summary['profit_after_tax'] ?? 0) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
