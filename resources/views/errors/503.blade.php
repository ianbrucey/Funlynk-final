@extends('layouts.error')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __('Undergoing Refurbishment.'))
@section('description', __('We are currently performing some scheduled maintenance to improve your experience. We will be back online shortly!'))
