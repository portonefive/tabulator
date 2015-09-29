<?php namespace PortOneFive\Tabulator;

class BladeTableCompiler {

    protected static $tableOpen = false;
    protected static $instance;
    protected static $rowsOpen = false;

    public static function getInstance()
    {
        return self::$instance ?: self::$instance = new self;
    }

    protected function compileTable($expression)
    {
        $tableClass = config('tabulator.class');

        return "<?php \$__table = new $tableClass{$expression}; ?>";
    }

    protected function compileEndtable()
    {
        return "<?= \$__table->render(); ?>";
    }

    protected function compileTitle($expression)
    {
        return "<?php \$__table->title{$expression}; ?>";
    }

    protected function compilePaginate($expression)
    {
        return "<?php \$__table->paginate{$expression}; ?>";
    }

    protected function compileColumn($expression)
    {
        if (self::$rowsOpen) {
            $expression = '__column_' . trim(trim($expression, '()'), '\'"');
            return "<?php \$__env->startSection('{$expression}'); ?>";
        }

        return "<?php \$__table->column{$expression}; ?>";
    }

    protected function compileEndcolumn()
    {
        return "<?php \$__sectionName = \$__env->stopSection(true);
            \$__row->setColumnOutput(
                str_replace('__column_', '', \$__sectionName),
                \$__env->getSections()[\$__sectionName]
            ); ?>";
    }

    protected function compileControl($expression)
    {
        return "<?php \$__table->control{$expression}; ?>";
    }

    protected function compileDelete($expression)
    {
        if (empty($expression)) {
            return "<?php \$__table->column('__delete'); ?>";
        }

        return "<?php \$__row->setDeleteRoute{$expression}; ?>";
    }

    protected function compileThumbnail($expression)
    {
        if (empty($expression)) {
            return "<?php \$__table->column('__thumbnail'); ?>";
        }

        return "<?php \$__row->setThumbnail{$expression}; ?>";
    }

    protected function compileRows()
    {
        self::$rowsOpen = true;

        return "<?php foreach (\$__table->rowsUngrouped() as \$__rowId => \$__row) : ?>";
    }

    protected function compileEndrows()
    {
        self::$rowsOpen = false;

        return "<?php endforeach; ?>";
    }

    protected function compileHref($expression)
    {
        return "<?php \$__row->setHref{$expression}; ?>";
    }

    protected function compileGroupby($expression)
    {
        return "<?php \$__table->groupBy{$expression} ?>";
    }

    protected function compileSortable($expression)
    {
        return "<?php \$__table->sortable{$expression} ?>";
    }

    protected function compileTemplate($expression)
    {
        return "<?php \$__table->template{$expression} ?>";
    }

    protected function compileClass($expression)
    {
        if (self::$rowsOpen) {
            return "<?php \$__row->setClass{$expression} ?>";
        }
    }

    protected function compileDirective($directive, $expression)
    {
        if ($directive == 'table' || $directive == 'endtable')
        {
            self::$tableOpen = $directive == 'table';
        }
        else if ( ! method_exists($this, 'compile' . ucfirst($directive)) || ! self::$tableOpen)
        {
            return false;
        }

        return $this->{'compile' . ucfirst($directive)}($expression);
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
