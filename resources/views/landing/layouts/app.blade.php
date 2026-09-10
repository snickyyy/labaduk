<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('landing.meta.title') }}</title>
        <meta name="description" content="{{ __('landing.meta.description') }}">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
        <meta property="og:title" content="{{ __('landing.meta.title') }}">
        <meta property="og:description" content="{{ __('landing.meta.description') }}">
        <meta property="og:url" content="{{ route('landing.home', ['locale' => app()->getLocale()]) }}">
        <link rel="canonical" href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}">

        @foreach (config('landing.supported_locales') as $locale)
            <link rel="alternate" hreflang="{{ $locale }}" href="{{ route('landing.home', ['locale' => $locale]) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ route('landing.home', ['locale' => config('landing.default_locale')]) }}">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite('resources/css/landing/index.css')
    </head>
    <body>
        <x-landing.navigation />

        <main>
            @yield('content')
        </main>

        <x-landing.footer />
    </body>
</html>
