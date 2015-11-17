<?php

namespace PortOneFive\Tabulator;

use PortOneFive\Tabulator\Contracts\TableSchema;

class DefaultSchema implements TableSchema
{
    /**
     * @return array
     */
    public function columns()
    {
        return [];
    }
}
