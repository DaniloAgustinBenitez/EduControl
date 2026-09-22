<?php

namespace Tests\Unit;

use App\Services\SuperposicionHoraria;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SuperposicionHorariaTest extends TestCase
{
    public static function casos(): array
    {
        return [
            'mismo día, se pisan' => [[1, '14:00', '15:30'], [1, '15:00', '16:30'], true],
            'mismo día, uno dentro del otro' => [[1, '08:00', '12:00'], [1, '09:00', '10:00'], true],
            'mismo día, idénticos' => [[2, '14:00', '15:00'], [2, '14:00', '15:00'], true],
            'mismo día, consecutivos (uno termina cuando empieza el otro)' => [[1, '14:00', '15:00'], [1, '15:00', '16:00'], false],
            'mismo día, separados' => [[1, '08:00', '12:00'], [1, '14:00', '15:30'], false],
            'distinto día, mismo horario' => [[1, '14:00', '15:30'], [2, '14:00', '15:30'], false],
            'orden invertido' => [[3, '15:00', '16:30'], [3, '14:00', '15:30'], true],
            'formato con segundos' => [[4, '14:00:00', '15:30:00'], [4, '15:29', '16:00'], true],
        ];
    }

    #[DataProvider('casos')]
    public function test_detecta_superposicion(array $a, array $b, bool $esperado): void
    {
        $this->assertSame($esperado, SuperposicionHoraria::seSuperponen($this->franja($a), $this->franja($b)));
    }

    public function test_devuelve_los_pares_en_conflicto(): void
    {
        $nuevos = [$this->franja([1, '15:00', '16:30']), $this->franja([3, '15:00', '16:30'])];
        $existentes = [$this->franja([1, '14:00', '15:30']), $this->franja([5, '14:00', '15:00'])];

        $conflictos = SuperposicionHoraria::conflictos($nuevos, $existentes);

        $this->assertCount(1, $conflictos);
        $this->assertSame(1, $conflictos[0][0]['dia']);
    }

    public function test_sin_existentes_no_hay_conflictos(): void
    {
        $this->assertSame([], SuperposicionHoraria::conflictos([$this->franja([1, '14:00', '15:00'])], []));
    }

    private function franja(array $datos): array
    {
        return ['dia' => $datos[0], 'hora_inicio' => $datos[1], 'hora_fin' => $datos[2]];
    }
}
