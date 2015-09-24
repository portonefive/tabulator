<?php

namespace PortOneFive\Tabulator\DataHandlers;

use Illuminate\Support\Collection;

class CollectionHandler implements DataHandler
{
    protected $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
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
