<?php namespace PortOneFive\Tabulator;

class BladeTableCompiler {

    protected static $tableOpen = false;
    protected static $instance;
    protected static $rowsOpen = false;

    public static function getInstance()
    {
        return self::$instance ?: self::$instance = new self;
    }

    protected function table($expression)
    {
        $tableClass = config('tabulator.class');

        return "<?php \$__table = new $tableClass{$expression}; ?>";
    }

    protected function endtable()
    {
        return "<?= \$__table->render(); ?>";
    }

    protected function title($expression)
    {
        return "<?php \$__table->title{$expression}; ?>";
    }

    protected function column($expression)
    {
        if (self::$rowsOpen) {
            return "<?php \$__env->startSection{$expression}; ?>";
        }

        return "<?php \$__table->column{$expression}; ?>";
    }

    protected function endcolumn()
    {
        return "<?php \$__sectionName = \$__env->stopSection(true);
            \$__row->setColumnOutput(\$__sectionName, \$__env->getSections()[\$__sectionName]); ?>";
    }

    protected function control($expression)
    {
        return "<?php \$__table->control{$expression}; ?>";
    }

    protected function delete($expression)
    {
        if (empty($expression)) {
            return "<?php \$__table->column('__delete'); ?>";
        }

        return "<?php \$__row->__set('__delete', {$expression}); ?>";
    }

    protected function thumbnail($expression)
    {
        if (empty($expression)) {
            return "<?php \$__table->column('__thumbnail'); ?>";
        }

        return "<?php \$__row->__set('__thumbnail', {$expression}); ?>";
    }

    protected function rows()
    {
        self::$rowsOpen = true;

        return "<?php foreach (\$__table->rows() as \$__row) : ?>";
    }

    protected function endrows()
    {
        self::$rowsOpen = false;

        return "<?php endforeach; ?>";
    }

    protected function href($expression)
    {
        return "<?php \$__row->setHref{$expression}; ?>";
    }

    protected function groupby($expression)
    {
        return "<?php \$__table->groupBy{$expression} ?>";
    }

    protected function sortable($expression)
    {
        return "<?php \$__table->sortable{$expression} ?>";
    }

    protected function compileDirective($directive, $expression)
    {
        if ($directive == 'table' || $directive == 'endtable')
        {
            self::$tableOpen = $directive == 'table';
        }
        else if ( ! method_exists($this, $directive) || ! self::$tableOpen)
        {
            return false;
        }

        return $this->{$directive}($expression);
    }

    public static function attempt($match)
    {
        if ($result = self::getInstance()->compileDirective($match[1], array_get($match, 3)))
        {
            $match[0] = $result;
        }

        return isset($match[3]) ? $match[0] : $match[0] . $match[2];
    }
}
