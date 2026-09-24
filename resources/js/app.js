document.querySelectorAll('[data-language-select]').forEach((select) => {
    select.addEventListener('change', (event) => {
        const target = event.currentTarget;

        if (target instanceof HTMLSelectElement && target.value) {
            window.location.assign(target.value);
        }
    });
});

document.querySelectorAll('[data-fit-text]').forEach((element) => {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    const fitText = () => {
        element.style.removeProperty('font-size');

        const availableWidth = element.clientWidth;
        const contentWidth = element.scrollWidth;

        if (!availableWidth || contentWidth <= availableWidth) {
            return;
        }

        const baseFontSize = Number.parseFloat(window.getComputedStyle(element).fontSize);
        const scale = (availableWidth / contentWidth) * 0.98;

        element.style.fontSize = `${baseFontSize * scale}px`;
    };

    if (element.parentElement && 'ResizeObserver' in window) {
        new ResizeObserver(fitText).observe(element.parentElement);
    } else {
        window.addEventListener('resize', fitText);
    }

    fitText();
    document.fonts?.ready?.then(fitText);
});

const appointmentForm = document.querySelector('[data-appointment-form]');

if (appointmentForm instanceof HTMLFormElement) {
    const submitButton = appointmentForm.querySelector('[type="submit"]');
    const formMessage = appointmentForm.querySelector('[data-form-message]');
    const dateInput = appointmentForm.querySelector('[data-appointment-date]');
    const slotsContainer = appointmentForm.querySelector('[data-appointment-slots]');
    const durationsContainer = appointmentForm.querySelector('[data-appointment-durations]');
    const summary = appointmentForm.querySelector('[data-appointment-summary]');
    const startInput = appointmentForm.elements.namedItem('start_at');
    const durationInput = appointmentForm.elements.namedItem('duration_minutes');
    const phoneInput = appointmentForm.querySelector('[data-phone-input]');
    let selectedSlot = null;

    const durationLabels = {
        30: '30 minutes',
        60: '1 hour',
        90: '1 hour 30 minutes',
        120: '2 hours',
    };

    const replaceWithMessage = (container, message) => {
        if (!(container instanceof HTMLElement)) {
            return;
        }

        container.replaceChildren();
        const element = document.createElement('p');
        element.className = 'appointment-slots__message';
        element.textContent = message;
        container.append(element);
    };

    const showFieldError = (field, message) => {
        const input = appointmentForm.elements.namedItem(field);
        const error = appointmentForm.querySelector(`[data-field-error="${field}"]`);

        if (input instanceof HTMLInputElement) {
            input.setAttribute('aria-invalid', 'true');
        }

        if (error instanceof HTMLElement) {
            error.textContent = message;
        }
    };

    const resetSummary = () => {
        if (summary instanceof HTMLElement) {
            summary.hidden = true;
        }
    };

    const resetDuration = () => {
        if (durationInput instanceof HTMLInputElement) {
            durationInput.value = '';
        }

        resetSummary();
        replaceWithMessage(durationsContainer, 'Choose a start time first.');
    };

    const resetSelection = () => {
        selectedSlot = null;

        if (startInput instanceof HTMLInputElement) {
            startInput.value = '';
        }

        resetDuration();
    };

    const formatDate = (date) => {
        const [year, month, day] = date.split('-');

        return `${day}.${month}.${year}`;
    };

    const endTime = (start, duration) => {
        const [hours, minutes] = start.split(':').map(Number);
        const total = (hours * 60) + minutes + duration;

        return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
    };

    const renderSummary = () => {
        if (
            !(summary instanceof HTMLElement)
            || !(dateInput instanceof HTMLInputElement)
            || !(durationInput instanceof HTMLInputElement)
            || !selectedSlot
            || !durationInput.value
        ) {
            resetSummary();
            return;
        }

        const duration = Number(durationInput.value);
        summary.querySelector('[data-summary-date]').textContent = formatDate(dateInput.value);
        summary.querySelector('[data-summary-start]').textContent = selectedSlot.time;
        summary.querySelector('[data-summary-duration]').textContent = durationLabels[duration];
        summary.querySelector('[data-summary-end]').textContent = endTime(selectedSlot.time, duration);
        summary.hidden = false;
    };

    const renderDurations = (slot) => {
        if (!(durationsContainer instanceof HTMLElement) || !(durationInput instanceof HTMLInputElement)) {
            return;
        }

        durationInput.value = '';
        resetSummary();
        durationsContainer.replaceChildren();

        slot.durations.forEach((duration) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'appointment-slot is-available';
            button.textContent = durationLabels[duration] ?? `${duration} minutes`;
            button.addEventListener('click', () => {
                durationsContainer.querySelectorAll('.appointment-slot.is-selected').forEach((selected) => {
                    selected.classList.remove('is-selected');
                    selected.removeAttribute('aria-pressed');
                });
                button.classList.add('is-selected');
                button.setAttribute('aria-pressed', 'true');
                durationInput.value = String(duration);
                durationInput.removeAttribute('aria-invalid');

                const error = appointmentForm.querySelector('[data-field-error="duration_minutes"]');
                if (error instanceof HTMLElement) {
                    error.textContent = '';
                }

                renderSummary();
            });
            durationsContainer.append(button);
        });
    };

    const renderSlots = (slots, isClosed) => {
        if (!(slotsContainer instanceof HTMLElement) || !(startInput instanceof HTMLInputElement)) {
            return;
        }

        slotsContainer.replaceChildren();

        if (isClosed) {
            if (dateInput instanceof HTMLInputElement) {
                dateInput.setCustomValidity('This date is closed for bookings.');
            }
            replaceWithMessage(slotsContainer, 'This date is closed for bookings.');
            return;
        }

        if (!slots.length) {
            if (dateInput instanceof HTMLInputElement) {
                dateInput.setCustomValidity('Choose a weekday.');
            }
            replaceWithMessage(slotsContainer, 'Appointments are available Monday through Friday.');
            return;
        }

        slots.forEach((slot) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'appointment-slot';
            button.textContent = slot.time;
            button.dataset.startAt = slot.start_at;
            button.disabled = !slot.is_available;

            if (!slot.is_available) {
                button.classList.add('is-unavailable');
                button.setAttribute('aria-label', `${slot.time}, unavailable`);
                button.title = slot.is_past ? 'This time has passed' : 'No duration is available';
                slotsContainer.append(button);
                return;
            }

            button.classList.add('is-available');
            button.setAttribute('aria-label', `${slot.time}, available`);
            button.addEventListener('click', () => {
                slotsContainer.querySelectorAll('.appointment-slot.is-selected').forEach((selected) => {
                    selected.classList.remove('is-selected');
                    selected.removeAttribute('aria-pressed');
                });
                button.classList.add('is-selected');
                button.setAttribute('aria-pressed', 'true');
                selectedSlot = slot;
                startInput.value = slot.start_at;
                startInput.removeAttribute('aria-invalid');

                const error = appointmentForm.querySelector('[data-field-error="start_at"]');
                if (error instanceof HTMLElement) {
                    error.textContent = '';
                }

                renderDurations(slot);
            });

            slotsContainer.append(button);
        });
    };

    const loadSlots = async () => {
        if (!(dateInput instanceof HTMLInputElement)) {
            return;
        }

        resetSelection();
        dateInput.setCustomValidity('');

        if (!dateInput.value) {
            replaceWithMessage(slotsContainer, 'Choose a date to see available times.');
            return;
        }

        replaceWithMessage(slotsContainer, 'Loading available times…');

        try {
            const url = new URL(appointmentForm.dataset.slotsUrl, window.location.origin);
            url.searchParams.set('date', dateInput.value);

            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const result = await response.json().catch(() => ({}));

            if (!response.ok || !Array.isArray(result.data?.slots)) {
                throw new Error('Could not load appointment slots.');
            }

            renderSlots(result.data.slots, result.data.is_closed === true);
        } catch {
            replaceWithMessage(slotsContainer, 'We could not load times for this date. Please try again.');
        }
    };

    if (dateInput instanceof HTMLInputElement) {
        dateInput.min = appointmentForm.dataset.today;
        dateInput.value = appointmentForm.dataset.today;
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
            showFieldError('start_at', 'Choose one available start time.');
            return;
        }

        if (!(durationInput instanceof HTMLInputElement) || !durationInput.value) {
            showFieldError('duration_minutes', 'Choose an available duration.');
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

                if (result.errors.start_at || result.errors.duration_minutes) {
                    formMessage.textContent = 'Availability changed. Please choose a start time and duration again.';
                    loadSlots();
                } else {
                    formMessage.textContent = 'Please check the highlighted fields.';
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
