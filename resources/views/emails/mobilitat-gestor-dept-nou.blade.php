<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procés de Mobilitat - Revisió Departament Nou</title>
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
            background-color: #059669;
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
            border-left: 4px solid #10b981;
        }
        .button {
            display: inline-block;
            background-color: #059669;
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
            background-color: #d1fae5;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Procés de Mobilitat</h1>
        <p>Revisió Departament Nou</p>
    </div>

    <div class="content">
        <h2>Hola,</h2>
        
        <p>Un procés de mobilitat ha arribat a la fase de revisió del departament nou i requereix la teva atenció com a gestor/a.</p>

        <div class="info-box">
            <h3>📋 Detalls del Procés</h3>
            <p><strong>Identificador:</strong> {{ $processMobilitat->identificador_unic }}</p>
            <p><strong>Empleat/da:</strong> {{ $processMobilitat->empleat->nom_complet }}</p>
            <p><strong>Departament Anterior:</strong> {{ $processMobilitat->departamentActual->nom }}</p>
            <p><strong>Nou Departament:</strong> {{ $processMobilitat->departamentNou->nom }}</p>
            <p><strong>Sol·licitat per:</strong> {{ $processMobilitat->usuariSolicitant->name }}</p>
            <p><strong>Data Sol·licitud:</strong> {{ $processMobilitat->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div class="info-box">
            <h3>📝 Justificació</h3>
            <p>{{ $processMobilitat->justificacio }}</p>
        </div>

        <div class="info-box">
            <h3>✅ Acció Requerida</h3>
            <p>Com a gestor/a del nou departament, has de:</p>
            <ul>
                <li>Revisar els sistemes d'accés actuals de l'empleat/da</li>
                <li>Modificar els nivells d'accés dels sistemes existents si cal</li>
                <li>Afegir nous sistemes necessaris per les noves funcions</li>
                <li>Confirmar o revertir eliminacions marcades pel departament anterior</li>
            </ul>
            <p class="highlight">Estat actual: Pendent revisió departament nou</p>
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
