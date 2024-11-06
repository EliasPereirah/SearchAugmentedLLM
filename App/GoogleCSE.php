<?php
namespace App;
use Exception;

class GoogleCSE {
    private array $search_results;

    /**
     * @throws Exception
     */
    public function search(string $term, int $max_results, int $start = 0):GoogleCSE {
        if ($max_results > 10) {
           $max_results = 10; // Google doesn't allow more than 10
        }
        if ($start > 91) {
            throw new Exception("Cannot list more than 91 results");
        }
        $term = urlencode($term);
        $url = "https://www.googleapis.com/customsearch/v1?key=" . GOOGLE_SEARCH_API_KEY . "&cx=".GOOGLE_SEARCH_CX."&q=$term&num=$max_results&start=$start";
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }
        $response = json_decode($response);
        $response_error = $response->error ?? false;
        if($response_error){
            throw new Exception($response->error->message ?? 'Error!');
        }
        $this->search_results[] = $response ?? [];
        return $this;
    }


    /**
     * Returns search results, passing $only_links true returns only the links
     * @param bool $only_links
     * @return array
     */
    public function getItems(bool $only_links = false):array {
        $results = $this->search_results;
        $this->search_results = []; // reset
        if($only_links){
            return $this->getLinks($results);
        }
        return $results;
    }


    /**
     * Returns just the links from the search results, without snippet or title, etc
     */
    private function getLinks($data):array {
        $links = [];
        foreach ($data as $datum) {
            foreach ($datum->items as $item) {
                $links[] = $item->link;
            }
        }
        return $links;
    }
}
