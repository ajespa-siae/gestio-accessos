<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sol·licitud Finalitzada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #28a745;
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
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .solicitud-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .success-icon {
            font-size: 48px;
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Sol·licitud Finalitzada</h1>
    </div>
    
    <div class="content">
        <div class="success-icon">🎉</div>
        
        <p>Hola {{ $usuari->name }},</p>
        
        <p>T'informem que la sol·licitud d'accés que vas crear ha estat <strong>completada i finalitzada</strong> amb èxit.</p>
        
        <div class="solicitud-details">
            <h3>📋 Detalls de la Sol·licitud</h3>
            <p><strong>Identificador:</strong> {{ $solicitud->identificador_unic }}</p>
            <p><strong>Empleat/da:</strong> {{ $empleat->nom_complet }}</p>
            <p><strong>Departament:</strong> {{ $empleat->departament ? $empleat->departament->nom : 'No especificat' }}</p>
            <p><strong>Data de creació:</strong> {{ $solicitud->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Data de finalització:</strong> {{ $solicitud->data_finalitzacio ? $solicitud->data_finalitzacio->format('d/m/Y H:i') : 'Ara mateix' }}</p>
            
            @if($solicitud->sistemesSolicitats->count() > 0)
                <p><strong>Sistemes processats:</strong></p>
                <ul>
                    @foreach($solicitud->sistemesSolicitats as $sistemaSol)
                        <li>{{ $sistemaSol->sistema->nom }} ({{ $sistemaSol->nivellAcces->nom }})</li>
                    @endforeach
                </ul>
            @endif
        </div>
        
        <p>Tots els accessos sol·licitats han estat implementats correctament. L'empleat/da ja pot utilitzar els sistemes assignats.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/admin/solicituds-acces/{{ $solicitud->id }}" class="button">Veure Sol·licitud</a>
        </div>
        
        <p style="margin-top: 30px; font-size: 14px; color: #666;">
            Gràcies per utilitzar el Sistema de Gestió d'Accessos (SIAE).
        </p>
    </div>
    
    <div class="footer">
        <p>Aquest és un missatge automàtic, si us plau no responguis a aquest correu.</p>
        <p>© {{ date('Y') }} SIAE: Sistema de Gestió d'Accessos</p>
    </div>
</body>
</html>
