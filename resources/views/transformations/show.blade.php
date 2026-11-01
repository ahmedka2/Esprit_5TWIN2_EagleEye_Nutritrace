@extends('layouts.admin')
@section('title', 'Suivi de la transformation')
@section('actions')<a href="{{ route('transformations.index') }}">← Liste</a>@endsection
@section('content')
@php($steps = $transformation->etapes ?? [])
@php($scheduledSteps = collect($steps)->contains(fn($step) => array_key_exists('duree_prevue_min', $step)))
@php($empreintes = $transformation->empreintes->keyBy('etape'))
@if (session('success'))<p class="flash" role="status">{{ session('success') }}</p>@endif
@if (session('error'))<p class="flash flash-error" role="alert">{{ session('error') }}</p>@endif
<section class="panel">
    <div class="panel-head"><div><p class="muted small">Référence</p><h2 class="mono">{{ $transformation->reference }}</h2></div><span class="tag">{{ ucfirst(str_replace('_',' ',$transformation->statut)) }}</span></div>
    <dl class="detail-grid"><div><dt>Matière première</dt><dd>{{ $transformation->matiere_premiere }}</dd></div><div><dt>Quantité d’entrée</dt><dd>{{ $transformation->quantite_utilisee }} {{ $transformation->unite }}</dd></div><div><dt>Produit prévu</dt><dd>{{ $transformation->nom_produit_final ?: '—' }}</dd></div><div><dt>Rendement</dt><dd>{{ $transformation->expected_yield_percent ?? $transformation->rendement ?? '—' }} %</dd></div><div><dt>Prévu</dt><dd>{{ $transformation->planned_start?->format('d/m/Y H:i') ?? $transformation->date_debut?->format('d/m/Y H:i') ?? '—' }} → {{ $transformation->planned_end?->format('d/m/Y H:i') ?? '—' }}</dd></div><div><dt>DLC suggérée</dt><dd>{{ $transformation->suggested_shelf_life_days ? $transformation->suggested_shelf_life_days.' jours' : '—' }}</dd></div></dl>
    @if (in_array($transformation->statut, ['brouillon','planifiee'], true))<form method="POST" action="{{ route('transformations.lancer',$transformation) }}">@csrf<button class="btn">Démarrer la transformation</button></form>@endif
    @if ($transformation->statut === 'finalisee' && $transformation->produitFinal)<a class="btn" href="{{ route('produits-finaux.show',$transformation->produitFinal) }}">Voir le produit fini {{ $transformation->produitFinal->code }}</a>@endif
    <a class="btn btn-ghost" href="{{ route('transformations.pdf',$transformation) }}">Télécharger le PDF</a>
</section>

@if ($transformation->statut === 'finalisee')
<section class="panel photo-upload-panel">
    <div class="panel-head"><div><h2>Photo du produit final</h2><p class="muted small">Ajoute une photo JPG, PNG ou WEBP (5 Mo maximum).</p></div></div>
    @if ($transformation->final_photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($transformation->final_photo_path))
        <img class="final-product-photo" src="{{ '/storage/'.ltrim($transformation->final_photo_path, '/') }}" alt="Photo du produit final {{ $transformation->nom_produit_final }}">
    @endif
    <form method="POST" enctype="multipart/form-data" action="{{ route('transformations.photo',$transformation) }}" class="photo-upload-form">
        @csrf
        <label class="photo-dropzone" for="final-photo-input">
            <span class="photo-upload-icon" aria-hidden="true">+</span>
            <strong>Choisir une photo</strong>
            <span class="muted small" id="photo-file-name">Clique ici pour selectionner une image</span>
            <input id="final-photo-input" type="file" name="final_photo" accept="image/jpeg,image/png,image/webp" required>
        </label>
        @error('final_photo')<span class="field-error" role="alert">{{ $message }}</span>@enderror
        <button class="btn" type="submit">Enregistrer la photo</button>
    </form>
