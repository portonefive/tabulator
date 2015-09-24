<?php

namespace PortOneFive\Tabulator\DataHandlers;

use Illuminate\Database\Eloquent\Builder;
use PortOneFive\Tabulator\Row;

class QueryBuilderHandler implements DataHandler
{

    /**
     * @var Builder
     */
    private $builder;

    private $groupBy;
    private $paginated = false;

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
    public function items()
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
     * @return void
     */
    public function paginate($perPage = 12, $pageName = 'page', $page = null)
    {
        $this->paginated = true;
        $this->builder->paginate($perPage, ['*'], $pageName, $page);
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
        return $this->isGrouped() ? (isset($this->groupBy[1]) ? $this->groupBy[1] : $this->groupBy[0]) : null;
    }

    /**
     */
    public function rowsGrouped()
    {
        if ($this->isGrouped()) {
            return $this->items()->groupBy(
                function ($row) {
                    return object_get($row, $this->groupBy[0]);
                }
            );
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isPaginated()
    {
        return $this->paginated;
    }

    /**
     * @return string|null
     */
    public function groupColumn()
    {
        return $this->groupBy[0];
    }
}
