<?php

namespace Tests\Browser\Console;

use Laravel\Dusk\Console\ChromeProcess;

class BrowserEnvironment
{
    public static function configure()
    {
        // Configurar Chrome para testes headless
        putenv('DUSK_HEADLESS_DISABLED=false');
        putenv('DUSK_CHROME_PROCESS=1');

        // Configurar timeouts
        putenv('DUSK_TIMEOUT=30');

        // Configurar resolução
        putenv('DUSK_SCREEN_WIDTH=1920');
        putenv('DUSK_SCREEN_HEIGHT=1080');
    }
}