{{-- Datos personales + cuenta de acceso, compartido por profesores y tutores. Espera $persona. --}}
<div class="row">
    <div class="col-md-6"><x-campo nombre="nombre" etiqueta="Nombre" :valor="$persona->nombre" requerido maxlength="60" /></div>
    <div class="col-md-6"><x-campo nombre="apellido" etiqueta="Apellido" :valor="$persona->apellido" requerido maxlength="60" /></div>
    <div class="col-md-6"><x-campo nombre="dni" etiqueta="DNI" :valor="$persona->dni" requerido maxlength="15" inputmode="numeric" /></div>
    <div class="col-md-6"><x-campo nombre="telefono" etiqueta="Teléfono" :valor="$persona->telefono" maxlength="30" /></div>
</div>
<h2 class="h6 mt-2">Cuenta de acceso</h2>
<div class="row">
    <div class="col-md-6"><x-campo nombre="email" etiqueta="Correo electrónico" tipo="email" :valor="$persona->user?->email" requerido /></div>
    <div class="col-md-6">
        <x-campo nombre="password" :etiqueta="$persona->exists ? 'Nueva contraseña (dejar vacío para no cambiarla)' : 'Contraseña'"
            tipo="password" :requerido="! $persona->exists" minlength="8" autocomplete="new-password" />
    </div>
</div>
