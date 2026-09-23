<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Jeremykenedy\LaravelToast\Events\ToastBroadcast;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use Jeremykenedy\LaravelToast\Services\ToastManager;

it('converts a flash once and expires it after the displayed response', function () {
    session()->flash('success', 'Saved once');
    session()->ageFlashData();
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    $manager->convertFlashMessages();
    expect($manager->get())->toHaveCount(1);
    session()->ageFlashData();
    expect($manager->get())->toBeEmpty();
});

it('ignores non string flash data consistently', function (string $key, mixed $value) {
    session()->flash($key, $value);
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    expect($manager->get())->toBeEmpty()->and(session($key))->toBe($value);
})->with(['success', 'error', 'warning', 'info', 'status'])->with([true, 1, [['data']]]);

it('keeps the string zero when converting a flash', function () {
    session()->flash('info', '0');
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    expect($manager->get()[0]['message'])->toBe('0');
});

it('resolves global settings into frontend payloads and supports stack overrides', function () {
    config(['toast.position' => 'bottom-left', 'toast.stack' => false, 'toast.css_framework' => 'bootstrap4']);
    $manager = app(ToastManager::class);
    $payload = $manager->build('info', 'Settings');
    expect($payload)->toMatchArray(['position' => 'bottom-left', 'stack' => false, 'css_framework' => 'bootstrap4']);
    $manager->info('First')->info('Second');
    expect($manager->get())->toHaveCount(1)->and($manager->get()[0]['message'])->toBe('Second');
    $manager->info('Third', options: ['stack' => true]);
    expect($manager->get())->toHaveCount(2);
});

it('renders only the replacement across positions for every blade framework', function (string $framework) {
    $manager = app(ToastManager::class);
    $manager->info('Superseded', options: ['position' => 'top-left']);
    $manager->info('Replacement', options: ['position' => 'bottom-right', 'stack' => false]);
    app('view')->replaceNamespace('toast', dirname(__DIR__, 2).'/resources/views/'.$framework.'/blade');
    app('view')->getFinder()->flush();
    $html = view('toast::toasts')->render();
    expect($html)->toContain('Replacement')->not->toContain('Superseded');
})->with(['tailwind', 'bootstrap4', 'bootstrap5']);

it('switches an existing toast override when ui kit is also installed', function () {
    config(['toast.css_framework' => 'tailwind', 'toast.frontend' => 'blade', 'ui-kit.css_framework' => 'tailwind', 'ui-kit.frontend' => 'blade']);
    $this->artisan('toast:switch', ['--css' => 'bootstrap5', '--frontend' => 'react'])->assertSuccessful();
    expect(ToastServiceProvider::cssFramework())->toBe('bootstrap5')
        ->and(ToastServiceProvider::frontend())->toBe('react')
        ->and(config('ui-kit.css_framework'))->toBe('tailwind');
});

it('broadcasts a payload on the configured private recipient channel without a session toast', function () {
    Event::fake([ToastBroadcast::class]);
    config(['toast.broadcast.enabled' => true, 'toast.broadcast.channel' => 'notifications.{userId}']);
    $manager = app(ToastManager::class);
    $manager->broadcast(42, 'Export ready', 'success', duration: 0);
    Event::assertDispatched(ToastBroadcast::class, function (ToastBroadcast $event) {
        return $event->broadcastOn()[0]->name === 'private-notifications.42'
            && $event->broadcastAs() === 'toast'
            && $event->broadcastWith()['toast']['message'] === 'Export ready';
    });
    expect($manager->get())->toBeEmpty();
});

it('does not broadcast while broadcasting is disabled', function () {
    Event::fake([ToastBroadcast::class]);
    config(['toast.broadcast.enabled' => false]);
    app(ToastManager::class)->broadcast(42, 'Disabled');
    Event::assertNotDispatched(ToastBroadcast::class);
});
