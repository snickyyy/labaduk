const appointmentForm = document.querySelector('[data-appointment-form]');

if (appointmentForm instanceof HTMLFormElement) {
    const submitButton = appointmentForm.querySelector('[type="submit"]');
    const formMessage = appointmentForm.querySelector('[data-form-message]');
    const startInput = appointmentForm.elements.namedItem('start_at');
    const endInput = appointmentForm.elements.namedItem('end_at');

    const dateTimeInputs = [startInput, endInput].filter(
        (input) => input instanceof HTMLInputElement,
    );

    const roundUpToQuarterHour = (date) => {
        const rounded = new Date(date);
        rounded.setUTCSeconds(0, 0);
        rounded.setUTCMinutes(Math.ceil(rounded.getUTCMinutes() / 15) * 15);

        return rounded;
    };

    const toDateTimeLocalValue = (date) => date.toISOString().slice(0, 16);

    const now = roundUpToQuarterHour(new Date());

    dateTimeInputs.forEach((input) => {
        input.min = toDateTimeLocalValue(now);
    });

    if (startInput instanceof HTMLInputElement && endInput instanceof HTMLInputElement) {
        startInput.addEventListener('change', () => {
            endInput.min = startInput.value || toDateTimeLocalValue(now);

            if (startInput.value && (!endInput.value || endInput.value <= startInput.value)) {
                const suggestedEnd = new Date(`${startInput.value}:00Z`);
                suggestedEnd.setUTCHours(suggestedEnd.getUTCHours() + 1);
                endInput.value = toDateTimeLocalValue(suggestedEnd);
            }
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

    const toUtcIsoString = (value) => new Date(`${value}:00Z`).toISOString();

    appointmentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        if (!appointmentForm.reportValidity()) {
            return;
        }

        if (!(submitButton instanceof HTMLButtonElement) || !(formMessage instanceof HTMLElement)) {
            return;
        }

        const formData = new FormData(appointmentForm);
        const payload = Object.fromEntries(formData.entries());

        payload.start_at = toUtcIsoString(payload.start_at);
        payload.end_at = toUtcIsoString(payload.end_at);

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
