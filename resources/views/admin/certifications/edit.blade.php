@extends('layouts.admin')
@section('title', 'Modifier la certification')
@section('content')<form method="post" action="{{ route('admin.certifications.update', $certification) }}">@method('put') @include('admin.certifications._form', ['submitLabel' => 'Enregistrer'])</form>@endsection
