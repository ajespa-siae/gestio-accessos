<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procés de Mobilitat - Revisió Departament Actual</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border: 1px solid #e2e8f0;
        }
        .info-box {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            background-color: #64748b;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Procés de Mobilitat</h1>
        <p>Revisió Departament Actual</p>
    </div>

    <div class="content">
        <h2>Hola,</h2>
        
        <p>S'ha iniciat un procés de mobilitat que requereix la teva revisió com a gestor/a del departament actual.</p>

        <div class="info-box">
            <h3>📋 Detalls del Procés</h3>
            <p><strong>Identificador:</strong> {{ $processMobilitat->identificador_unic }}</p>
            <p><strong>Empleat/da:</strong> {{ $processMobilitat->empleat->nom_complet }}</p>
            <p><strong>Departament Actual:</strong> {{ $processMobilitat->departamentActual->nom }}</p>
            <p><strong>Nou Departament:</strong> {{ $processMobilitat->departamentNou->nom }}</p>
            <p><strong>Sol·licitat per:</strong> {{ $processMobilitat->usuariSolicitant->name }}</p>
            <p><strong>Data Sol·licitud:</strong> {{ $processMobilitat->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div class="info-box">
            <h3>📝 Justificació</h3>
            <p>{{ $processMobilitat->justificacio }}</p>
        </div>

        <div class="info-box">
            <h3>⚠️ Acció Requerida</h3>
            <p>Com a gestor/a del departament actual, has de revisar els sistemes d'accés de l'empleat/da i marcar aquells que s'han d'eliminar.</p>
            <p class="highlight">Estat actual: Pendent revisió departament actual</p>
        </div>

        <p>Pots accedir al procés de mobilitat fent clic al següent enllaç:</p>
        
        <a href="{{ config('app.url') }}/operatiu/process-mobilitats/{{ $processMobilitat->id }}" class="button">
            Revisar Procés de Mobilitat
        </a>

        <p>Si tens qualsevol dubte, pots contactar amb RRHH o l'administrador del sistema.</p>

        <p>Gràcies per la teva col·laboració.</p>
    </div>

    <div class="footer">
        <p>Aquest email ha estat enviat automàticament pel sistema de Gestió d'Accessos.</p>
        <p>Si us plau, no responguis a aquest email.</p>
    </div>
</body>
</html>
