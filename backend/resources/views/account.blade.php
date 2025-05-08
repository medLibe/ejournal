<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Akun</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border: 1px solid black;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid black;
        }
        th {
            background-color: #f2f2f2;
        }
        .indent {
            padding-left: 20px;
        }

        .indent-2 {
            padding-left: 30px;
        }

        .right {
            text-align: right;
        }
        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h2>Master Data Akun</h2>

    <table>
        <thead>
            <tr>
                <th>No. Akun</th>
                <th>Nama Akun</th>
                <th>Tipe Akun</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($accounts as $account)
                @if ($account->parent_id == null)  <!-- Akun induk -->
                    <tr>
                        <td class="bold">{{ $account->account_code }}</td>
                        <td class="bold">{{ $account->account_name }}</td>
                        <td class="bold">{{ $account->accountType->account_type_name }}</td>
                        <td class="right bold">Rp {{ $account->opening_balance ? number_format($account->opening_balance, 2, ',', '.') : '-' }}</td>
                    </tr>

                    @foreach ($account->subAccounts as $subAccount)  <!-- Sub-Akun -->
                        <tr>
                            <td class="indent">{{ $subAccount->account_code }}</td>
                            <tds>{{ $subAccount->account_name }}</tds>
                            <td>{{ $subAccount->accountType->account_type_name }}</td>
                            <td class="right">Rp {{ $subAccount->opening_balance ? number_format($subAccount->opening_balance, 2, ',', '.') : '-' }}</td>
                        </tr>

                        @foreach ($subAccount->subAccounts as $subSubAccount)  <!-- Sub-Sub-Akun -->
                            <tr>
                                <td class="indent-2">{{ $subSubAccount->account_code }}</td>
                                <td>{{ $subSubAccount->account_name }}</td>
                                <td>{{ $subSubAccount->accountType->account_type_name }}</td>
                                <td class="right">Rp {{ $subSubAccount->opening_balance ? number_format($subSubAccount->opening_balance, 2, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

</body>
</html>
