<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', __('landing.meta.title'))</title>
        <meta name="description" content="@yield('description', __('landing.meta.description'))">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
        <meta property="og:title" content="@yield('title', __('landing.meta.title'))">
        <meta property="og:description" content="@yield('description', __('landing.meta.description'))">
        <meta property="og:url" content="{{ url()->current() }}">
        <link rel="canonical" href="{{ url()->current() }}">

        @foreach (config('landing.supported_locales') as $locale)
            <link rel="alternate" hreflang="{{ $locale }}" href="{{ route(request()->route()->getName(), [...request()->route()->parameters(), 'locale' => $locale]) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ route(request()->route()->getName(), [...request()->route()->parameters(), 'locale' => config('landing.default_locale')]) }}">

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
