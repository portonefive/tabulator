<?php namespace PortOneFive\Tabulator;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PortOneFive\Tabulator\Contracts\DataHandler;
use PortOneFive\Tabulator\DataHandlers\CollectionHandler;
use PortOneFive\Tabulator\DataHandlers\PaginationHandler;
use PortOneFive\Tabulator\DataHandlers\QueryBuilderHandler;
use PortOneFive\Tabulator\Pagination\FoundationPresenter;

class TableBuilder
{

    /**
     * @var Request
     */
    protected static $request;
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
    protected $columns  = [];
    protected $controls = [];

    /**
     * @var DataHandler
     */
    protected $itemHandler;

    /**
     * @var Collection
     * */
    protected $rows;

    /**
     * @param RowCollection|Builder|array $items
     * @param array                       $attributes
     */
    public function __construct($items, array $attributes = [])
    {
        $this->itemHandler = $this->makeItemHandler($items, $attributes);
        $this->setAttributes($attributes);
    }

    /**
     * @param Request $request
     */
    public static function setRequest(Request $request)
    {
        self::$request = $request;
    }

    /**
     * @return Request
     */
    protected static function request()
    {
        return self::$request;
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
            'searchable' => (bool) $searchable,
            'sortable'   => (bool) $sortable
        ];

        return $this;
    }

    public function columns($includeAll = false)
    {
        return $includeAll ? $this->columns : array_except($this->columns, ['__thumbnail', '__delete', '__drag']);
    }

    public function dragColumn()
    {
        return isset($this->columns['__drag']) ? $this->columns['__drag'] : false;
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

    public function controls()
    {
        return $this->controls;
    }

    /**
     * @return static
     */
    public function rows()
    {
        if ($this->rows) {
            return $this->rows;
        }

        $rows = $this->itemHandler()->rows();

        if ($rows instanceof GroupedCollection) {

            return $rows->map(
                function ($collection) {
                    return $this->convertCollectionToRows($collection);
                }
            );
        }

        return $this->convertCollectionToRows($rows);
    }

    public function rowsUngrouped()
    {
        if ( ! isset($this->rows)) {
            $this->rows = $this->rows();
        }

        return $this->rows instanceof GroupedCollection ? new Collection($this->rows->collapse()) : $this->rows;
    }

    public function template($template = null)
    {
        return $template == null ? $this->template : $this->template = $template;
    }

    public function render()
    {
        return view(
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
        if ( ! $this->itemHandler->isPaginated()) {
            return null;
        }

        $this->itemHandler->paginator()->appends($this->request()->except('page', 'order_by', 'order_direction'));

        return $this->itemHandler->paginator()->render();
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
     * @param mixed $items
     *
     * @return DataHandler
     */
    public function makeItemHandler($items)
    {
        if ($items instanceof Collection || is_array($items)) {
            return new CollectionHandler(new Collection($items));
        }

        if ($items instanceof Paginator) {
            return new PaginationHandler($items);
        }

        if ($items instanceof Builder) {
            return new QueryBuilderHandler($items);
        }

        throw new \InvalidArgumentException('Unexpected value given to TableBuilder');
    }

    /**
     * @return DataHandler
     */
    public function itemHandler()
    {
        return $this->itemHandler;
    }

    protected function getDefaultAttributes()
    {
        return [];
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
        foreach ((array) $attributes as $key => $value) {
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

    /**
     * @param Collection $collection
     *
     * @return static
     */
    private function convertCollectionToRows(Collection $collection)
    {
        return $collection->map(
            function ($row) {
                return new Row($row);
            }
        );
    }
}
