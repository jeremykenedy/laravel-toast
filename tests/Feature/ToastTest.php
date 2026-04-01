<?php

use Jeremykenedy\LaravelToast\Facades\Toast;
use Jeremykenedy\LaravelToast\Services\ToastManager;
use Jeremykenedy\LaravelToast\Traits\HasToasts;

// ─── Facade Tests ───────────────────────────────────────────────

it('facade resolves to ToastManager', function () {
    Toast::success('Test');
    expect(Toast::get())->toHaveCount(1);
});

it('facade success creates a success toast', function () {
    Toast::success('Great!');
    $toasts = Toast::get();

    expect($toasts[0]['type'])->toBe('success')
        ->and($toasts[0]['message'])->toBe('Great!');
});

it('facade error creates an error toast', function () {
    Toast::error('Bad!', 'Error Title');
    $toasts = Toast::get();

    expect($toasts[0]['type'])->toBe('error')
        ->and($toasts[0]['title'])->toBe('Error Title');
});

it('facade warning creates a warning toast', function () {
    Toast::warning('Careful!');
    expect(Toast::get()[0]['type'])->toBe('warning');
});

it('facade info creates an info toast', function () {
    Toast::info('FYI');
    expect(Toast::get()[0]['type'])->toBe('info');
});

it('facade clear removes all toasts', function () {
    Toast::success('One');
    Toast::error('Two');
    Toast::clear();

    expect(Toast::get())->toBeEmpty();
});

// ─── Global Helper Tests ────────────────────────────────────────

it('toast() helper returns ToastManager instance', function () {
    expect(toast())->toBeInstanceOf(ToastManager::class);
});

it('toast() helper with message creates a toast', function () {
    toast('Hello');
    $toasts = app(ToastManager::class)->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['message'])->toBe('Hello')
        ->and($toasts[0]['type'])->toBe('success');
});

it('toast() helper with type creates typed toast', function () {
    toast('Bad thing', 'error');
    $toasts = app(ToastManager::class)->get();

    expect($toasts[0]['type'])->toBe('error');
});

it('toast() helper with title creates titled toast', function () {
    toast('Body', 'info', 'Title');
    $toasts = app(ToastManager::class)->get();

    expect($toasts[0]['title'])->toBe('Title');
});

it('toast() helper with duration creates timed toast', function () {
    toast('Quick', 'success', null, 1000);
    $toasts = app(ToastManager::class)->get();

    expect($toasts[0]['duration'])->toBe(1000);
});

it('toast() helper chained calls work', function () {
    toast()->success('A')->error('B')->warning('C');
    $toasts = app(ToastManager::class)->get();

    expect($toasts)->toHaveCount(3);
});

it('toast() helper without message does not create toast', function () {
    toast();
    expect(app(ToastManager::class)->get())->toBeEmpty();
});

// ─── HasToasts Trait Tests ──────────────────────────────────────

it('HasToasts trait has all expected methods', function () {
    $methods = (new ReflectionClass(HasToasts::class))->getMethods();
    $names = array_map(fn ($m) => $m->getName(), $methods);

    expect($names)->toContain('toast')
        ->and($names)->toContain('toastSuccess')
        ->and($names)->toContain('toastError')
        ->and($names)->toContain('toastWarning')
        ->and($names)->toContain('toastInfo');
});

it('HasToasts trait toast() returns ToastManager', function () {
    $obj = new class() {
        use HasToasts;

        public function getToast(): ToastManager
        {
            return $this->toast();
        }
    };

    expect($obj->getToast())->toBeInstanceOf(ToastManager::class);
});

it('HasToasts trait toastSuccess creates success toast', function () {
    $obj = new class() {
        use HasToasts;

        public function doIt(): static
        {
            return $this->toastSuccess('Trait success', 'Title');
        }
    };

    $obj->doIt();
    $toasts = app(ToastManager::class)->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('success')
        ->and($toasts[0]['title'])->toBe('Title');
});

it('HasToasts trait toastError creates error toast', function () {
    $obj = new class() {
        use HasToasts;

        public function doIt(): static
        {
            return $this->toastError('Trait error');
        }
    };

    $obj->doIt();
    expect(app(ToastManager::class)->get()[0]['type'])->toBe('error');
});

it('HasToasts trait toastWarning creates warning toast', function () {
    $obj = new class() {
        use HasToasts;

        public function doIt(): static
        {
            return $this->toastWarning('Trait warning');
        }
    };

    $obj->doIt();
    expect(app(ToastManager::class)->get()[0]['type'])->toBe('warning');
});

