<?php

namespace App\Support\Analytics;

use App\Models\OutboundApplyClick;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OutboundApplyClickReport
{
    /** @return Builder<Vacancy> */
    public function vacancies(OutboundApplyClickFilters $filters): Builder
    {
        $clickScope = fn (Builder $query): Builder => $query
            ->whereBetween('clicked_at', [$filters->dateFrom, $filters->dateTo]);

        return Vacancy::query()
            ->withTrashed()
            ->with([
                'company' => fn ($query) => $query->withTrashed(),
                'importSource' => fn ($query) => $query->withTrashed(),
            ])
            ->whereHas('outboundApplyClicks', $clickScope)
            ->withCount(['outboundApplyClicks as outbound_apply_clicks_count' => $clickScope])
            ->when($filters->vacancyId, fn (Builder $query, int $id): Builder => $query->whereKey($id))
            ->when($filters->companyId, fn (Builder $query, int $id): Builder => $query->where('company_id', $id))
            ->when($filters->importSourceId, fn (Builder $query, int $id): Builder => $query->where('import_source_id', $id));
    }

    public function total(OutboundApplyClickFilters $filters): int
    {
        return $this->eventQuery($filters)->count();
    }

    /** @return Collection<int, object{id: int, name: string, click_count: int}> */
    public function companies(OutboundApplyClickFilters $filters): Collection
    {
        return $this->aggregateQuery($filters)
            ->join('companies', 'companies.id', '=', 'vacancies.company_id')
            ->select(['companies.id', 'companies.name'])
            ->selectRaw('COUNT(outbound_apply_clicks.id) as click_count')
            ->groupBy('companies.id', 'companies.name')
            ->orderByDesc('click_count')
            ->orderBy('companies.name')
            ->orderBy('companies.id')
            ->get();
    }

    /** @return Collection<int, object{id: ?int, name: ?string, click_count: int}> */
    public function importSources(OutboundApplyClickFilters $filters): Collection
    {
        return $this->aggregateQuery($filters)
            ->leftJoin('import_sources', 'import_sources.id', '=', 'vacancies.import_source_id')
            ->select(['import_sources.id', 'import_sources.name'])
            ->selectRaw('COUNT(outbound_apply_clicks.id) as click_count')
            ->groupBy('import_sources.id', 'import_sources.name')
            ->orderByDesc('click_count')
            ->orderBy('import_sources.name')
            ->orderBy('import_sources.id')
            ->get();
    }

    /** @return Builder<OutboundApplyClick> */
    private function eventQuery(OutboundApplyClickFilters $filters): Builder
    {
        return OutboundApplyClick::query()
            ->whereBetween('clicked_at', [$filters->dateFrom, $filters->dateTo])
            ->whereHas('vacancy', function (Builder $query) use ($filters): void {
                $query
                    ->when($filters->vacancyId, fn (Builder $query, int $id): Builder => $query->whereKey($id))
                    ->when($filters->companyId, fn (Builder $query, int $id): Builder => $query->where('company_id', $id))
                    ->when($filters->importSourceId, fn (Builder $query, int $id): Builder => $query->where('import_source_id', $id));
            });
    }

    private function aggregateQuery(OutboundApplyClickFilters $filters): QueryBuilder
    {
        return DB::table('outbound_apply_clicks')
            ->join('vacancies', 'vacancies.id', '=', 'outbound_apply_clicks.vacancy_id')
            ->whereBetween('outbound_apply_clicks.clicked_at', [$filters->dateFrom, $filters->dateTo])
            ->when($filters->vacancyId, fn (QueryBuilder $query, int $id): QueryBuilder => $query->where('vacancies.id', $id))
            ->when($filters->companyId, fn (QueryBuilder $query, int $id): QueryBuilder => $query->where('vacancies.company_id', $id))
            ->when($filters->importSourceId, fn (QueryBuilder $query, int $id): QueryBuilder => $query->where('vacancies.import_source_id', $id));
    }
}
