<?php

namespace App\Services;

use App\Models\ActivityLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Monta os arquivos de exportação (Excel e PDF) do Histórico de Atividades,
 * a partir dos mesmos registros já filtrados na tela.
 */
class ActivityLogExportService
{
    public const HEADERS = ['Ação', 'Descrição', 'Tipo', 'Usuário', 'Data/Hora'];

    /**
     * @return array<int, array<int, string>>
     */
    public function rows(Collection $logs): array
    {
        return $logs->map(fn (ActivityLog $log) => [
            $log->action_label,
            $log->description,
            $log->subject_label,
            $log->user?->name ?? 'Sistema',
            $log->created_at->format('d/m/Y H:i:s'),
        ])->all();
    }

    public function toXlsx(Collection $logs): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->fromArray($this->rows($logs), null, 'A2');

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');

        return ob_get_clean();
    }

    public function toPdf(Collection $logs): string
    {
        return Pdf::loadView('exports.activity-log-pdf', [
            'headers' => self::HEADERS,
            'rows' => $this->rows($logs),
        ])->output();
    }
}
