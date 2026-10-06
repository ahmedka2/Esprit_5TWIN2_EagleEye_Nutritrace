@extends('layouts.admin')
@section('title', 'Modifier la catégorie')
@section('content')
<form method="post" action="{{ route('admin.categories.update', $categorie) }}">@method('put') @include('admin.categories._form', ['submitLabel' => 'Enregistrer'])</form>
@endsection
