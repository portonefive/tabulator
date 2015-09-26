<?php

namespace PortOneFive\Tabulator\DataHandlers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use PortOneFive\Tabulator\Contracts\DataHandler;
use PortOneFive\Tabulator\GroupedCollection;

class CollectionHandler implements DataHandler
{
    protected $collection;

    protected $paginator = null;
    protected $groupBy   = [];

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function rows()
    {
        return $this->collection;
    }

    /**
     * @param int    $perPage
     * @param string $pageName
     * @param null   $page
     * @param array  $options
     *
     * @return LengthAwarePaginator
     */
    public function paginate($perPage = 12, $pageName = 'page', $page = null, array $options = [])
    {
        $total = $this->count();

        $items = $this->collection->forPage($page = $page ?: Paginator::resolveCurrentPage($pageName), $perPage);

        return $this->paginator = new LengthAwarePaginator($items, $total, $perPage, $page, $options);
    }

    public function groupBy($column, $labelColumn = null)
    {
        if ($this->isPaginated()) {
            throw new \Exception('Collection must be grouped prior to pagination.');
        }

        $this->groupBy = [$column, $labelColumn];

        $this->collection = new GroupedCollection($this->collection->groupBy($this->groupColumn()));
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->isPaginated() ? $this->paginator()->count() : $this->collection->count();
    }

    /**
     * @return bool
     */
    public function isPaginated()
    {
        return ! empty($this->paginator);
    }

    /**
     * @return bool
     */
    public function isGrouped()
    {
        return !empty($this->groupBy);
    }

    /**
     * @return string|null
     */
    public function groupLabelColumn()
    {
        return $this->isGrouped() ? (! empty($this->groupBy[1]) ? $this->groupBy[1] : $this->groupBy[0]) : null;
    }

    /**
     * @return string|null
     */
    public function groupColumn()
    {
        return $this->isGrouped() ? $this->groupBy[0] : null;
    }

    /**
     * @return Paginator
     */
    public function paginator()
    {
        return $this->isPaginated() ? $this->paginator : null;
    }
}
