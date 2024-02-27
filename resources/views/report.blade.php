<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{$report->title}}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            margin: 0;
            width: 100%;
        }

        table {
            margin-top: 50px;
            border-collapse: collapse;
            width: 800px;
        }

        th,
        td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        td {
            border-bottom: 1px solid #ddd;
        }

        div {
            width: 800px;
            margin-top: 25px;
        }
    </style>
</head>

<body>
    <div>
        Reported at: {{$report->created_at}}
    </div>
    <table>
        <thead>
            <tr>
                <th>Trade Name</th>
                <th>Quantity</th>
                @if(isset($row->row4))
                <th>First time to buy</th>
                @else
                <th>Total</th>
                @endif
                @if(isset($row->row4))
                <th>Favorite by</th>
                @endif

            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
            <tr>
                <td>{{ $row->row1 }}</td>
                <td>{{ $row->row2 }}</td>
                <td>{{ $row->row3 }}</td>
                @if(isset($row->row4))
                <td>{{ $row->row4 }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>

</html>