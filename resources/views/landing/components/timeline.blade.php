<section class="landing-timeline" aria-labelledby="timeline-title">
    <div class="landing-timeline__heading">
        <h2 id="timeline-title">{{ __('landing.timeline.title') }}</h2>
        <span>{{ __('landing.timeline.module') }}</span>
    </div>

    <ol class="landing-timeline__items">
        @foreach ($lessons as $index => $lesson)
            <li @class(['is-current' => $loop->first])>
                <span class="landing-timeline__number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <strong>{{ __($lesson) }}</strong>
            </li>
        @endforeach
    </ol>
</section>
