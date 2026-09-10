<footer class="landing-footer" id="footer">
    <a class="landing-brand landing-footer__brand" href="{{ route('landing.home', ['locale' => app()->getLocale()]) }}">
        {{ __('landing.brand') }}
    </a>

    <div class="landing-footer__links" aria-label="{{ __('landing.footer.label') }}">
        <span>{{ __('landing.footer.privacy') }}</span>
        <span>{{ __('landing.footer.terms') }}</span>
        <span>{{ __('landing.footer.advertise') }}</span>
        <span>{{ __('landing.footer.contact') }}</span>
    </div>

    <p>{{ __('landing.footer.copyright') }}</p>
</footer>
