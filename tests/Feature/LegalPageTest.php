<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class LegalPageTest extends TestCase
{
    public function test_privacy_policy_page_renders(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('government-issued ID');
    }

    public function test_terms_page_renders(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms of Service')
            ->assertSee('not a gambling application');
    }
}
