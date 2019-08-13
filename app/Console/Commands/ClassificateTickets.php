<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClassificateTickets extends Command
{
    protected $signature = 'classificate {file}';
    protected $description = 'Classifica os tickets do arquivo indicado.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        echo "Iniciando classificação dos tickets no arquivo '" . $this->argument('file') . "'";
    }
}
