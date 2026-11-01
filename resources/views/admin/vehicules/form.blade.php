@extends($layout)

@section('title', $vehicule->exists ? 'Modifier le véhicule' : 'Ajouter un véhicule')

@section('content')
@php
    preg_match('/^(\d{1,4})\s+TUN\s+(\d{1,4})$/i', (string) $vehicule->immatriculation, $matricule);
@endphp
<form class="form" method="post" action="{{ $vehicule->exists ? route($routePrefix.'.vehicules.update', $vehicule) : route($routePrefix.'.vehicules.store') }}" novalidate>
    @csrf
    @if ($vehicule->exists) @method('PUT') @endif

    @if ($errors->any())
        <p class="form-error" role="alert">Le véhicule n'a pas pu être enregistré : corrigez les champs signalés.</p>
    @endif

    <fieldset class="panel">
        <legend>Véhicule</legend>
        @if (! $vehicule->exists)
            <section class="scan-carte-grise" aria-labelledby="scan-carte-grise-titre">
                <div>
                    <p class="eyebrow">Préremplissage</p>
                    <h2 id="scan-carte-grise-titre">Scanner une carte grise tunisienne</h2>
                    <p class="muted small">La photo est analysée temporairement et n’est jamais enregistrée.</p>
                </div>
                <div class="scan-carte-grise-actions">
                    <label class="btn btn-ghost" for="carte-grise">Choisir une photo</label>
                    <input id="carte-grise" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                    <button class="btn btn-ghost" type="button" id="ouvrir-camera">Utiliser la caméra</button>
                    <button class="btn" type="button" id="analyser-carte-grise">Analyser la photo</button>
                </div>
                <div id="camera-zone" hidden>
                    <video id="camera-video" autoplay playsinline></video>
                    <img id="camera-photo" alt="Photo capturée de la carte grise" hidden>
                    <button class="btn btn-ghost" type="button" id="prendre-photo">Prendre la photo</button>
                </div>
                <p id="scan-carte-grise-message" class="hint" role="status" aria-live="polite"></p>
            </section>
        @endif
        <div class="fields">
            <label>Immatriculation
                <span class="matricule-input">
                    <input name="immatriculation_debut" class="mono" value="{{ old('immatriculation_debut', $matricule[1] ?? '') }}" inputmode="numeric" aria-label="De un à quatre chiffres avant TUN" @error('immatriculation_debut') aria-invalid="true" @enderror>
                    <span class="matricule-country">TUN</span>
                    <input name="immatriculation_fin" class="mono" value="{{ old('immatriculation_fin', $matricule[2] ?? '') }}" inputmode="numeric" aria-label="De un à quatre chiffres après TUN" @error('immatriculation_fin') aria-invalid="true" @enderror>
                </span>
                @error('immatriculation_debut') <span class="field-error">{{ $message }}</span> @enderror
                @error('immatriculation_fin') <span class="field-error">{{ $message }}</span> @enderror
                @error('immatriculation') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Marque
                <input name="marque" value="{{ old('marque', $vehicule->marque) }}" maxlength="80" @error('marque') aria-invalid="true" @enderror>
                @error('marque') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Type
                <select name="type" @error('type') aria-invalid="true" @enderror>
                    <option value="">Choisir un type…</option>
                    @foreach (\App\Enums\TypeVehicule::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $vehicule->type) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Réfrigéré
                <select name="refrigere" @error('refrigere') aria-invalid="true" @enderror>
                    <option value="0" @selected((string) old('refrigere', (int) $vehicule->refrigere) === '0')>Non</option>
                    <option value="1" @selected((string) old('refrigere', (int) $vehicule->refrigere) === '1')>Oui</option>
                </select>
                @error('refrigere') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Émissions (kg CO₂e/km)
                <input name="emission_co2_km" value="{{ old('emission_co2_km', $vehicule->emission_co2_km) }}" class="mono" @error('emission_co2_km') aria-invalid="true" @enderror>
                @error('emission_co2_km') <span class="field-error">{{ $message }}</span> @enderror
            </label>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="{{ $vehicule->exists ? route($routePrefix.'.vehicules.show', $vehicule) : route($routePrefix.'.vehicules.index') }}">Annuler</a>
        <button class="btn" type="submit">{{ $vehicule->exists ? 'Enregistrer les modifications' : 'Ajouter le véhicule' }}</button>
    </div>
