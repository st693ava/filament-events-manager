<?php

it('can capture simple screenshot', function () {
    $page = visit('/');
    $page->screenshot(filename: 'simple-homepage');
})->group('screenshots');