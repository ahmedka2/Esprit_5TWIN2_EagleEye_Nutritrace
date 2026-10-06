@extends('layouts.admin')
@section('title', 'Nouvelle matière première')
@section('content')
@if ($errors->any())<div class="form-error" role="alert">Veuillez corriger les erreurs du formulaire.</div>@endif
<form method="post" action="{{ route($routePrefix.'.matieres-premieres.store') }}">@include('admin.matieres-premieres._form', ['submitLabel' => 'Créer'])</form>
@endsection
