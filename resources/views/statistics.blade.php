<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>eduID.cz statistics</title>
    <link rel="stylesheet" href="{{ asset('css/statistic.css') }}">

</head>

<body>

    <h1>Simple eduID.cz statistics</h1>

    <table>
        <tr>
            <td>eduID.cz</td>
            <td>{{ $eduidcz }}</td>
        </tr>
        <tr>
            <td>eduGAIN</td>
            <td>{{ $edugain }}</td>
        </tr>
        <tr>
            <td>eduID.cz services</td>
            <td>{{ $services }}</td>
        </tr>
        <tr>
            <td>eduID.cz organizations</td>
            <td>{{ $organizations }}</td>
        </tr>
    </table>

</body>

</html>