</form>
@if (! $vehicule->exists)
<script>
(() => {
    const input = document.getElementById('carte-grise');
    const analyse = document.getElementById('analyser-carte-grise');
    const message = document.getElementById('scan-carte-grise-message');
    const openCamera = document.getElementById('ouvrir-camera');
    const cameraZone = document.getElementById('camera-zone');
    const video = document.getElementById('camera-video');
    const preview = document.getElementById('camera-photo');
    const capture = document.getElementById('prendre-photo');
    let photo = null;
    let stream = null;

    const inform = (text, error = false) => {
        message.textContent = text;
        message.classList.toggle('field-error', error);
    };

    input.addEventListener('change', () => {
        photo = input.files[0] || null;
        inform(photo ? 'Photo prête à être analysée.' : '');
    });

    openCamera.addEventListener('click', async () => {
        try {
            stream?.getTracks().forEach(track => track.stop());
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            video.hidden = false;
            preview.hidden = true;
            preview.removeAttribute('src');
            capture.hidden = false;
            cameraZone.hidden = false;
            inform('Cadrez la carte grise puis prenez la photo.');
        } catch (error) {
            inform('La caméra a été refusée ou est indisponible. Vous pouvez choisir une photo.', true);
        }
    });

    capture.addEventListener('click', () => {
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        canvas.toBlob((blob) => {
            if (!blob) return inform('La photo n’a pas pu être prise. Réessayez.', true);
            photo = new File([blob], 'carte-grise.jpg', { type: 'image/jpeg' });
            preview.src = canvas.toDataURL('image/jpeg', .92);
            stream?.getTracks().forEach(track => track.stop());
            stream = null;
            video.hidden = true;
            capture.hidden = true;
            preview.hidden = false;
            inform('Photo prise et affichée. Vous pouvez lancer l’analyse ou rouvrir la caméra.');
        }, 'image/jpeg', .92);
    });

    analyse.addEventListener('click', async () => {
        if (!photo) return inform('Ajoutez une photo ou utilisez la caméra avant de lancer l’analyse.', true);

        analyse.disabled = true;
        inform('Analyse de la carte grise en cours…');
        const data = new FormData();
        data.append('carte_grise', photo);

        try {
            const response = await fetch('{{ route($routePrefix.'.vehicules.analyse-carte-grise') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: data,
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || result.errors?.carte_grise?.[0]);

            const champsRemplis = [];
            const match = result.immatriculation?.match(/^(\d{1,4})\s+TUN\s+(\d{1,4})$/i);
            if (match) {
                document.querySelector('[name="immatriculation_debut"]').value = match[1];
                document.querySelector('[name="immatriculation_fin"]').value = match[2];
                champsRemplis.push('le matricule');
            }
            if (result.marque) {
                document.querySelector('[name="marque"]').value = result.marque;
                champsRemplis.push('la marque');
            }
            if (result.type) {
                document.querySelector('[name="type"]').value = result.type;
                champsRemplis.push('le type');
            }

            inform(champsRemplis.length
                ? `Analyse terminée : ${champsRemplis.join(' et ')} ${champsRemplis.length > 1 ? 'ont été remplis' : 'a été rempli'}. Vérifiez avant d’enregistrer.`
                : 'Aucune donnée fiable n’a pu être ajoutée. Saisissez les champs manuellement.');
        } catch (error) {
            inform(error.message || 'L’analyse de la carte grise a échoué.', true);
        } finally {
            analyse.disabled = false;
        }
    });
})();
</script>
@endif
@endsection
