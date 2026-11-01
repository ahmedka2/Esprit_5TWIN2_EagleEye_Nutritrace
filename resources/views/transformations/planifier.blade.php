@extends('layouts.admin')
@section('title', 'Planifier avec l’IA')
@section('actions')<a href="{{ route('transformations.index') }}">← Liste</a>@endsection
@section('content')
@if (session('error'))<p class="flash flash-error" role="alert">{{ session('error') }}</p>@endif
<form class="form" method="POST" action="{{ route('transformations.ai.generate') }}" data-plan-form>
    @csrf
    <section class="panel">
        <h2>Décris le besoin de transformation</h2>
        <label>Besoin en langage naturel
            <textarea name="original_request" rows="5" maxlength="5000" required placeholder="Je veux transformer 100 kg de tomates en sauce, je commence lundi à 8h">{{ old('original_request') }}</textarea>
            @error('original_request')<span class="field-error">{{ $message }}</span>@enderror
        </label>
        <label>Date et heure prévues de début
            <input type="datetime-local" name="planned_start" value="{{ old('planned_start') }}" required>
            @error('planned_start')<span class="field-error">{{ $message }}</span>@enderror
        </label>
        <p class="hint">Le plan proposé est indicatif et doit être contrôlé par le responsable qualité.</p>
        <button class="btn" type="submit">Générer le plan</button>
        <span data-loading hidden role="status">Génération en cours…</span>
    </section>
</form>
<script>document.querySelector('[data-plan-form]')?.addEventListener('submit',e=>{e.currentTarget.querySelector('button').disabled=true;e.currentTarget.querySelector('[data-loading]').hidden=false});</script>
@endsection
