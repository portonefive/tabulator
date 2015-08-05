<?php namespace PortOneFive\Tabulator;

use Illuminate\Database\Eloquent\Model;

class Row {

    protected $row;
    protected $columnOutput;
    public $href;

    /**
     * @param Model $row
     */
    public function __construct(Model $row)
    {
        $this->row = $row;
    }

    public function setColumnOutput($columnId, $content)
    {
        $this->columnOutput[$columnId] = $content;

        return $this;
    }

    public function columnOutput($columnId)
    {
        if ($columnId == '__delete' || $columnId == '__thumbnail')
        {
            return 'special';
        }

        if (isset($this->columnOutput[$columnId]))
        {
            return $this->columnOutput[$columnId];
        }

        return object_get($this->row, $columnId, $this->row{$columnId});
    }

    public function setHref($link)
    {
        $this->href = $link;

        return $this;
    }

    public function usesSoftDeletes()
    {
        return method_exists($this->row, 'trashed');
    }

    public function __get($key)
    {
        return $this->row->__get($key);
    }

    public function __isset($key)
    {
        return $this->row->__isset($key);
    }

    public function __set($key, $value)
    {
        $this->row->__set($key, $value);
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->row, $method], $arguments);
    }
}
