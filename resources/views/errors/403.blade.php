@extends('errors::layout')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: __("Oops! It seems you have wandered into a restricted zone.")))
@section('linkURL', url('/'))
@section('linkText', __('← Back to home'))
