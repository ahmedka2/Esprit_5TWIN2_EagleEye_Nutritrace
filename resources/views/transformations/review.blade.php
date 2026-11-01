@extends('layouts.admin')
@section('title', 'Vérifier le plan')
@section('actions')<a href="{{ route('transformations.ai.create') }}">← Recommencer</a>@endsection
@section('content')
@if ($errors->any())<p class="flash flash-error" role="alert">{{ $errors->first() }}</p>@endif
<section class="panel"><h2>Besoin initial</h2><p>{{ $originalRequest }}</p><p class="hint">Suggestion indicative générée par IA, à valider par le responsable qualité.</p>
    @if (!empty($plan['warnings']))<ul>@foreach ($plan['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul>@endif
</section>
<form class="form" method="POST" action="{{ route('transformations.ai.store') }}">
    @csrf
    <section class="panel"><h2>Transformation</h2>
        <label>Matière première<select name="matiere_premiere_id" required><option value="">Choisir…</option>@foreach($matieresPremieres as $matiere)<option value="{{ $matiere->id }}" @selected(old('matiere_premiere_id') == $matiere->id || (strcasecmp($matiere->name, $plan['raw_material_name']) === 0))>{{ $matiere->name }}</option>@endforeach</select></label>
        <label>Quantité d’entrée<div class="quantity-fields"><input name="quantite_utilisee" type="number" step="0.01" min="0.01" value="{{ old('quantite_utilisee', $plan['input_quantity']) }}" required><input name="unite" maxlength="10" value="{{ old('unite', $plan['input_unit']) }}" required></div></label>
        <label>Nom du produit final<input name="product_name" maxlength="255" value="{{ old('product_name', $plan['product_name']) }}" required></label>
        <label>Rendement estimé (%)<input name="expected_yield_percent" type="number" min="0.01" max="100" step="0.01" value="{{ old('expected_yield_percent', $plan['expected_yield_percent']) }}" required></label>
        <label>DLC suggérée (jours)<input name="suggested_shelf_life_days" type="number" min="1" value="{{ old('suggested_shelf_life_days', $plan['suggested_shelf_life_days']) }}" required></label>
        <label>Début prévu<input name="planned_start" type="datetime-local" value="{{ old('planned_start', \Carbon\Carbon::parse($plannedStart)->format('Y-m-d\TH:i')) }}" required></label>
    </section>
    <section class="panel"><div class="panel-head"><h2>Étapes proposées</h2><button type="button" class="btn btn-ghost" id="add-step">Ajouter une étape</button></div>
        <ol id="plan-steps">@foreach(old('steps', $plan['steps']) as $i => $step)
        <li class="panel plan-step"><div class="fields">
            <label>Nom<input name="steps[{{ $i }}][name]" value="{{ $step['name'] }}" required></label>
            <label>Durée (minutes)<input name="steps[{{ $i }}][duration_min]" type="number" min="1" value="{{ $step['duration_min'] }}" required></label>
            @if(array_key_exists('target_temperature', $step) && $step['target_temperature'] !== null)<label>Température cible (°C)<input name="steps[{{ $i }}][target_temperature]" type="number" min="-30" max="250" step="0.1" value="{{ $step['target_temperature'] ?? '' }}"></label>@endif
            <label>Étape critique<select name="steps[{{ $i }}][is_critical]"><option value="0" @selected(!($step['is_critical'] ?? false))>Non</option><option value="1" @selected($step['is_critical'] ?? false)>Oui</option></select></label>
            <label>Description<textarea name="steps[{{ $i }}][description]" rows="2">{{ $step['description'] ?? '' }}</textarea></label>
        </div><div><button type="button" class="link-button move-up">↑ Monter</button> <button type="button" class="link-button move-down">↓ Descendre</button> <button type="button" class="link-button remove-step">Supprimer</button></div></li>
        @endforeach</ol>
    </section>
    <div class="form-actions"><button class="btn btn-ghost" name="intent" value="draft">Enregistrer en brouillon</button><button class="btn" name="intent" value="validate">Valider le plan</button></div>
</form>
<script>
(()=>{const list=document.querySelector('#plan-steps');let next=list.children.length;const renumber=()=>[...list.children].forEach((li,index)=>li.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace(/steps\[\d+\]/,`steps[${index}]`)));document.querySelector('#add-step').onclick=()=>{if(list.children.length>=15)return alert('Maximum 15 étapes.');const i=next++;const li=document.createElement('li');li.className='panel plan-step';li.innerHTML=`<div class="fields"><label>Nom<input name="steps[${i}][name]" required></label><label>Durée (minutes)<input name="steps[${i}][duration_min]" type="number" min="1" required></label><label>Température cible (°C)<input name="steps[${i}][target_temperature]" type="number" min="-30" max="250" step="0.1"></label><label>Étape critique<select name="steps[${i}][is_critical]"><option value="0">Non</option><option value="1">Oui</option></select></label><label>Description<textarea name="steps[${i}][description]" rows="2"></textarea></label></div><div><button type="button" class="link-button move-up">↑ Monter</button> <button type="button" class="link-button move-down">↓ Descendre</button> <button type="button" class="link-button remove-step">Supprimer</button></div>`;list.append(li);renumber()};list.addEventListener('click',e=>{const li=e.target.closest('li');if(!li)return;if(e.target.matches('.remove-step')){if(list.children.length>1)li.remove()}if(e.target.matches('.move-up')&&li.previousElementSibling)list.insertBefore(li,li.previousElementSibling);if(e.target.matches('.move-down')&&li.nextElementSibling)list.insertBefore(li.nextElementSibling,li);renumber()})})();
</script>
@endsection
