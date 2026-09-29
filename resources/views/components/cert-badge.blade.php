@props(['cert'])
@php $ok = $cert['statut'] === 'vérifié'; @endphp

<span {{ $attributes->class(['stamp', 'stamp--declared' => ! $ok]) }} title="{{ $cert['organisme'] }}">
    <span class="stamp-type">{{ $cert['type'] }}</span>
    <span class="stamp-status">{{ $ok ? 'vérifié' : 'déclaré · non vérifié' }}</span>
</span>
