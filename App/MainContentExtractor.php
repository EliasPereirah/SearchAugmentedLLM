<?php
namespace App;
use Exception;
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

/**
 * This class uses Readability (https://github.com/fivefilters/readability.php) to get only the most relevant text from an HTML page.
**/

class MainContentExtractor{
    private Readability $Readability;
    public function __construct()
    {
        $this->Readability = new Readability(new Configuration());
    }

    /**
     * This function receives an HTML and returns its main, removing content such as header, footer, sidebar.
     * @param string $html
     * @return Readability
     * @throws Exception
     */
    public function getMainContent(string $html): Readability
    {
        try {
            $this->Readability->parse($html);
            return $this->Readability;
        } catch (ParseException $e) {
            throw new Exception("Parse error: ".$e->getMessage());
        }

    }
}