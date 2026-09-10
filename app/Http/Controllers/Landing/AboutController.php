<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        $profiles = [
            'labaduk' => [
                'name' => 'Labaduk',
                'role' => 'Lead guitar · Founder',
                'age' => '24',
                'from' => 'Berlin',
                'experience' => '9 years',
                'quote' => 'A song should leave a mark before the last chord has even faded.',
                'bio' => 'Labaduk started the group as a place for heavy riffs, honest lyrics and the kind of live energy that cannot be polished into something safe. He shapes the guitar sound, sketches the first arrangements and keeps every song focused on one clear emotion.',
                'motive' => 'His reason for playing is simple: turn the things people struggle to say out loud into something a whole room can feel together.',
            ],
            'mongol' => [
                'name' => 'Mongol',
                'role' => 'Vocals · Visual direction',
                'age' => '22',
                'from' => 'Ulaanbaatar → Berlin',
                'experience' => '7 years',
                'quote' => 'The voice is another instrument — sometimes it cuts, sometimes it carries.',
                'bio' => 'Mongol is the voice at the centre of the group. She moves between restrained verses and raw, open choruses, helping turn early demos into songs with a distinct point of view. She also guides the visual language around releases and live shows.',
                'motive' => 'She wants every performance to feel personal, even in a crowded room: direct, imperfect and impossible to fake.',
            ],
            'bogdan' => [
                'name' => 'Bogdan',
                'role' => 'Bass · Arrangements',
                'age' => '23',
                'from' => 'Kyiv → Berlin',
                'experience' => '8 years',
                'quote' => 'The bass is where the weight of a song learns how to move.',
                'bio' => 'Bogdan holds the low end together and gives the arrangements their physical pulse. His parts sit between rhythm and melody, making room for the guitars while pushing the songs forward with a steady, deliberate force.',
                'motive' => 'He plays to build the kind of foundation that lets everyone else take risks without the song losing its centre.',
            ],
            'kakoito-hui-1' => [
                'name' => 'Kakoito Hui I',
                'role' => 'Production · Stage crew',
                'age' => '25',
                'from' => 'Potsdam',
                'experience' => '6 years',
                'quote' => 'If a stage has character, the band already sounds louder.',
                'bio' => 'Behind the amplifiers, lights and cables, Kakoito Hui I turns rough ideas into a working show. He builds practical stage pieces, solves last-minute problems and makes sure the group can arrive, plug in and play without losing momentum.',
                'motive' => 'He is driven by the hidden craft of live music — the details no one notices until they are missing.',
            ],
            'kakoito-hui-2' => [
                'name' => 'Kakoito Hui II',
                'role' => 'Drums · Live dynamics',
                'age' => '21',
                'from' => 'Leipzig',
                'experience' => '10 years',
                'quote' => 'Tempo is a promise. The interesting part is how hard you can lean against it.',
                'bio' => 'Kakoito Hui II drives the live set from behind the kit. His playing mixes tight, economical grooves with sudden bursts of noise, giving quiet sections tension and making the heaviest moments land with purpose.',
                'motive' => 'He wants rhythm to feel less like a metronome and more like a conversation that can change direction at any second.',
            ],
            'kakoito-hui-3' => [
                'name' => 'Kakoito Hui III',
                'role' => 'Guitar · Sound design',
                'age' => '20',
                'from' => 'Hamburg',
                'experience' => '5 years',
                'quote' => 'The right noise can say more than the cleanest melody.',
                'bio' => 'Kakoito Hui III adds texture around the main riffs: feedback, octave lines and small details that reveal themselves after several listens. In the studio he experiments with pedals and unconventional tunings to give each track its own atmosphere.',
                'motive' => 'He is here to find sounds that feel unfamiliar at first and inevitable by the end of the song.',
            ],
        ];

        $files = collect(glob(public_path('images/members/*.{jpg,jpeg,png,webp}'), GLOB_BRACE) ?: [])
            ->map(function (string $path) use ($profiles): array {
                $slug = pathinfo($path, PATHINFO_FILENAME);
                $fallbackName = Str::of($slug)->replace(['-', '_'], ' ')->title()->toString();

                return array_merge([
                    'name' => $fallbackName,
                    'role' => 'Band member',
                    'age' => '—',
                    'from' => 'Berlin',
                    'experience' => 'Independent musician',
                    'quote' => 'Every new voice changes what the whole group can become.',
                    'bio' => $fallbackName.' brings an individual sound, perspective and stage presence to the group. This profile is ready to be replaced with their personal story, musical background and role in the band.',
                    'motive' => 'They joined to make honest music with people who value instinct, discipline and the energy of playing together.',
                ], $profiles[$slug] ?? [], [
                    'slug' => $slug,
                    'image' => '/images/members/'.basename($path),
                ]);
            })
            ->sortBy(function (array $member): string {
                $order = ['labaduk' => '00', 'mongol' => '01'];

                return ($order[$member['slug']] ?? '10').$member['slug'];
            })
            ->values();

        return view('landing.pages.about', ['members' => $files]);
    }
}
