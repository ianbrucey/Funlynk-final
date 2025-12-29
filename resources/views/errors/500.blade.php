@extends('layouts.error')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Our systems are having a moment.'))
@section('description', __('Something went wrong on our end. We have been notified and are working to stabilize the orbit. Please try again in a bit.'))
