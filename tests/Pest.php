<?php

use Jeremykenedy\LaravelToast\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// Skipped by the CI job that installs without livewire/livewire.
uses()->group('livewire')->in('Feature/LivewireToastTest.php');