it('HasToasts trait toastInfo creates info toast', function () {
    $obj = new class() {
        use HasToasts;

        public function doIt(): static
        {
            return $this->toastInfo('Trait info');
        }
    };

    $obj->doIt();
    expect(app(ToastManager::class)->get()[0]['type'])->toBe('info');
});

// ─── ServiceProvider Tests ──────────────────────────────────────

it('registers config defaults', function () {
    expect(config('toast.position'))->toBe('top-right')
        ->and(config('toast.duration'))->toBe(5000)
        ->and(config('toast.max_visible'))->toBe(5)
        ->and(config('toast.session_key'))->toBe('toast_notifications');
});

it('registers toast blade directive', function () {
    $directives = app('blade.compiler')->getCustomDirectives();
    expect($directives)->toHaveKey('toasts');
});

// ─── View Rendering Tests: Tailwind ─────────────────────────────

it('renders tailwind blade view with toasts', function () {
    config(['ui-kit.css_framework' => 'tailwind']);
    app(ToastManager::class)->success('TW Success');

    $html = view('toast::toasts')->render();

    expect($html)->toContain('TW Success')
        ->and($html)->toContain('x-data')
        ->and($html)->toContain('bg-green-50')
        ->and($html)->toContain('dark:bg-green-950')
        ->and($html)->not->toContain('alert-success')
        ->and($html)->not->toContain('toast-body');
});

it('renders tailwind view with all four toast types', function () {
    config(['ui-kit.css_framework' => 'tailwind']);
    $manager = app(ToastManager::class);
    $manager->success('S')->error('E')->warning('W')->info('I');

    $html = view('toast::toasts')->render();

    expect($html)->toContain('bg-green-50')
        ->and($html)->toContain('bg-red-50')
        ->and($html)->toContain('bg-amber-50')
        ->and($html)->toContain('bg-blue-50');
});

it('renders tailwind view with dark mode classes', function () {
    config(['ui-kit.css_framework' => 'tailwind']);
    app(ToastManager::class)->success('Dark test');

    $html = view('toast::toasts')->render();

    expect($html)->toContain('dark:bg-green-950')
        ->and($html)->toContain('dark:border-green-800')
        ->and($html)->toContain('dark:text-green-200');
});

it('renders tailwind view empty when no toasts', function () {
    config(['ui-kit.css_framework' => 'tailwind']);
    $html = view('toast::toasts')->render();

    expect(trim($html))->toBeEmpty();
});

it('tailwind view has aria attributes', function () {
    config(['ui-kit.css_framework' => 'tailwind']);
    app(ToastManager::class)->success('Accessible');

    $html = view('toast::toasts')->render();

    expect($html)->toContain('role="alert"')
        ->and($html)->toContain('aria-live="polite"')
        ->and($html)->toContain('aria-label');
});

it('tailwind view has x-cloak for flash prevention', function () {
    config(['ui-kit.css_framework' => 'tailwind']);
    app(ToastManager::class)->info('Cloak test');

    $html = view('toast::toasts')->render();

    expect($html)->toContain('x-cloak');
});

// ─── View Rendering Tests: Bootstrap 5 ─────────────────────────

it('renders bootstrap5 blade view with toasts', function () {
    config(['ui-kit.css_framework' => 'bootstrap5']);

    $this->app->make('view')->getFinder()->flush();
    $css = config('ui-kit.css_framework', 'tailwind');
    $path = base_path('packages/laravel-toast/resources/views/'.$css.'/blade');
    $this->app->make('view')->addNamespace('toast', $path);

    app(ToastManager::class)->success('BS5 Success');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('BS5 Success')
        ->and($html)->toContain('toast-body')
        ->and($html)->toContain('text-bg-success')
        ->and($html)->toContain('btn-close')
        ->and($html)->not->toContain('x-data')
        ->and($html)->not->toContain('bg-green-50');
});

it('renders bootstrap5 error toast as danger variant', function () {
    config(['ui-kit.css_framework' => 'bootstrap5']);

    $this->app->make('view')->getFinder()->flush();
    $path = base_path('packages/laravel-toast/resources/views/bootstrap5/blade');
    $this->app->make('view')->addNamespace('toast', $path);

    app(ToastManager::class)->error('BS5 Error');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('text-bg-danger');
});

it('renders bootstrap5 view with auto-dismiss script', function () {
    config(['ui-kit.css_framework' => 'bootstrap5']);

    $this->app->make('view')->getFinder()->flush();
    $path = base_path('packages/laravel-toast/resources/views/bootstrap5/blade');
    $this->app->make('view')->addNamespace('toast', $path);

    app(ToastManager::class)->info('Script test');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('<script>')
        ->and($html)->toContain('DOMContentLoaded');
});

