@extends('layouts.admin')
@section('title', 'Certifications')
@section('actions')
    @can('create', App\Models\Certification::class)<a class="btn" href="{{ route('admin.certifications.create') }}">Ajouter une certification</a>@endcan
@endsection
@section('content')
@if ($errors->any())<div class="flash" role="alert">{{ $errors->first() }}</div>@endif
<div class="panel panel-flush"><table class="table"><thead><tr><th>Nom</th><th>Type</th><th>Organisme</th><th>Matières</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody>
@forelse ($certifications as $certification)<tr><td><strong>{{ $certification->nom }}</strong></td><td>{{ $certification->type->label() }}</td><td>{{ $certification->organisme }}</td><td>{{ $certification->matieres_premieres_count }}</td><td class="row-actions"><a href="{{ route('admin.certifications.show', $certification) }}">Voir</a> @can('update', $certification)<a href="{{ route('admin.certifications.edit', $certification) }}">Modifier</a>@endcan @can('delete', $certification)<form method="post" action="{{ route('admin.certifications.destroy', $certification) }}" style="display:inline">@csrf @method('delete')<button class="link-button" type="submit">Supprimer</button></form>@endcan</td></tr>
@empty<tr><td colspan="5" class="empty">Aucune certification.</td></tr>@endforelse
</tbody></table></div>{{ $certifications->links() }}
@endsection
