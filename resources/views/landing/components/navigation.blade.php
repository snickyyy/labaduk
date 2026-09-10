<header class="landing-header">
    <nav class="landing-nav" aria-label="{{ __('landing.nav.label') }}">
        <div class="landing-nav__identity">
            <a class="landing-brand" href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}">
                {{ __('landing.brand') }}
            </a>

            <div class="landing-nav__links">
                <a href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}#archive">{{ __('landing.nav.archive') }}</a>
                <a href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}#gear">{{ __('landing.nav.gear') }}</a>
                <a @class(['is-active' => request()->routeIs('landing.home')]) href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}#lessons" @if(request()->routeIs('landing.home')) aria-current="page" @endif>{{ __('landing.nav.lessons') }}</a>
                <a @class(['is-active' => request()->routeIs('landing.about')]) href="{{ route('landing.about', ['locale' => app()->getLocale()]) }}" @if(request()->routeIs('landing.about')) aria-current="page" @endif>About</a>
                <a @class(['is-active' => request()->routeIs('landing.visit-rules')]) href="{{ route('landing.visit-rules', ['locale' => app()->getLocale()]) }}" @if(request()->routeIs('landing.visit-rules')) aria-current="page" @endif>Visit rules</a>
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
                        href="{{ route(request()->route()->getName(), ['locale' => $locale]) }}"
                        hreflang="{{ $locale }}"
                        @if ($locale === app()->getLocale()) aria-current="true" @endif
                    >
                        {{ strtoupper($locale) }}
                    </a>
                @endforeach
            </div>
            <a class="landing-button" href="{{ route('landing.appointments.create', ['locale' => app()->getLocale()]) }}">
                {{ __('landing.nav.book_visit') }}
            </a>

            <details class="landing-menu">
                <summary aria-label="{{ __('landing.nav.menu') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18"></path>
                    </svg>
                </summary>
                <div class="landing-menu__panel">
                    <a href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}#archive">{{ __('landing.nav.archive') }}</a>
                    <a href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}#gear">{{ __('landing.nav.gear') }}</a>
                    <a href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}#lessons">{{ __('landing.nav.lessons') }}</a>
                    <a href="{{ route('landing.about', ['locale' => app()->getLocale()]) }}" @if(request()->routeIs('landing.about')) aria-current="page" @endif>About</a>
                    <a href="{{ route('landing.visit-rules', ['locale' => app()->getLocale()]) }}" @if(request()->routeIs('landing.visit-rules')) aria-current="page" @endif>Visit rules</a>
                    <a href="{{ route('landing.appointments.create', ['locale' => app()->getLocale()]) }}" @if(request()->routeIs('landing.appointments.*')) aria-current="page" @endif>{{ __('landing.nav.book_visit') }}</a>
                    @foreach (config('landing.supported_locales') as $locale)
                        <a
                            href="{{ route(request()->route()->getName(), ['locale' => $locale]) }}"
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
