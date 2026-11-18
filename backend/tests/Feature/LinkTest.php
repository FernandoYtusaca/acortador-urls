<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkTest extends TestCase
{
    use RefreshDatabase;

    private function makeLink(User $user, array $attributes = []): Link
    {
        return $user->links()->create(array_merge([
            'original_url' => 'https://example.com',
            'code' => 'abc123',
        ], $attributes));
    }

    public function test_user_can_create_link_with_generated_code(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/links', ['original_url' => 'https://laravel.com/docs'])
            ->assertCreated()
            ->assertJsonStructure(['id', 'code', 'short_url', 'total_clicks', 'is_expired'])
            ->assertJsonPath('total_clicks', 0)
            ->assertJsonPath('is_expired', false);

        $this->assertMatchesRegularExpression('/^[a-z0-9]{6}$/', $response->json('code'));
        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://laravel.com/docs',
        ]);
    }

    public function test_generated_codes_use_only_lowercase_letters_and_digits(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]{6}$/', Link::generateCode());
        }
    }

    public function test_user_can_create_link_with_custom_alias(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/links', ['original_url' => 'https://vuejs.org', 'alias' => 'vue'])
            ->assertCreated()
            ->assertJsonPath('code', 'vue');
    }

    public function test_alias_must_be_unique(): void
    {
        $user = User::factory()->create();
        $this->makeLink($user, ['code' => 'vue']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/links', ['original_url' => 'https://vuejs.org', 'alias' => 'vue'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alias');
    }

    public function test_invalid_or_reserved_alias_is_rejected(): void
    {
        $user = User::factory()->create();

        foreach (['Vue!', 'ab', 'api'] as $alias) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/links', ['original_url' => 'https://vuejs.org', 'alias' => $alias])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('alias');
        }
    }

    public function test_only_valid_http_urls_are_accepted(): void
    {
        $user = User::factory()->create();

        foreach (['no-es-una-url', 'ftp://example.com', 'javascript:alert(1)'] as $url) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/links', ['original_url' => $url])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('original_url');
        }
    }

    public function test_cannot_shorten_a_link_of_the_same_site(): void
    {
        config(['app.url' => 'https://short.test']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/links', ['original_url' => 'https://short.test/algo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('original_url');
    }

    public function test_expiration_must_be_in_the_future(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/links', [
                'original_url' => 'https://example.com',
                'expires_at' => now()->subDay()->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expires_at');
    }

    public function test_user_only_sees_and_changes_own_links(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $link = $this->makeLink($owner);

        $this->actingAs($other, 'sanctum')
            ->getJson('/api/links')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/links/{$link->id}")
            ->assertNotFound();

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/links/{$link->id}", ['original_url' => 'https://evil.com'])
            ->assertNotFound();

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/links/{$link->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('links', ['id' => $link->id]);
    }

    public function test_user_can_change_destination_and_remove_expiration(): void
    {
        $user = User::factory()->create();
        $link = $this->makeLink($user, ['expires_at' => now()->addWeek()]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/links/{$link->id}", [
                'original_url' => 'https://vuejs.org/guide',
                'expires_at' => null,
            ])
            ->assertOk()
            ->assertJsonPath('original_url', 'https://vuejs.org/guide')
            ->assertJsonPath('expires_at', null);
    }

    public function test_expired_link_can_be_reactivated(): void
    {
        $user = User::factory()->create();
        $link = $this->makeLink($user, ['expires_at' => now()->subMinute()]);

        $this->assertTrue($link->is_expired);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/links/{$link->id}", [
                'expires_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertOk()
            ->assertJsonPath('is_expired', false);
    }

    public function test_user_can_delete_link(): void
    {
        $user = User::factory()->create();
        $link = $this->makeLink($user);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/links/{$link->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_links_require_authentication(): void
    {
        $this->getJson('/api/links')->assertUnauthorized();
    }
}
