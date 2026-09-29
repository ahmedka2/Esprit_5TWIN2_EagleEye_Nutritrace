@props(['etapes'])
@php $total = array_sum(array_column($etapes, 'co2')) ?: 1; @endphp

<ol class="route" aria-label="Parcours du produit">
    @foreach ($etapes as $e)
        <li class="stop" style="--i: {{ $loop->index }}">
            <span class="stop-role">{{ $e['role'] }}</span>
            <strong class="stop-actor">{{ $e['acteur'] }}</strong>
            <span class="stop-meta">{{ $e['lieu'] }}@if ($e['date']) · <time datetime="{{ $e['date'] }}">{{ \Illuminate\Support\Carbon::parse($e['date'])->translatedFormat('d M Y') }}</time>@endif</span>
            <span class="co2" title="{{ round($e['co2'] / $total * 100) }} % des émissions">
                <span class="co2-bar"><span style="width: {{ round($e['co2'] / $total * 100) }}%"></span></span>
                <span class="co2-val">{{ number_format($e['co2'], 2, ',', ' ') }} kg</span>
            </span>
        </li>
    @endforeach
</ol>
