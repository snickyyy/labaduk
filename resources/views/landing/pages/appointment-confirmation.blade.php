@extends('landing.layouts.app')

@section('title', __('confirmationPage.meta.title'))
@section('description', __('confirmationPage.meta.description'))

@section('content')
    <section class="appointment-confirmation" aria-labelledby="confirmation-title">
        <div class="appointment-confirmation__mark" aria-hidden="true">
            <svg viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="58"></circle>
                <path d="m34 61 17 17 36-39"></path>
            </svg>
        </div>

        <span class="appointment-eyebrow">{{ __('confirmationPage.eyebrow') }}</span>
        <h1 id="confirmation-title">{{ __('confirmationPage.heading') }}<br><em>{{ __('confirmationPage.heading_emphasis') }}</em></h1>
        <p>{{ __('confirmationPage.description') }}</p>
        <a href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}">{{ __('confirmationPage.back_home') }} <span aria-hidden="true">↗</span></a>
    </section>
@endsection
