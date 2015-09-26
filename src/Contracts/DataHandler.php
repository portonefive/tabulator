<?php

namespace PortOneFive\Tabulator\Contracts;

use Illuminate\Contracts\Pagination\Paginator;

interface DataHandler
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function rows();

    /**
     * @return int
     */
    public function count();

    /**
     * @param int    $perPage
     * @param string $pageName
     * @param null   $page
     *
     * @param array  $options
     *
     * @return
     */
    public function paginate($perPage = 12, $pageName = 'page', $page = null, array $options = []);

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
     * @return string|null
     */
    public function groupLabelColumn();

    /**
     * @return string|null
     */
    public function groupColumn();

    /**
     * @return Paginator
     */
    public function paginator();
}
