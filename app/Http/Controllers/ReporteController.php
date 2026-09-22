<?php

namespace App\Http\Controllers;

use App\Models\CicloLectivo;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Etapa 6 — Reportes consolidados (HU9) con exportación a PDF y planilla (RF-19). */
class ReporteController extends Controller
{
    public const TIPOS = [
        'alumno' => ['Por alumno', 'Servicios contratados por cada alumno', 'bi-person'],
        'docente' => ['Por docente', 'Niveles, cursos y materias de cada docente', 'bi-person-badge'],
        'curso' => ['Por curso', 'Alumnos de cada curso y sus servicios', 'bi-people'],
        'materia' => ['Por materia', 'Materias con docentes y cantidad de alumnos', 'bi-journal-text'],
        'deporte' => ['Por deporte', 'Inscriptos en cada deporte', 'bi-trophy'],
        'recorrido' => ['Por recorrido', 'Alumnos por recorrido de transporte', 'bi-bus-front'],
    ];

    public const MAX_FILAS_PDF = 500;

    public function __construct(private ReporteService $reportes) {}

    public function index()
    {
        return view('reportes.index', ['tipos' => self::TIPOS]);
    }

    public function show(Request $request, string $tipo)
    {
        $datos = $request->validate([
            'ciclo' => ['nullable', 'exists:ciclos_lectivos,id'],
            'filtro' => ['nullable', 'integer'],
            'formato' => ['nullable', 'in:pdf,xlsx'],
        ]);

        $ciclo = isset($datos['ciclo']) ? CicloLectivo::find($datos['ciclo']) : (CicloLectivo::actual() ?? CicloLectivo::orderByDesc('anio')->first());
        abort_unless($ciclo, 404, 'No hay ciclos lectivos cargados.');

        $reporte = $this->reportes->generar($tipo, $ciclo, $datos['filtro'] ?? null);

        return match ($datos['formato'] ?? null) {
            'pdf' => $this->pdf($reporte),
            'xlsx' => $this->planilla($reporte),
            default => view('reportes.show', [
                'tipo' => $tipo,
                'reporte' => $reporte,
                'ciclo' => $ciclo,
                'ciclos' => CicloLectivo::orderByDesc('anio')->get(),
                'filtro' => $this->reportes->opcionesFiltro($tipo),
            ]),
        };
    }

    private function pdf(array $reporte)
    {
        // DomPDF arma todo el documento en memoria: con miles de filas conviene filtrar o usar la planilla.
        if ($reporte['filas']->count() > self::MAX_FILAS_PDF) {
            return back()->with('error', 'El reporte tiene '.$reporte['filas']->count().' filas y el PDF admite hasta '
                .self::MAX_FILAS_PDF.'. Aplicá un filtro o exportalo a Excel.');
        }

        return Pdf::loadView('reportes.pdf', $reporte)
            ->setPaper('a4', count($reporte['columnas']) > 5 ? 'landscape' : 'portrait')
            ->download($this->archivo($reporte, 'pdf'));
    }

    private function planilla(array $reporte)
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Reporte');
        $hoja->setCellValue('A1', $reporte['titulo']);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $hoja->fromArray($reporte['columnas'], null, 'A3');
        $hoja->fromArray($reporte['filas']->all(), null, 'A4');

        $ultima = $hoja->getHighestColumn();
        $hoja->getStyle("A3:{$ultima}3")->getFont()->setBold(true);
        $hoja->setAutoFilter("A3:{$ultima}".max(3, $hoja->getHighestRow()));
        foreach (range(1, count($reporte['columnas'])) as $columna) {
            $hoja->getColumnDimensionByColumn($columna)->setAutoSize(true);
        }

        return response()->streamDownload(fn () => (new Xlsx($libro))->save('php://output'), $this->archivo($reporte, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function archivo(array $reporte, string $extension): string
    {
        return Str::slug($reporte['titulo']).'-'.now()->format('Ymd-His').'.'.$extension;
    }
}
