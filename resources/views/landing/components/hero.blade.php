<section class="landing-hero" id="archive">
    <picture class="landing-hero__media">
        <source
            type="image/webp"
            srcset="{{ asset('images/landing/hero-320.webp') }} 320w, {{ asset('images/landing/hero-512.webp') }} 512w"
            sizes="100vw"
        >
        <img
            src="{{ asset('images/landing/hero.jpg') }}"
            width="512"
            height="512"
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
    </div>
</section>
