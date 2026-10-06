@extends('layouts.admin')
@section('title', $certification->nom)
@section('content')<div class="panel"><p><strong>Nom :</strong> {{ $certification->nom }}</p><p><strong>Type :</strong> {{ $certification->type->label() }}</p><p><strong>Organisme :</strong> {{ $certification->organisme }}</p><a class="btn btn-ghost" href="{{ route('admin.certifications.index') }}">Retour</a></div>@endsection
