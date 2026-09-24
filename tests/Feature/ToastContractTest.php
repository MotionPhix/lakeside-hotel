<?php

/*
 * Toasts belong to the back end.
 *
 * Every message the application shows in a toast is raised by a controller as
 * Inertia flash data, and the front end shows what it was told rather than deciding
 * for itself that something worked. A component raising its own toast contradicts
 * the server the moment the server refuses - the toast claims success while the
 * error sits under the field - so the arrangement is held in place here rather
 * than left to review.
 *
 * These read the source rather than the browser, because the rule is about what
 * the front end is allowed to contain, and a toast raised on a click is not
 * something a request can be made to disprove.
 */

/**
 * Every frontend source file, keyed by its path inside `resources/js`.
 *
 * @return array<string, string>
 */
function toastFrontendSources(): array
{
    return toastSourceFilesUnder(resource_path('js'), ['ts', 'tsx']);
}

/**
 * Every source file under a directory, keyed by its path relative to it.
 *
 * @param  list<string>  $extensions
 * @return array<string, string>
 */
function toastSourceFilesUnder(string $root, array $extensions): array
{
    $root = str_replace('\\', '/', $root);

    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || ! in_array($file->getExtension(), $extensions, true)) {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());

        $files[str_replace($root.'/', '', $path)] = (string) file_get_contents($path);
    }

    return $files;
}

test('the front end never raises a toast of its own', function () {
    $offenders = [];

    foreach (toastFrontendSources() as $path => $source) {
        // The flash listener is the single exception: it is the one place that
        // shows what the back end sent.
        if ($path === 'hooks/use-flash-toast.ts') {
            continue;
        }

        // `toast.success(...)`, `toast.error(...)`, `toast['error'](...)`: any way
        // of raising one from a component. Importing the library to render the
        // container is fine, and is what the wrapper below does.
        if (preg_match('/\btoast(\.\w+|\[)/', $source) === 1) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([]);
});

test('nothing but the listener and the container knows about the library', function () {
    $importers = [];

    foreach (toastFrontendSources() as $path => $source) {
        if (str_contains($source, "'sonner'")) {
            $importers[] = $path;
        }
    }

    sort($importers);

    // The listener that shows what the back end sent, and the container that
    // displays it. Nothing else has any business with the library, however
    // convenient it would be to reach for it.
    expect($importers)->toBe([
        'components/ui/sonner.tsx',
        'hooks/use-flash-toast.ts',
    ]);
});

test('the flash listener is mounted exactly once, and app-wide', function () {
    $sources = toastFrontendSources();

    $mounts = [];

    foreach ($sources as $path => $source) {
        if (str_contains($source, 'useFlashToast();')) {
            $mounts[] = $path;
        }
    }

    // Two mounts are two listeners, and two listeners are two copies of every
    // message the back end sends.
    expect($mounts)->toBe(['components/ui/sonner.tsx']);

    // And the listener is only app-wide because the Toaster that carries it is
    // rendered once, around every page, rather than inside a layout.
    expect($sources['app.tsx'])->toContain('<Toaster />');
});

test('every toast the application shows is raised by a controller', function () {
    $flashes = 0;

    foreach (toastSourceFilesUnder(app_path('Http'), ['php']) as $source) {
        $flashes += substr_count($source, "Inertia::flash('toast'");
    }

    // The channel is only worth having if something fills it.
    expect($flashes)->toBeGreaterThan(0);
});
