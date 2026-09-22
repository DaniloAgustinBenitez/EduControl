<?php

namespace App\Services;

use App\Models\Horario;

/**
 * Detecta franjas horarias que se pisan (RN-04 entre deportes, RN-05 con el cursado).
 *
 * Una franja es un Horario o un array con las claves dia, hora_inicio y hora_fin.
 * Dos franjas consecutivas (una termina justo cuando empieza la otra) no se superponen.
 */
final class SuperposicionHoraria
{
    public static function seSuperponen(Horario|array $a, Horario|array $b): bool
    {
        if ((int) $a['dia'] !== (int) $b['dia']) {
            return false;
        }

        return self::minutos($a['hora_inicio']) < self::minutos($b['hora_fin'])
            && self::minutos($b['hora_inicio']) < self::minutos($a['hora_fin']);
    }

    /**
     * @return array<int, array{0: Horario|array, 1: Horario|array}> pares [nuevo, existente] que se superponen
     */
    public static function conflictos(iterable $nuevos, iterable $existentes): array
    {
        $existentes = is_array($existentes) ? $existentes : iterator_to_array($existentes, false);
        $conflictos = [];

        foreach ($nuevos as $nuevo) {
            foreach ($existentes as $existente) {
                if (self::seSuperponen($nuevo, $existente)) {
                    $conflictos[] = [$nuevo, $existente];
                }
            }
        }

        return $conflictos;
    }

    private static function minutos(string $hora): int
    {
        [$h, $m] = explode(':', $hora);

        return (int) $h * 60 + (int) $m;
    }
}
