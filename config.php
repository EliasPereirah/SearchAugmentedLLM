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

if($_POST['GOOGLE_SEARCH_API_KEY']){
    $GOOGLE_SEARCH_API_KEY = $_POST['GOOGLE_SEARCH_API_KEY'];
}

if($_POST['GOOGLE_SEARCH_CX']){
    $GOOGLE_SEARCH_CX = $_POST['GOOGLE_SEARCH_CX'];
}

define("COHERE_API_KEY", $COHERE_API_KEY);
define("COHERE_RERANK_ENDPOINT", $COHERE_RERANK_ENDPOINT);
define("COHERE_RERANK_MODEL", $COHERE_RERANK_MODEL);

define("GOOGLE_SEARCH_API_KEY", $GOOGLE_SEARCH_API_KEY);  // API key: https://developers.google.com/custom-search/v1/overview?hl=pt-br
define("GOOGLE_SEARCH_CX", $GOOGLE_SEARCH_CX);  // CX ID: https://programmablesearchengine.google.com/controlpanel/all

