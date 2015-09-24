<?php

namespace PortOneFive\Tabulator\DataHandlers;

use Illuminate\Contracts\Pagination\Paginator;

class PaginationHandler implements DataHandler
{
    protected $paginator;

    public function __construct(Paginator $paginator)
    {
        $this->paginator = $paginator;
    }

    public function items()
    {
        // TODO: Implement rows() method.
    }

    public function paginate($perPage = 12, $pageName = 'page', $page = null)
    {
        // TODO: Implement paginate() method.
    }

    public function groupBy($column, $labelColumn = null)
    {
        // TODO: Implement group() method.
    }
}
