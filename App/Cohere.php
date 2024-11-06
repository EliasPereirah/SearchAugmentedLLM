<?php
namespace App;
class Cohere
{
    /**
     * This method takes in a query and a list of texts and produces an ordered array with each text assigned a relevance score.
     * @param string $query The search query
     * @param array $documents Chunk text to be reranked
     * @param int|null $top_n The number of most relevant documents or indexes to return, defaults to the length of the documents
    **/
    public function rerank(string $query, array $documents, int|null $top_n = null):array
    {

        $data = [
            'model' => COHERE_RERANK_MODEL,
            'query' => $query,
            'documents' => $documents,
            'return_documents' => true
        ];
        if($top_n !== null) {
            $data['top_n'] = $top_n;
        }
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: bearer ".COHERE_API_KEY
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, COHERE_RERANK_ENDPOINT);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $data = ['result' => $documents, 'error' => 'Curl Error: ' . curl_error($ch)];
        } else {
            $data = ['result' => json_decode($response)];
        }
        curl_close($ch);
        return $data;
    }
}