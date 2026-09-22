@props(['editar', 'eliminar' => null, 'mensaje' => '¿Seguro que querés eliminar este registro?'])

<td class="text-end text-nowrap">
    {{ $slot }}
    <a href="{{ $editar }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
    @if ($eliminar)
        <form method="POST" action="{{ $eliminar }}" class="d-inline" data-confirmar="{{ $mensaje }}">
            @csrf
            @method('DELETE')
            <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
        </form>
    @endif
</td>
