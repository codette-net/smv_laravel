<?php

namespace App\Support\Analytics;

use App\Models\OutboundApplyClick;
use App\Models\Vacancy;
use App\Models\VacancyDetailView;
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
        $viewScope = fn (Builder $query): Builder => $query
            ->whereBetween('viewed_at', [$filters->dateFrom, $filters->dateTo]);

        return Vacancy::query()
            ->withTrashed()
            ->with([
                'company' => fn ($query) => $query->withTrashed(),
                'importSource' => fn ($query) => $query->withTrashed(),
            ])
            ->where(fn (Builder $query): Builder => $query
                ->whereHas('outboundApplyClicks', $clickScope)
                ->orWhereHas('detailViews', $viewScope))
            ->withCount(['outboundApplyClicks as outbound_apply_clicks_count' => $clickScope])
            ->withCount(['detailViews as vacancy_detail_views_count' => $viewScope])
            ->when($filters->vacancyId, fn (Builder $query, int $id): Builder => $query->whereKey($id))
            ->when($filters->companyId, fn (Builder $query, int $id): Builder => $query->where('company_id', $id))
            ->when($filters->importSourceId, fn (Builder $query, int $id): Builder => $query->where('import_source_id', $id));
    }

    public function total(OutboundApplyClickFilters $filters): int
    {
        return $this->eventQuery($filters)->count();
    }

    public function totalViews(OutboundApplyClickFilters $filters): int
    {
        return $this->viewQuery($filters)->count();
    }

    public function rawCtr(int $clicks, int $views): ?float
    {
        return $views === 0 ? null : ($clicks / $views) * 100;
    }

    /** @return Collection<int, object{id: int, name: string, click_count: int, view_count: int}> */
    public function companies(OutboundApplyClickFilters $filters): Collection
    {
        return $this->aggregateQuery($filters)
            ->join('companies', 'companies.id', '=', 'vacancies.company_id')
            ->select(['companies.id', 'companies.name'])
            ->selectRaw('SUM(COALESCE(click_totals.click_count, 0)) as click_count')
            ->selectRaw('SUM(COALESCE(view_totals.view_count, 0)) as view_count')
            ->groupBy('companies.id', 'companies.name')
            ->orderByDesc('click_count')
            ->orderBy('companies.name')
            ->orderBy('companies.id')
            ->get();
    }

    /** @return Collection<int, object{id: ?int, name: ?string, click_count: int, view_count: int}> */
    public function importSources(OutboundApplyClickFilters $filters): Collection
    {
        return $this->aggregateQuery($filters)
            ->leftJoin('import_sources', 'import_sources.id', '=', 'vacancies.import_source_id')
            ->select(['import_sources.id', 'import_sources.name'])
            ->selectRaw('SUM(COALESCE(click_totals.click_count, 0)) as click_count')
            ->selectRaw('SUM(COALESCE(view_totals.view_count, 0)) as view_count')
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

    /** @return Builder<VacancyDetailView> */
    private function viewQuery(OutboundApplyClickFilters $filters): Builder
    {
        return VacancyDetailView::query()
            ->whereBetween('viewed_at', [$filters->dateFrom, $filters->dateTo])
            ->whereHas('vacancy', function (Builder $query) use ($filters): void {
                $query
                    ->when($filters->vacancyId, fn (Builder $query, int $id): Builder => $query->whereKey($id))
                    ->when($filters->companyId, fn (Builder $query, int $id): Builder => $query->where('company_id', $id))
                    ->when($filters->importSourceId, fn (Builder $query, int $id): Builder => $query->where('import_source_id', $id));
            });
    }

    private function aggregateQuery(OutboundApplyClickFilters $filters): QueryBuilder
    {
        $clickTotals = DB::table('outbound_apply_clicks')
            ->select('vacancy_id')
            ->selectRaw('COUNT(*) as click_count')
            ->whereBetween('clicked_at', [$filters->dateFrom, $filters->dateTo])
            ->groupBy('vacancy_id');
        $viewTotals = DB::table('vacancy_detail_views')
            ->select('vacancy_id')
            ->selectRaw('COUNT(*) as view_count')
            ->whereBetween('viewed_at', [$filters->dateFrom, $filters->dateTo])
            ->groupBy('vacancy_id');

        return DB::table('vacancies')
            ->leftJoinSub($clickTotals, 'click_totals', 'click_totals.vacancy_id', '=', 'vacancies.id')
            ->leftJoinSub($viewTotals, 'view_totals', 'view_totals.vacancy_id', '=', 'vacancies.id')
            ->where(fn (QueryBuilder $query): QueryBuilder => $query
                ->whereNotNull('click_totals.vacancy_id')
                ->orWhereNotNull('view_totals.vacancy_id'))
            ->when($filters->vacancyId, fn (QueryBuilder $query, int $id): QueryBuilder => $query->where('vacancies.id', $id))
            ->when($filters->companyId, fn (QueryBuilder $query, int $id): QueryBuilder => $query->where('vacancies.company_id', $id))
            ->when($filters->importSourceId, fn (QueryBuilder $query, int $id): QueryBuilder => $query->where('vacancies.import_source_id', $id));
    }
}