// ─── View Rendering Tests: Bootstrap 4 ─────────────────────────

it('renders bootstrap4 blade view with toasts', function () {
    config(['ui-kit.css_framework' => 'bootstrap4']);

    $this->app->make('view')->getFinder()->flush();
    $path = base_path('packages/laravel-toast/resources/views/bootstrap4/blade');
    $this->app->make('view')->addNamespace('toast', $path);

    app(ToastManager::class)->success('BS4 Success');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('BS4 Success')
        ->and($html)->toContain('alert-success')
        ->and($html)->toContain('alert-dismissible')
        ->and($html)->toContain('data-dismiss="alert"')
        ->and($html)->not->toContain('x-data')
        ->and($html)->not->toContain('bg-green-50');
});

it('renders bootstrap4 error toast as danger variant', function () {
    config(['ui-kit.css_framework' => 'bootstrap4']);

    $this->app->make('view')->getFinder()->flush();
    $path = base_path('packages/laravel-toast/resources/views/bootstrap4/blade');
    $this->app->make('view')->addNamespace('toast', $path);

    app(ToastManager::class)->error('BS4 Error');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('alert-danger');
});

it('renders bootstrap4 view with auto-dismiss script', function () {
    config(['ui-kit.css_framework' => 'bootstrap4']);

    $this->app->make('view')->getFinder()->flush();
    $path = base_path('packages/laravel-toast/resources/views/bootstrap4/blade');
    $this->app->make('view')->addNamespace('toast', $path);

    app(ToastManager::class)->warning('Script test');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('<script>')
        ->and($html)->toContain('data-duration');
});

// ─── Position Tests Across Frameworks ───────────────────────────

it('tailwind view reflects top-right position', function () {
    config(['ui-kit.css_framework' => 'tailwind', 'toast.position' => 'top-right']);
    app(ToastManager::class)->success('Pos test');

    $html = view('toast::toasts')->render();
    expect($html)->toContain('top-20')
        ->and($html)->toContain('right-4');
});

it('tailwind view reflects bottom-left position', function () {
    config(['ui-kit.css_framework' => 'tailwind', 'toast.position' => 'bottom-left']);
    app(ToastManager::class)->success('Pos test');

    $html = view('toast::toasts')->render();
    expect($html)->toContain('bottom-4')
        ->and($html)->toContain('left-4');
});

it('tailwind view reflects top-center position', function () {
    config(['ui-kit.css_framework' => 'tailwind', 'toast.position' => 'top-center']);
    app(ToastManager::class)->success('Pos test');

    $html = view('toast::toasts')->render();
    expect($html)->toContain('left-1/2')
        ->and($html)->toContain('-translate-x-1/2');
});

it('bootstrap5 view reflects bottom-right position', function () {
    config(['ui-kit.css_framework' => 'bootstrap5', 'toast.position' => 'bottom-right']);

    $this->app->make('view')->getFinder()->flush();
    $path = base_path('packages/laravel-toast/resources/views/bootstrap5/blade');
    $this->app->make('view')->addNamespace('toast', $path);

    app(ToastManager::class)->success('Pos test');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('bottom: 1rem')
        ->and($html)->toContain('right: 1rem');
});

it('bootstrap4 view reflects top-left position', function () {
    config(['ui-kit.css_framework' => 'bootstrap4', 'toast.position' => 'top-left']);

    $this->app->make('view')->getFinder()->flush();
    $path = base_path('packages/laravel-toast/resources/views/bootstrap4/blade');
    $this->app->make('view')->addNamespace('toast', $path);

    app(ToastManager::class)->success('Pos test');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('top: 1rem')
        ->and($html)->toContain('left: 1rem');
});

// ─── Flash Message Conversion in Views ──────────────────────────

it('tailwind view auto-converts flash messages when enabled', function () {
    config(['ui-kit.css_framework' => 'tailwind', 'toast.convert_flash' => true]);
    session()->flash('success', 'Auto converted!');

    $html = view('toast::toasts')->render();

    expect($html)->toContain('Auto converted!');
});

it('tailwind view does not auto-convert when disabled', function () {
    config(['ui-kit.css_framework' => 'tailwind', 'toast.convert_flash' => false]);
    session()->flash('success', 'Should not appear');

    $html = view('toast::toasts')->render();

    expect(trim($html))->toBeEmpty();
});

// ─── Toast Title Display ────────────────────────────────────────

it('tailwind view displays toast title', function () {
    config(['ui-kit.css_framework' => 'tailwind']);
    app(ToastManager::class)->success('Body text', 'Title text');

    $html = view('toast::toasts')->render();

    expect($html)->toContain('Title text')
        ->and($html)->toContain('Body text');
});

