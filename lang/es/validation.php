<?php

/**
 * RNF-05: los errores se informan en lenguaje claro y en español.
 * Solo se traducen las reglas que usa el sistema; el resto cae en el inglés de Laravel.
 */
return [
    'accepted' => 'Tenés que aceptar :attribute.',
    'after' => ':Attribute debe ser posterior a :date.',
    'after_or_equal' => ':Attribute debe ser igual o posterior a :date.',
    'alpha' => ':Attribute solo puede contener letras.',
    'alpha_num' => ':Attribute solo puede contener letras y números.',
    'array' => ':Attribute debe ser una lista de valores.',
    'before' => ':Attribute debe ser anterior a :date.',
    'before_or_equal' => ':Attribute debe ser igual o anterior a :date.',
    'between' => [
        'array' => ':Attribute debe tener entre :min y :max elementos.',
        'file' => ':Attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => ':Attribute debe estar entre :min y :max.',
        'string' => ':Attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => ':Attribute solo puede ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña es incorrecta.',
    'date' => ':Attribute no es una fecha válida.',
    'date_equals' => ':Attribute debe ser una fecha igual a :date.',
    'date_format' => ':Attribute no coincide con el formato :format.',
    'different' => ':Attribute y :other deben ser distintos.',
    'digits' => ':Attribute debe tener :digits dígitos.',
    'digits_between' => ':Attribute debe tener entre :min y :max dígitos.',
    'email' => ':Attribute debe ser una dirección de correo válida.',
    'exists' => 'El valor elegido en :attribute no existe.',
    'filled' => 'Falta completar :attribute.',
    'gt' => [
        'numeric' => ':Attribute debe ser mayor que :value.',
        'string' => ':Attribute debe tener más de :value caracteres.',
    ],
    'gte' => [
        'numeric' => ':Attribute debe ser mayor o igual que :value.',
        'string' => ':Attribute debe tener :value caracteres o más.',
    ],
    'image' => ':Attribute debe ser una imagen.',
    'in' => 'El valor elegido en :attribute no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'lt' => [
        'numeric' => ':Attribute debe ser menor que :value.',
        'string' => ':Attribute debe tener menos de :value caracteres.',
    ],
    'lte' => [
        'numeric' => ':Attribute debe ser menor o igual que :value.',
        'string' => ':Attribute debe tener :value caracteres o menos.',
    ],
    'max' => [
        'array' => ':Attribute no puede tener más de :max elementos.',
        'file' => ':Attribute no puede pesar más de :max kilobytes.',
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'array' => ':Attribute debe tener al menos :min elementos.',
        'file' => ':Attribute debe pesar al menos :min kilobytes.',
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => ':Attribute debe ser un número.',
    'present' => 'Falta enviar :attribute.',
    'prohibited' => 'No se permite :attribute.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'Falta completar :attribute.',
    'required_if' => 'Falta completar :attribute cuando :other es :value.',
    'required_with' => 'Falta completar :attribute cuando se completa :values.',
    'same' => ':Attribute y :other deben coincidir.',
    'size' => [
        'array' => ':Attribute debe contener :size elementos.',
        'numeric' => ':Attribute debe ser :size.',
        'string' => ':Attribute debe tener :size caracteres.',
    ],
    'string' => ':Attribute debe ser texto.',
    'unique' => ':Attribute ya está en uso.',
    'uploaded' => 'No se pudo subir :attribute.',
    'url' => ':Attribute debe ser una dirección web válida.',

    /*
    |--------------------------------------------------------------------------
    | Nombres de los campos
    |--------------------------------------------------------------------------
    |
    | Cómo se nombra cada campo dentro del mensaje, para que el error se lea
    | como lo escribiría una persona: "El DNI ya está registrado".
    |
    */

    'attributes' => [
        'activo' => 'el estado',
        'alumnos' => 'los alumnos a cargo',
        'anio' => 'el año',
        'apellido' => 'el apellido',
        'ciclo' => 'el ciclo lectivo',
        'ciclo_lectivo_id' => 'el ciclo lectivo',
        'curso_id' => 'el curso',
        'deporte_id' => 'el deporte',
        'descripcion' => 'la descripción',
        'dia' => 'el día',
        'direccion' => 'el domicilio',
        'dni' => 'el DNI',
        'email' => 'el correo electrónico',
        'fecha_nacimiento' => 'la fecha de nacimiento',
        'filtro' => 'el filtro',
        'formato' => 'el formato',
        'hora_fin' => 'la hora de fin',
        'hora_inicio' => 'la hora de inicio',
        'legajo' => 'el legajo',
        'materia_id' => 'la materia',
        'materias' => 'las materias',
        'nivel_id' => 'el nivel',
        'niveles' => 'los niveles',
        'nombre' => 'el nombre',
        'parentesco' => 'el parentesco',
        'password' => 'la contraseña',
        'recorrido_id' => 'el recorrido',
        'telefono' => 'el teléfono',
        'cursos' => 'los cursos',
    ],
];
