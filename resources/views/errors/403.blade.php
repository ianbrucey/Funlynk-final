@extends('layouts.error')

@section('title', __('Access Denied'))
@section('code', '403')
@section('message', __('This sector is restricted.'))
@section('description', __('You do not have the required clearance to access this activity. If you think this is a mistake, please check your permissions.'))
