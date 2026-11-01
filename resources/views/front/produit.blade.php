@extends('layouts.front')

@section('title', $p['nom'])

@section('content')
@php
    $declares = array_filter($p['certifs'], fn ($c) => $c['statut'] !== 'vérifié');
    $t = $lot->transformation;
    $empreinte = $lot->empreinte();
@endphp

<section class="wrap product-head">
    @if ($lot->hasAlerteSanitaireActive())
        <div role="alert" style="background:#fef2f2; border:2px solid #dc2626; color:#991b1b; border-radius:12px; padding:1.15rem 1.4rem; margin-bottom:1.5rem; display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
            <span style="font-size:1.85rem; line-height:1;">🚨</span>
            <div style="flex:1; min-width:240px;">
                <strong style="font-size:1.05rem; display:block; margin-bottom:.25rem;">Alerte Sanitaire Active sur ce lot ({{ $lot->code }})</strong>
                <p style="margin:0; font-size:.9rem;">
                    Ce lot fait actuellement l'objet d'un rappel officiel ou d'un cumul critique de signalements qualité en cours d'analyse. Par mesure de précaution, vérifiez le numéro de lot avant consommation.
                </p>
            </div>
        </div>
    @endif

    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1.5rem; flex-wrap:wrap;">
        <div>
            <p class="mono lot-code"><a href="{{ route('home') }}">Produits</a> / Lot {{ $p['lot'] }}</p>
            <h1>{{ $p['nom'] }}</h1>
            <p class="lede">{{ $p['categorie'] }} · origine {{ $p['origine'] }}</p>
        </div>
        <div style="margin-top: .5rem; display:flex; align-items:center; gap:.65rem; flex-wrap:wrap;">
            @auth
                @php $isFav = $lot->isFavoritedBy(auth()->user()); @endphp
                <form method="POST" action="{{ route('favoris.toggle', $lot->code) }}">
                    @csrf
                    <button type="submit" class="btn-fav {{ $isFav ? 'btn-fav--active' : '' }}">
                        <span aria-hidden="true">{{ $isFav ? '★' : '☆' }}</span>
                        <span>{{ $isFav ? 'Dans mes favoris' : 'Ajouter aux favoris' }}</span>
                    </button>
                </form>
                <a href="{{ route('signalements-qualite.create', ['produit_final_id' => $lot->id, 'code_lot_saisi' => $lot->code]) }}"
                   class="btn-fav" style="text-decoration:none; border-color:#f87171; color:#dc2626;">
                    <span aria-hidden="true">⚠️</span>
                    <span>Signaler une anomalie</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-fav" title="Connectez-vous pour ajouter aux favoris">
                    <span aria-hidden="true">☆</span>
                    <span>Ajouter aux favoris</span>
                </a>
                <a href="{{ route('signalements-qualite.create', ['produit_final_id' => $lot->id, 'code_lot_saisi' => $lot->code]) }}"
                   class="btn-fav" style="text-decoration:none; border-color:#f87171; color:#dc2626;">
                    <span aria-hidden="true">⚠️</span>
                    <span>Signaler une anomalie</span>
                </a>
            @endauth
        </div>
    </div>

    @if ($declares)
        <p class="alert" role="note">
            <strong>Label non vérifié.</strong>
            {{ implode(', ', array_column($declares, 'type')) }} : le vendeur l'affiche, mais aucun certificat n'a été transmis à NutriTrace.
        </p>
    @endif
</section>

<section class="wrap section">
    <h2>Parcours</h2>
    <x-trace-route :etapes="$p['etapes']" />
</section>

