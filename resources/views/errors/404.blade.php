@extends('errors::layout')

@section('title', __('Not Found'))
@section('code', '404')
@section('message', __('Oh no, the page you are looking for must be lost in space.'))
@section('additional', __('The page you are looking for might have been removed, had its name changed or is temporarily unavailable.'))
@section('linkURL', url('/'))
@section('linkText', __('← Back to home'))
