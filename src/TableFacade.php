<?php namespace PortOneFive\Tabulator;

use Illuminate\Support\Facades\Facade;

/**
 * @see \PortOneFive\Tabulator\TableBuilder
 */
class TableFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'table'; }

}