<?php

namespace PortOneFive\Tabulator;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use PortOneFive\Tabulator\Contracts\CanFilter;
use PortOneFive\Tabulator\Contracts\CanSearch;
use PortOneFive\Tabulator\Contracts\TableSchema;

class Tabulator implements Arrayable, Jsonable, JsonSerializable
{

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var TableSchema|CanSearch|CanFilter
     */
    protected $schema;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $controls = [];

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var string
     */
    protected $title;

    /** @var array */
    protected $displayMutators = [];

    /** @var string|callable|null */
    protected $rowHref = null;

    /** @var array */
    private $data = [];

    /** @var callable|string */
    private $groupBy;

    /**
     * @var array|null
     */
    private $paginate;

    /**
     * @param Model|Builder|Collection $model
     * @param TableSchema|null         $schema
     * @param array                    $options
     *
     * @throws \Exception
     */
    public function __construct($model, $schema = null, array $options = [])
    {
        $this->setModel($model, isset($options['model']) ? $options['model']: null);
        $this->setSchema($schema);

        if ( ! isset($options['title'])) {
            $this->setTitle(ucwords(str_replace(['-', '_'], ' ', $this->model()->getTable())));
        } else {
            $this->setTitle($options['title']);
            unset($options['title']);
        }

        $this->options = array_merge($this->options, $options);
    }

    /**
     * @return Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * @param Builder $newQuery
     *
     * @return $this
     */
    public function setQuery(Builder $newQuery)
    {
        $this->query = $newQuery;

        return $this;
    }

    /**
     * @return Model
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * @param Builder|Model|string $model
     * @param null                 $emptyModel
     *
     * @return $this
     * @throws \Exception
     */
    public function setModel($model, $emptyModel = null)
    {
        if ($model instanceof Collection || is_array($model)) {

            $collection = new Collection($model);

            $collection->map(function ($model) {
                if ( ! $model instanceof Model) {
                    throw new \Exception('All elements of array must be an Eloquent Model');
                }
            });

            /** @var Model $firstModel */
            $firstModel = $collection->first() ?: $emptyModel;

            $this->setData($collection);

            return $this->setModel(new $firstModel);
        }

        if ($model instanceof Model) {
            $this->setQuery($model->query());
        } else if ($model instanceof Builder) {
            $this->setQuery($model);
            $model = $this->query()->getModel();
        } else if ($model instanceof Relation) {
            $this->setQuery($model->getQuery());
            $model = $model->getRelated();
        } else if (is_string($model)) {

            $model = new $model;

            if ( ! $model instanceof Model) {
                throw new \Exception("Invalid model class passed to Tabulator. Class must be an Eloquent Model");
            }

            $this->setQuery($model->newQuery());
        }

        $this->model = $model;

        return $this;
    }

    public function data()
    {
        return $this->data;
    }

