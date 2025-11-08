<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard • {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @filamentStyles
    </head>
    <body class="min-h-screen bg-gray-950 text-gray-100 antialiased">
        <div class="px-4 py-12">
            <div class="mx-auto max-w-5xl">
                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">Dashboard</h1>
                            <p class="mt-2 text-sm text-gray-400">
                                You're signed in. Replace this view with your real experience.
                            </p>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <x-filament::button type="submit" color="danger">
                                Sign out
                            </x-filament::button>
                        </form>
                    </div>
                </x-filament::card>
            </div>
        </div>
        @filamentScripts
    </body>
</html>
