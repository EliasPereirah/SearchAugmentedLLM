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
$time_out = $_GET['time_out'] ?? $_POST['time_out'] ?? 5;
$time_out = (int) $time_out; // Maximum time that a curl request to links extracted from Google search will wait
$ParallelRequest->setTimeout($time_out); // in seconds

$GoogleCSE = new \App\GoogleCSE();
$MainContentExtractor = new \App\MainContentExtractor();
$TextToChunk = new \App\TextToChunk();
$Cohere = new \App\Cohere();

$term = $_POST['term'] ?? $_GET['term'] ?? '';
$errors = [];

$max_results = $_GET['max_results'] ?? $_POST['max_results'] ?? 4; // Google Search maximum results
$max_results = (int) $max_results;

$max_chunks = $_GET['max_chunks'] ?? $_POST['max_chunks'] ?? 100;

if ($max_results > $max_chunks) {
    $errors[] = ["max_results exceeds maximum number of chunks"];
}

$do_rerank = $_GET['do_rerank'] ?? $_POST['do_rerank'] ?? true;
$do_rerank = (bool)$do_rerank;

// $min_char The minimum number of characters a chunk must have
// $max_char The maximum number of characters a chunk must have - must be greater than the sum of ($min_char + $max_seq)
// $max_seq The maximum allowed length for words inside the chunk. Longer sequences then $max_seq  will be removed.
$min_char = $_GET['do_rerank'] ?? $_POST['do_rerank'] ?? 300;
$max_char = $_GET['do_rerank'] ?? $_POST['do_rerank'] ?? 450;
$max_seq = $_GET['do_rerank'] ?? $_POST['do_rerank'] ?? 51;

$min_char = (int) $min_char;
$max_char = (int) $max_char;
$max_seq = (int) $max_seq;


// maximum number of characters returned as output.
$max_characters_output = $_GET['max_chars_output'] ?? $_POST['max_chars_output'] ?? 2500;
$max_characters_output = (int) $max_characters_output;

$all_data = [];
if ($term) {
    $all_links = [];
    try {
        // make a search on Google and return just the links
        $all_links = $GoogleCSE->search($term, $max_results)->getItems(true);
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
    $max_chunks_per_url = (int) ($max_chunks / $max_results);
    foreach ($results as $item) {
        $raw_html = $item->html;
        $url = $item->url;
        try {
            $readability = $MainContentExtractor->getMainContent($raw_html);
            $main_html = $readability->getContent();
            $chunks = $TextToChunk->makeChunks($main_html, $max_chunks_per_url, $min_char, $max_char, $max_seq);
            $all_data[] = (object)[
                "url" => $url,
                'chunks' => $chunks,
            ];

        } catch (Exception $e) {
            $errors['readability'] = $e->getMessage();
        }
    } // end foreach

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
            $reranks = $Cohere->rerank($term, $all_chunks)['result']->results ?? [];
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
    $data = ["text" => "", "msg"=> 'No search terms were passed'];
}
echo json_encode($data);
