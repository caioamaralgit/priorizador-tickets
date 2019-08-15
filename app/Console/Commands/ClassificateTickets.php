<?php

namespace App\Console\Commands;

use App\ClassificationAlgorithm;
use Illuminate\Console\Command;

class ClassificateTickets extends Command
{
    protected $description = 'Classifica os tickets do arquivo indicado.';
    protected $signature = 'classificate 
        {file : Caminho absoluto do arquivo JSON a ser classificado.}     
        {output? : Diretório onde o novo JSON deve ser colocado. Caso nulo o programa assumirá o diretório do arquivo de entrada.}';

    protected $algorithm;
    protected $file;
    protected $output;
    protected $startTime;

    public function __construct()
    {
        parent::__construct();
        $this->startTime = date("d/m/Y H:i:s.").gettimeofday()["usec"]; // Suggested by "bamossza" in https://stackoverflow.com/a/46058305/6161969
        $this->algorithm = new ClassificationAlgorithm();
    }

    public function handle()
    {
        $this->file = $this->argument('file');
        $this->output = $this->defineOutput($this->argument('output'));
        
        echo "\n-> Iniciando classificação dos tickets no arquivo '" . $this->file . "'";
        echo "\n-> Tempo de início: " . $this->startTime;

        $tickets = $this->returnArrayFromFile();
        
        $this->saveCustomersIdOccurrences($tickets);

        foreach ($tickets as $index => $ticket) 
        {
            $this->algorithm->assumeTicket($ticket);

            $classificationResults = $this->algorithm->classificate();

            $tickets[$index]["Pontuacao"] = $classificationResults["score"];
            $tickets[$index]["Classificacao"] = $classificationResults["priority"];
        }

        \Redis::flushAll();

        $this->saveResultFile($tickets);

        $finishTime = date("d/m/Y H:i:s.").gettimeofday()["usec"];
        
        echo "\n-> Processo finalizado em " . $finishTime;
    }
    
    protected function defineOutput($output) 
    {
        if ($output != "") return $output;
        
        return $this->file; 
    }

    protected function returnArrayFromFile()
    {
        $json = file_get_contents($this->file);
        return json_decode($json, true);
    }

    protected function saveCustomersIdOccurrences($tickets)
    {
        foreach ($tickets as $ticket)
        {
            $occurrencesNumber = \Redis::exists("customer:" . $ticket["CustomerID"]) ? \Redis::get("customer:" . $ticket["CustomerID"]) : 0;
            $occurrencesNumber++;

            \Redis::set("customer:" . $ticket["CustomerID"], $occurrencesNumber);
        }
    }

    protected function saveResultFile($tickets)
    {
        $file = fopen($this->output, 'w');
        fwrite($file, json_encode($tickets, JSON_PRETTY_PRINT));   
        fclose($file);
    }
}
