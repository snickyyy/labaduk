<section class="landing-open-room" id="the-room" aria-labelledby="room-title">
    <div class="landing-open-room__introduction">
        <span class="landing-open-room__eyebrow">{{ __('landing.room.eyebrow') }}</span>
        <h2 id="room-title">{{ __('landing.room.title_line_one') }}<br><em>{{ __('landing.room.title_line_two') }}</em></h2>
        <p class="landing-open-room__lead">{{ __('landing.room.lead') }}</p>

        <ol class="landing-open-room__steps">
            <li>
                <span>01</span>
                <div>
                    <h3>{{ __('landing.room.step_one.title') }}</h3>
                    <p>{{ __('landing.room.step_one.description') }}</p>
                </div>
            </li>
            <li>
                <span>02</span>
                <div>
                    <h3>{{ __('landing.room.step_two.title') }}</h3>
                    <p>{{ __('landing.room.step_two.description') }}</p>
                </div>
            </li>
            <li>
                <span>03</span>
                <div>
                    <h3>{{ __('landing.room.step_three.title') }}</h3>
                    <p>{{ __('landing.room.step_three.description') }}</p>
                </div>
            </li>
        </ol>
    </div>

    <aside class="landing-open-room__visit" aria-labelledby="visit-title">
        <picture class="landing-open-room__photo">
            <source
                type="image/webp"
                srcset="{{ asset('images/landing/open-room-720.webp') }} 720w, {{ asset('images/landing/open-room-1120.webp') }} 1120w"
                sizes="(min-width: 768px) 42vw, 100vw"
            >
            <img
                src="{{ asset('images/landing/open-room.jpg') }}"
                width="1400"
                height="1050"
                alt="{{ __('landing.room.image_alt') }}"
                loading="lazy"
            >
        </picture>

        <div class="landing-open-room__visit-copy">
            <span>{{ __('landing.room.visit.eyebrow') }}</span>
            <h2 id="visit-title">{{ __('landing.room.visit.title_line_one') }}<br>{{ __('landing.room.visit.title_line_two') }}</h2>
            <p>{{ __('landing.room.visit.description') }}</p>
            <a href="{{ route('landing.appointments.create', ['locale' => app()->getLocale()]) }}">{{ __('landing.nav.book_visit') }} <span aria-hidden="true">↗</span></a>
        </div>
    </aside>
</section>
