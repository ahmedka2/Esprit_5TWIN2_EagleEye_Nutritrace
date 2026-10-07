@extends('layouts.front')

@section('title', 'Discussion — ' . $produit->nom)

@section('content')
<section class="wrap section" style="max-width: 860px; margin-inline: auto;">
    <div style="margin-bottom: 1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <p class="mono lot-code">
                <a href="{{ route('chat.index') }}">← Retour aux messages</a> ·
                <a href="{{ route('produit', $produit->code) }}">Voir la fiche du lot {{ $produit->code }}</a>
            </p>
            <h1 style="font-size: clamp(1.6rem, 3.5vw, 2.4rem); margin: 0 0 .25rem;">Discussion sur {{ $produit->nom }}</h1>
            <p class="muted" style="margin:0;">Lot {{ $produit->code }} · {{ $produit->transformation?->matierePremiere?->categorie?->name ?? 'Produit' }}</p>
        </div>

        <div>
            <form method="POST" action="{{ route('chat.archive') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="produit_final_id" value="{{ $produit->id }}">
                <input type="hidden" name="interlocutor_id" value="{{ $interlocutor->id }}">
                <button type="submit" class="btn-fav" style="font-size:.8rem; padding:.35rem .75rem; text-decoration:none;">
                    {{ $isArchived ? '📂 Désarchiver' : '📁 Archiver' }}
                </button>
            </form>
        </div>
    </div>

    <div class="chat-card">
        <div class="chat-card-head">
            <div class="chat-user-info">
                <div class="chat-avatar">
                    {{ strtoupper(mb_substr($interlocutor->name, 0, 1)) }}
                </div>
                <div class="chat-user-meta">
                    <h4>{{ $interlocutor->name }}</h4>
                    <p>{{ $interlocutor->role_label }} · {{ $interlocutor->email }}</p>
                </div>
            </div>
            <div>
                <span class="mono" style="font-size: .8rem; color: var(--fern-ink); background: var(--sprout); padding: .25rem .75rem; border-radius: 999px;">
                    En direct avec {{ $interlocutor->isProducteur() || $interlocutor->isTransformateur() ? 'le producteur' : 'le consommateur' }}
                </span>
            </div>
        </div>

        <div id="chat-thread" class="chat-thread">
            @forelse ($messages as $msg)
                @php 
                    $isMine = $msg->sender_id === auth()->id();
                    $motifLabel = $msg->motif ? ($motifs[$msg->motif] ?? null) : null;
                @endphp
                <div class="chat-msg-row {{ $isMine ? 'chat-msg-row--mine' : 'chat-msg-row--theirs' }}" data-msg-id="{{ $msg->id }}" id="msg-row-{{ $msg->id }}">
                    <div class="chat-bubble {{ $msg->deleted_by_sender ? 'chat-bubble--deleted' : '' }}" style="{{ $msg->deleted_by_sender ? 'font-style:italic; opacity:.75;' : '' }}">
                        @if ($motifLabel && !$msg->deleted_by_sender)
                            <div style="font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--fern); margin-bottom:.25rem; display:flex; align-items:center; gap:.3rem;">
                                <span>🏷️</span> {{ $motifLabel }}
                            </div>
                        @endif

                        @if ($msg->deleted_by_sender)
                            <span class="deleted-msg-text">Ce message a été supprimé.</span>
                        @else
                            {{ $msg->message }}
                        @endif
                    </div>
                    <div class="chat-bubble-meta" style="display:flex; align-items:center; gap:.5rem;">
                        <span>{{ $isMine ? 'Vous' : $msg->sender->name }} · {{ $msg->created_at->format('H:i') }}</span>
                        @if ($isMine && !$msg->deleted_by_sender)
                            <button type="button" class="btn-delete-msg" onclick="deleteMessage({{ $msg->id }})" title="Supprimer ce message" style="background:none; border:none; padding:0; cursor:pointer; font-size:.75rem; opacity:.5; transition:opacity .2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.5">
                                🗑️
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="chat-empty-state" style="text-align: center; padding: 3rem 1rem; color: var(--moss);">
                    <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: .5rem;">Aucun message échangé pour l'instant.</p>
                    <p style="font-size: .9rem; margin: 0;">Envoyez le premier message ci-dessous pour démarrer la discussion.</p>
                </div>
            @endforelse
        </div>

        {{-- Barre d'outils au-dessus de la saisie : Motifs & Réponses rapides --}}
        <div style="background:var(--paper); padding:.6rem 1.25rem; border-top: 1px solid var(--line); display:flex; flex-direction:column; gap:.5rem;">
            {{-- Sélection de motif prédéfini --}}
            <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                <span class="mono" style="font-size:.75rem; color:var(--moss);">Sujet :</span>
                @foreach ($motifs as $key => $label)
                    <button type="button" class="motif-pill" data-motif="{{ $key }}" style="background:rgba(0,0,0,.04); border:1px solid transparent; border-radius:999px; padding:.2rem .6rem; font-size:.75rem; cursor:pointer; color:var(--ink); transition:all .15s ease;">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Réponses rapides du producteur si disponibles --}}
            @if ($reponsesRapides->isNotEmpty())
                <div style="display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; margin-top:.2rem;">
                    <span class="mono" style="font-size:.75rem; color:var(--fern); font-weight:600;">⚡ Réponses rapides :</span>
                    <select id="quick-reply-select" style="font-size:.8rem; padding:.25rem .6rem; border-radius:6px; border:1px solid var(--line); background:#fff; cursor:pointer; max-width:280px;">
                        <option value="">Sélectionner un modèle à insérer...</option>
                        @foreach ($reponsesRapides as $qr)
                            <option value="{{ $qr->contenu }}">{{ $qr->titre }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('reponses-rapides.index') }}" class="muted" style="font-size:.75rem; text-decoration:underline;">Gérer mes modèles</a>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('chat.store', $produit->code) }}" class="chat-form" id="chat-form" style="border-top:none; padding-top:.4rem;">
            @csrf
            <input type="hidden" name="receiver_id" value="{{ $interlocutor->id }}">
            <input type="hidden" name="motif" id="selected-motif" value="">
            <textarea name="message" id="chat-input" placeholder="Écrivez votre message à {{ $interlocutor->name }}... (Entrée pour envoyer)" rows="2" required></textarea>
            <button type="submit" class="btn" id="chat-submit-btn">Envoyer</button>
        </form>
    </div>
