@props(['filters', 'sort', 'sortOptions', 'locations', 'taxonomyOptions', 'companies', 'hasFilters' => false, 'hasAdditionalFilters' => false, 'secondaryFilterCount' => 0, 'filterErrors' => []])

<x-vacancy.filter-form
    :filters="$filters"
    :sort="$sort"
    :sort-options="$sortOptions"
    :locations="$locations"
    :taxonomy-options="$taxonomyOptions"
    :companies="$companies"
    :action="route('home')"
    :has-filters="$hasFilters"
    :has-additional-filters="$hasAdditionalFilters"
    :secondary-filter-count="$secondaryFilterCount"
    :filter-errors="$filterErrors"
/>
