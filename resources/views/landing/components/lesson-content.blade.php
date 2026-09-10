<section class="landing-lesson" id="lessons">
    <div class="landing-lesson__grid">
        <div class="landing-player">
            <picture class="landing-player__poster">
                <source
                    type="image/webp"
                    srcset="{{ asset('images/landing/lesson-poster-320.webp') }} 320w, {{ asset('images/landing/lesson-poster-512.webp') }} 512w"
                    sizes="(min-width: 768px) 58vw, 100vw"
                >
                <img
                    src="{{ asset('images/landing/lesson-poster.jpg') }}"
                    width="512"
                    height="279"
                    alt="{{ __('landing.lesson.poster_alt') }}"
                    loading="lazy"
                >
            </picture>

            <span class="landing-player__play" aria-hidden="true">
                <svg viewBox="0 0 32 32" aria-hidden="true">
                    <path d="M11 7.5 24 16 11 24.5Z"></path>
                </svg>
            </span>

            <div class="landing-player__controls" aria-hidden="true">
                <span class="landing-player__track"><span></span></span>
                <span>{{ __('landing.lesson.duration') }}</span>
            </div>
        </div>

        <article class="landing-notes" id="technical-notes">
            <div class="landing-notes__copy">
                <h2>{{ __('landing.notes.title') }}</h2>
                <p class="landing-notes__lead">{{ __('landing.notes.lead') }}</p>
                <p>{{ __('landing.notes.body') }}</p>
            </div>

            <div class="landing-gear" id="gear">
                <h3>{{ __('landing.gear.title') }}</h3>

                <ul>
                    <li>
                        <span class="landing-gear__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>
                        </span>
                        <span>
                            <strong>{{ __('landing.gear.guitar.name') }}</strong>
                            <small>{{ __('landing.gear.guitar.description') }}</small>
                        </span>
                    </li>
                    <li>
                        <span class="landing-gear__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="7" y="3.5" width="10" height="17"></rect><circle cx="12" cy="14" r="2.5"></circle><path d="M10 7h4"></path></svg>
                        </span>
                        <span>
                            <strong>{{ __('landing.gear.amp.name') }}</strong>
                            <small>{{ __('landing.gear.amp.description') }}</small>
                        </span>
                    </li>
                    <li>
                        <span class="landing-gear__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"></circle><circle cx="12" cy="12" r="3"></circle></svg>
                        </span>
                        <span>
                            <strong>{{ __('landing.gear.strings.name') }}</strong>
                            <small>{{ __('landing.gear.strings.description') }}</small>
                        </span>
                    </li>
                </ul>
            </div>
        </article>
    </div>
</section>
