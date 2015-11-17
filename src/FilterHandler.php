<?php

namespace PortOneFive\Tabulator;

use Illuminate\Http\Request;
use PortOneFive\Tabulator\Contracts\DataHandler;
use PortOneFive\Tabulator\Contracts\FilterDefinition;
use PortOneFive\Tabulator\Contracts\SearchDefinition;
use ReflectionClass;
use ReflectionMethod;

class FilterHandler
{

    /**
     * @var SearchDefinition
     */
    protected $definition;

    /**
     * @var DataHandler
     */
    private $dataHandler;

    /**
     * @var array
     */
    private $options;

    /**
     * @param DataHandler $dataHandler
     * @param array       $options
     */
    public function __construct(DataHandler $dataHandler, array $options = [])
    {
        $this->dataHandler = $dataHandler;
        $this->options     = array_merge(
            [
                'searchName' => 'q'
            ],
            $options
        );
    }

    public function handle(array $searchData)
    {
        $this->dataHandler->query()->where(
            function ($builder) use ($searchData) {
                foreach ($searchData as $column => $data) {
                    if ($this->hasColumnFilter($column)) {
                        $this->filterColumn($column, $builder, $data);
                    }
                }
            }
        );
    }

    public function getFilterCookieName()
    {
        return $this->options['searchName'];
    }

    public function hasColumnFilter($column)
    {
        return method_exists($this->definition(), $this->makeFilterMethodName($column));
    }

    public function filterColumn($column, $builder, $data)
    {
        return call_user_func_array([$this->definition(), $this->makeFilterMethodName($column)], [$builder, $data]);
    }

    public function setDefinition(FilterDefinition $definition)
    {
        $this->definition = $definition;
    }

    public function definition()
    {
        return $this->definition;
    }

    /**
     * @return Request
     */
    public function request()
    {
        return app('request');
    }

    /**
     * @return DataHandler
     */
    public function dataHandler()
    {
        return $this->dataHandler;
    }

    private function makeFilterMethodName($column)
    {
        return 'filter' . str_replace('_', '', title_case($column));
    }
}