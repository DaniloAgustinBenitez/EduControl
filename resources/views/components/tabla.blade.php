@props(['columnas' => [], 'acciones' => true])

{{-- Tabla reutilizable con DataTables (tarea 3.1). Uso: <x-tabla :columnas="['Nombre', 'Nivel']"> filas </x-tabla> --}}
<div class="card">
    <div class="card-body">
        <table {{ $attributes->merge(['class' => 'table table-striped table-hover align-middle datatable w-100']) }}>
            <thead>
                <tr>
                    @foreach ($columnas as $columna)
                        <th>{{ $columna }}</th>
                    @endforeach
                    @if ($acciones)
                        <th class="no-orden text-end">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
