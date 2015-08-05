<?php namespace PortOneFive\Tabulator;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class TableBuilder
{

    /**
     * @var string
     */
    public $title   = null;
    public $groupBy = false;

    /** @var \Illuminate\View\Factory */
    protected $view;
    /**
     * @var Collection
     */
    protected $collection;
    /**
     * @var array
     */
    protected $attributes;

    protected $template = null;

    protected $rowCount          = 0;
    protected $columns           = [];
    protected $controls          = [];
    protected $defaultAttributes = [];

    /** @var LengthAwarePaginator */
    protected $paginator;

    protected $sortable = false;

    /**
     * @param            $resultSet
     * @param array      $attributes
     *
     * @internal param $name
     * @internal param Collection $collection
     */
    public function __construct($resultSet, $attributes = [])
    {
        $this->view = app('view');

        if ($resultSet instanceof Collection) {
            $this->collection = $resultSet;
        } else if ($resultSet instanceof LengthAwarePaginator) {
            $this->paginator  = $resultSet;
            $this->collection = $resultSet->getCollection();
        } else if ($resultSet instanceof Builder) {
            $this->collection = $resultSet->get();
        }

        $this->collection = $this->collection->map(
            function ($row) {
                return new Row($row);
            }
        );

        $this->rowCount = $this->collection->count();

        $this->attributes = array_merge($this->getDefaultAttributes(), $attributes);

        if ( ! isset($this->attributes['class'])) {
            $this->attributes['class'] = 'Tabulator';
        } else {
            $this->attributes['class'] = 'Tabulator ' . $this->attributes['class'];
        }

        if (isset($this->attributes['template'])) {
            $this->template = $this->attributes['template'];
        }
    }

    protected function getDefaultAttributes()
    {
        return $this->defaultAttributes;
    }

    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    public function column($columnId = null, $label = null, $searchable = false, $sortable = false)
    {
        $columnId = $columnId ?: str_random(5);

        $this->columns[$columnId] = [
            'id'         => $columnId,
            'label'      => $label,
            'searchable' => (bool)$searchable,
            'sortable'   => (bool)$sortable
        ];

        if ($sortable == true) {
            $this->sortable = true;
        }

        return $this;
    }

    public function columns($includeAll = false)
    {
        return $includeAll ? $this->columns : array_except($this->columns, ['__thumbnail', '__delete']);
    }

    //public function deleteColumn($route, array $routeParams = [])
    //{
    //    $this->columns['__delete'] = [
    //        'id'          => '__delete',
    //        'label'       => '',
    //        'route'       => $route,
    //        'routeParams' => $routeParams
    //    ];
    //
    //    return $this;
    //}

    public function thumbnailColumn()
    {
        return isset($this->columns['__thumbnail']) ? $this->columns['__thumbnail'] : false;
    }

    public function deleteColumn()
    {
        return isset($this->columns['__delete']) ? $this->columns['__delete'] : false;
    }

    public function control($route, $label, $attributes = [])
    {
        //$routeKey = is_array($route) ? $route[0] : $route;
        $this->controls[] = [
            'route'      => $route,
            'label'      => $label,
            'attributes' => $this->attributeArrayToHtmlString($attributes)
        ];

        return $this;
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    protected function attributeArrayToHtmlString(array $attributes = [])
    {
        $html = [];

        // For numeric keys we will assume that the key and the value are the same
        // as this will convert HTML attributes such as "required" to a correct
        // form like required="required" instead of using incorrect numerics.
        foreach ((array)$attributes as $key => $value) {
            $element = $this->attributeElement($key, $value);

            if ( ! is_null($element)) {
                $html[] = $element;
            }
        }

        return count($html) > 0 ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Build a single attribute element.
     *
     * @param  string $key
     * @param  string $value
     *
     * @return string
     */
    protected function attributeElement($key, $value)
    {
        if (is_numeric($key)) {
            $key = $value;
        }

        if ( ! is_null($value)) {
            return $key . '="' . e($value) . '"';
        }
    }

    public function groupBy($columnId, $relationId, $relationTitleColumn = null)
    {
        $this->groupBy = [
            'columnId'              => $columnId,
            'relationId'            => $relationId,
            'relationTitleProperty' => $relationTitleColumn
        ];

        return $this;
    }

    public function controls()
    {
        return $this->controls;
    }

    public function rows()
    {
        return $this->collection;
    }

    public function rowCount()
    {
        return $this->rowCount;
    }

    public function render()
    {
        if ($this->groupBy) {
            $this->collection = $this->collection->groupBy(
                function ($row) {
                    return object_get($row, $this->groupBy['columnId']);
                }
            );
        }

        $request = Request::capture();
        if ($this->isPaginated()) {
            $this->paginator->appends($request->except('page', 'order_by', 'order_direction'));
        }

        return $this->view->make(
            $this->template ?: config('tabulator.template'),
            [
                'table'   => $this,
                'request' => Request::capture()
            ]
        )->render();
    }

    /**
     * Build an HTML attribute string from an array.
     *
     * @return string
     */
    public function attributes()
    {
        return $this->attributeArrayToHtmlString($this->attributes);
    }

    /**
     * @return LengthAwarePaginator
     */
    public function paginator()
    {
        return $this->paginator;
    }

    /**
     * @return bool
     */
    public function isPaginated()
    {
        return $this->paginator instanceof LengthAwarePaginator;
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        return $this->sortable;
    }
}
