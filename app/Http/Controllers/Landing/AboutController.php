<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

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
                'inspired_by' => null,
            ],
            'mongol' => [
                'name' => 'Nastya',
                'role' => 'Rhythmusgitarre',
                'age' => '16',
                'from' => null,
                'experience' => null,
                'quote' => 'Musik ist für mich eine Art, mich selbst auszudrücken.',
                'bio' => 'Ich spiele Rhythmusgitarre und liebe Rockmusik. Schon als Kind wollte ich Gitarre spielen, da mein Vater selbst lange Gitarre gespielt hat. Mit etwa sechs Jahren begann ich, mich für dieses Instrument zu begeistern. Mit 12 bekam ich meine erste Gitarre zum Geburtstag und wusste sofort, dass Musik ein wichtiger Teil meines Lebens sein soll.',
                'motive' => 'Mein größtes Vorbild als Gitarrist ist Izzy Stradlin – ich bewundere seinen Spielstil und seine Gitarrenparts. Außerdem inspiriert mich Marilyn Manson mit seiner besonderen Musik und Atmosphäre.',
                'inspired_by' => 'Izzy Stradlin · Marilyn Manson',
            ],
            'bogdan' => [
                'name' => 'Bogdan',
                'role' => 'Schlagzeug',
                'age' => '17',
                'from' => null,
                'experience' => null,
                'quote' => 'Sepultura do Brasil! Um, dois, três, quatro!',
                'bio' => 'Zu meiner Rolle als Schlagzeuger fand ich dank Joey Jordison von Slipknot – meinem damaligen absoluten Lieblingsschlagzeuger. Ich wollte genauso schnell und präzise sein wie er, und deshalb begann ich, Schlagzeug zu spielen.',
                'motive' => null,
                'inspired_by' => 'Eloy Casagrande',
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
                'inspired_by' => null,
            ],
            'kakoito-hui-3' => [
                'name' => 'Fynn Opitz',
                'role' => 'Gitarre / Gesang',
                'age' => '18',
                'from' => null,
                'experience' => null,
                'quote' => null,
                'bio' => null,
                'motive' => null,
                'inspired_by' => 'Authentische, handgemachte Musik',
            ],
        ];

        $order = [
            'labaduk' => '01',
            'mongol' => '02',
            'bogdan' => '03',
            'kakoito-hui-1' => '04',
            'kakoito-hui-3' => '06',
        ];

        $files = collect(glob(public_path('images/members/*.{jpg,jpeg,png,webp}'), GLOB_BRACE) ?: [])
            ->filter(function (string $path) use ($profiles): bool {
                $slug = pathinfo($path, PATHINFO_FILENAME);

                return array_key_exists($slug, $profiles);
            })
            ->map(function (string $path) use ($profiles, $order): array {
                $slug = pathinfo($path, PATHINFO_FILENAME);

                return array_merge($profiles[$slug], [
                    'slug' => $slug,
                    'index' => $order[$slug],
                    'image' => '/images/members/'.basename($path),
                ]);
            })
            ->sortBy(function (array $member): string {
                return $member['index'].$member['slug'];
            })
            ->values();

        return view('landing.pages.about', ['members' => $files]);
    }
}
