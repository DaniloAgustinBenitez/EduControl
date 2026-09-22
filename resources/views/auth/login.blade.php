@extends('layouts.guest')

@section('titulo', 'Ingresar')

@section('contenido')
<div class="d-lg-none text-center mb-4">
    <span class="ec-logo mx-auto mb-2"><i class="bi bi-mortarboard-fill"></i></span>
    <h1 class="h4 mb-0">{{ config('app.name') }}</h1>
</div>

<h2 class="h4 mb-1">Ingresar</h2>
<p class="text-secondary mb-4">Usá la cuenta que te dio la institución.</p>

<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <div class="input-group">
            <span class="input-group-text bg-transparent"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="username" placeholder="nombre@educontrol.test">
        </div>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <div class="input-group">
            <span class="input-group-text bg-transparent"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
            <button class="btn btn-outline-secondary" type="button" id="verClave" aria-label="Mostrar u ocultar la contraseña"><i class="bi bi-eye"></i></button>
        </div>
    </div>
    <div class="form-check mb-4">
        <input type="checkbox" name="recordarme" id="recordarme" class="form-check-input">
        <label for="recordarme" class="form-check-label">Mantener la sesión iniciada</label>
    </div>
    <button class="btn btn-primary w-100 py-2">Ingresar</button>
</form>

<p class="text-secondary small mt-4 mb-0">
    ¿Olvidaste tu contraseña? Comunicate con la administración de la escuela.
</p>

<script>
    document.getElementById('verClave').addEventListener('click', e => {
        const campo = document.getElementById('password');
        campo.type = campo.type === 'password' ? 'text' : 'password';
        e.currentTarget.innerHTML = `<i class="bi bi-eye${campo.type === 'password' ? '' : '-slash'}"></i>`;
    });
</script>
@endsection