    public function setData(Collection $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return TableSchema|CanSearch|CanFilter
     */
    public function schema()
    {
        return $this->schema;
    }

    /**
     * @param TableSchema|string $schema
     *
     * @return $this
     * @throws \Exception
     */
    public function setSchema($schema)
    {
        if (is_array($schema) || empty($schema)) {
            $this->schema = new DefaultSchema;
        } else if (is_string($schema) || is_object($schema)) {
            $this->schema = is_object($schema) ? $schema : new $schema;
        } else if ($schema instanceof TableSchema) {
            $this->schema = $schema;
        } else {
            throw new \Exception('Invalid schema class passed to Tabulator. Class must implement TableSchema');
        }

        if (is_array($schema)) {
            $this->setColumns($schema);
        } else {

            $this->setColumns($this->schema()->columns());

            if (is_callable([$this->schema(), 'rowHref'])) {
                $this->setRowHref([$this->schema(), 'rowHref']);
            }

            foreach ($this->columns() as $columnId => $label) {

                $callback = [$this->schema(), 'get' . Str::studly(str_replace('.', '_', $columnId)) . 'Display'];

                if (is_callable($callback)) {
                    $this->setColumnDisplay($columnId, $callback);
                }
            }
        }

        $this->options['search_enabled'] = $this->isSearchable();
        $this->options['filter_enabled'] = $this->isFilterable();

        return $this;
    }

    /**
     * @return array
     */
    public function columns()
    {
        return $this->columns;
    }

    /**
     * @param $columnId
     * @param $label
     *
     * @return $this
     */
    public function column($columnId, $label)
    {
        $this->columns[$columnId] = $label;

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return array
     */
    public function controls()
    {
        return $this->controls;
    }

    /**
     * @param       $href
     * @param       $label
     * @param array $attributes
     *
     * @return $this
     */
    public function control($href, $label, array $attributes = [])
    {
        $this->controls[] = [
            'href'       => $href,
            'label'      => $label,
            'attributes' => $attributes
        ];

        return $this;
    }

    /**
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param int    $perPage
     * @param string $pageName
     * @param null   $page
     * @param array  $options
     *
     * @return $this
     */
    public function paginate($perPage = null, $pageName = 'page', $page = null, array $options = [])
    {
        $perPage = $perPage ?: $this->model()->getPerPage();

        $this->paginate = [$perPage, ['*'], $pageName, $page, $options];

        return $this;
    }

    /**
     * @param  callable|string $groupBy
     *
     * @return $this
     */
    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    /**
     * @return Collection|Model[]
     */
    public function get()
    {
        if ( ! empty($this->groupBy)) {
            $this->query()->orderBy($this->groupBy);
        }

        list($rows, $mergeData) = $this->getAndPrepareRows();

        $columns = [];

        foreach ($this->columns() as $columnId => $label) {
            $columns[] = [
                'id'    => $columnId,
                'label' => $label
            ];
        }

        $query = urldecode(http_build_query(array_except(app('request')->all(), ['page', 'q'])));
        $url   = Paginator::resolveCurrentPath() . ($query ? '?' . $query : null);

        return array_merge(
            [
                'title'       => $this->title(),
                'searchQuery' => app('request')->get('q'),
                'requestUri'  => app('request')->getUri(),
                'path'        => $url,
                'columns'     => $columns,
                'controls'    => $this->controls,
                'paginated'   => false,
                'total'       => 0,
                'grouped'     => ! empty($this->groupBy),
                'data'        => null,
                'options'     => $this->options,
                'filters'     => $this->isFilterable() ? $this->schema()->filters() : []
            ],
            $mergeData,
            ['data' => $rows]
        );
    }

    public function isSearchable()
    {
        return $this->schema() instanceof CanSearch;
    }

    public function isFilterable()
    {
        return $this->schema() instanceof CanFilter;
    }

    /**
     * @return bool
     */
    public function hasRowHref()
    {
        return isset($this->rowHref);
    }

    /**
     * @param $row
     *
     * @return mixed
     */
    public function getRowHref(Model $row)
    {
        if ($this->hasRowHref()) {
            return is_callable($this->rowHref) ? call_user_func($this->rowHref, $row) : $this->rowHref;
        }

        return null;
    }

    /**
     * @param callable|string|null $href
     *
     * @return $this
     */
    public function setRowHref($href)
    {
        $this->rowHref = $href;

        return $this;
    }

    /**
     * @param string   $columnId
     * @param callable $callback
     *
     * @return $this
     */
    public function setColumnDisplay($columnId, $callback)
    {
        $this->displayMutators[$columnId] = $callback;

        return $this;
    }

    /**
     * Determine if a display mutator exists for a column.
     *
     * @param  string $columnId
     *
     * @return bool
     */
    public function hasColumnDisplayMutator($columnId)
    {
        return isset($this->displayMutators[$columnId]);
    }

    /**
     * @param $columnId
     * @param $row
     *
     * @return mixed
     */
    public function getColumnDisplay($columnId, Model $row)
    {
        if ($this->hasColumnDisplayMutator($columnId)) {
            return $this->mutateColumn($columnId, $row);
        }

        return object_get($row, $columnId);
    }

    public function toArray()
    {
        return $this->get();
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @return $this
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        if (is_callable([$this->query(), $method])) {
            call_user_func_array([$this->query(), $method], $arguments);

            return $this;
        }

        throw new \Exception("Invalid method [{$method}] called");
    }

    /**
     * @return array [array, array]
     */
    protected function getAndPrepareRows()
    {
        if ( ! empty($this->data)) {
            return [$this->prepareRowsForDisplay(new Collection($this->data)), ['total' => count($this->data)]];
        }

        if ($this->isSearchable() && $searchQuery = app('request')->has('q')) {
            $this->schema()->processSearch($this->query(), $searchQuery);
        }

        if ( ! empty($this->paginate)) {

            /** @var LengthAwarePaginator $paginator */
            $paginator = call_user_func_array([$this->query(), 'paginate'], $this->paginate);

            $rows      = $paginator->getCollection();
            $mergeData = [
                'total'      => $paginator->total(),
                'paginated'  => true,
                'pagination' => [
                    'currentPage' => $paginator->currentPage(),
                    'lastPage'    => $paginator->lastPage(),
                    'from'        => $paginator->firstItem(),
                    'to'          => $paginator->lastItem(),
                    'perPage'     => $paginator->perPage()
                ]
            ];
        } else {

            $rows = $this->query()->get();

            $mergeData = ['total' => $rows->count(),];
        }

        return [$this->prepareRowsForDisplay($rows), $mergeData];
    }

    /**
     * @param Collection $rows
     *
     * @return $this
     */
    protected function setColumnsToDefault(Collection $rows)
    {
        if ($rows->count() == 0) {
            $columns = ['id' => 'ID'];
        } else {
            foreach ($columns = $rows->first()->attributesToArray() as $columnId => &$label) {
                $label = ucwords(str_replace(['-', '_'], ' ', $columnId));
                $label = str_replace('Id', 'ID', $label);
            }
        }

        $this->setColumns($columns);

        return $this;
    }

    /**
     * @param       $columnId
     * @param Model $row
     *
     * @return mixed
     */
    protected function mutateColumn($columnId, Model $row)
    {
        return call_user_func($this->displayMutators[$columnId], $row);
    }

    /**
     * @param Collection $rows
     *
     * @return array
     */
    private function prepareRowsForDisplay(Collection $rows)
    {
        if (empty($this->columns)) {
            $this->setColumnsToDefault($rows);
        }

        $rows->transform(
            function (Model $row) {

                $return = [
                    '__href' => $this->getRowHref($row),
                ];

                foreach ($this->columns() as $columnId => $label) {
                    $return[$columnId] = $this->getColumnDisplay($columnId, $row);

                    if ($return[$columnId] instanceof \DateTime) {
                        $return[$columnId] = (string)$return[$columnId];
                    }
                }

                return $return;
            }
        );

        if ( ! empty($this->groupBy)) {
            $rows = $rows->groupBy($this->groupBy);

            $rows->transform(
                function ($rowGroup, $label) {

                    return [
                        'label' => $label,
                        'data'  => $rowGroup
                    ];
                }
            );
        }

        return array_values($rows->toArray());
    }
}
