@extends('landing.layouts.app')

@section('title', 'Visit Rules — Slash Editorial')
@section('description', 'Twelve simple rules for a safe, focused and enjoyable visit to the Slash Editorial rehearsal room.')

@section('content')
    @php
        $rules = [
            ['title' => 'Arrive on time', 'text' => 'Come 10 minutes before your slot so we can start without rushing.', 'icon' => 'clock'],
            ['title' => 'Check in first', 'text' => 'Meet your host at the entrance. Please do not enter the rehearsal area alone.', 'icon' => 'door'],
            ['title' => 'Bring your ID', 'text' => 'We may ask to confirm the name used for your booking.', 'icon' => 'card'],
            ['title' => 'Wear closed shoes', 'text' => 'Cables, stands and heavy cases make open footwear unsafe in the room.', 'icon' => 'shoe'],
            ['title' => 'Protect your hearing', 'text' => 'Use the earplugs provided whenever the full band is playing.', 'icon' => 'ear'],
            ['title' => 'Keep drinks capped', 'text' => 'Water is welcome. Keep all drinks closed and away from equipment.', 'icon' => 'bottle'],
            ['title' => 'No food inside', 'text' => 'Please finish snacks before entering the rehearsal space.', 'icon' => 'food'],
            ['title' => 'Ask before touching', 'text' => 'Instruments, pedals and amplifiers are handled only with permission.', 'icon' => 'hand'],
            ['title' => 'Photos by consent', 'text' => 'Ask everyone in the room before taking a photo or recording video.', 'icon' => 'camera'],
            ['title' => 'Silence your phone', 'text' => 'Keep notifications and calls off while a rehearsal or lesson is running.', 'icon' => 'phone'],
            ['title' => 'Respect the room', 'text' => 'No smoking, vaping, aggressive behaviour or discriminatory language.', 'icon' => 'heart'],
            ['title' => 'Leave it as found', 'text' => 'Take your belongings and put borrowed protection in the marked bin.', 'icon' => 'spark'],
        ];
    @endphp

    <section class="visit-rules" aria-labelledby="visit-rules-title">
        <header class="visit-rules__intro">
            <div class="visit-rules__heading">
                <span class="visit-rules__eyebrow">Before you step inside</span>
                <h1 id="visit-rules-title">Visit<br><em>rules</em></h1>
            </div>

            <div class="visit-rules__count" aria-hidden="true">
                <span>12</span>
                <svg viewBox="0 0 160 160">
                    <circle cx="80" cy="80" r="69"></circle>
                    <path d="M80 0v22M80 138v22M0 80h22M138 80h22"></path>
                </svg>
            </div>

            <p>Simple habits keep the room safe, the gear working and the focus on the music. Please read these before your visit.</p>
        </header>

        <ol class="visit-rules__grid">
            @foreach ($rules as $rule)
                <li class="visit-rule">
                    <div class="visit-rule__topline">
                        <span class="visit-rule__number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="visit-rule__icon" aria-hidden="true">
                            @switch($rule['icon'])
                                @case('clock')
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"></circle><path d="M12 7v5l3 2"></path></svg>
                                    @break
                                @case('door')
                                    <svg viewBox="0 0 24 24"><path d="M5 21h14M7 21V4l10-1v18M14 12h.01"></path></svg>
                                    @break
                                @case('card')
                                    <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14"></rect><path d="M3 9h18M7 14h4"></path></svg>
                                    @break
                                @case('shoe')
                                    <svg viewBox="0 0 24 24"><path d="M4 14c3 0 5-2 5-6h4c0 4 3 5 7 7v4H4z"></path></svg>
                                    @break
                                @case('ear')
                                    <svg viewBox="0 0 24 24"><path d="M6 10a6 6 0 0 1 12 0c0 4-4 4-4 8a2 2 0 0 1-4 0M9 11a3 3 0 0 1 6 0c0 2-2 2-2 4"></path></svg>
                                    @break
                                @case('bottle')
                                    <svg viewBox="0 0 24 24"><path d="M9 3h6M10 3v4l-2 3v11h8V10l-2-3V3M8 13h8"></path></svg>
                                    @break
                                @case('food')
                                    <svg viewBox="0 0 24 24"><path d="M4 4l16 16M8 5v6M5 5v4c0 2 2 3 4 3M16 5c-3 2-3 6 0 8v6"></path></svg>
                                    @break
                                @case('hand')
                                    <svg viewBox="0 0 24 24"><path d="M6 12V8a1.5 1.5 0 0 1 3 0v3-5a1.5 1.5 0 0 1 3 0v5-4a1.5 1.5 0 0 1 3 0v4-2a1.5 1.5 0 0 1 3 0v5c0 5-3 7-7 7s-6-2-7-5l-1-3a1.6 1.6 0 0 1 3-1z"></path></svg>
                                    @break
                                @case('camera')
                                    <svg viewBox="0 0 24 24"><path d="M3 8h4l2-3h6l2 3h4v11H3z"></path><circle cx="12" cy="13" r="3"></circle></svg>
                                    @break
                                @case('phone')
                                    <svg viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="1"></rect><path d="M10 18h4M4 4l16 16"></path></svg>
                                    @break
                                @case('heart')
                                    <svg viewBox="0 0 24 24"><path d="M12 20S4 16 4 9a4 4 0 0 1 7-3l1 1 1-1a4 4 0 0 1 7 3c0 7-8 11-8 11z"></path></svg>
                                    @break
                                @case('spark')
                                    <svg viewBox="0 0 24 24"><path d="M12 2c0 6-3 9-9 9 6 0 9 3 9 9 0-6 3-9 9-9-6 0-9-3-9-9z"></path></svg>
                                    @break
                            @endswitch
                        </span>
                    </div>
                    <h2>{{ $rule['title'] }}</h2>
                    <p>{{ $rule['text'] }}</p>
                </li>
            @endforeach
        </ol>

        <footer class="visit-rules__note">
            <span>One last thing</span>
            <p>If you feel unwell or cannot make your time, let us know before travelling. We will help you reschedule.</p>
            <a href="mailto:hello@slasheditorial.com">Ask a question <span aria-hidden="true">↗</span></a>
        </footer>
    </section>
@endsection
