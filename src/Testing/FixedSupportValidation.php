<?php

namespace St693ava\FilamentEventsManager\Testing;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Features\SupportValidation\SupportValidation as BaseSupportValidation;

class FixedSupportValidation extends BaseSupportValidation
{
    function render($view, $data)
    {
        $errorBag = $this->component->getErrorBag() ?? new MessageBag;
        $errors = (new ViewErrorBag)->put('default', $errorBag);

        $revert = \Livewire\Features\SupportValidation\Utils::shareWithViews('errors', $errors);

        return function () use ($revert) {
            $revert();
        };
    }
}