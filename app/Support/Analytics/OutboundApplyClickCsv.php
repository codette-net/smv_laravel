<?php

namespace App\Support\Analytics;

use App\Models\Vacancy;
use RuntimeException;

class OutboundApplyClickCsv
{
    public function __construct(private readonly OutboundApplyClickReport $report) {}

    public function generate(OutboundApplyClickFilters $filters): string
    {
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new RuntimeException('CSV-uitvoer kon niet worden geopend.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'vacancy_id',
            'source_reference',
            'vacancy_title',
            'company',
            'import_source',
            'vacancy_status',
            'raw_vacancy_detail_views',
            'raw_outbound_apply_clicks',
            'raw_outbound_apply_ctr_percent',
            'period_from_utc',
            'period_to_utc',
        ], ',', '"', '');

        $this->report->vacancies($filters)
            ->orderByDesc('outbound_apply_clicks_count')
            ->orderBy('title')
            ->orderBy('id')
            ->each(function (Vacancy $vacancy) use ($filters, $stream): void {
                fputcsv($stream, [
                    $vacancy->getKey(),
                    $vacancy->source_reference,
                    $vacancy->title,
                    $vacancy->company?->name,
                    $vacancy->importSource?->name ?? 'Geen importbron',
                    $vacancy->status->getLabel(),
                    $vacancy->vacancy_detail_views_count,
                    $vacancy->outbound_apply_clicks_count,
                    $this->formatCtr(
                        (int) $vacancy->outbound_apply_clicks_count,
                        (int) $vacancy->vacancy_detail_views_count,
                    ),
                    $filters->fromDate(),
                    $filters->toDate(),
                ], ',', '"', '');
            });

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new RuntimeException('CSV-uitvoer kon niet worden gelezen.');
        }

        return $csv;
    }

    private function formatCtr(int $clicks, int $views): string
    {
        $ctr = $this->report->rawCtr($clicks, $views);

        return $ctr === null ? 'N/A' : number_format($ctr, 2, '.', '');
    }
}