<style>
.photo-upload-panel{display:grid;gap:1rem}.photo-upload-form{display:grid;grid-template-columns:minmax(220px,1fr) auto;gap:1rem;align-items:center}.photo-dropzone{min-height:100px;border:2px dashed #86a992;border-radius:14px;background:rgba(36,130,77,.06);display:grid;place-content:center;text-align:center;gap:.25rem;padding:1rem;cursor:pointer}.photo-dropzone input{max-width:100%;margin:.4rem auto 0}.photo-upload-icon{font-size:1.5rem;color:#24824d}.final-product-photo{max-width:100%;max-height:180px;object-fit:contain;justify-self:start;border-radius:10px}@media(max-width:640px){.photo-upload-form{grid-template-columns:1fr}.photo-upload-form .btn{width:100%}}
</style>
<script>
document.querySelector('#final-photo-input')?.addEventListener('change', event => { const file=event.target.files?.[0]; if(file) document.querySelector('#photo-file-name').textContent=file.name; });
</script>
</section>
@endif

<section class="panel"><div class="panel-head"><h2>Checklist des étapes</h2><span class="muted">{{ collect($steps)->where('statut','terminee')->count() }} / {{ count($steps) }} terminées</span></div>
    <ol class="timeline">
    @foreach($steps as $i => $step)
        @php($done = in_array($step['statut'] ?? '', ['terminee','done'], true))
        <li class="timeline-item {{ $done ? 'is-done' : (($step['statut'] ?? '') === 'en_cours' ? 'is-current' : 'is-todo') }}"><span class="timeline-dot" aria-hidden="true">{{ $i + 1 }}</span><div class="timeline-card">
            <div class="timeline-top"><strong>{{ $i+1 }}. {{ $step['nom'] ?? 'Étape' }}</strong><span class="timeline-badge">{{ $done ? 'Terminée' : ucfirst($step['statut'] ?? 'planifiée') }} @if($step['critique'] ?? false) · Critique @endif</span></div>
            @if(!empty($step['description']))<p>{{ $step['description'] }}</p>@endif
            @if($empreinte = ($empreintes[$i] ?? null))<p class="muted small mono">{{ number_format($empreinte->co2_kg, 2, ',', ' ') }} kg CO₂e · {{ number_format($empreinte->eau_litres, 0, ',', ' ') }} L d’eau @if($empreinte->estime_par_ia) · estimation IA @endif</p>@endif
            <p class="muted small">Prévu : {{ !empty($step['debut_prevu']) ? \Carbon\Carbon::parse($step['debut_prevu'])->format('d/m H:i') : '—' }} → {{ !empty($step['fin_prevue']) ? \Carbon\Carbon::parse($step['fin_prevue'])->format('d/m H:i') : '—' }} @if(($step['temperature_cible'] ?? null) !== null) · Cible : {{ $step['temperature_cible'] }} °C @endif</p>
            @if($done)<p>Réel : {{ !empty($step['debut_reel']) ? \Carbon\Carbon::parse($step['debut_reel'])->format('d/m H:i') : '—' }} → {{ !empty($step['terminee_le']) ? \Carbon\Carbon::parse($step['terminee_le'])->format('d/m H:i') : '—' }} @if(($step['temperature_cible'] ?? null) !== null) · Température mesurée : {{ $step['temperature_mesuree'] ?? '—' }} °C @endif</p><p>Opérateur : {{ \App\Models\User::find($step['realisee_par'] ?? null)?->name ?? '—' }}</p>@if(!empty($step['notes']))<p>Notes : {{ $step['notes'] }}</p>@endif
                @if(auth()->user()->isAdmin())<form method="POST" action="{{ route('transformations.etape.update',[$transformation,$i]) }}" class="form">@csrf @method('PATCH')@if(($step['temperature_cible'] ?? null) !== null)<label>Corriger la température mesurée<input type="number" name="temperature_mesuree" min="-30" max="250" step="0.1" value="{{ $step['temperature_mesuree'] ?? '' }}"></label>@endif<label>Corriger les notes<textarea name="notes">{{ $step['notes'] ?? '' }}</textarea></label><button class="btn btn-ghost">Corriger (journalisé)</button></form>@endif
                @if(!empty($step['journal']))<details><summary>Journal des corrections</summary>@foreach($step['journal'] as $entry)<p class="muted small">{{ $entry['action'] ?? 'Modification' }} · {{ \App\Models\User::find($entry['user_id'] ?? null)?->name ?? 'Compte supprimé' }} · {{ !empty($entry['date']) ? \Carbon\Carbon::parse($entry['date'])->format('d/m/Y H:i') : '' }}</p>@endforeach</details>@endif
            @elseif($transformation->statut === 'en_cours' && ($step['statut'] ?? '') === 'en_cours')
                @if($scheduledSteps)
                <form method="POST" action="{{ route('transformations.etape.executer',[$transformation,$i]) }}" class="form step-action" data-step-form>@csrf<input type="hidden" name="estime_par_ia" value="0"><label>Carbone (kg CO₂e)<input name="co2_kg" type="number" min="0" step="0.01" required></label><label>Eau (litres)<input name="eau_litres" type="number" min="0" step="0.01" required></label>@if(($step['temperature_cible'] ?? null) !== null)<label>Température mesurée (°C)<input type="number" name="temperature_mesuree" min="-30" max="250" step="0.1"></label>@endif<label>Remarque<textarea name="notes" rows="2"></textarea></label><div class="step-buttons"><button class="btn btn-ghost" type="button" data-estimer="{{ route('transformations.etape.estimer',[$transformation,$i]) }}">Estimer carbone et eau avec l’IA</button><button class="btn">Terminer cette étape</button></div><p class="ai-hint small" role="status" aria-live="polite" hidden></p></form>
                @else
                <form method="POST" action="{{ route('transformations.etape.terminer',[$transformation,$i]) }}" class="form step-action" data-step-form>@csrf<input type="hidden" name="estime_par_ia" value="0"><label>Carbone (kg CO₂e)<input type="number" name="co2_kg" min="0" step="0.01" required></label><label>Eau (litres)<input type="number" name="eau_litres" min="0" step="0.01" required></label><div class="step-buttons"><button class="btn btn-ghost" type="button" data-estimer="{{ route('transformations.etape.estimer',[$transformation,$i]) }}">Estimer carbone et eau avec l’IA</button><button class="btn">Terminer cette étape</button></div><p class="ai-hint small" role="status" aria-live="polite" hidden></p></form>
                @endif
            @endif
        </div></li>
    @endforeach
    </ol>
</section>
@if ($transformation->statut === 'en_cours' && $transformation->toutesEtapesCompletes())
<section class="panel"><h2>Finaliser</h2><form method="POST" action="{{ route('transformations.finaliser',$transformation) }}" class="form">@csrf<label>Rendement obtenu (%)<input type="number" name="rendement" min="0.01" max="100" step="0.01" value="{{ $transformation->expected_yield_percent ?? '' }}" required></label><label>Nom du produit final<input name="nom_produit_final" value="{{ $transformation->nom_produit_final }}" required></label><button class="btn">Finaliser la transformation</button></form></section>
@endif
@endsection
