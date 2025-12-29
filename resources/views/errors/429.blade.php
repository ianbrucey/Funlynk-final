@extends('layouts.error')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('message', __('Slow down, Explorer!'))
@section('description', __('You are moving a bit too fast for our systems. Please take a small break and try again in a few moments.'))
