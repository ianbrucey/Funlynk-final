<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_terms_and_privacy_pages_are_public(): void
    {
        $this->get('/terms-of-service')->assertOk();
        $this->get('/privacy')->assertOk();
    }

    public function test_terms_short_url_redirects_to_the_terms_page(): void
    {
        $this->get('/terms')->assertRedirect('/terms-of-service');
    }

    public function test_legal_pages_disclose_the_poster_publishing_system(): void
    {
        $this->get('/terms-of-service')
            ->assertOk()
            ->assertSee('Official Social Media Accounts');

        $this->get('/privacy')
            ->assertOk()
            ->assertSee('FunLynk Poster');
    }

    public function test_site_layout_links_the_legal_pages_from_the_footer(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee(route('terms'))
            ->assertSee(route('privacy'));
    }
}
