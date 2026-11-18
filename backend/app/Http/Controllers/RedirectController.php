<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RedirectController extends Controller
{
    public function __invoke(Request $request, string $code)
    {
        $link = Link::where('code', $code)->first();

        abort_if($link === null, 404);
        abort_if($link->is_expired, 410, 'Este enlace ha expirado.');

        DB::transaction(function () use ($link, $request) {
            $link->increment('total_clicks');
            $link->clicks()->create([
                'referer' => $this->limit($request->headers->get('referer')),
                'user_agent' => $this->limit($request->userAgent()),
                'clicked_at' => now(),
            ]);
        });

        return redirect()->away($link->original_url);
    }

    private function limit(?string $value): ?string
    {
        return $value === null ? null : mb_substr($value, 0, 512);
    }
}
