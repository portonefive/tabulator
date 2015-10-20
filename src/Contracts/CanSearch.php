<?php

namespace PortOneFive\Tabulator\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface CanSearch
{
    public function processSearch(Builder $query, $searchQuery);

    public function getSearchParamName();
}