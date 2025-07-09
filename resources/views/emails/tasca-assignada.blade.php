<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nova Tasca Assignada</title>
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
        .task-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nova Tasca Assignada</h1>
    </div>
    
    <div class="content">
        <p>Hola {{ $usuari->name }},</p>
        
        <p>Se t'ha assignat una nova tasca al sistema SIAE:</p>
        
        <div class="task-details">
            <h3>{{ $task->nom }}</h3>
            @if($task->descripcio)
                <p><strong>Descripció:</strong> {{ $task->descripcio }}</p>
            @endif
            
            @if($task->data_limit)
                <p><strong>Data límit:</strong> {{ $task->data_limit->format('d/m/Y') }}</p>
            @endif
            
            @if($empleat)
                <p><strong>Empleat/da relacionat/da:</strong> {{ $empleat->nom_complet }}</p>
                <p><strong>Departament:</strong> {{ $empleat->departament ? $empleat->departament->nom : 'No especificat' }}</p>
            @endif
            
            <p><strong>Obligatòria:</strong> {{ $task->obligatoria ? 'Sí' : 'No' }}</p>
        </div>
        
        <p>Si us plau, accedeix al sistema per gestionar aquesta tasca el més aviat possible.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/operatiu/checklist-tasks/{{ $task->id }}" class="button">Veure Tasca</a>
        </div>
    </div>
    
    <div class="footer">
        <p>Aquest és un missatge automàtic, si us plau no responguis a aquest correu.</p>
        <p>© {{ date('Y') }} SIAE: Sistema de Gestió d'Accessos</p>
    </div>
</body>
</html>
