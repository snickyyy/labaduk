@extends('landing.layouts.app')

@section('title', __('aboutPage.meta.title'))
@section('description', __('aboutPage.meta.description'))

@section('content')
    <section class="about-intro" aria-labelledby="about-title">
        <div class="about-intro__copy">
            <span class="about-eyebrow">{{ __('aboutPage.hero.eyebrow') }}</span>
            <h1 id="about-title">{{ __('landing.nav.about') }}<br><em>{{ __('aboutPage.hero.title_line_two') }}</em></h1>
            <p class="about-intro__lead">{{ __('aboutPage.hero.lead') }}</p>
            <p>{{ __('aboutPage.hero.paragraph_one') }}</p>
            <p>{{ __('aboutPage.hero.paragraph_two') }}</p>
        </div>

        <div class="about-intro__gallery" aria-label="{{ __('aboutPage.gallery.aria_label') }}">
            @foreach ($members->take(3) as $member)
                <figure>
                    <img src="{{ $member['image'] }}" alt="{{ $member['name'] }}, {{ strtolower($member['role']) }}" @if (!$loop->first) loading="lazy" @endif>
                    <figcaption>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ $member['name'] }}</figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    <div class="about-members-divider" id="members" aria-labelledby="members-title">
        <span aria-hidden="true"></span>
        <h2 id="members-title">{{ __('aboutPage.members.heading') }}</h2>
        <span aria-hidden="true"></span>
    </div>

    <section class="about-members" aria-label="{{ __('aboutPage.members.aria_label') }}">
        @forelse ($members as $member)
            <article class="member-profile" id="{{ $member['slug'] }}">
                <div class="member-profile__image">
                    <img src="{{ $member['image'] }}" alt="{{ __('aboutPage.member.portrait_alt', ['name' => $member['name']]) }}" loading="lazy">
                    <span class="member-profile__index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </div>

                <div class="member-profile__content">
                    <header>
                        <span class="member-profile__role">{{ $member['role'] }}</span>
                        <h3>{{ $member['name'] }}</h3>
                    </header>

                    <blockquote>“{{ $member['quote'] }}”</blockquote>
                    <p>{{ $member['bio'] }}</p>

                    <dl class="member-profile__facts">
                        <div>
                            <dt>{{ __('aboutPage.member.age') }}</dt>
                            <dd>{{ $member['age'] }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('aboutPage.member.from') }}</dt>
                            <dd>{{ $member['from'] }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('aboutPage.member.experience') }}</dt>
                            <dd>{{ $member['experience'] }}</dd>
                        </div>
                    </dl>

                    <div class="member-profile__motive">
                        <span>{{ __('aboutPage.member.motive_label') }}</span>
                        <p>{{ $member['motive'] }}</p>
                    </div>
                </div>
            </article>
        @empty
            <p class="about-members__empty">{{ __('aboutPage.members.empty') }}</p>
        @endforelse
    </section>

    <section class="about-cta" aria-labelledby="about-cta-title">
        <span class="about-cta__label">{{ __('aboutPage.cta.label') }}</span>
        <h2 id="about-cta-title">{{ __('aboutPage.cta.heading') }} <em>{{ __('aboutPage.cta.heading_emphasis') }}</em></h2>
        <p>{{ __('aboutPage.cta.description') }}</p>
        <a href="{{ route('landing.appointments.create', ['locale' => app()->getLocale()]) }}">{{ __('landing.nav.book_visit') }} <span aria-hidden="true">↗</span></a>
    </section>
@endsection
