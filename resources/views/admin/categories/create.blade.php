@extends('layouts.admin')
@section('title', 'Nouvelle catégorie')
@section('content')
<form method="post" action="{{ route('admin.categories.store') }}">@include('admin.categories._form', ['submitLabel' => 'Créer'])</form>
@endsection
