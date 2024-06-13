@extends('errors::layout')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('Oops! It seems this page is shy and doesn\'t want to be seen.'))
@section('linkURL', url('/'))
@section('linkText', __('← Back to home'))
