<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nova sol·licitud d'accés per validar</title>
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
            background-color: #005b96;
            color: white;
            padding: 15px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
            color: #777;
        }
        .button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .info-box {
            background-color: #e9f5fe;
            border-left: 4px solid #005b96;
            padding: 10px 15px;
            margin: 15px 0;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Nova sol·licitud d'accés per validar</h2>
    </div>
    
    <div class="content">
        <p>Hola {{ $validacio->validador->name }},</p>
        
        <p>Has rebut una nova sol·licitud d'accés que requereix la teva validació:</p>
        
        <div class="info-box">
            <p><strong>Empleat:</strong> {{ $empleat->nom_complet }}</p>
            <p><strong>Sistema:</strong> {{ $sistema->nom }}</p>
            <p><strong>Sol·licitud:</strong> {{ $solicitud->identificador_unic }}</p>
            <p><strong>Justificació:</strong> {{ $solicitud->justificacio }}</p>
        </div>
        
        @if($esGrup)
        <div class="info-box warning">
            <p><strong>Nota:</strong> Aquesta sol·licitud s'ha enviat a tots els gestors del teu departament. Qualsevol gestor pot validar-la.</p>
        </div>
        @endif
        
        <p>Si us plau, revisa i valida aquesta sol·licitud el més aviat possible per no retardar el procés d'accés.</p>
        
        <center>
            <a href="{{ $url }}" class="button">Revisar sol·licitud</a>
        </center>
        
        <p>Si tens algun dubte, contacta amb l'equip de suport.</p>
        
        <p>Gràcies,<br>
        SIAE: Sistema de Gestió d'Accessos</p>
    </div>
    
    <div class="footer">
        <p>Aquest és un correu automàtic, si us plau no responguis directament.</p>
    </div>
</body>
</html>
