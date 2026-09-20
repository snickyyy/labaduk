const appointmentForm = document.querySelector('[data-appointment-form]');

if (appointmentForm instanceof HTMLFormElement) {
    const submitButton = appointmentForm.querySelector('[type="submit"]');
    const formMessage = appointmentForm.querySelector('[data-form-message]');
    const dateInput = appointmentForm.querySelector('[data-appointment-date]');
    const slotsContainer = appointmentForm.querySelector('[data-appointment-slots]');
    const startInput = appointmentForm.elements.namedItem('start_at');
    const phoneInput = appointmentForm.querySelector('[data-phone-input]');

    const utcToday = () => new Date().toISOString().slice(0, 10);

    const setSlotsMessage = (message) => {
        if (slotsContainer instanceof HTMLElement) {
            slotsContainer.replaceChildren();
            const element = document.createElement('p');
            element.className = 'appointment-slots__message';
            element.textContent = message;
            slotsContainer.append(element);
        }
    };

    const showStartError = (message) => {
        const error = appointmentForm.querySelector('[data-field-error="start_at"]');

        if (startInput instanceof HTMLInputElement) {
            startInput.setAttribute('aria-invalid', 'true');
        }

        if (error instanceof HTMLElement) {
            error.textContent = message;
        }
    };

    const renderSlots = (slots) => {
        if (!(slotsContainer instanceof HTMLElement) || !(startInput instanceof HTMLInputElement)) {
            return;
        }

        slotsContainer.replaceChildren();

        if (!slots.length) {
            setSlotsMessage('There are no appointment times for this date.');
            return;
        }

        slots.forEach((slot) => {
            const button = document.createElement('button');
            const isUnavailable = slot.is_booked || slot.is_past;

            button.type = 'button';
            button.className = 'appointment-slot';
            button.textContent = slot.time;
            button.disabled = isUnavailable;
            button.dataset.startAt = slot.start_at;

            if (slot.is_booked) {
                button.classList.add('is-booked');
                button.setAttribute('aria-label', `${slot.time}, booked`);
            } else if (slot.is_past) {
                button.classList.add('is-unavailable');
                button.setAttribute('aria-label', `${slot.time}, no longer available`);
            } else {
                button.classList.add('is-available');
                button.setAttribute('aria-label', `${slot.time}, available`);
                button.addEventListener('click', () => {
                    slotsContainer.querySelectorAll('.appointment-slot.is-selected').forEach((selected) => {
                        selected.classList.remove('is-selected');
                        selected.removeAttribute('aria-pressed');
                    });
                    button.classList.add('is-selected');
                    button.setAttribute('aria-pressed', 'true');
                    startInput.value = slot.start_at;
                    startInput.removeAttribute('aria-invalid');

                    const error = appointmentForm.querySelector('[data-field-error="start_at"]');
                    if (error instanceof HTMLElement) {
                        error.textContent = '';
                    }
                });
            }

            slotsContainer.append(button);
        });
    };

    const loadSlots = async () => {
        if (!(dateInput instanceof HTMLInputElement) || !(startInput instanceof HTMLInputElement)) {
            return;
        }

        startInput.value = '';

        if (!dateInput.value) {
            setSlotsMessage('Choose a date to see available times.');
            return;
        }

        setSlotsMessage('Loading available times…');

        try {
            const url = new URL(appointmentForm.dataset.slotsUrl, window.location.origin);
            url.searchParams.set('date', dateInput.value);

            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const result = await response.json().catch(() => ({}));

            if (!response.ok || !Array.isArray(result.data?.slots)) {
                throw new Error('Could not load appointment slots.');
            }

            renderSlots(result.data.slots);
        } catch {
            setSlotsMessage('We could not load times for this date. Please try again.');
        }
    };

    if (dateInput instanceof HTMLInputElement) {
        dateInput.min = utcToday();
        dateInput.value = utcToday();
        dateInput.addEventListener('change', loadSlots);
        loadSlots();
    }

    if (phoneInput instanceof HTMLInputElement) {
        phoneInput.addEventListener('input', () => {
            const hasCountryPrefix = phoneInput.value.trim().startsWith('+');
            const digits = phoneInput.value.replace(/\D/g, '');

            if (!hasCountryPrefix || digits.length < 3) {
                phoneInput.value = hasCountryPrefix ? `+${digits}` : digits;
                return;
            }

            const countryCodeLength = digits.startsWith('49') ? 2 : 1;
            phoneInput.value = `+${digits.slice(0, countryCodeLength)} ${digits.slice(countryCodeLength)}`;
        });
    }

    const clearErrors = () => {
        appointmentForm.querySelectorAll('[data-field-error]').forEach((element) => {
            element.textContent = '';
        });

        appointmentForm.querySelectorAll('[aria-invalid="true"]').forEach((element) => {
            element.removeAttribute('aria-invalid');
        });

        if (formMessage instanceof HTMLElement) {
            formMessage.textContent = '';
        }
    };

    const showValidationErrors = (errors) => {
        let firstInvalidField = null;

        Object.entries(errors).forEach(([field, messages]) => {
            const input = appointmentForm.elements.namedItem(field);
            const error = appointmentForm.querySelector(`[data-field-error="${field}"]`);

            if (input instanceof HTMLInputElement) {
                input.setAttribute('aria-invalid', 'true');
                firstInvalidField ??= input;
            }

            if (error instanceof HTMLElement && Array.isArray(messages)) {
                error.textContent = messages[0] ?? '';
            }
        });

        firstInvalidField?.focus();
    };

    appointmentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        if (!appointmentForm.reportValidity()) {
            return;
        }

        if (!(startInput instanceof HTMLInputElement) || !startInput.value) {
            showStartError('Choose one available time.');
            return;
        }

        if (!(submitButton instanceof HTMLButtonElement) || !(formMessage instanceof HTMLElement)) {
            return;
        }

        const formData = new FormData(appointmentForm);
        const payload = Object.fromEntries(formData.entries());

        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        submitButton.dataset.originalLabel = submitButton.textContent.trim();
        submitButton.textContent = 'Booking…';
        formMessage.textContent = 'Checking your selected time…';

        try {
            const response = await fetch(appointmentForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                window.location.assign(appointmentForm.dataset.successUrl);
                return;
            }

            const result = await response.json().catch(() => ({}));

            if (response.status === 422 && result.errors) {
                showValidationErrors(result.errors);
                formMessage.textContent = 'Please check the highlighted fields.';

                if (result.errors.start_at) {
                    loadSlots();
                }
            } else {
                formMessage.textContent = 'We could not complete your booking. Please try again.';
            }
        } catch {
            formMessage.textContent = 'We could not reach the booking service. Please try again.';
        } finally {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
            submitButton.textContent = submitButton.dataset.originalLabel;
        }
    });
}
