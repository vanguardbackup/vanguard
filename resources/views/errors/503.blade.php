@extends('errors::layout')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __('Oh no! Vanguard is currently unavailable.'))
@section('additional', __('We could be down for maintenance or experiencing technical difficulties. We are working hard to fix this.'))
@section('linkURL', url('https://status.vanguard.larsens.dev'))
@section('linkText', __('View the status page →'))