// ─── Artisan Command Tests ──────────────────────────────────────

it('toast:install command runs with valid options', function () {
    $this->artisan('toast:install', [
        '--css'            => 'tailwind',
        '--frontend'       => 'blade',
        '--no-interaction' => true,
    ])->assertSuccessful();
});

it('toast:install command fails with invalid css', function () {
    $this->artisan('toast:install', [
        '--css'            => 'invalid',
        '--frontend'       => 'blade',
        '--no-interaction' => true,
    ])->assertFailed();
});

it('toast:install command fails with invalid frontend', function () {
    $this->artisan('toast:install', [
        '--css'            => 'tailwind',
        '--frontend'       => 'invalid',
        '--no-interaction' => true,
    ])->assertFailed();
});

it('toast:install accepts bootstrap5', function () {
    $this->artisan('toast:install', [
        '--css'            => 'bootstrap5',
        '--frontend'       => 'blade',
        '--no-interaction' => true,
    ])->assertSuccessful();
});

it('toast:install accepts bootstrap4', function () {
    $this->artisan('toast:install', [
        '--css'            => 'bootstrap4',
        '--frontend'       => 'blade',
        '--no-interaction' => true,
    ])->assertSuccessful();
});

it('toast:install accepts livewire frontend', function () {
    $this->artisan('toast:install', [
        '--css'            => 'tailwind',
        '--frontend'       => 'livewire',
        '--no-interaction' => true,
    ])->assertSuccessful();
});

it('toast:install accepts vue frontend', function () {
    $this->artisan('toast:install', [
        '--css'            => 'tailwind',
        '--frontend'       => 'vue',
        '--no-interaction' => true,
    ])->assertSuccessful();
});

it('toast:install accepts react frontend', function () {
    $this->artisan('toast:install', [
        '--css'            => 'tailwind',
        '--frontend'       => 'react',
        '--no-interaction' => true,
    ])->assertSuccessful();
});

it('toast:install accepts svelte frontend', function () {
    $this->artisan('toast:install', [
        '--css'            => 'tailwind',
        '--frontend'       => 'svelte',
        '--no-interaction' => true,
    ])->assertSuccessful();
});

// All 15 CSS + Frontend combinations for install
$cssFrameworks = ['tailwind', 'bootstrap5', 'bootstrap4'];
$frontendFrameworks = ['blade', 'livewire', 'vue', 'react', 'svelte'];

foreach ($cssFrameworks as $css) {
    foreach ($frontendFrameworks as $frontend) {
        it("toast:install works with {$css} + {$frontend}", function () use ($css, $frontend) {
            $this->artisan('toast:install', [
                '--css'            => $css,
                '--frontend'       => $frontend,
                '--no-interaction' => true,
            ])->assertSuccessful();
        });
    }
}

it('toast:switch command switches css framework', function () {
    $this->artisan('toast:switch', [
        '--css' => 'bootstrap5',
    ])->assertSuccessful();
});

it('toast:switch command switches frontend framework', function () {
    $this->artisan('toast:switch', [
        '--frontend' => 'livewire',
    ])->assertSuccessful();
});

it('toast:switch command switches both at once', function () {
    $this->artisan('toast:switch', [
        '--css'      => 'bootstrap4',
        '--frontend' => 'vue',
    ])->assertSuccessful();
});

it('toast:switch fails with no options', function () {
    $this->artisan('toast:switch')->assertFailed();
});

it('toast:switch fails with invalid css', function () {
    $this->artisan('toast:switch', [
        '--css' => 'material',
    ])->assertFailed();
});

it('toast:switch fails with invalid frontend', function () {
    $this->artisan('toast:switch', [
        '--frontend' => 'angular',
    ])->assertFailed();
});

// All CSS switch combinations
foreach ($cssFrameworks as $css) {
    it("toast:switch works with --css={$css}", function () use ($css) {
        $this->artisan('toast:switch', ['--css' => $css])->assertSuccessful();
    });
}

// All frontend switch combinations
foreach ($frontendFrameworks as $frontend) {
    it("toast:switch works with --frontend={$frontend}", function () use ($frontend) {
        $this->artisan('toast:switch', ['--frontend' => $frontend])->assertSuccessful();
    });
}

// All 15 switch combinations
foreach ($cssFrameworks as $css) {
    foreach ($frontendFrameworks as $frontend) {
        it("toast:switch works with {$css} + {$frontend}", function () use ($css, $frontend) {
            $this->artisan('toast:switch', [
                '--css'      => $css,
                '--frontend' => $frontend,
            ])->assertSuccessful();
        });
    }
}
