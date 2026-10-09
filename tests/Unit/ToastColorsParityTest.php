<?php

use Jeremykenedy\LaravelToast\Support\ToastColors;

it('generates the same stylesheet as the JavaScript generator', function () {
    $input = json_decode(file_get_contents(__DIR__.'/../fixtures/toast-colors-input.json'), true);

    expect(ToastColors::css($input)."\n")->toBe(file_get_contents(__DIR__.'/../fixtures/toast-colors-expected.css'));
});
