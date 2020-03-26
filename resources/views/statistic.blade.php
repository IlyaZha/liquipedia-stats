<p>Тип турниров: {{ $type }}</p>
<p>Анализировано турниров: {{ $tournamentsCount }}</p>
@foreach ($statisticsBo3 as $statisticBo3)
    {{ $statisticBo3 }}
@endforeach
<br>
<h4>Прочая статистика:</h4>
<p>Винрейт верхней команды в виннерах НЕ первого раунда {{ $wrNotFirstRound }}% ({{ $winNotFirstRound }}/{{ $totalNotFirstRound }})</p>
<p>Винрейт верхней команды, упавшей из виннер брекета {{ $wrFallenFromTop }}% ({{ $winFallenFromTop }}/{{ $totalFallenFromTop }})</p>
