<?php namespace PortOneFive\Tabulator;

use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use PortOneFive\Tabulator\DataHandlers\CollectionHandler;
use PortOneFive\Tabulator\DataHandlers\DataHandler;
use PortOneFive\Tabulator\DataHandlers\PaginationHandler;
use PortOneFive\Tabulator\DataHandlers\QueryBuilderHandler;
use PortOneFive\Tabulator\Pagination\FoundationPresenter;

class TableBuilder
{
    /**
     * @var RowCollection
     */
    protected $items;

    /**
     * @var LengthAwarePaginator|null
     */
    protected $paginator = null;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var string
     */
    protected $title = null;

    /**
     * @var string|null
     */
    protected $template = null;

    /**
     * @var \Illuminate\View\Factory
     */
    protected static $viewFactory;

    /**
     * @var Request
     */
    protected static $request;


    protected $columns  = [];
    protected $controls = [];

    /**
     * @var DataHandler
     */
    protected $itemHandler;

    /**
     * @var array|null
     */
    protected $groupBy = null;

    /**
     * @param RowCollection|Builder|array $items
     * @param array                       $attributes
     */
    public function __construct($items, array $attributes = [])
    {
        $this->itemHandler = $this->makeItemHandler($items, $attributes);
        $this->setAttributes($attributes);
    }

    protected function getDefaultAttributes()
    {
        return [];
    }

    /**
     * @param null $title
     *
     * @return $this|string
     */
    public function title($title = null)
    {
        if (empty($title)) {
            return $this->title;
        }

        $this->title = $title;

        return $this;
    }

    public function __call($method, array $arguments)
    {
        if (method_exists($this->itemHandler, $method)) {
            return call_user_func_array([$this->itemHandler, $method], $arguments);
        }

        throw new \BadMethodCallException('Invalid method: ' . $method);
    }

    public function column($column = null, $label = null, $searchable = false, $sortable = false)
    {
        $column = $column ?: str_random(5);

        $this->columns[$column] = [
            'id'         => $column,
            'label'      => $label,
            'searchable' => (bool)$searchable,
            'sortable'   => (bool)$sortable
        ];

        return $this;
    }

    public function columns($includeAll = false)
    {
        return $includeAll ? $this->columns : array_except($this->columns, ['__thumbnail', '__delete']);
    }

    public function thumbnailColumn()
    {
        return isset($this->columns['__thumbnail']) ? $this->columns['__thumbnail'] : false;
    }

    public function deleteColumn()
    {
        return isset($this->columns['__delete']) ? $this->columns['__delete'] : false;
    }

    /**
     * @param       $href
     * @param       $label
     * @param array $attributes
     *
     * @return $this
     */
    public function control($href, $label, $attributes = [])
    {
        $this->controls[] = [
            'href'       => $href,
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

    public function controls()
    {
        return $this->controls;
    }

    public function items()
    {
        return $this->itemHandler->items();
    }

    /**
     * @return static
     */
    public function rows()
    {
        return $this->items()->map(
            function ($row) {
                return new Row($row);
            }
        );
    }

    /**
     * @return null|static
     */
    public function rowsGrouped()
    {
        if ($this->itemHandler->isGrouped()) {
            return $this->itemHandler->rowsGrouped();
        }

        return null;
    }

    public function render()
    {
        //if ($this->isGrouped()) {
        //    $this->items = $this->items->groupBy(
        //        function ($row) {
        //            return object_get($row, $this->groupBy['column']);
        //        }
        //    );
        //}

        return $this->getViewFactory()->make(
            $this->template ?: config('tabulator.template'),
            [
                'table'   => $this,
                'request' => Request::capture()
            ]
        )->render();
    }

    /**
     * @return null|string
     */
    public function renderPaginator()
    {
        if ( ! $this->paginator()) {
            return null;
        }

        $this->paginator->appends($this->getRequest()->except('page', 'order_by', 'order_direction'));

        return $this->paginator->render(new FoundationPresenter($this->paginator));
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
     * @return Paginator
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
        return $this->paginator !== null;
    }

    /**
     * @param mixed $items
     *
     * @return DataHandler
     */
    public function makeItemHandler($items)
    {
        if ($items instanceof Collection || is_array($items)) {
            return new CollectionHandler(is_array($items) ? new Collection($items) : $items);
        }

        if ($items instanceof \Illuminate\Contracts\Pagination\Paginator) {
            return new PaginationHandler($items);
        }

        if ($items instanceof Builder) {
            return new QueryBuilderHandler($items);
        }

        throw new \InvalidArgumentException('Unexpected value given to TableBuilder');
    }

    /**
     * @return Factory
     */
    protected static function getViewFactory()
    {
        return self::$viewFactory;
    }

    /**
     * @param Factory $viewFactory
     */
    public static function setViewFactory(Factory $viewFactory)
    {
        self::$viewFactory = $viewFactory;
    }

    /**
     * @return Factory
     */
    protected static function getRequest()
    {
        return self::$request;
    }

    /**
     * @param Request $request
     */
    public static function setRequest(Request $request)
    {
        self::$request = $request;
    }

    /**
     * @param array $attributes
     */
    protected function setAttributes(array $attributes)
    {
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
}
