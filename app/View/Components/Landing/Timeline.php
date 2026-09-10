<?php

namespace App\View\Components\Landing;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Timeline extends Component
{
    /**
     * @var list<string>
     */
    public array $lessons = [
        'landing.timeline.lesson_01',
        'landing.timeline.lesson_02',
        'landing.timeline.lesson_03',
        'landing.timeline.lesson_04',
        'landing.timeline.lesson_05',
        'landing.timeline.lesson_06',
        'landing.timeline.lesson_07',
        'landing.timeline.lesson_08',
    ];

    public function render(): View
    {
        return view('landing.components.timeline');
    }
}
