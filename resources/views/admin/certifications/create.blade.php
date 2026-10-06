@extends('layouts.admin')
@section('title', 'Nouvelle certification')
@section('content')<form method="post" action="{{ route('admin.certifications.store') }}">@include('admin.certifications._form', ['submitLabel' => 'Créer'])</form>@endsection
