<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LinkController extends Controller
{
    private const RESERVED = ['api', 'up', 'login', 'register', 'admin', 'storage'];

    public function index(Request $request)
    {
        return $request->user()->links()->latest()->paginate(15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'original_url' => $this->urlRules(),
            'alias' => [
                'nullable',
                'regex:/^[a-z0-9-]{3,32}$/',
                'unique:links,code',
                Rule::notIn(self::RESERVED),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $link = $request->user()->links()->create([
            'original_url' => $data['original_url'],
            'code' => $data['alias'] ?? Link::generateCode(),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return response()->json($link->refresh(), 201);
    }

    public function show(Request $request, string $id)
    {
        return $request->user()->links()->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $link = $request->user()->links()->findOrFail($id);

        $data = $request->validate([
            'original_url' => ['sometimes', ...$this->urlRules()],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ]);

        $link->update($data);

        return $link->refresh();
    }

    public function destroy(Request $request, string $id)
    {
        $request->user()->links()->findOrFail($id)->delete();

        return response()->noContent();
    }

    public function stats(Request $request, string $id)
    {
        $request->validate(['days' => 'nullable|integer|between:1,90']);

        $link = $request->user()->links()->findOrFail($id);
        $days = $request->integer('days', 30);
        $from = now()->subDays($days - 1)->startOfDay();

        $perDay = $link->clicks()
            ->where('clicked_at', '>=', $from)
            ->selectRaw('DATE(clicked_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        // Rellena con 0 los días sin clics para que la serie esté completa
        $series = collect(range(0, $days - 1))->map(function ($i) use ($from, $perDay) {
            $day = $from->copy()->addDays($i)->toDateString();

            return ['date' => $day, 'clicks' => (int) ($perDay[$day] ?? 0)];
        });

        $referers = $link->clicks()
            ->whereNotNull('referer')
            ->selectRaw('referer, COUNT(*) as total')
            ->groupBy('referer')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'link_id' => $link->id,
            'code' => $link->code,
            'total_clicks' => $link->total_clicks,
            'period_days' => $days,
            'clicks_in_period' => $series->sum('clicks'),
            'clicks_by_day' => $series,
            'top_referers' => $referers,
        ];
    }

    private function urlRules(): array
    {
        return [
            'required', 'url:http,https', 'max:2048',
            function ($attribute, $value, $fail) {
                $host = parse_url($value, PHP_URL_HOST);

                if ($host && $host === parse_url(config('app.url'), PHP_URL_HOST)) {
                    $fail('No se puede acortar un enlace de este mismo sitio.');
                }
            },
        ];
    }
}
