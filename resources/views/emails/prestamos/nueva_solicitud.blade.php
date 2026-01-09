<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva solicitud de préstamo</title>
</head>
<body>
    <h2>Nueva solicitud de préstamo pendiente</h2>

    <p><strong>Cliente:</strong>
        {{ $cliente->nombre ?? $cliente->full_name ?? $cliente->name ?? ('Cliente #'.$cliente->id) }}
    </p>
    <p><strong>Email del cliente:</strong> {{ $cliente->email ?? 'No registrado' }}</p>

    <hr>

    <p><strong>ID préstamo:</strong> {{ $prestamo->id }}</p>
    <p><strong>Monto solicitado:</strong> ${{ number_format($prestamo->cantidad, 2) }}</p>
    <p><strong>Interés:</strong> {{ $prestamo->interes }} %</p>

    @if($prestamo->semanas)
        <p><strong>Semanas:</strong> {{ $prestamo->semanas }}</p>
    @endif

    @if($prestamo->tipo_prestamo)
        <p><strong>Tipo / periodo del préstamo:</strong> {{ $prestamo->tipo_prestamo }}</p>
    @endif

    <p><strong>Fecha de solicitud:</strong> {{ $prestamo->fecha_solicitud }}</p>

    @if($prestamo->caja)
        <p><strong>Caja:</strong> {{ $prestamo->caja->nombre }} (ID {{ $prestamo->caja->id_caja }})</p>
    @endif

    @if(!empty($prestamo->codigo_aval))
        <p><strong>Código de aval usado:</strong> {{ $prestamo->codigo_aval }}</p>
    @endif

    @if(!empty($docsUrls))
        <h3>Documentos del aval</h3>
        <ul>
            @if(!empty($docsUrls['solicitud']))
                <li>
                    Solicitud de aval:
                    <a href="{{ $docsUrls['solicitud'] }}" target="_blank" rel="noopener noreferrer">Ver archivo</a>
                </li>
            @endif
            @if(!empty($docsUrls['domicilio']))
                <li>
                    Comprobante de domicilio:
                    <a href="{{ $docsUrls['domicilio'] }}" target="_blank" rel="noopener noreferrer">Ver archivo</a>
                </li>
            @endif
            @if(!empty($docsUrls['ine_frente']))
                <li>
                    INE frente:
                    <a href="{{ $docsUrls['ine_frente'] }}" target="_blank" rel="noopener noreferrer">Ver archivo</a>
                </li>
            @endif
            @if(!empty($docsUrls['ine_reverso']))
                <li>
                    INE reverso:
                    <a href="{{ $docsUrls['ine_reverso'] }}" target="_blank" rel="noopener noreferrer">Ver archivo</a>
                </li>
            @endif
        </ul>
    @endif

    <hr>

    <p>
        La solicitud se creó en estado <strong>PENDIENTE</strong>.<br>
        Revisa y procesa el préstamo desde el panel de administración de la Caja Growcap.
    </p>

    <p style="font-size: 12px; color: #666;">
        Correo generado automáticamente por el sistema Growcap.
    </p>
</body>
</html>
