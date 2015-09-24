<?php

namespace PortOneFive\Tabulator\DataHandlers;

interface DataHandler
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function items();

    /**
     * @return int
     */
    public function count();

    /**
     * @param int    $perPage
     * @param string $pageName
     * @param null   $page
     *
     * @return void
     */
    public function paginate($perPage = 12, $pageName = 'page', $page = null);

    /**
     * @return bool
     */
    public function isPaginated();

    /**
     * @param      $column
     * @param null $labelColumn
     *
     * @return void
     */
    public function groupBy($column, $labelColumn = null);

    /**
     * @return bool
     */
    public function isGrouped();

    /**
     */
    public function rowsGrouped();

    /**
     * @return string|null
     */
    public function groupLabelColumn();


    /**
     * @return string|null
     */
    public function groupColumn();
}
