<?php

namespace App;

use DateTime;

class ClassificationAlgorithm
{
    private $badWords;
    private $dateDiff;
    private $goodWords;
    private $ocurrences;
    private $score;
    private $ticket;
    
    private $highPriorityScore = 50;
    private $weights = [
        "ocurrences" => 10, 
        "time" => .0003,
        "words" => 20
    ];

    public function __construct()
    {     
        $words = file_get_contents(config_path("words.json"));
        $words = json_decode($words, true);

        $this->badWords = $words["bad-words"];
        $this->goodWords = $words["good-words"];    
    }

    public function assumeTicket($ticket) 
    {
        $this->ticket = $ticket;
        $this->setMeasures();
    }

    public function classificate()
    {
        $this->scoreOcurrences();
        $this->scoreTime();

        foreach ($this->ticket["Interactions"] as $interaction)
        {
            if ($interaction["Sender"] === "Customer")
            {
                $this->scoreWords($interaction["Subject"]);
                $this->scoreWords($interaction["Message"]);
            }
        }

        $highPriority = $this->score >= $this->highPriorityScore;

        return $highPriority ? "Alta" : "Normal";
    }

    private function clearString($string)
    {
        $string = $this->removeAccents($string);
        return strtolower($string);
    }

    // Based on "Neil Townsend" response in https://stackoverflow.com/a/23675286/6161969
    private function getMinutesDiff($dateCreated, $dateUpdated)
    {
        $dateCreated = new DateTime($dateCreated);
        $dateUpdated = new DateTime($dateUpdated);

        $dateDiff = $dateCreated->diff($dateUpdated);
        $intervalInSeconds = (new DateTime())->setTimeStamp(0)->add($dateDiff)->getTimeStamp();
        $intervalInMinutes = ceil($intervalInSeconds / 60);

        return $intervalInMinutes;
    }

