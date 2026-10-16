@extends('errors.layout')

@section('code', '403')
@section('title', 'Access denied')
@section('message', filled($exception->getMessage()) && $exception->getMessage() !== 'This action is unauthorized.'
    ? $exception->getMessage()
    : "You don't have permission to view this page. If you think this is a mistake, contact your administrator.")
