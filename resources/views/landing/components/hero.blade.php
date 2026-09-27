<section class="landing-hero" id="home">
    <picture class="landing-hero__media">
        <source
            type="image/webp"
            srcset="{{ asset('images/landing/studio-room-768.webp') }} 768w, {{ asset('images/landing/studio-room-1280.webp') }} 1280w"
            sizes="100vw"
        >
        <img
            src="{{ asset('images/landing/studio-room.jpg') }}"
            width="1600"
            height="1200"
            alt="{{ __('landing.hero.image_alt') }}"
            fetchpriority="high"
        >
    </picture>

    <div class="landing-hero__content">
        <span class="landing-kicker">{{ __('landing.hero.eyebrow') }}</span>
        <h1>
            <span>{{ __('landing.hero.title_line_one') }}</span>
            <span>{{ __('landing.hero.title_line_two') }}</span>
        </h1>
        <p class="landing-hero__lead">{{ __('landing.hero.lead') }}</p>
        <div class="landing-hero__actions">
            <a class="landing-button" href="{{ route('landing.appointments.create', ['locale' => app()->getLocale()]) }}">{{ __('landing.nav.book_visit') }}</a>
            <a class="landing-button landing-button--outline" href="#the-room">{{ __('landing.hero.explore_room') }}</a>
        </div>
    </div>
</section>
