<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    private function makeLink(array $attributes = []): Link
    {
        return User::factory()->create()->links()->create(array_merge([
            'original_url' => 'https://example.com',
            'code' => 'abc123',
        ], $attributes));
    }

    public function test_code_redirects_to_original_url(): void
    {
        $this->makeLink();

        $this->get('/abc123')
            ->assertStatus(302)
            ->assertRedirect('https://example.com');
    }

    public function test_redirect_counts_the_click_and_stores_its_data(): void
    {
        $link = $this->makeLink();

        $this->withHeaders(['Referer' => 'https://google.com', 'User-Agent' => 'TestAgent'])
            ->get('/abc123')
            ->assertRedirect();

        $this->assertSame(1, $link->fresh()->total_clicks);
        $this->assertDatabaseHas('link_clicks', [
            'link_id' => $link->id,
            'referer' => 'https://google.com',
            'user_agent' => 'TestAgent',
        ]);
    }

    public function test_unknown_code_returns_404(): void
    {
        $this->get('/zzzzzz')->assertNotFound();
    }

    public function test_expired_link_returns_410_and_is_not_counted(): void
    {
        $link = $this->makeLink(['expires_at' => now()->subMinute()]);

        $this->get('/abc123')->assertStatus(410);

        $this->assertSame(0, $link->fresh()->total_clicks);
        $this->assertDatabaseCount('link_clicks', 0);
    }

    public function test_codes_with_uppercase_are_not_matched(): void
    {
        $this->makeLink();

        $this->get('/ABC123')->assertNotFound();
    }

    public function test_redirect_does_not_start_a_session(): void
    {
        $this->makeLink();

        $this->get('/abc123')->assertCookieMissing(config('session.cookie'));
    }
}
