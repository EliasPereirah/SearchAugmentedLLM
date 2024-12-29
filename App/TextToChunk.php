<?php

namespace App;
class TextToChunk
{


    /**
     * Generate chunks from a string, being the string text or HTML
     * Note: This function will remove any HTML in the content by calling removeHTML function
     * @param int $max_chunks The maximum number of chunks that will be returned
     * @param string $content the text or HTML content
     * @param int $min_char The minimum number of characters a chunk must have
     * @param int $max_char The maximum number of characters a chunk must have - must be greater than the sum of ($min_char + $max_seq)
     * @param int $max_seq The maximum allowed length for words inside the chunk. Longer sequences then $max_seq  will be removed.
     *  as they will be considered an anomaly in the text.
     * @return array
     **/
    public function makeChunks(string $content, int $max_chunks, int $min_char = 300, int $max_char = 450, int $max_seq = 51): array
    {

        if ($min_char > $max_char) {
            exit("min_char cannot be greater than max_char");
        }

        if ($max_char <= ($min_char + $max_seq)) {
            $max_char += ($max_seq + 1);
            // $max_chat must be greater than the sum of ($min_char + $max_seq)
        }

        $text = $this->removeHTML($content);
        $pattern = '/[^\s.]{' . $max_seq . ',}/'; // remove text sequence greater than $max_seq
        $text = preg_replace($pattern, " ", $text);
        // this is important, because if there is a very long sequence of text it may cause the regex below
        // to leave part of the text out, not to mention that such a long sequence of characters probably will not
        // be an interesting text to index, since there are no words that long

        $pattern = '/(\b.{' . $min_char . ',' . $max_char . '}?\.)|(\b.{' . $min_char . ',' . $max_char . '}?\s)/s';
        // Separates words into groups between $min_char to $max_char characters and ends with a period or space
        preg_match_all($pattern, $text, $matches);
        if (empty($matches[0])) {
            // when text is less than $min_char
            $chunks = [$text];
        } else {
            // only the first n chunks
            $chunks = array_slice($matches[0], 0, $max_chunks);
        }
        return $chunks;

    }


    /**
     * Remove HTML and empty break lines
     **/
    public function removeHTML(string $text): string
    {
        $text = preg_replace("/</", "\n<", $text);
        $text = strip_tags($text);
        $text = preg_replace('/\t+/', " ", $text);
        $text = preg_replace('/\R+/', " ", $text);
        $text = preg_replace("/\[\s+?(\d+)\s+?]/", " ", $text); // remove [\d] characters, ex [1] or [ 2 ]

        $text = preg_replace('/\s+/', " ", $text);
        return $text;
    }


}
