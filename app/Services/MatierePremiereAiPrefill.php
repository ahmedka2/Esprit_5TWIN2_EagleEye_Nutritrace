<?php

namespace App\Services;

use App\Models\Categorie;
use App\Models\Certification;
use Illuminate\Support\Facades\Http;

/**
 * Transforme une phrase libre ("j'ai 100 kg de tomates bio") en valeurs
 * de formulaire pour une matière première. Rien n'est enregistré : le
 * producteur relit et valide le formulaire.
 */
class MatierePremiereAiPrefill
{
    /**
     * @return array{name: ?string, quantite: ?float, unite: ?string, price: ?float, categorie_id: ?int, certification_ids: list<int>, notes: list<string>}
     */
    public function extraire(string $prompt): array
    {
        $categories = Categorie::query()->orderBy('name')->get(['id', 'name']);
        $certifications = Certification::query()->orderBy('nom')->get(['id', 'nom', 'type']);

        $contenu = Http::withToken(config('services.ai.key'))->timeout(30)
            ->post(rtrim(config('services.ai.base_url'), '/').'/chat/completions', [
                'model' => config('services.ai.model'),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu remplis un formulaire de matière première agricole à partir d\'une phrase du producteur. '
                        .'Réponds uniquement en JSON : {"name": string|null, "quantite": nombre|null, "unite": "kg"|"litre"|null, "price": nombre|null, '
                        .'"categorie_id": entier|null, "certification_ids": [entiers], "notes": [phrases courtes en français]}. '
                        .'Règles : "name" est le nom du produit au singulier avec majuscule initiale, sans quantité ni label (ex. "Tomate"). '
                        .'Convertis la quantité en kg (tonnes ×1000, grammes ÷1000) pour un solide, ou en litres pour un liquide (lait, huile, jus…). '
                        .'N\'invente JAMAIS une valeur absente de la phrase : mets null (notamment pour le prix). '
                        .'"categorie_id" et "certification_ids" doivent venir exclusivement des listes fournies, sinon null / []. '
                        .'Dans "notes", signale ce qui n\'a pas pu être rempli ou ne correspond à aucune option (ex. un label cité mais absent de la liste).'],
                    ['role' => 'user', 'content' => "Phrase : {$prompt}\n\n"
                        .'Catégories disponibles : '.$categories->map(fn ($c) => "{$c->id}={$c->name}")->implode(', ')."\n"
                        .'Certifications disponibles : '.$certifications->map(fn ($c) => "{$c->id}={$c->nom} (type {$c->type->value})")->implode(', ')],
                ],
            ])->throw()->json('choices.0.message.content');

        $data = json_decode((string) $contenu, true);
        if (! is_array($data)) {
            throw new \UnexpectedValueException('Réponse IA invalide : '.$contenu);
        }

        $quantite = is_numeric($data['quantite'] ?? null) && $data['quantite'] > 0 ? round((float) $data['quantite'], 3) : null;
        $price = is_numeric($data['price'] ?? null) && $data['price'] > 0 ? round((float) $data['price'], 2) : null;
        $name = is_string($data['name'] ?? null) && trim($data['name']) !== '' ? mb_substr(trim($data['name']), 0, 255) : null;

        return [
            'name' => $name,
            'quantite' => $quantite,
            'unite' => in_array($data['unite'] ?? null, ['kg', 'litre'], true) ? $data['unite'] : null,
            'price' => $price,
            'categorie_id' => $categories->contains('id', $data['categorie_id'] ?? null) ? (int) $data['categorie_id'] : null,
            'certification_ids' => collect($data['certification_ids'] ?? [])
                ->filter(fn ($id) => $certifications->contains('id', $id))
                ->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'notes' => collect($data['notes'] ?? [])->filter(fn ($note) => is_string($note))->take(5)->values()->all(),
        ];
    }
}
