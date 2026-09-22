<form method="POST" action="{{ route('autogestion.baja', [$alumno, $servicio, $inscripcion->id]) }}" class="d-inline" data-confirmar="¿Confirmás la baja de esta inscripción?">
    @csrf @method('DELETE')
    <button class="btn btn-outline-danger btn-sm">{{ $texto }}</button>
</form>
