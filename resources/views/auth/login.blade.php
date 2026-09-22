@extends('layouts.app')

@section('titulo', 'Ingresar')

@section('contenido')
<div class="row justify-content-center mt-5">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="text-center mb-4">
            <i class="bi bi-mortarboard text-primary" style="font-size: 3rem"></i>
            <h1 class="h4 mt-2">{{ config('app.name') }}</h1>
            <p class="text-muted">Ingresá con tu cuenta institucional</p>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="recordarme" id="recordarme" class="form-check-input">
                        <label for="recordarme" class="form-check-label">Recordarme</label>
                    </div>
                    <button class="btn btn-primary w-100">Ingresar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
