<tr class="{{ $row->usesSoftDeletes() && $row->trashed() ? 'deleted' : '' }}"
    data-href="{{ object_get($row, 'href') }}">

    @if ($table->thumbnailColumn())
        <td class="__thumbnail">
            <img class="row-thumbnail" src="{{ object_get($row, 'thumbnail')}}"/>
        </td>
    @endif

    @foreach ($table->columns() as $columnId => $column)
        <td>
            {!! $row->columnOutput($columnId) !!}
        </td>
    @endforeach

    @if ($table->deleteColumn())
        <td class="__delete">
            <form action="{{ object_get($row, 'delete') }}" method="post">
                <input type="hidden" name="_method" value="delete"/>
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <button type="submit">X</button>
            </form>
        </td>
    @endif

</tr>
