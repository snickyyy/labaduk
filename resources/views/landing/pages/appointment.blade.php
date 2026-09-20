@extends('landing.layouts.app')

@section('title', 'Book a Visit — Slash Editorial')
@section('description', 'Choose a date and time to visit the Slash Editorial rehearsal room.')

@section('content')
    <section class="appointment-page" aria-labelledby="appointment-title">
        <header class="appointment-intro">
            <span class="appointment-eyebrow">Rehearsal room · Berlin</span>
            <h1 id="appointment-title">Book<br><em>a visit</em></h1>
            <p>Choose a time, leave your contact details and we’ll reserve your visit.</p>

            <div class="appointment-intro__note">
                <span aria-hidden="true">01</span>
                <p>Choose a weekday, a start time and a duration. Visits are available from 14:00 to 18:00 Berlin time.</p>
            </div>
        </header>

        <div class="appointment-form-panel">
            <form
                class="appointment-form"
                action="{{ route('appointments.store') }}"
                method="post"
                data-appointment-form
                data-success-url="{{ route('landing.appointments.confirmation', ['locale' => app()->getLocale()]) }}"
                data-slots-url="{{ route('appointments.slots') }}"
                data-today="{{ now('Europe/Berlin')->toDateString() }}"
            >
                <div class="appointment-form__heading">
                    <span>Your details</span>
                    <span>All fields are required</span>
                </div>

                <div class="appointment-form__grid">
                    <div class="appointment-field">
                        <label for="first_name">First name</label>
                        <input id="first_name" name="first_name" type="text" autocomplete="given-name" maxlength="255" required aria-describedby="first_name_error">
                        <p id="first_name_error" class="appointment-field__error" data-field-error="first_name"></p>
                    </div>

                    <div class="appointment-field">
                        <label for="last_name">Last name</label>
                        <input id="last_name" name="last_name" type="text" autocomplete="family-name" maxlength="255" required aria-describedby="last_name_error">
                        <p id="last_name_error" class="appointment-field__error" data-field-error="last_name"></p>
                    </div>

                    <div class="appointment-field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" maxlength="255" inputmode="email" required aria-describedby="email_error">
                        <p id="email_error" class="appointment-field__error" data-field-error="email"></p>
                    </div>

                    <div class="appointment-field">
                        <label for="phone_number">Phone number</label>
                        <input id="phone_number" name="phone_number" type="tel" autocomplete="tel" maxlength="255" inputmode="tel" placeholder="+49 123 456789" required aria-describedby="phone_number_error" data-phone-input>
                        <p id="phone_number_error" class="appointment-field__error" data-field-error="phone_number"></p>
                    </div>

                    <div class="appointment-field appointment-field--full">
                        <label for="appointment_date">Date <span>Europe/Berlin</span></label>
                        <input id="appointment_date" type="date" required data-appointment-date aria-describedby="start_at_error">
                    </div>

                    <div class="appointment-field appointment-field--full">
                        <label id="appointment-slots-label">Start time <span>30-minute steps</span></label>
                        <div class="appointment-slots" data-appointment-slots aria-labelledby="appointment-slots-label" aria-live="polite">
                            <p class="appointment-slots__message">Choose a date to see available times.</p>
                        </div>
                        <input id="start_at" name="start_at" type="hidden" aria-describedby="start_at_error">
                        <p id="start_at_error" class="appointment-field__error" data-field-error="start_at"></p>
                    </div>

                    <div class="appointment-field appointment-field--full">
                        <label id="appointment-durations-label">Duration <span>30–120 minutes</span></label>
                        <div class="appointment-slots appointment-durations" data-appointment-durations aria-labelledby="appointment-durations-label" aria-live="polite">
                            <p class="appointment-slots__message">Choose a start time first.</p>
                        </div>
                        <input id="duration_minutes" name="duration_minutes" type="hidden" aria-describedby="duration_minutes_error">
                        <p id="duration_minutes_error" class="appointment-field__error" data-field-error="duration_minutes"></p>
                    </div>

                    <div class="appointment-field appointment-field--full appointment-summary" data-appointment-summary hidden>
                        <p class="appointment-summary__title">Your visit</p>
                        <dl>
                            <div><dt>Date</dt><dd data-summary-date></dd></div>
                            <div><dt>Start</dt><dd data-summary-start></dd></div>
                            <div><dt>Duration</dt><dd data-summary-duration></dd></div>
                            <div><dt>End</dt><dd data-summary-end></dd></div>
                        </dl>
                    </div>
                </div>

                <div class="appointment-form__submit">
                    <p data-form-message role="status" aria-live="polite"></p>
                    <button type="submit">Confirm visit <span aria-hidden="true">↗</span></button>
                </div>
            </form>
        </div>
    </section>
@endsection
