<?php

namespace Tests\Feature\Landing;

use Tests\TestCase;

class VisitRulesPageTest extends TestCase
{
    public function test_visit_rules_page_renders_all_twelve_rules(): void
    {
        $this->get('/en/visit-rules')
            ->assertOk()
            ->assertSee('Visit rules')
            ->assertSee('Arrive on time')
            ->assertSee('Protect your hearing')
            ->assertSee('Leave it as found')
            ->assertSee('12');
    }
}
