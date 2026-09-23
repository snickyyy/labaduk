@extends('landing.layouts.app')

@section('title', __('appointmentPage.meta.title'))
@section('description', __('appointmentPage.meta.description'))

@section('content')
    <section class="appointment-page" aria-labelledby="appointment-title">
        <header class="appointment-intro">
            <span class="appointment-eyebrow">{{ __('appointmentPage.hero.eyebrow') }}</span>
            <h1 id="appointment-title">{{ __('appointmentPage.hero.title_line_one') }}<br><em>{{ __('appointmentPage.hero.title_line_two') }}</em></h1>
            <p>{{ __('appointmentPage.hero.description') }}</p>

            <div class="appointment-intro__note">
                <span aria-hidden="true">01</span>
                <p>{{ __('appointmentPage.hero.note') }}</p>
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
                    <span>{{ __('appointmentPage.form.details') }}</span>
                    <span>{{ __('appointmentPage.form.required') }}</span>
                </div>

                <div class="appointment-form__grid">
                    <div class="appointment-field">
                        <label for="first_name">{{ __('appointmentPage.form.first_name') }}</label>
                        <input id="first_name" name="first_name" type="text" autocomplete="given-name" maxlength="255" required aria-describedby="first_name_error">
                        <p id="first_name_error" class="appointment-field__error" data-field-error="first_name"></p>
                    </div>

                    <div class="appointment-field">
                        <label for="last_name">{{ __('appointmentPage.form.last_name') }}</label>
                        <input id="last_name" name="last_name" type="text" autocomplete="family-name" maxlength="255" required aria-describedby="last_name_error">
                        <p id="last_name_error" class="appointment-field__error" data-field-error="last_name"></p>
                    </div>

                    <div class="appointment-field">
                        <label for="email">{{ __('appointmentPage.form.email') }}</label>
                        <input id="email" name="email" type="email" autocomplete="email" maxlength="255" inputmode="email" required aria-describedby="email_error">
                        <p id="email_error" class="appointment-field__error" data-field-error="email"></p>
                    </div>

                    <div class="appointment-field">
                        <label for="phone_number">{{ __('appointmentPage.form.phone_number') }}</label>
                        <input id="phone_number" name="phone_number" type="tel" autocomplete="tel" maxlength="255" inputmode="tel" placeholder="{{ __('appointmentPage.form.phone_placeholder') }}" required aria-describedby="phone_number_error" data-phone-input>
                        <p id="phone_number_error" class="appointment-field__error" data-field-error="phone_number"></p>
                    </div>

                    <div class="appointment-field appointment-field--full">
                        <label for="appointment_date">{{ __('appointmentPage.form.date') }} <span>{{ __('appointmentPage.form.timezone') }}</span></label>
                        <input id="appointment_date" type="date" required data-appointment-date aria-describedby="start_at_error">
                    </div>

                    <div class="appointment-field appointment-field--full">
                        <label id="appointment-slots-label">{{ __('appointmentPage.form.start_time') }} <span>{{ __('appointmentPage.form.time_steps') }}</span></label>
                        <div class="appointment-slots" data-appointment-slots aria-labelledby="appointment-slots-label" aria-live="polite">
                            <p class="appointment-slots__message">{{ __('appointmentPage.form.choose_date') }}</p>
                        </div>
                        <input id="start_at" name="start_at" type="hidden" aria-describedby="start_at_error">
                        <p id="start_at_error" class="appointment-field__error" data-field-error="start_at"></p>
                    </div>

                    <div class="appointment-field appointment-field--full">
                        <label id="appointment-durations-label">{{ __('appointmentPage.form.duration') }} <span>{{ __('appointmentPage.form.duration_range') }}</span></label>
                        <div class="appointment-slots appointment-durations" data-appointment-durations aria-labelledby="appointment-durations-label" aria-live="polite">
                            <p class="appointment-slots__message">{{ __('appointmentPage.form.choose_start') }}</p>
                        </div>
                        <input id="duration_minutes" name="duration_minutes" type="hidden" aria-describedby="duration_minutes_error">
                        <p id="duration_minutes_error" class="appointment-field__error" data-field-error="duration_minutes"></p>
                    </div>

                    <div class="appointment-field appointment-field--full appointment-summary" data-appointment-summary hidden>
                        <p class="appointment-summary__title">{{ __('appointmentPage.form.summary') }}</p>
                        <dl>
                            <div><dt>{{ __('appointmentPage.form.date') }}</dt><dd data-summary-date></dd></div>
                            <div><dt>{{ __('appointmentPage.form.start_time') }}</dt><dd data-summary-start></dd></div>
                            <div><dt>{{ __('appointmentPage.form.duration') }}</dt><dd data-summary-duration></dd></div>
                            <div><dt>{{ __('appointmentPage.form.end') }}</dt><dd data-summary-end></dd></div>
                        </dl>
                    </div>
                </div>

                <div class="appointment-form__submit">
                    <p data-form-message role="status" aria-live="polite"></p>
                    <button type="submit">{{ __('appointmentPage.form.confirm') }} <span aria-hidden="true">↗</span></button>
                </div>
            </form>
        </div>
    </section>
@endsection
