<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0d6efd; color: #fff; text-align: left; }
        th, td { padding: 4px 5px; border-bottom: 1px solid #ddd; vertical-align: top; }
        tr:nth-child(even) td { background: #f5f7fa; }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    <div class="meta">{{ config('app.name') }} · {{ $filas->count() }} registros · generado el {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead><tr>@foreach ($columnas as $columna)<th>{{ $columna }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>@foreach ($fila as $celda)<td>{{ $celda }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($columnas) }}">Sin datos para los filtros elegidos.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
