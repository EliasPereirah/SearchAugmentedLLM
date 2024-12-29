<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/bootstrap.php";
$ParallelRequest = new \App\ParallelRequest(10,3);
$time_out = $_GET['time_out'] ?? $_POST['time_out'] ?? TIME_OUT;
$time_out = (int) $time_out; // Maximum time that a curl request to links extracted from Google search will wait
$ParallelRequest->setTimeout($time_out); // in seconds

$GoogleCSE = new \App\GoogleCSE();
$MainContentExtractor = new \App\MainContentExtractor();
$TextToChunk = new \App\TextToChunk();
$Cohere = new \App\Cohere();

$query = $_POST['query'] ?? $_GET['query'] ?? '';
$language = $_POST['language'] ?? $_GET['language']  ?? 'pt-BR';
$errors = [];

$max_results = $_GET['max_results'] ?? $_POST['max_results'] ?? MAX_RESULTS; // Google Search maximum results
$max_results = (int) $max_results;

$max_chunks = $_GET['max_chunks'] ?? $_POST['max_chunks'] ?? MAX_CHUNKS;
$max_chunks = (int) $max_chunks;
if ($max_results > $max_chunks) {
    $errors[] = ["max_results exceeds maximum number of chunks"];
}

$do_rerank = $_GET['do_rerank'] ?? $_POST['do_rerank'] ?? DO_RERANK;
$do_rerank = (bool) $do_rerank;

// $min_char The minimum number of characters a chunk must have
// $max_char The maximum number of characters a chunk must have - must be greater than the sum of ($min_char + $max_seq)
// $max_seq The maximum allowed length for words inside the chunk. Longer sequences then $max_seq  will be removed.
$min_char = $_GET['min_char'] ?? $_POST['min_char'] ?? MIN_CHAR;
$max_char = $_GET['max_char'] ?? $_POST['max_char'] ?? MAX_CHAR;
$max_seq = $_GET['max_seq'] ?? $_POST['max_seq'] ?? MAX_SEQ;

$min_char = (int) $min_char;
$max_char = (int) $max_char;
$max_seq = (int) $max_seq;


// maximum number of characters returned as output.
$max_characters_output = $_GET['max_chars_output'] ?? $_POST['max_chars_output'] ?? MAX_CHARACTERS_OUTPUT;
$max_characters_output = (int) $max_characters_output;

$all_data = [];
if ($query) {
    $all_links = [];
    try {
        // make a search on Google and return just the links
        $all_links = $GoogleCSE->search($query, $max_results, 0, $language)->getItems(true);
        $arr_snippets = $GoogleCSE->getSnippets();
    } catch (Exception $e) {
        $errors['google_cse'] = $e->getMessage();
    }
    foreach ($all_links as $link) {
        $ParallelRequest->addUrl($link);
    }
    $results = $ParallelRequest->request();

    $request_errors = $ParallelRequest->getError();
    if($request_errors){
        $errors[] = $request_errors;
    }

    $total_results = count($results);
    if($total_results > 0){
        $max_chunks_per_url = (int) ($max_chunks / count($results));
        foreach ($results as $item) {
            $raw_html = $item->html;
            $url = $item->url;
            try {
                $readability = $MainContentExtractor->getMainContent($raw_html);
                $main_html = $readability->getContent();
                if(empty($main_html)){
                    $main_html = ''; // null will cause errors
                }
                $chunks = $TextToChunk->makeChunks($main_html, $max_chunks_per_url, $min_char, $max_char, $max_seq);
                $all_data[] = (object) [
                    "url" => $url,
                    'chunks' => $chunks
                ];

            } catch (Exception $e) {
                $errors['readability'] = $e->getMessage()." - url: $url";
            }
        } // end foreach
    }else{
        $do_rerank = false;
    }


    $reranks = [];
    $text = '';
    $urls_references = [];
    $idx = 1;
    $was_reranked = false;
    if ($all_data) {
        $all_chunks = [];
        foreach ($all_data as $item) {
            $url = $item->url;
            $urls_references[$idx] = $url;
            foreach ($item->chunks as $chunk) {
                $all_chunks[] = (object)["text" => $chunk, "url" => $url, "url_id" => $idx];
            }
            $idx++;
        }
        if ($do_rerank) {
            $reranks = $Cohere->rerank($query, $all_chunks)['result']->results ?? [];
        }
        $last_url = '';
        $text = '';
        $next_text = '';
        $paragraphs_ids = [];
        if ($reranks) {
            $was_reranked = true;
            foreach ($reranks as $item) {
                $url_id = $item->document->url_id;
                if (empty($paragraphs_ids[$url_id])) {
                    $paragraphs_ids[$url_id] = 1;
                }
                $p_id = $paragraphs_ids[$url_id];
                $next_text .= "<p>{$item->document->text} [{$item->document->url_id}][$p_id]</p>";
                if (mb_strlen($next_text) > $max_characters_output) {
                    break;
                } else {
                    $text = $next_text;
                }
                $paragraphs_ids[$url_id]++;
            }
        } else {
            // There was no success in reranking, so it will return chunks without reranking.
            $was_reranked = false;
            foreach ($all_chunks as $item) {
                $url_id = $item->url_id;
                if (empty($paragraphs_ids[$url_id])) {
                    $paragraphs_ids[$url_id] = 1;
                }
                $p_id = $paragraphs_ids[$url_id];
                $next_text .= "<p>{$item->text} [{$item->url_id}][$p_id]</p>";
                if (mb_strlen($next_text) > $max_characters_output) {
                    break;
                } else {
                    $text = $next_text;
                }
                $paragraphs_ids[$url_id]++;
            }

        }
    }

    $data = [
        'text' => $text,
        'urls_references' => $urls_references,
        'reranked' => $was_reranked
    ];

    if (count($errors) >= 1) {
        $data['errors'] = $errors;
    }
}else{
    $data = ["text" => "", "msg"=> 'No search query were passed'];
}

if(!empty($arr_snippets)){
    $data['snippets'] = $arr_snippets;
}
echo json_encode($data);
