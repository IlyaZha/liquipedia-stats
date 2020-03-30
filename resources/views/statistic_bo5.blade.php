@if ($total > 3)
    <br>
    <div>
        <p>Bracket: {{ $bracket }}, round: {{ $round }}</p>
        <p>Винрейт верхней команды: <b>{{ $wr }}%</b> ({{ $win }}/{{ $total }})</p>
        <p>Кол-во матчей 3:0 или 0:3: <b>{{ $percent3_0 }}%</b> ({{ $win3_0 }}/{{ $total_bo5 }})</p>
        <p>Кол-во матчей 3:1 или 1:3: <b>{{ $percent3_1 }}%</b> ({{ $win3_1 }}/{{ $total_bo5 }})</p>
        <p>Кол-во матчей 3:2 или 2:3: <b>{{ $percent3_2 }}%</b> ({{ $win3_2 }}/{{ $total_bo5 }})</p>
    </div>
@endif
