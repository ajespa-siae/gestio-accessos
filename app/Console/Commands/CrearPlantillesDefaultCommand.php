<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CrearPlantillesDefaultCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:crear-plantilles-default-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
    
    protected $commands = [
        \App\Console\Commands\CrearPlantillesDefaultCommand::class,
    ];
}
