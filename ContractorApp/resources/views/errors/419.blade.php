@extends('errors.layout')

@section('code', '419')
@section('title', 'Session expired')
@section('message', 'Your session expired, so the form couldn't be submitted. Go back, refresh the page and try again.')
@section('actions')<a href="{{ url()->previous() }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Go back</a>@endsection
