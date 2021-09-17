<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Torchlight\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Torchlight\TorchlightServiceProvider;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'torchlight:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Torchlight config file into your app.';

    public function __construct()
    {
        parent::__construct();

        if (file_exists(config_path('torchlight.php'))) {
            $this->setHidden(true);
        }
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        Artisan::call('vendor:publish', [
            '--provider' => TorchlightServiceProvider::class
        ]);

        $this->info('Config file published!');
    }
}
