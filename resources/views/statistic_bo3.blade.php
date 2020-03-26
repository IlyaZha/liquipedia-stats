@if ($total > 3)
    <br>
    <div>
        <p>Bracket: {{ $bracket }}, round: {{ $round }}</p>
        <p>Винрейт верхней команды: <b>{{ $wr }}%</b> ({{ $win }}/{{ $total }})</p>
        <p>Кол-во матчей всухую: <b>{{ $percent2_0 }}%</b> ({{ $win2_0 }}/{{ $total_bo3 }})</p>
    </div>
@endif
