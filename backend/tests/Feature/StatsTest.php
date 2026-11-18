<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLink(User $user): Link
    {
        return $user->links()->create([
            'original_url' => 'https://example.com',
            'code' => 'abc123',
        ]);
    }

    public function test_stats_return_a_complete_series_with_zeros(): void
    {
        $user = User::factory()->create();
        $link = $this->makeLink($user);

        $link->clicks()->create(['clicked_at' => now()]);
        $link->clicks()->create(['clicked_at' => now()]);
        $link->clicks()->create(['clicked_at' => now()->subDay()]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/links/{$link->id}/stats?days=7")
            ->assertOk()
            ->assertJsonCount(7, 'clicks_by_day')
            ->assertJsonPath('clicks_in_period', 3);

        $series = $response->json('clicks_by_day');

        $this->assertSame(2, $series[6]['clicks']); // hoy
        $this->assertSame(1, $series[5]['clicks']); // ayer
        $this->assertSame(0, $series[0]['clicks']); // hace 6 días
    }

    public function test_stats_list_top_referers(): void
    {
        $user = User::factory()->create();
        $link = $this->makeLink($user);

        $link->clicks()->create(['clicked_at' => now(), 'referer' => 'https://google.com']);
        $link->clicks()->create(['clicked_at' => now(), 'referer' => 'https://google.com']);
        $link->clicks()->create(['clicked_at' => now(), 'referer' => 'https://twitter.com']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/links/{$link->id}/stats")
            ->assertOk()
            ->assertJsonPath('top_referers.0.referer', 'https://google.com');

        $this->assertEquals(2, $response->json('top_referers.0.total'));
    }

    public function test_days_parameter_is_validated(): void
    {
        $user = User::factory()->create();
        $link = $this->makeLink($user);

        foreach ([0, 100] as $days) {
            $this->actingAs($user, 'sanctum')
                ->getJson("/api/links/{$link->id}/stats?days={$days}")
                ->assertUnprocessable()
                ->assertJsonValidationErrors('days');
        }
    }

    public function test_cannot_see_stats_of_another_users_link(): void
    {
        $link = $this->makeLink(User::factory()->create());

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/links/{$link->id}/stats")
            ->assertNotFound();
    }
}
