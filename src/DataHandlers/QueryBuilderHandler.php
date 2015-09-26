<?php

namespace PortOneFive\Tabulator\DataHandlers;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use PortOneFive\Tabulator\Contracts\DataHandler;
use PortOneFive\Tabulator\Row;

class QueryBuilderHandler implements DataHandler
{
    /**
     * @var Paginator
     */
    protected $paginator;

    /**
     * @var Builder
     */
    private $builder;

    private $groupBy;

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function rows()
    {
        return $this->builder->get()->map(
            function ($row) {
                return new Row($row);
            }
        );
    }

    /**
     * @param int    $perPage
     * @param string $pageName
     * @param null   $page
     *
     * @param array  $options
     *
     * @return $this
     */
    public function paginate($perPage = 12, $pageName = 'page', $page = null, array $options = [])
    {
        $this->paginator = $this->builder->paginate($perPage, ['*'], $pageName, $page);

        return $this;
    }

    /**
     * @param      $column
     * @param null $labelColumn
     *
     * @return void
     */
    public function groupBy($column, $labelColumn = null)
    {
        $this->builder->orderBy($column);

        $this->groupBy = [$column, $labelColumn];
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->builder->count();
    }

    public function isGrouped()
    {
        return ! empty($this->groupBy);
    }

    /**
     * @return string|null
     */
    public function groupLabelColumn()
    {
        return $this->isGrouped() ? (! empty($this->groupBy[1]) ? $this->groupBy[1] : $this->groupBy[0]) : null;
    }

    /**
     * @return bool
     */
    public function isPaginated()
    {
        return isset($this->paginator);
    }

    /**
     * @return string|null
     */
    public function groupColumn()
    {
        return $this->groupBy[0];
    }

    /**
     * @return Paginator
     */
    public function paginator()
    {
        return $this->paginator;
    }
}
