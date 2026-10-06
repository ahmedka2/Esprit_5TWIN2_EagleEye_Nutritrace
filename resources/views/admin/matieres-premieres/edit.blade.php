@extends('layouts.admin')
@section('title', 'Modifier la matière première')
@section('content')
@if ($errors->any())<div class="form-error" role="alert">Veuillez corriger les erreurs du formulaire.</div>@endif
<form method="post" action="{{ route($routePrefix.'.matieres-premieres.update', $matierePremiere) }}">@method('put') @include('admin.matieres-premieres._form', ['submitLabel' => 'Enregistrer'])</form>
@endsection
