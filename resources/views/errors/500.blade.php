@extends('errors::layout')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Oh dear! Our server seems to be having a bad day. Please try again later.'))
