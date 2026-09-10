@extends('landing.layouts.app')

@section('title', 'Visit Confirmed — Slash Editorial')
@section('description', 'Your visit to the Slash Editorial rehearsal room has been booked.')

@section('content')
    <section class="appointment-confirmation" aria-labelledby="confirmation-title">
        <div class="appointment-confirmation__mark" aria-hidden="true">
            <svg viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="58"></circle>
                <path d="m34 61 17 17 36-39"></path>
            </svg>
        </div>

        <span class="appointment-eyebrow">Booking received</span>
        <h1 id="confirmation-title">Thank you.<br><em>We’ll see you soon.</em></h1>
        <p>Your visit is booked. Keep an eye on your inbox in case we need to contact you before your appointment.</p>
        <a href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}">Back to home <span aria-hidden="true">↗</span></a>
    </section>
@endsection
