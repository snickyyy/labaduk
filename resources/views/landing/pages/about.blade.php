@extends('landing.layouts.app')

@section('title', 'About Us — Slash Editorial')
@section('description', 'Meet the people behind Slash Editorial: an independent rock group built on loud rooms, honest songs and the work of playing together.')

@section('content')
    <section class="about-intro" aria-labelledby="about-title">
        <div class="about-intro__copy">
            <span class="about-eyebrow">Independent since 2021</span>
            <h1 id="about-title">About<br><em>us</em></h1>
            <p class="about-intro__lead">We are six people building loud, direct music from the ground up.</p>
            <p>Slash Editorial began in a borrowed rehearsal room in Berlin: two unfinished songs, one unreliable amplifier and a shared refusal to make anything that felt disposable. Since then, the group has grown into a collective shaped equally by rehearsals, stage work and the conversations that happen after the noise stops.</p>
            <p>Our sound lives between hard rock, alternative metal and the rough edges we choose not to edit out. Every member brings a different discipline, but the aim stays the same — make songs with enough weight to be felt and enough detail to be remembered.</p>
        </div>

        <div class="about-intro__gallery" aria-label="Members of the group">
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
        <h2 id="members-title">Members</h2>
        <span aria-hidden="true"></span>
    </div>

    <section class="about-members" aria-label="Band member profiles">
        @forelse ($members as $member)
            <article class="member-profile" id="{{ $member['slug'] }}">
                <div class="member-profile__image">
                    <img src="{{ $member['image'] }}" alt="Portrait of {{ $member['name'] }}" loading="lazy">
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
                            <dt>Age</dt>
                            <dd>{{ $member['age'] }}</dd>
                        </div>
                        <div>
                            <dt>From</dt>
                            <dd>{{ $member['from'] }}</dd>
                        </div>
                        <div>
                            <dt>Experience</dt>
                            <dd>{{ $member['experience'] }}</dd>
                        </div>
                    </dl>

                    <div class="member-profile__motive">
                        <span>Why they play</span>
                        <p>{{ $member['motive'] }}</p>
                    </div>
                </div>
            </article>
        @empty
            <p class="about-members__empty">Member profiles are being prepared.</p>
        @endforelse
    </section>

    <section class="about-cta" aria-labelledby="about-cta-title">
        <span class="about-cta__label">Come as you are</span>
        <h2 id="about-cta-title">Want to get to know us <em>up close?</em></h2>
        <p>Book a visit, step into the rehearsal room and see how the noise comes together.</p>
        <a href="{{ route('landing.appointments.create', ['locale' => app()->getLocale()]) }}">Book a visit <span aria-hidden="true">↗</span></a>
    </section>
@endsection
