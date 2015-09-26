<?php namespace PortOneFive\Tabulator;

use Illuminate\Database\Eloquent\Model;

class Row
{

    public $href;
    public $class;
    public $thumbnail;
    public $delete;

    /**
     * @var Model|object
     */
    protected $data;
    protected $columnOutput;

    /**
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = (object)$data;
    }

    public function setColumnOutput($columnId, $content)
    {
        $this->columnOutput[$columnId] = $content;

        return $this;
    }

    public function columnOutput($columnId)
    {
        if ($columnId == '__delete' || $columnId == '__thumbnail') {
            return 'special';
        }

        if (isset($this->columnOutput[$columnId])) {
            return $this->columnOutput[$columnId];
        }

        return object_get($this->data, $columnId);
    }

    public function setThumbnail($url)
    {
        $this->thumbnail = $url;

        return $this;
    }

    public function setDeleteRoute($route)
    {
        $this->delete = $route;

        return $this;
    }

    public function setHref($link)
    {
        $this->href = $link;

        return $this;
    }

    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    public function usesSoftDeletes()
    {
        return $this->isEloquent() && method_exists($this->data, 'trashed');
    }

    public function __get($key)
    {
        if ($this->isEloquent()) {
            return $this->data->__get($key);
        }

        return object_get($this->data, $key);
    }

    public function __set($key, $value)
    {
        //
    }

    public function __isset($key)
    {
        return isset($this->data->{$key});
    }


    private function isEloquent()
    {
        return $this->data instanceof Model;
    }
}
