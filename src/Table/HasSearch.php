<?php

namespace PortOneFive\Tabulator\Table;

use Illuminate\Database\Eloquent\Builder;

trait HasSearch
{
    protected $searchParamName;

    /**
     * @param Builder $query
     * @param string $searchQuery
     */
    public function processSearch(Builder $query, $searchQuery)
    {
        $query->where(
            function ($builder) use ($searchQuery) {

                if ($this->request()->has($this->getSearchParamName())) {
                    $this->searchAll($builder, $this->request()->get($this->getSearchParamName()));

                    return;
                }

//                foreach ($searchData as $column => $data) {
//                    if ($this->hasColumnSearch($column)) {
//                        $this->searchColumn($column, $builder, $data);
//                    }
//                }
            }
        );
    }

    public function getSearchParamName()
    {
        return isset($this->searchParamName) ? $this->searchParamName : 'q';
    }

    public function hasColumnSearch($column)
    {
        return method_exists($this, $this->makeSearchMethodName($column));
    }

    public function searchColumn($column, Builder $builder, $searchQuery)
    {
        return call_user_func_array([$this, $this->makeSearchMethodName($column)], [$builder, $searchQuery]);
    }

    public function searchAll($builder, $searchQuery)
    {
        foreach ($this->columns() as $columnId => $label) {
            if ($this->hasColumnSearch($columnId)) {
                $this->searchColumn($columnId, $builder, $searchQuery);
            }
        }
    }

    protected function makeSearchMethodName($column)
    {
        return 'search' . str_replace(['.', '_'], '', title_case($column));
    }

    protected function request()
    {
        return app('request');
    }
}
