<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClassificateTickets extends Command
{
    protected $signature = 'classificate 
        {file : Caminho absoluto do arquivo JSON a ser classificado.} 
        {output? : Diretório onde o novo JSON deve ser colocado. Caso nulo o programa assumirá o diretório do arquivo de entrada.}';

    protected $description = 'Classifica os tickets do arquivo indicado.';
    protected $file;
    protected $output;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->file = $this->argument('file');
        $this->output = $this->defineOutput($this->argument('output'));
        
        echo "-> Iniciando classificação dos tickets no arquivo '" . $this->file . "'";
    }
    
    protected function defineOutput($output) {
        if ($output != "") return $output;
        
        return substr($this->file, 0, strrpos($this->file, '/')); // Captura o caminho do arquivo até a última "/"
    }
}