<section class="wrap section split">
    <div>
        <h2>Empreinte environnementale</h2>
        @if ($empreinte)
            <dl class="read-list">
                <div><dt>Éco-score</dt><dd><x-eco-score :score="$empreinte['eco_score']" /></dd></div>
                <div><dt>Carbone</dt><dd><span class="mono">{{ number_format($empreinte['co2_par_unite'], 2, ',', ' ') }}</span> kg CO₂e par {{ $lot->unite }}</dd></div>
                <div><dt>Eau</dt><dd><span class="mono">{{ number_format($empreinte['eau_litres'], 0, ',', ' ') }}</span> litres pour le lot</dd></div>
            </dl>
            <table class="co2-table">
                <thead><tr><th scope="col">Étape</th><th scope="col" class="num">kg CO₂e</th><th scope="col" class="num">Eau (L)</th><th scope="col" class="num">Part CO₂</th></tr></thead>
                <tbody>
                @foreach ($t->empreintes as $em)
                    <tr>
                        <td>{{ \App\Models\Transformation::labelEtape($t->etapes[$em->etape]['nom'] ?? '') }} @if ($em->estime_par_ia) <span class="ai-tag" title="Valeur estimée par IA puis validée par le transformateur, non mesurée">estimation IA</span> @endif</td>
                        <td class="num mono">{{ number_format($em->co2_kg, 2, ',', ' ') }}</td>
                        <td class="num mono">{{ number_format($em->eau_litres, 0, ',', ' ') }}</td>
                        <td class="num mono">{{ $empreinte['co2_kg'] ? round($em->co2_kg / $empreinte['co2_kg'] * 100) : 0 }} %</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr><th scope="row">Total du lot</th><td class="num mono">{{ number_format($empreinte['co2_kg'], 2, ',', ' ') }}</td><td class="num mono">{{ number_format($empreinte['eau_litres'], 0, ',', ' ') }}</td><td class="num mono">100 %</td></tr></tfoot>
            </table>
        @else
            <p class="muted">Empreinte pas encore renseignée pour ce lot.</p>
        @endif

    </div>

    <div>
        <h2>Certifications</h2>
        <ul class="cert-list">
            @foreach ($p['certifs'] as $c)
                <li>
                    <x-cert-badge :cert="$c" />
                    <span class="muted">{{ $c['organisme'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

<section class="wrap section" id="chat">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
        <div>
            <h2>Poser une question au producteur</h2>
            <p class="lede">Échangez directement avec l'artisan ou le producteur responsable de ce lot.</p>
        </div>
        @if ($creator)
            <div style="display:flex; align-items:center; gap:.75rem; background:var(--paper); padding:.5rem 1rem; border:1px solid var(--line); border-radius:999px;">
                <span class="mono" style="font-size:.8rem; color:var(--moss);">Producteur :</span>
                <strong>{{ $creator->name }}</strong>
                <span class="tag" style="font-size:.7rem;">{{ $creator->role_label }}</span>
            </div>
        @endif
    </div>

    @auth
        @if ($creator && auth()->id() === $creator->id)
            <div class="empty" style="max-width:none;">
                <h3>Vous êtes le producteur / créateur de ce lot</h3>
                <p class="muted" style="margin-top:.5rem; margin-bottom:1.25rem;">
                    Consultez votre messagerie pour lire et répondre aux questions envoyées par les consommateurs.
                </p>
                <a href="{{ route('chat.index') }}" class="btn" style="text-decoration:none; display:inline-block;">
                    Accéder à ma messagerie
                </a>
            </div>
        @elseif ($creator)
            <div class="chat-card">
                <div class="chat-card-head">
                    <div class="chat-user-info">
                        <div class="chat-avatar">
                            {{ strtoupper(mb_substr($creator->name, 0, 1)) }}
                        </div>
                        <div class="chat-user-meta">
                            <h4>{{ $creator->name }}</h4>
                            <p style="margin:0; font-size:.82rem;">
                                <span class="tag" style="font-size:.7rem; margin-right:.35rem;">{{ $creator->role_label }}</span>
                                Responsable & créateur de ce lot
                            </p>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('chat.index') }}" class="mono" style="font-size:.8rem; text-decoration:none;">
                            Toutes mes discussions →
                        </a>
                    </div>
                </div>

                <div id="product-chat-thread" class="chat-thread" style="max-height:320px;">
                    @forelse ($chatMessages as $msg)
                        @php $isMine = $msg->sender_id === auth()->id(); @endphp
                        <div class="chat-msg-row {{ $isMine ? 'chat-msg-row--mine' : 'chat-msg-row--theirs' }}" data-msg-id="{{ $msg->id }}">
                            <div class="chat-bubble">
                                {{ $msg->message }}
                            </div>
                            <div class="chat-bubble-meta">
                                {{ $isMine ? 'Vous' : $msg->sender->name . ' (' . $msg->sender->role_label . ')' }} · {{ $msg->created_at->format('H:i') }}
                            </div>
                        </div>
                    @empty
                        <div class="chat-empty-state" style="text-align:center; padding:2rem 1rem; color:var(--moss);">
                            <p style="font-size:1rem; font-weight:600; margin-bottom:.35rem;">Aucun message pour ce lot</p>
                            <p style="font-size:.88rem; margin:0;">Posez directement votre question à <strong>{{ $creator->name }}</strong> ci-dessous.</p>
                        </div>
                    @endforelse
                </div>

                <div style="background:var(--paper); padding:.6rem 1.5rem 0; border-top: 1px solid var(--line);">
                    <p class="mono" style="margin:0; font-size:.78rem; color:var(--moss); display:flex; align-items:center; gap:.4rem;">
                        <span style="color:var(--fern);">✉ Destinataire :</span>
                        <strong style="color:var(--forest);">{{ $creator->name }}</strong>
                        <span>({{ $creator->role_label }})</span>
                        <span id="product-chat-status" style="margin-left:auto; font-size:.72rem; color:var(--fern); display:flex; align-items:center; gap:.3rem;">
                            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:var(--fern); animation: pulse 2s infinite;"></span>
                            En direct
                        </span>
                    </p>
                </div>

                <form method="POST" action="{{ route('chat.store', $lot->code) }}" class="chat-form" id="product-chat-form" style="border-top:none; padding-top:.4rem; flex-direction:column; gap:.5rem;">
                    @csrf
                    <input type="hidden" name="motif" id="prod-selected-motif" value="">
                    <div style="display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; padding:0 .25rem;">
                        <span class="mono" style="font-size:.72rem; color:var(--moss);">Sujet :</span>
                        @foreach (\App\Models\Message::MOTIFS as $mKey => $mLabel)
                            <button type="button" class="prod-motif-pill" data-motif="{{ $mKey }}" style="background:rgba(0,0,0,.04); border:1px solid transparent; border-radius:999px; padding:.15rem .5rem; font-size:.72rem; cursor:pointer; color:var(--ink); transition:all .15s ease;">
                                {{ $mLabel }}
                            </button>
                        @endforeach
                    </div>
                    <div style="display:flex; gap:.75rem; width:100%;">
                        <textarea name="message" id="product-chat-input" placeholder="Écrivez votre message à {{ $creator->name }}... (Entrée pour envoyer)" rows="2" required style="flex:1;"></textarea>
                        <button type="submit" class="btn" id="product-chat-submit">Envoyer</button>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const thread = document.getElementById('product-chat-thread');
                        const form = document.getElementById('product-chat-form');
                        const input = document.getElementById('product-chat-input');
                        const submitBtn = document.getElementById('product-chat-submit');

                        let lastMsgId = {{ $chatMessages->max('id') ?? 0 }};
                        const pollUrl = "{{ route('chat.poll', [$lot->code, $creator->id]) }}";

                        function scrollToBottom() {
                            if (thread) {
                                thread.scrollTo({ top: thread.scrollHeight, behavior: 'smooth' });
                            }
                        }

                        const motifInput = document.getElementById('prod-selected-motif');
                        const motifPills = document.querySelectorAll('.prod-motif-pill');

                        motifPills.forEach(pill => {
                            pill.addEventListener('click', function () {
                                const isSelected = this.classList.contains('is-selected');
                                motifPills.forEach(p => {
                                    p.classList.remove('is-selected');
                                    p.style.background = 'rgba(0,0,0,.04)';
                                    p.style.borderColor = 'transparent';
                                    p.style.color = 'var(--ink)';
                                });

                                if (!isSelected) {
                                    this.classList.add('is-selected');
                                    this.style.background = 'rgba(45,90,61,.15)';
                                    this.style.borderColor = 'var(--fern)';
                                    this.style.color = 'var(--fern)';
                                    if (motifInput) motifInput.value = this.dataset.motif;
                                } else {
                                    if (motifInput) motifInput.value = '';
                                }
                            });
                        });

                        scrollToBottom();

                        function appendBubble(text, senderName, time, isMine, id = null) {
                            const emptyEl = thread.querySelector('.chat-empty-state');
                            if (emptyEl) emptyEl.remove();

                            const row = document.createElement('div');
                            row.className = `chat-msg-row ${isMine ? 'chat-msg-row--mine' : 'chat-msg-row--theirs'}`;
                            if (id) row.dataset.msgId = id;

                            const bubble = document.createElement('div');
                            bubble.className = 'chat-bubble';
                            bubble.textContent = text;

                            const meta = document.createElement('div');
                            meta.className = 'chat-bubble-meta';
                            meta.textContent = `${senderName} · ${time}`;

                            row.appendChild(bubble);
                            row.appendChild(meta);
                            thread.appendChild(row);
                        }

                        if (form && input) {
                            form.addEventListener('submit', function (e) {
                                e.preventDefault();
                                e.stopPropagation();

                                const text = input.value.trim();
                                if (!text) return;

                                if (submitBtn) submitBtn.disabled = true;

                                const formData = new FormData(form);

                                fetch(form.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    }
                                })
                                .then(res => res.json())
                                .then(res => {
                                    if (submitBtn) submitBtn.disabled = false;
                                    if (res.success && res.data) {
                                        input.value = '';
                                        input.focus();
                                        appendBubble(res.data.text, 'Vous', res.data.created_at, true, res.data.id);
                                        if (res.data.id > lastMsgId) {
                                            lastMsgId = res.data.id;
                                        }
                                        scrollToBottom();
                                    }
                                })
                                .catch(err => {
                                    if (submitBtn) submitBtn.disabled = false;
                                    console.error('Erreur:', err);
                                });
                            });

                            input.addEventListener('keydown', function (e) {
                                if (e.key === 'Enter' && !e.shiftKey) {
                                    e.preventDefault();
                                    form.dispatchEvent(new Event('submit', { cancelable: true }));
                                }
                            });
                        }

                        function pollMessages() {
                            if (document.hidden) return;

                            fetch(`${pollUrl}?last_id=${lastMsgId}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success && res.messages && res.messages.length > 0) {
                                    let hasNew = false;
                                    res.messages.forEach(msg => {
                                        if (!document.querySelector(`[data-msg-id="${msg.id}"]`)) {
                                            appendBubble(msg.message, msg.sender_name, msg.time, msg.is_mine, msg.id);
                                            hasNew = true;
                                        }
                                    });
                                    if (res.last_id > lastMsgId) {
                                        lastMsgId = res.last_id;
                                    }
                                    if (hasNew) {
                                        scrollToBottom();
                                    }
                                }
                            })
                            .catch(err => console.debug('Polling...', err));
                        }

                        setInterval(pollMessages, 3000);

                        document.addEventListener('visibilitychange', function () {
                            if (!document.hidden) {
                                pollMessages();
                            }
                        });
                    });
                </script>
            </div>
        @else
            <p class="muted">Le producteur de ce lot n'est pas encore joignable par messagerie.</p>
        @endif
    @else
        <div class="empty" style="max-width:none; text-align:center;">
            <h3>Vous avez une question sur ce produit ?</h3>
            <p class="muted" style="margin-top:.5rem; margin-bottom:1.25rem;">
                Connectez-vous pour discuter directement avec {{ $creator?->name ?? 'le producteur' }} et lui poser vos questions sur ce lot.
            </p>
            <a href="{{ route('login') }}" class="btn" style="text-decoration:none; display:inline-block;">
                Se connecter pour discuter
            </a>
        </div>
    @endauth
</section>

<section class="wrap section" id="signaler">
    <h2>Signaler un problème</h2>
    @if (session('status') && !str_contains(session('status'), 'favori'))
        <p class="alert" role="status" style="border-left: 4px solid var(--fern); background: color-mix(in srgb, var(--sprout) 20%, var(--paper));">{{ session('status') }}</p>
    @endif
    <form method="post" action="{{ route('rates.store', $lot) }}#signaler" class="rate-form">
        @csrf
        <label>Motif
            <select name="motif" required>
                @foreach (\App\Models\Rate::MOTIFS as $m)
                    <option @selected(old('motif') === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </label>
        <label>Description
            <textarea name="description" rows="4" maxlength="2000" required>{{ old('description') }}</textarea>
        </label>
        @error('description') <p class="alert" role="alert">{{ $message }}</p> @enderror
        <button class="btn">Envoyer</button>
    </form>
</section>
@endsection
