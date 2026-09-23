<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The elevation scale in `resources/css/app.css` is what keeps the interface flat:
 * tight offsets, low alpha, and a navy tint instead of black.
 *
 * These guards exist because the failure is quiet rather than loud. Nothing
 * errors when a stock heavy shadow creeps back in - the interface simply starts
 * looking heavier than it was designed to, one component at a time. The checks
 * are about a ceiling rather than exact values, so the scale can still be tuned
 * without editing the tests.
 */

/**
 * Every `--shadow-*` level declared in the theme, keyed by level name.
 *
 * @return array<string, string>
 */
$elevationTokens = function (): array {
    preg_match_all(
        '/--shadow-(2xs|xs|sm|md|lg|xl|2xl):\s*(.+?);/s',
        file_get_contents(resource_path('css/app.css')),
        $matches,
        PREG_SET_ORDER,
    );

    return collect($matches)
        ->mapWithKeys(fn (array $match): array => [$match[1] => Str::squish($match[2])])
        ->all();
};

/**
 * The blur radius of each shadow in a value such as
 * "0 2px 4px -1px rgb(23 50 77 / 0.06), 0 1px 2px -1px rgb(23 50 77 / 0.04)".
 *
 * Colours are written in the space separated syntax, which is what makes a comma
 * unambiguous: it always separates one shadow from the next, never a colour.
 *
 * @return list<int>
 */
$shadowBlurs = function (string $value): array {
    return collect(explode(',', $value))
        ->map(fn (string $shadow): int => (int) Str::of($shadow)
            ->before('rgb')
            ->squish()
            ->explode(' ')
            ->get(2, '0'))
        ->all();
};

test('the elevation scale defines every shadow level the interface can ask for', function () use ($elevationTokens) {
    expect(array_keys($elevationTokens()))
        ->toEqualCanonicalizing(['2xs', 'xs', 'sm', 'md', 'lg', 'xl', '2xl']);
});

test('no shadow level is black tinted or wider than a whisper', function () use ($elevationTokens, $shadowBlurs) {
    foreach ($elevationTokens() as $level => $value) {
        expect($value)
            ->not->toContain('rgb(0 0 0')
            ->not->toContain('#000');

        // A parser that quietly found nothing would make this whole test
        // vacuous, so the blur list has to actually be there.
        expect($shadowBlurs($value))->not->toBeEmpty();

        foreach ($shadowBlurs($value) as $blur) {
            expect($blur)->toBeLessThanOrEqual(12, "shadow-{$level} blurs {$blur}px");
        }
    }
});

test('no component writes a heavier shadow of its own', function () use ($shadowBlurs) {
    $offenders = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        preg_match_all('/shadow-\[([^\]]+)\]/', file_get_contents($file->getPathname()), $matches);

        foreach ($matches[1] as $raw) {
            $value = str_replace('_', ' ', $raw);

            if (str_contains($value, '#000') || preg_match('/rgba?\(\s*0[,\s]+0[,\s]+0\b/', $value)) {
                $offenders[] = "{$file->getFilename()}: black tinted shadow-[{$raw}]";
            }

            foreach ($shadowBlurs($value) as $blur) {
                if ($blur > 12) {
                    $offenders[] = "{$file->getFilename()}: {$blur}px blur in shadow-[{$raw}]";
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});
