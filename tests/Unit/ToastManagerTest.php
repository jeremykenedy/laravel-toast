<?php

use Jeremykenedy\LaravelToast\Services\ToastManager;

it('resolves toast manager from container', function () {
    expect(app(ToastManager::class))->toBeInstanceOf(ToastManager::class);
});

it('resolves as a singleton', function () {
    $a = app(ToastManager::class);
    $b = app(ToastManager::class);
    expect($a)->toBe($b);
});

it('adds a success toast', function () {
    $manager = app(ToastManager::class);
    $manager->success('Saved!');
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('success')
        ->and($toasts[0]['message'])->toBe('Saved!');
});

it('adds an error toast', function () {
    $manager = app(ToastManager::class);
    $manager->error('Failed!', 'Error');
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('error')
        ->and($toasts[0]['title'])->toBe('Error');
});

it('adds a warning toast', function () {
    $manager = app(ToastManager::class);
    $manager->warning('Careful!');
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('warning')
        ->and($toasts[0]['message'])->toBe('Careful!');
});

it('adds an info toast', function () {
    $manager = app(ToastManager::class);
    $manager->info('FYI');
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('info')
        ->and($toasts[0]['message'])->toBe('FYI');
});

it('adds toast via add() with type', function () {
    $manager = app(ToastManager::class);
    $manager->add('success', 'Direct add');
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('success')
        ->and($toasts[0]['message'])->toBe('Direct add');
});

it('defaults invalid type to info', function () {
    $manager = app(ToastManager::class);
    $manager->add('invalid_type', 'Test');
    $toasts = $manager->get();

    expect($toasts[0]['type'])->toBe('info');
});

it('includes title when provided', function () {
    $manager = app(ToastManager::class);
    $manager->success('Message', 'My Title');
    $toasts = $manager->get();

    expect($toasts[0]['title'])->toBe('My Title');
});

it('sets title to null when not provided', function () {
    $manager = app(ToastManager::class);
    $manager->success('Message');
    $toasts = $manager->get();

    expect($toasts[0]['title'])->toBeNull();
});

it('uses custom duration', function () {
    $manager = app(ToastManager::class);
    $manager->success('Quick!', null, 2000);
    $toasts = $manager->get();

    expect($toasts[0]['duration'])->toBe(2000);
});

it('uses default duration from config', function () {
    config(['toast.duration' => 7000]);
    $manager = app(ToastManager::class);
    $manager->info('Default duration');
    $toasts = $manager->get();

    expect($toasts[0]['duration'])->toBe(7000);
});

it('uses zero duration to disable auto-dismiss', function () {
    $manager = app(ToastManager::class);
    $manager->success('Persistent', null, 0);
    $toasts = $manager->get();

    expect($toasts[0]['duration'])->toBe(0);
});

it('generates unique ids for each toast', function () {
    $manager = app(ToastManager::class);
    $manager->success('A')->success('B')->success('C');
    $toasts = $manager->get();

    $ids = array_column($toasts, 'id');
    expect($ids)->toHaveCount(3)
        ->and(array_unique($ids))->toHaveCount(3);
});

it('generates ids with toast_ prefix', function () {
    $manager = app(ToastManager::class);
    $manager->success('Test');
    $toasts = $manager->get();

    expect($toasts[0]['id'])->toStartWith('toast_');
});

it('includes timestamp on each toast', function () {
    $manager = app(ToastManager::class);
    $manager->success('Test');
    $toasts = $manager->get();

    expect($toasts[0]['timestamp'])->toBeString()
        ->and($toasts[0]['timestamp'])->not->toBeEmpty();
});

it('supports fluent chaining', function () {
    $manager = app(ToastManager::class);
    $result = $manager->success('A')->error('B')->warning('C')->info('D');

    expect($result)->toBeInstanceOf(ToastManager::class)
        ->and($manager->get())->toHaveCount(4);
});

