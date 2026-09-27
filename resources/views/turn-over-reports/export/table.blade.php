<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="{{ 1 + (count($months) * 3) }}">
                <h3 class="text-left">Turnover Report ({{ $locationName }})</h3>
            </th>
        </tr>
        <tr>
            <th rowspan="2">Description</th>
            @foreach ($months as $month)
                <th colspan="3">{{ $month }} - {{ $shortFormatYear }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($months as $month)
                <th>Operation</th>
                <th>Supporting</th>
                <th>Total</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($reportRows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                @foreach ($reportData as $monthData)
                    @php($values = $monthData[$row['key']])
                    @foreach (['operation', 'supporting', 'total'] as $category)
                        <td>{{ $values[$category] }}{{ $row['percentage'] ? '%' : '' }}</td>
                    @endforeach
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
