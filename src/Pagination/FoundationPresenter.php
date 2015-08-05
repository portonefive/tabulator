<?php namespace PortOneFive\Tabulator\Pagination;

use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Contracts\Pagination\Presenter;
use Illuminate\Pagination\UrlWindow;
use Illuminate\Pagination\UrlWindowPresenterTrait;

/**
 * Class FoundationPresenter
 *
 * @package PortOneFive\Essentials\Pagination
 */
class FoundationPresenter implements Presenter
{

    use UrlWindowPresenterTrait;

    /**
     * The paginator implementation.
     *
     * @var \Illuminate\Contracts\Pagination\Paginator
     */
    protected $paginator;
    /**
     * The URL window data structure.
     *
     * @var array
     */
    protected $window;

    /**
     * Construct an instance of a FoundationPresenter
     *
     * @param  \Illuminate\Contracts\Pagination\Paginator $paginator
     * @param  \Illuminate\Pagination\UrlWindow|null      $window
     */
    public function __construct(PaginatorContract $paginator, UrlWindow $window = null)
    {
        $this->paginator = $paginator;
        $this->window    = is_null($window) ? UrlWindow::make($paginator) : $window->get();
    }

    /**
     * Render the given paginator.
     *
     * @return string
     */
    public function render()
    {
        if ($this->hasPages()) {
            return sprintf(
                '<ul class="pagination">%s %s %s</ul>',
                $this->getPreviousButton(),
                $this->getLinks(),
                $this->getNextButton()
            );
        }

        return '';
    }

    /**
     * Determine if the underlying paginator being presented has pages to show.
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->paginator->hasPages();
    }

    /**
     * Get the previous page pagination element.
     *
     * @param  string $text
     *
     * @return string
     */
    public function getPreviousButton($text = '&laquo;')
    {
        // If the current page is less than or equal to one, it means we can't go any
        // further back in the pages, so we will render a disabled previous button
        // when that is the case. Otherwise, we will give it an active "status".
        if ($this->paginator->currentPage() <= 1) {
            return '<li class="arrow unavailable"><a>' . $text . '</a></li>';
        } else {
            $url = $this->paginator->previousPageUrl();

            return '<li class="arrow"><a href="' . $url . '">' . $text . '</a></li>';
        }
    }

    /**
     * Get the next page pagination element.
     *
     * @param  string $text
     *
     * @return string
     */
    public function getNextButton($text = '&raquo;')
    {
        // If the current page is greater than or equal to the last page, it means we
        // can't go any further into the pages, as we're already on this last page
        // that is available, so we will make it the "next" link style disabled.
        if ( ! $this->paginator->hasMorePages()) {
            return '<li class="arrow unavailable"><a>' . $text . '</a></li>';
        }
        $url = $this->paginator->nextPageUrl();

        return '<li class="arrow"><a href="' . $url . '">' . $text . '</a></li>';
    }

    /**
     * Create a range of pagination links.
     *
     * @param  int $start
     * @param  int $end
     *
     * @return string
     */
    public function getPageRange($start, $end)
    {
        $pages = [];

        for ($page = $start; $page <= $end; $page++) {
            // If the current page is equal to the page we're iterating on, we will create a
            // disabled link for that page. Otherwise, we can create a typical active one
            // for the link. These views use the "Twitter Bootstrap" styles by default.
            if ($this->currentPage() == $page) {
                $pages[] = '<li class="current"><a href="#">' . $page . '</a></li>';
            } else {
                $pages[] = $this->getLink($page);
            }
        }

        return implode('', $pages);
    }

    /**
     * Get a pagination "dot" element.
     *
     * @return string
     */
    public function getDots()
    {
        return $this->getDisabledTextWrapper('&hellip;');
    }

    /**
     * Get HTML wrapper for disabled text.
     *
     * @param  string $text
     *
     * @return string
     */
    protected function getDisabledTextWrapper($text)
    {
        return '<li class="unavailable"><a>' . $text . '</a></li>';
    }

    /**
     * Get HTML wrapper for an available page link.
     *
     * @param  string      $url
     * @param  int         $page
     * @param  string|null $rel
     *
     * @return string
     */
    protected function getAvailablePageWrapper($url, $page, $rel = null)
    {
        $rel = is_null($rel) ? '' : ' rel="' . $rel . '"';

        return '<li><a href="' . $url . '"' . $rel . '>' . $page . '</a></li>';
    }

    /**
     * Get HTML wrapper for active text.
     *
     * @param  string $text
     *
     * @return string
     */
    protected function getActivePageWrapper($text)
    {
        return '<li class="current"><a>' . $text . '</a></li>';
    }

    /**
     * Get the current page from the paginator.
     *
     * @return int
     */
    protected function currentPage()
    {
        return $this->paginator->currentPage();
    }

    /**
     * Get the last page from the paginator.
     *
     * @return int
     */
    protected function lastPage()
    {
        return $this->paginator->lastPage();
    }
}
