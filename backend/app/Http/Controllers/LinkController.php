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
            'original_url' => [
                'required', 'url:http,https', 'max:2048',
                function ($attribute, $value, $fail) {
                    $host = parse_url($value, PHP_URL_HOST);

                    if ($host && $host === parse_url(config('app.url'), PHP_URL_HOST)) {
                        $fail('No se puede acortar un enlace de este mismo sitio.');
                    }
                },
            ],
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

    public function destroy(Request $request, string $id)
    {
        $request->user()->links()->findOrFail($id)->delete();

        return response()->noContent();
    }
}
