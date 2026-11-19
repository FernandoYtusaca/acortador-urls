<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo',
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $sites = [
            ['https://laravel.com/docs', 'laravel'],
            ['https://vuejs.org/guide/introduction.html', 'vue'],
            ['https://developer.mozilla.org/es/docs/Web/JavaScript', 'js-docs'],
            ['https://github.com', null],
            ['https://www.php.net/manual/es/', null],
        ];

        foreach ($sites as [$url, $alias]) {
            $link = $user->links()->create([
                'original_url' => $url,
                'code' => $alias ?? Link::generateCode(),
            ]);

            $clicks = random_int(5, 60);

            for ($i = 0; $i < $clicks; $i++) {
                $link->clicks()->create([
                    'clicked_at' => now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1439)),
                    'referer' => fake()->randomElement([null, 'https://google.com', 'https://twitter.com', 'https://linkedin.com']),
                    'user_agent' => 'Seeder',
                ]);
            }

            $link->forceFill(['total_clicks' => $clicks])->save();
        }

        // Un enlace con expiración futura y otro ya vencido, para ver los tres estados
        $user->links()->create([
            'original_url' => 'https://example.com/promo',
            'code' => 'promo',
            'expires_at' => now()->addDays(10),
        ]);

        $user->links()->create([
            'original_url' => 'https://example.com/oferta',
            'code' => 'oferta',
            'expires_at' => now()->subDay(),
        ]);
    }
}
