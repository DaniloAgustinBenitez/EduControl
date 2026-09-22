@props(['nombre', 'etiqueta', 'valor' => null, 'tipo' => 'text', 'requerido' => false])

<div class="mb-3">
    <label for="{{ $nombre }}" class="form-label">{{ $etiqueta }}@if ($requerido) <span class="text-danger">*</span>@endif</label>
    <input type="{{ $tipo }}" name="{{ $nombre }}" id="{{ $nombre }}" value="{{ old($nombre, $valor) }}"
        {{ $attributes->merge(['class' => 'form-control'.($errors->has($nombre) ? ' is-invalid' : '')]) }} @required($requerido)>
    @error($nombre)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
