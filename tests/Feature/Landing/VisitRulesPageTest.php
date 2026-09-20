<?php

namespace Tests\Feature\Landing;

use Tests\TestCase;

class VisitRulesPageTest extends TestCase
{
    public function test_rehearsal_room_rules_page_renders_all_rule_groups(): void
    {
        $this->get('/en/visit-rules')
            ->assertOk()
            ->assertSee('Room')
            ->assertSee('rules.')
            ->assertSee('Before you play.')
            ->assertSee('Please use hand sanitiser before rehearsing.')
            ->assertSee('General')
            ->assertSee('Guitars & amplifiers')
            ->assertSee('Electronics & tech')
            ->assertSee('Before you leave.')
            ->assertSee('The rehearsal room is no place for carelessness.')
            ->assertSee('Report it.');
    }
}
