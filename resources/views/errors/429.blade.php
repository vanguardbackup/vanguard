@extends('errors::layout')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('message', __('Oops! It seems you\'ve been clicking too fast. Let\'s take a short break.'))
