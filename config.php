<?php
const PRODUCTION = true;
if(!PRODUCTION){
    ini_set('display_errors',true);
}

$COHERE_API_KEY = $_ENV['COHERE_API_KEY'] ?? '';
$COHERE_RERANK_ENDPOINT  = $_ENV['COHERE_RERANK_ENDPOINT'] ?? '';
$COHERE_RERANK_MODEL = $_ENV['COHERE_RERANK_MODEL'] ?? '';
$GOOGLE_SEARCH_API_KEY  = $_ENV['GOOGLE_SEARCH_API_KEY'] ?? '';
$GOOGLE_SEARCH_CX = $_ENV['GOOGLE_SEARCH_CX'] ?? '';



/*
if(!empty($_POST['GOOGLE_SEARCH_API_KEY'])){
    $GOOGLE_SEARCH_API_KEY = $_POST['GOOGLE_SEARCH_API_KEY'];
}

if(!empty($_POST['GOOGLE_SEARCH_CX'])){
    $GOOGLE_SEARCH_CX = $_POST['GOOGLE_SEARCH_CX'];
}
*/

const TIME_OUT = 6; // Maximum time (seconds) for a request to each link

const MAX_RESULTS = 9; // Maximum number of Google Search results
const MAX_CHUNKS = 100; // Maximum number of chunks to generate
$DO_RERANK = true; // Rerank results for better quality (requires Cohere API key) //
const MAX_SEQ = 51; // Maximum word length inside a chunk (longer sequences are removed)
const MIN_CHAR = 300; // Minimum number of characters per chunk
const MAX_CHAR = 450; // Maximum number of characters per chunk (must be > min_char + max_seq)
const MAX_CHARACTERS_OUTPUT = 4800; // Maximum number of characters in the output



define("COHERE_API_KEY", $COHERE_API_KEY);
define("COHERE_RERANK_ENDPOINT", $COHERE_RERANK_ENDPOINT);
define("COHERE_RERANK_MODEL", $COHERE_RERANK_MODEL);

define("GOOGLE_SEARCH_API_KEY", $GOOGLE_SEARCH_API_KEY);  // API key: https://developers.google.com/custom-search/v1/overview?hl=pt-br
define("GOOGLE_SEARCH_CX", $GOOGLE_SEARCH_CX);  // CX ID: https://programmablesearchengine.google.com/controlpanel/all

if($COHERE_API_KEY == ''){
    $DO_RERANK = false;
}

define('DO_RERANK', $DO_RERANK);