it('respects max visible limit', function () {
    config(['toast.max_visible' => 3]);
    $manager = app(ToastManager::class);

    for ($i = 0; $i < 5; $i++) {
        $manager->add('info', "Toast {$i}");
    }

    $toasts = $manager->get();
    expect($toasts)->toHaveCount(3)
        ->and($toasts[0]['message'])->toBe('Toast 2')
        ->and($toasts[2]['message'])->toBe('Toast 4');
});

it('keeps newest toasts when exceeding max visible', function () {
    config(['toast.max_visible' => 2]);
    $manager = app(ToastManager::class);
    $manager->success('First')->success('Second')->success('Third');
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(2)
        ->and($toasts[0]['message'])->toBe('Second')
        ->and($toasts[1]['message'])->toBe('Third');
});

it('clears all toasts', function () {
    $manager = app(ToastManager::class);
    $manager->success('One')->error('Two');
    $manager->clear();

    expect($manager->get())->toBeEmpty();
});

it('returns empty array when no toasts exist', function () {
    $manager = app(ToastManager::class);
    expect($manager->get())->toBeArray()->toBeEmpty();
});

it('returns position from config', function () {
    config(['toast.position' => 'bottom-left']);
    $manager = app(ToastManager::class);
    expect($manager->position())->toBe('bottom-left');
});

it('defaults invalid position to top-right', function () {
    config(['toast.position' => 'invalid-position']);
    $manager = app(ToastManager::class);
    expect($manager->position())->toBe('top-right');
});

it('returns all valid positions', function () {
    $manager = app(ToastManager::class);
    $positions = $manager->validPositions();

    expect($positions)->toContain('top-right')
        ->and($positions)->toContain('top-left')
        ->and($positions)->toContain('top-center')
        ->and($positions)->toContain('bottom-right')
        ->and($positions)->toContain('bottom-left')
        ->and($positions)->toContain('bottom-center');
});

it('returns all valid types', function () {
    $manager = app(ToastManager::class);
    $types = $manager->validTypes();

    expect($types)->toContain('success')
        ->and($types)->toContain('error')
        ->and($types)->toContain('warning')
        ->and($types)->toContain('info');
});

it('converts success flash message', function () {
    session()->flash('success', 'Flash success');
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('success')
        ->and($toasts[0]['message'])->toBe('Flash success');
});

it('converts error flash message', function () {
    session()->flash('error', 'Flash error');
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('error')
        ->and($toasts[0]['message'])->toBe('Flash error');
});

it('converts warning flash message', function () {
    session()->flash('warning', 'Flash warning');
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('warning')
        ->and($toasts[0]['message'])->toBe('Flash warning');
});

it('converts info flash message', function () {
    session()->flash('info', 'Flash info');
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('info')
        ->and($toasts[0]['message'])->toBe('Flash info');
});

it('converts status flash message to info toast', function () {
    session()->flash('status', 'Password updated');
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(1)
        ->and($toasts[0]['type'])->toBe('info')
        ->and($toasts[0]['message'])->toBe('Password updated');
});

it('converts multiple flash messages at once', function () {
    session()->flash('success', 'Great!');
    session()->flash('warning', 'Watch out!');
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();
    $toasts = $manager->get();

    expect($toasts)->toHaveCount(2);
    $types = array_column($toasts, 'type');
    expect($types)->toContain('success')
        ->and($types)->toContain('warning');
});

it('ignores non-string info flash message', function () {
    session()->flash('info', ['array', 'data']);
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();

    expect($manager->get())->toBeEmpty();
});

it('ignores non-string status flash message', function () {
    session()->flash('status', 123);
    $manager = app(ToastManager::class);
    $manager->convertFlashMessages();

    expect($manager->get())->toBeEmpty();
});

it('uses custom session key', function () {
    config(['toast.session_key' => 'custom_key']);
    $manager = app(ToastManager::class);
    $manager->success('Custom key test');

    expect(session('custom_key'))->toHaveCount(1);
});
