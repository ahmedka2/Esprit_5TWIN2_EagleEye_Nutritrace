@extends('layouts.admin')
@section('title', $categorie->name)
@section('content')
<div class="panel"><p><strong>Nom :</strong> {{ $categorie->name }}</p><p><strong>Matières premières :</strong> {{ $categorie->matieresPremieres()->count() }}</p><a class="btn btn-ghost" href="{{ route('admin.categories.index') }}">Retour</a></div>
@endsection
