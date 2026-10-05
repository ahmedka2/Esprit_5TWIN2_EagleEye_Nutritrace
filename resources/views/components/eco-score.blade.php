@props(['score'])
<span class="eco" role="img" aria-label="Éco-score {{ $score }}, sur une échelle de A (impact faible) à E (impact fort)">
    @foreach (['A', 'B', 'C', 'D', 'E'] as $l)
        <span @class(['eco-cell', 'is-on' => $l === $score]) data-l="{{ $l }}">{{ $l }}</span>
    @endforeach
</span>
