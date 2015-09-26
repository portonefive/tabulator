<?php

namespace PortOneFive\Tabulator\DataHandlers;

use Illuminate\Contracts\Pagination\Paginator;
use PortOneFive\Tabulator\Contracts\DataHandler;

class PaginationHandler implements DataHandler
{
    protected $paginator;

    public function __construct(Paginator $paginator)
    {
        $this->paginator = $paginator;
    }

    public function rows()
    {
        return $this->paginator()->getCollection();
    }

    public function paginate($perPage = 12, $pageName = 'page', $page = null, array $options = [])
    {
    }

    public function groupBy($column, $labelColumn = null)
    {
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->paginator()->count();
    }

    /**
     * @return bool
     */
    public function isPaginated()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isGrouped()
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function groupLabelColumn()
    {
        // TODO: Implement groupLabelColumn() method.
    }

    /**
     * @return string|null
     */
    public function groupColumn()
    {
        // TODO: Implement groupColumn() method.
    }

    /**
     * @return Paginator
     */
    public function paginator()
    {
        return $this->paginator;
    }
}
