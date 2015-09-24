<table {!! $table->attributes() !!} data-table-id="{{ snake_case($table->title()) }}">

    @if ($table->title() || count($table->controls()) > 0)
        <caption class="table-header">
            {{ $table->title() }}
            @foreach ($table->controls() as $control)
                <a href="{{ $control['href'] }}" {{ $control['attributes'] }}>{{ $control['label'] }}</a>
            @endforeach
        </caption>
    @endif

    <thead>
    <tr>

        @if ($table->thumbnailColumn())
            <th class="__thumbnail"></th>
        @endif

        @foreach ($table->columns() as $columnId => $column)
            <th data-column-id="{{ $columnId }}">{{ $column['label'] }}</th>
        @endforeach

        @if ($table->deleteColumn())
            <th class="__delete"></th>
        @endif

    </tr>
    </thead>

    @if ($table->count() == 0)

        <tr class="no-data">
            <td colspan="{{ count($table->columns(true)) }}">- No {{ strtolower($table->title()) }} -</td>
        </tr>

    @elseif ($table->isGrouped())

        @foreach ($table->rowsGrouped() as $group => $items)

            <tbody>

            <tr class="group-header">
                <td colspan="{{ count($table->columns(true)) }}">
                    {{ object_get($items->first(), $table->groupLabelColumn(), object_get($items->first(), $table->groupColumn())) }}
                </td>
            </tr>

            @foreach ($items as $row)
                @include('partial.table-row')
            @endforeach

            </tbody>

        @endforeach

    @else
        <tbody>
        @foreach ($table->rows() as $row)
            @include('partial.table-row')
        @endforeach
        </tbody>
    @endif

    <caption class="table-footer">

        @if ($table->isPaginated())
            {!! $table->renderPaginator() !!}
        @endif

        <span class="FilterTableCount">
                @if ($table->isPaginated())
                Showing {{ $table->paginator()->firstItem() }}-{{ $table->paginator()->lastItem() }}
                of {{ $table->paginator()->total() }}
            @else
                Showing {{ $table->count() }}
            @endif
            {{ str_plural(strtolower($table->title()), $table->isPaginated() ? $table->paginator()->total() : $table->count())}}
            </span>

    </caption>

</table>
