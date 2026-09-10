<?php

namespace App\View\Components\Landing;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LessonContent extends Component
{
    public function render(): View
    {
        return view('landing.components.lesson-content');
    }
}
