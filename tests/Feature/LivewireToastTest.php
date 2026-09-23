<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Jeremykenedy\LaravelToast\Livewire\ToastContainer;
use Jeremykenedy\LaravelToast\Services\ToastManager;
use Livewire\Livewire;

it('registers the toast-container component', function () {
    expect(Livewire::new('toast-container'))->toBeInstanceOf(ToastContainer::class);
});

it('picks up session toasts on mount', function () {
    app(ToastManager::class)->success('From session');

    Livewire::test(ToastContainer::class)
        ->assertSee('From session')
        ->assertCount('toasts', 1);
});

it('converts flash messages on mount when enabled', function () {
    config(['toast.convert_flash' => true]);
    session()->flash('error', 'Flashed failure');

    Livewire::test(ToastContainer::class)->assertSee('Flashed failure');
});

it('leaves flash messages alone when conversion is disabled', function () {
    config(['toast.convert_flash' => false]);
    session()->flash('error', 'Flashed failure');

    Livewire::test(ToastContainer::class)
        ->assertDontSee('Flashed failure')
        ->assertCount('toasts', 0);
});

it('adds a toast from the toast event', function () {
    Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'Dispatched', type: 'warning')
        ->assertSet('toasts.0.message', 'Dispatched')
        ->assertSet('toasts.0.type', 'warning');
});

it('falls back to info for an unknown dispatched type', function () {
    Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'Odd type', type: 'catastrophe')
        ->assertSet('toasts.0.type', 'info');
});

it('supports the per type shortcut events', function (string $event, string $type) {
    Livewire::test(ToastContainer::class)
        ->dispatch($event, message: 'Shortcut')
        ->assertSet('toasts.0.type', $type);
})->with([
    ['toast-success', 'success'],
    ['toast-error', 'error'],
    ['toast-warning', 'warning'],
    ['toast-info', 'info'],
]);

it('applies per toast options from the dispatched payload', function () {
    Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'RTL', type: 'info', options: [
            'dir'            => 'rtl',
            'exit_animation' => 'slide-left',
            'show_close'     => false,
        ])
        ->assertSet('toasts.0.dir', 'rtl')
        ->assertSet('toasts.0.exit_animation', 'slide-left')
        ->assertSet('toasts.0.show_close', false);
});

it('falls back to config defaults for options that are not passed', function () {
    config(['toast.dir' => 'rtl', 'toast.enter_animation' => 'fade', 'toast.duration' => 1234]);

    Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'Defaults')
        ->assertSet('toasts.0.dir', 'rtl')
        ->assertSet('toasts.0.enter_animation', 'fade')
        ->assertSet('toasts.0.duration', 1234);
});

it('caps the stack at max_visible', function () {
    config(['toast.max_visible' => 3, 'toast.stack' => true]);

    $component = Livewire::test(ToastContainer::class);

    foreach (range(1, 6) as $i) {
        $component->dispatch('toast', message: "Message {$i}");
    }

    $component->assertCount('toasts', 3)
        ->assertSet('toasts.0.message', 'Message 4')
        ->assertSet('toasts.2.message', 'Message 6');
});

it('replaces instead of stacking when stacking is off', function () {
    config(['toast.stack' => false]);

    Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'First')
        ->dispatch('toast', message: 'Second')
        ->assertCount('toasts', 1)
        ->assertSet('toasts.0.message', 'Second');
});

it('dismisses a toast by id and leaves the others', function () {
    $component = Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'Keep me')
        ->dispatch('toast', message: 'Remove me');

    $id = $component->get('toasts')[1]['id'];

    $component->call('dismiss', $id)
        ->assertCount('toasts', 1)
        ->assertSet('toasts.0.message', 'Keep me');
});

it('ignores a dismiss for an unknown id', function () {
    Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'Still here')
        ->call('dismiss', 'toast_does_not_exist')
        ->assertCount('toasts', 1);
});

it('builds the same payload shape as the manager', function () {
    $component = Livewire::test(ToastContainer::class)->dispatch('toast', message: 'Shape');

    $fromComponent = array_keys($component->get('toasts')[0]);
    $fromManager = array_keys(app(ToastManager::class)->build('info', 'Shape'));

    sort($fromComponent);
    sort($fromManager);

    expect($fromComponent)->toBe($fromManager);
});

it('renders the css framework specific markup', function (string $css, string $expected, string $notExpected) {
    config(['toast.css_framework' => $css]);

    $path = $css === 'tailwind'
        ? realpath(__DIR__.'/../../resources/views/livewire')
        : realpath(__DIR__."/../../resources/views/livewire/{$css}");

    $this->app->make('view')->getFinder()->flush();
    $this->app->make('view')->replaceNamespace('toast-livewire', [
        $path,
        realpath(__DIR__.'/../../resources/views/livewire'),
    ]);

    $html = Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'Framework', type: 'success')
        ->html();

    expect($html)->toContain($expected)
        ->and($html)->not->toContain($notExpected);
})->with([
    ['tailwind', 'bg-green-50', 'text-bg-success'],
    ['bootstrap5', 'text-bg-success', 'bg-green-50'],
    ['bootstrap4', 'alert-success', 'bg-green-50'],
]);

it('never puts alpine directives in a livewire view', function (string $file) {
    $source = file_get_contents(dirname(__DIR__, 2)."/resources/views/livewire/{$file}");

    expect($source)->not->toContain('x-data')
        ->and($source)->toContain('wire:key');
})->with([
    'toast-container.blade.php',
    'bootstrap5/toast-container.blade.php',
    'bootstrap4/toast-container.blade.php',
]);

it('honors a per toast replacement without restoring old session toasts', function () {
    $component = Livewire::test(ToastContainer::class)
        ->dispatch('toast', message: 'Old', options: ['position' => 'top-left'])
        ->dispatch('toast', message: 'New', options: ['position' => 'bottom-right', 'stack' => false])
        ->assertCount('toasts', 1)
        ->assertSet('toasts.0.message', 'New');

    $component->call('dismiss', $component->get('toasts')[0]['id'])->assertCount('toasts', 0);
});

it('subscribes to the authenticated users configured private channel', function () {
    config(['toast.broadcast.enabled' => true, 'toast.broadcast.channel' => 'notifications.{userId}']);
    Auth::shouldReceive('id')->andReturn(42);

    expect(Livewire::test(ToastContainer::class)->instance()->getListeners())
        ->toBe(['echo-private:notifications.42,.toast' => 'receiveBroadcast']);
});

it('does not subscribe a guest to broadcasts', function () {
    config(['toast.broadcast.enabled' => true]);
    Auth::shouldReceive('id')->andReturn(null);

    expect(Livewire::test(ToastContainer::class)->instance()->getListeners())->toBe([]);
});

it('receives a broadcast with its identity and ignores duplicates', function () {
    $payload = app(ToastManager::class)->build('success', 'Broadcast payload', options: ['stack' => false]);
    Livewire::test(ToastContainer::class)
        ->call('receiveBroadcast', ['toast' => $payload])
        ->call('receiveBroadcast', ['toast' => $payload])
        ->assertCount('toasts', 1)
        ->assertSet('toasts.0.id', $payload['id'])
        ->assertSee('Broadcast payload');
});
