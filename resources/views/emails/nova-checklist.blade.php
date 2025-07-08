<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nova Checklist de {{ $tipus }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .button {
            display: inline-block;
            background-color: #0d6efd;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nova Checklist de {{ $tipus }}</h1>
    </div>
    
    <div class="content">
        <p>Hola,</p>
        
        <p>S'ha creat una nova checklist de <strong>{{ $tipus }}</strong> per a l'empleat:</p>
        
        <ul>
            <li><strong>Nom:</strong> {{ $empleat->nom_complet }}</li>
            <li><strong>Departament:</strong> {{ $departament }}</li>
            <li><strong>Identificador:</strong> {{ $empleat->identificador_unic }}</li>
        </ul>
        
        <p>Tens les següents tasques pendents:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Tasca</th>
                    <th>Descripció</th>
                    <th>Data límit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasques as $tasca)
                <tr>
                    <td>{{ $tasca->nom }}</td>
                    <td>{{ $tasca->descripcio }}</td>
                    <td>{{ $tasca->data_limit ? $tasca->data_limit->format('d/m/Y') : 'Sense data límit' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <p>Si us plau, accedeix al sistema per completar aquestes tasques el més aviat possible.</p>
        
        <div style="text-align: center;">
            <a href="{{ $url }}" class="button">Veure Checklist</a>
        </div>
    </div>
    
    <div class="footer">
        <p>Aquest és un missatge automàtic, si us plau no responguis a aquest correu.</p>
        <p>© {{ date('Y') }} SIAE: Sistema de Gestió d'Accessos</p>
    </div>
</body>
</html>
