@extends('errors::layout')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __('Oh no! Vanguard is currently unavailable.'))
@section('additional', __('We could be down for maintenance or experiencing technical difficulties. We are working hard to fix this.'))

@if (config('app.status_page_url'))
    @section('linkURL', config('app.status_page_url'))
    @section('linkText', __('View the status page →'))
@endif