</section>

<script>
    const csrfToken = "{{ csrf_token() }}";

    function deleteMessage(id) {
        if (!confirm('Voulez-vous supprimer ce message ?')) return;

        fetch(`/messages/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const row = document.getElementById(`msg-row-${id}`);
                if (row) {
                    const bubble = row.querySelector('.chat-bubble');
                    if (bubble) {
                        bubble.innerHTML = '<span class="deleted-msg-text">Ce message a été supprimé.</span>';
                        bubble.style.fontStyle = 'italic';
                        bubble.style.opacity = '0.75';
                    }
                    const delBtn = row.querySelector('.btn-delete-msg');
                    if (delBtn) delBtn.remove();
                }
            }
        })
        .catch(err => console.error('Erreur suppression:', err));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const thread = document.getElementById('chat-thread');
        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-input');
        const submitBtn = document.getElementById('chat-submit-btn');
        const motifInput = document.getElementById('selected-motif');
        const motifPills = document.querySelectorAll('.motif-pill');
        const quickSelect = document.getElementById('quick-reply-select');

        let lastMsgId = {{ $messages->max('id') ?? 0 }};
        const pollUrl = "{{ route('chat.poll', [$produit->code, $interlocutor->id]) }}";

        // Insertion réponse rapide
        if (quickSelect) {
            quickSelect.addEventListener('change', function () {
                if (this.value) {
                    input.value = this.value;
                    input.focus();
                    this.value = '';
                }
            });
        }

        // Sélection de motif
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
                    motifInput.value = this.dataset.motif;
                } else {
                    motifInput.value = '';
                }
            });
        });

        function scrollToBottom() {
            if (thread) {
                thread.scrollTo({ top: thread.scrollHeight, behavior: 'smooth' });
            }
        }

        scrollToBottom();

        function appendBubble(text, senderName, time, isMine, id = null, motifLabel = null, isDeleted = false) {
            const emptyEl = thread.querySelector('.chat-empty-state');
            if (emptyEl) emptyEl.remove();

            const row = document.createElement('div');
            row.className = `chat-msg-row ${isMine ? 'chat-msg-row--mine' : 'chat-msg-row--theirs'}`;
            if (id) {
                row.dataset.msgId = id;
                row.id = `msg-row-${id}`;
            }

            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble';

            if (isDeleted) {
                bubble.innerHTML = '<span class="deleted-msg-text">Ce message a été supprimé.</span>';
                bubble.style.fontStyle = 'italic';
                bubble.style.opacity = '0.75';
            } else {
                if (motifLabel) {
                    const tag = document.createElement('div');
                    tag.style.fontSize = '.72rem';
                    tag.style.fontWeight = '600';
                    tag.style.textTransform = 'uppercase';
                    tag.style.color = 'var(--fern)';
                    tag.style.marginBottom = '.25rem';
                    tag.textContent = '🏷️ ' + motifLabel;
                    bubble.appendChild(tag);
                }
                const msgText = document.createTextNode(text);
                bubble.appendChild(msgText);
            }

            const meta = document.createElement('div');
            meta.className = 'chat-bubble-meta';
            meta.style.display = 'flex';
            meta.style.alignItems = 'center';
            meta.style.gap = '.5rem';
            meta.innerHTML = `<span>${senderName} · ${time}</span>`;

            if (isMine && !isDeleted && id) {
                const delBtn = document.createElement('button');
                delBtn.className = 'btn-delete-msg';
                delBtn.textContent = '🗑️';
                delBtn.title = 'Supprimer ce message';
                delBtn.style.cssText = 'background:none; border:none; padding:0; cursor:pointer; font-size:.75rem; opacity:.5;';
                delBtn.onclick = () => deleteMessage(id);
                meta.appendChild(delBtn);
            }

            row.appendChild(bubble);
            row.appendChild(meta);
            thread.appendChild(row);
        }

        // Envoi instantané AJAX
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
                        appendBubble(res.data.text, 'Vous', res.data.created_at, true, res.data.id, res.data.motif_label, false);
                        if (res.data.id > lastMsgId) {
                            lastMsgId = res.data.id;
                        }
                        scrollToBottom();

                        // Réinitialiser le motif après envoi
                        motifInput.value = '';
                        motifPills.forEach(p => {
                            p.classList.remove('is-selected');
                            p.style.background = 'rgba(0,0,0,.04)';
                            p.style.borderColor = 'transparent';
                            p.style.color = 'var(--ink)';
                        });
                    }
                })
                .catch(err => {
                    if (submitBtn) submitBtn.disabled = false;
                    console.error('Erreur envoi message:', err);
                });
            });

            // Envoi avec Entrée
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit', { cancelable: true }));
                }
            });
        }

        // Polling temps réel
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
                        const existing = document.getElementById(`msg-row-${msg.id}`);
                        if (!existing) {
                            appendBubble(msg.message, msg.sender_name, msg.time, msg.is_mine, msg.id, msg.motif_label, msg.is_deleted);
                            hasNew = true;
                        } else if (msg.is_deleted) {
                            const b = existing.querySelector('.chat-bubble');
                            if (b && !b.classList.contains('chat-bubble--deleted')) {
                                b.innerHTML = '<span class="deleted-msg-text">Ce message a été supprimé.</span>';
                                b.style.fontStyle = 'italic';
                                b.style.opacity = '0.75';
                            }
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
            .catch(err => console.debug('Polling update:', err));
        }

        setInterval(pollMessages, 2500);

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                pollMessages();
            }
        });
    });
</script>
@endsection
