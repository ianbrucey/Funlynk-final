@extends('layouts.error')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('Session Timed Out.'))
@section('description', __('It looks like you were inactive for too long and your session has expired. Please refresh the page and try again.'))