    // Function proposed by "ling" in https://stackoverflow.com/a/34649673/6161969
    private function removeAccents($str)
    {
        $map = [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'ą' => 'a',
            'å' => 'a',
            'ā' => 'a',
            'ă' => 'a',
            'ǎ' => 'a',
            'ǻ' => 'a',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Ą' => 'A',
            'Å' => 'A',
            'Ā' => 'A',
            'Ă' => 'A',
            'Ǎ' => 'A',
            'Ǻ' => 'A',
            'ç' => 'c',
            'ć' => 'c',
            'ĉ' => 'c',
            'ċ' => 'c',
            'č' => 'c',
            'Ç' => 'C',
            'Ć' => 'C',
            'Ĉ' => 'C',
            'Ċ' => 'C',
            'Č' => 'C',
            'ď' => 'd',
            'đ' => 'd',
            'Ð' => 'D',
            'Ď' => 'D',
            'Đ' => 'D',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ę' => 'e',
            'ē' => 'e',
            'ĕ' => 'e',
            'ė' => 'e',
            'ě' => 'e',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ę' => 'E',
            'Ē' => 'E',
            'Ĕ' => 'E',
            'Ė' => 'E',
            'Ě' => 'E',
            'ƒ' => 'f',
            'ĝ' => 'g',
            'ğ' => 'g',
            'ġ' => 'g',
            'ģ' => 'g',
            'Ĝ' => 'G',
            'Ğ' => 'G',
            'Ġ' => 'G',
            'Ģ' => 'G',
            'ĥ' => 'h',
            'ħ' => 'h',
            'Ĥ' => 'H',
            'Ħ' => 'H',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ĩ' => 'i',
            'ī' => 'i',
            'ĭ' => 'i',
            'į' => 'i',
            'ſ' => 'i',
            'ǐ' => 'i',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ĩ' => 'I',
            'Ī' => 'I',
            'Ĭ' => 'I',
            'Į' => 'I',
            'İ' => 'I',
            'Ǐ' => 'I',
            'ĵ' => 'j',
            'Ĵ' => 'J',
            'ķ' => 'k',
            'Ķ' => 'K',
            'ł' => 'l',
            'ĺ' => 'l',
            'ļ' => 'l',
            'ľ' => 'l',
            'ŀ' => 'l',
            'Ł' => 'L',
            'Ĺ' => 'L',
            'Ļ' => 'L',
            'Ľ' => 'L',
            'Ŀ' => 'L',
            'ñ' => 'n',
            'ń' => 'n',
            'ņ' => 'n',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ñ' => 'N',
            'Ń' => 'N',
            'Ņ' => 'N',
            'Ň' => 'N',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ð' => 'o',
            'ø' => 'o',
            'ō' => 'o',
            'ŏ' => 'o',
            'ő' => 'o',
            'ơ' => 'o',
            'ǒ' => 'o',
            'ǿ' => 'o',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ø' => 'O',
            'Ō' => 'O',
            'Ŏ' => 'O',
            'Ő' => 'O',
            'Ơ' => 'O',
            'Ǒ' => 'O',
            'Ǿ' => 'O',
            'ŕ' => 'r',
            'ŗ' => 'r',
            'ř' => 'r',
            'Ŕ' => 'R',
            'Ŗ' => 'R',
            'Ř' => 'R',
            'ś' => 's',
            'š' => 's',
            'ŝ' => 's',
            'ş' => 's',
            'Ś' => 'S',
            'Š' => 'S',
            'Ŝ' => 'S',
            'Ş' => 'S',
            'ţ' => 't',
            'ť' => 't',
            'ŧ' => 't',
            'Ţ' => 'T',
            'Ť' => 'T',
            'Ŧ' => 'T',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ũ' => 'u',
            'ū' => 'u',
            'ŭ' => 'u',
            'ů' => 'u',
            'ű' => 'u',
            'ų' => 'u',
            'ư' => 'u',
            'ǔ' => 'u',
            'ǖ' => 'u',
            'ǘ' => 'u',
            'ǚ' => 'u',
            'ǜ' => 'u',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ũ' => 'U',
            'Ū' => 'U',
            'Ŭ' => 'U',
            'Ů' => 'U',
            'Ű' => 'U',
            'Ų' => 'U',
            'Ư' => 'U',
            'Ǔ' => 'U',
            'Ǖ' => 'U',
            'Ǘ' => 'U',
            'Ǚ' => 'U',
            'Ǜ' => 'U',
            'ŵ' => 'w',
            'Ŵ' => 'W',
            'ý' => 'y',
            'ÿ' => 'y',
            'ŷ' => 'y',
            'Ý' => 'Y',
            'Ÿ' => 'Y',
            'Ŷ' => 'Y',
            'ż' => 'z',
            'ź' => 'z',
            'ž' => 'z',
            'Ż' => 'Z',
            'Ź' => 'Z',
            'Ž' => 'Z',
            'Ǽ' => 'A',
            'ǽ' => 'a',
        ];

        return strtr($str, $map);
    }

    private function retrieveCustomerOccurrences($customerId)
    {
        return \Redis::get("customer:" . $customerId);
    }

    public function setMeasures()
    {
        $this->dateDiff = $this->getMinutesDiff($this->ticket["DateCreate"], $this->ticket["DateUpdate"]); 
        $this->ocurrences = $this->retrieveCustomerOccurrences($this->ticket["CustomerID"]);
        $this->score = 0;
    }

    private function scoreOcurrences()
    {
        $this->score += $this->ocurrences * $this->weights["ocurrences"];
    }

    private function scoreTime()
    {
        $this->score += $this->dateDiff * $this->weights["time"];
    }

    private function scoreWords($message)
    {
        $message = $this->clearString($message);

        foreach ($this->badWords as $badWord)
        {
            $this->score += substr_count($message, $this->clearString($badWord)) * $this->weights["words"];
        }

        foreach ($this->goodWords as $goodWord)
        {
            $this->score -= substr_count($message, $this->clearString($goodWord)) * $this->weights["words"];
        }
    }
}