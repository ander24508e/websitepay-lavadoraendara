@extends('layouts.admin')

@section('title', 'Nuevo rol')

@push('styles')
    @vite('resources/scss/admin/roles.scss')
@endpush

@section('content')
    @include('admin.roles._form')
@endsection
