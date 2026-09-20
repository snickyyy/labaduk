<?php

namespace Tests\Feature\Landing;

use Tests\TestCase;

class AppointmentPageTest extends TestCase
{
    public function test_appointment_page_renders_contact_fields_date_and_slot_picker(): void
    {
        $apiUrl = route('appointments.store');
        $slotsUrl = route('appointments.slots');
        $confirmationUrl = route('landing.appointments.confirmation', ['locale' => 'en']);

        $this->get('/en/appointment')
            ->assertOk()
            ->assertSee('Book a visit')
            ->assertSee('action="'.$apiUrl.'"', false)
            ->assertSee('data-success-url="'.$confirmationUrl.'"', false)
            ->assertSee('data-slots-url="'.$slotsUrl.'"', false)
            ->assertSee('name="first_name"', false)
            ->assertSee('name="last_name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="phone_number"', false)
            ->assertSee('data-appointment-date', false)
            ->assertSee('data-appointment-slots', false)
            ->assertSee('name="start_at"', false)
            ->assertSee('data-appointment-durations', false)
            ->assertSee('name="duration_minutes"', false)
            ->assertSee('data-appointment-summary', false)
            ->assertDontSee('name="end_at"', false);
    }

    public function test_confirmation_page_is_available(): void
    {
        $this->get('/en/appointment/confirmation')
            ->assertOk()
            ->assertSee('Thank you.')
            ->assertSee('We’ll see you soon.');
    }

    public function test_landing_booking_links_point_to_the_appointment_page(): void
    {
        $appointmentUrl = route('landing.appointments.create', ['locale' => 'en']);

        $this->get('/en')
            ->assertOk()
            ->assertSee('href="'.$appointmentUrl.'"', false);

        $this->get('/en/about')
            ->assertOk()
            ->assertSee('href="'.$appointmentUrl.'"', false);
    }
}
