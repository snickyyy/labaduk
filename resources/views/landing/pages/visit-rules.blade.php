@extends('landing.layouts.app')

@section('title', __('rulesPage.meta.title'))
@section('description', __('rulesPage.meta.description'))

@section('content')
    @php
        $sections = [
            [
                'number' => __('rulesPage.sections.general.number'),
                'label' => __('rulesPage.sections.general.label'),
                'heading' => __('rulesPage.sections.general.heading'),
                'items' => [
                    __('rulesPage.sections.general.items.care_for_room'),
                    __('rulesPage.sections.general.items.leave_as_found'),
                    __('rulesPage.sections.general.items.no_misuse'),
                    __('rulesPage.sections.general.items.damage_liability'),
                ],
            ],
            [
                'number' => __('rulesPage.sections.drums.number'),
                'label' => __('rulesPage.sections.drums.label'),
                'heading' => __('rulesPage.sections.drums.heading'),
                'items' => [
                    __('rulesPage.sections.drums.items.no_excessive_force'),
                    __('rulesPage.sections.drums.items.stool_only_when_playing'),
                    __('rulesPage.sections.drums.items.return_every_part'),
                    __('rulesPage.sections.drums.items.keep_items_away'),
                ],
            ],
            [
                'number' => __('rulesPage.sections.guitars.number'),
                'label' => __('rulesPage.sections.guitars.label'),
                'heading' => __('rulesPage.sections.guitars.heading'),
                'items' => [
                    __('rulesPage.sections.guitars.items.return_all_guitars'),
                    __('rulesPage.sections.guitars.items.do_not_lay_down'),
                    __('rulesPage.sections.guitars.items.mute_before_cables'),
                    __('rulesPage.sections.guitars.items.no_max_volume'),
                    __('rulesPage.sections.guitars.items.keep_cables_clear'),
                ],
            ],
            [
                'number' => __('rulesPage.sections.technology.number'),
                'label' => __('rulesPage.sections.technology.label'),
                'heading' => __('rulesPage.sections.technology.heading'),
                'items' => [
                    __('rulesPage.sections.technology.items.check_connections'),
                    __('rulesPage.sections.technology.items.no_damaged_cables'),
                    __('rulesPage.sections.technology.items.never_pull_cables'),
                    __('rulesPage.sections.technology.items.switch_everything_off'),
                    __('rulesPage.sections.technology.items.report_malfunctions'),
                ],
            ],
            [
                'number' => __('rulesPage.sections.microphones.number'),
                'label' => __('rulesPage.sections.microphones.label'),
                'heading' => __('rulesPage.sections.microphones.heading'),
                'items' => [
                    __('rulesPage.sections.microphones.items.do_not_drop'),
                    __('rulesPage.sections.microphones.items.stands_stable'),
                    __('rulesPage.sections.microphones.items.return_after_use'),
                    __('rulesPage.sections.microphones.items.do_not_adjust'),
                ],
            ],
            [
                'number' => __('rulesPage.sections.food_drink.number'),
                'label' => __('rulesPage.sections.food_drink.label'),
                'heading' => __('rulesPage.sections.food_drink.heading'),
                'items' => [
                    __('rulesPage.sections.food_drink.items.keep_drinks_away'),
                    __('rulesPage.sections.food_drink.items.no_drinks_on_equipment'),
                    __('rulesPage.sections.food_drink.items.no_litter'),
                    __('rulesPage.sections.food_drink.items.clean_spills'),
                ],
            ],
            [
                'number' => __('rulesPage.sections.volume.number'),
                'label' => __('rulesPage.sections.volume.label'),
                'heading' => __('rulesPage.sections.volume.heading'),
                'items' => [
                    __('rulesPage.sections.volume.items.no_dangerous_volume'),
                    __('rulesPage.sections.volume.items.respect_neighbours'),
                    __('rulesPage.sections.volume.items.listen_to_requests'),
                ],
            ],
        ];

        $checklist = [
            __('rulesPage.after_rehearsal.items.dispose_waste'),
            __('rulesPage.after_rehearsal.items.return_guitars'),
            __('rulesPage.after_rehearsal.items.leave_drums_orderly'),
            __('rulesPage.after_rehearsal.items.coil_cables'),
            __('rulesPage.after_rehearsal.items.return_microphones'),
            __('rulesPage.after_rehearsal.items.switch_off_technology'),
            __('rulesPage.after_rehearsal.items.check_lights_power'),
            __('rulesPage.after_rehearsal.items.lock_room'),
        ];

        $finalActions = [
            __('rulesPage.final_rule.actions.report_damage'),
            __('rulesPage.final_rule.actions.clean_mess'),
            __('rulesPage.final_rule.actions.return_items'),
            __('rulesPage.final_rule.actions.restore_settings'),
        ];
    @endphp

    <article class="room-rules">
        <header class="room-rules__hero">
            <div class="room-rules__shell room-rules__hero-layout">
                <div>
                    <p class="room-rules__eyebrow">{{ __('rulesPage.hero.eyebrow') }}</p>
                    <h1 id="room-rules-title">
                        <span>{{ __('rulesPage.hero.title_line_one') }}</span>
                        <span>{{ __('rulesPage.hero.title_line_two') }}</span>
                    </h1>
                </div>

                <p class="room-rules__intro">{{ __('rulesPage.hero.description') }}</p>
            </div>
        </header>

        <section class="room-rules__important room-rules__shell" aria-labelledby="important-rules-title">
            <div class="room-rules__important-header">
                <p>{{ __('rulesPage.important.eyebrow') }}</p>
                <h2 id="important-rules-title">{{ __('rulesPage.important.heading') }}</h2>
            </div>

            <ol class="room-rules__important-list">
                <li>
                    <span aria-hidden="true">01</span>
                    <p>{{ __('rulesPage.important.items.use_sanitiser') }}</p>
                </li>
                <li>
                    <span aria-hidden="true">02</span>
                    <p>{{ __('rulesPage.important.items.bring_change_of_shoes') }}</p>
                </li>
            </ol>
        </section>

        <div class="room-rules__shell room-rules__sections" aria-label="{{ __('rulesPage.sections.label') }}">
            @foreach ($sections as $section)
                <section class="room-rules__section" aria-labelledby="room-rules-section-{{ $loop->iteration }}">
                    <header class="room-rules__section-header">
                        <p class="room-rules__section-number">{{ $section['number'] }}</p>
                        <div>
                            <p class="room-rules__section-label">{{ $section['label'] }}</p>
                            <h2 id="room-rules-section-{{ $loop->iteration }}">{{ $section['heading'] }}</h2>
                        </div>
                    </header>

                    <ol class="room-rules__list">
                        @foreach ($section['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </div>

        <section class="room-rules__after" aria-labelledby="after-rehearsal-title">
            <div class="room-rules__shell room-rules__after-layout">
                <header>
                    <p class="room-rules__eyebrow">{{ __('rulesPage.after_rehearsal.eyebrow') }}</p>
                    <h2 id="after-rehearsal-title">{{ __('rulesPage.after_rehearsal.heading') }}</h2>
                </header>

                <ol class="room-rules__checklist">
                    @foreach ($checklist as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="room-rules__final room-rules__shell" aria-labelledby="final-rule-title">
            <header>
                <p class="room-rules__final-label">{{ __('rulesPage.final_rule.eyebrow') }}</p>
                <h2 id="final-rule-title">{{ __('rulesPage.final_rule.heading') }}</h2>
            </header>

            <div class="room-rules__final-content">
                <p class="room-rules__final-lead">{{ __('rulesPage.final_rule.lead') }}</p>
                <ul>
                    @foreach ($finalActions as $action)
                        <li>{{ $action }}</li>
                    @endforeach
                </ul>
                <p class="room-rules__final-support">{{ __('rulesPage.final_rule.support') }}</p>
            </div>
        </section>
    </article>
@endsection
