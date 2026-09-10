<header class="landing-header">
    <nav class="landing-nav" aria-label="{{ __('landing.nav.label') }}">
        <div class="landing-nav__identity">
            <a class="landing-brand" href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}">
                {{ __('landing.brand') }}
            </a>

            <div class="landing-nav__links">
                <a href="#archive">{{ __('landing.nav.archive') }}</a>
                <a href="#gear">{{ __('landing.nav.gear') }}</a>
                <a class="is-active" href="#lessons" aria-current="page">{{ __('landing.nav.lessons') }}</a>
                <span>{{ __('landing.nav.tours') }}</span>
            </div>
        </div>

        <div class="landing-nav__actions">
            <span class="landing-nav__search" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="6.5"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
            </span>
            <div class="landing-languages" aria-label="{{ __('landing.locale.label') }}">
                @foreach (config('landing.supported_locales') as $locale)
                    <a
                        href="{{ route(request()->route()->getName(), [...request()->route()->parameters(), 'locale' => $locale]) }}"
                        hreflang="{{ $locale }}"
                        @if ($locale === app()->getLocale()) aria-current="true" @endif
                    >
                        {{ strtoupper($locale) }}
                    </a>
                @endforeach
            </div>
            <span class="landing-button">{{ __('landing.nav.subscribe') }}</span>

            <details class="landing-menu">
                <summary aria-label="{{ __('landing.nav.menu') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18"></path>
                    </svg>
                </summary>
                <div class="landing-menu__panel">
                    <a href="#archive">{{ __('landing.nav.archive') }}</a>
                    <a href="#gear">{{ __('landing.nav.gear') }}</a>
                    <a href="#lessons">{{ __('landing.nav.lessons') }}</a>
                    <span>{{ __('landing.nav.tours') }}</span>
                    @foreach (config('landing.supported_locales') as $locale)
                        <a
                            href="{{ route(request()->route()->getName(), [...request()->route()->parameters(), 'locale' => $locale]) }}"
                            hreflang="{{ $locale }}"
                            @if ($locale === app()->getLocale()) aria-current="true" @endif
                        >
                            {{ strtoupper($locale) }}
                        </a>
                    @endforeach
                </div>
            </details>
        </div>
    </nav>
</header>
