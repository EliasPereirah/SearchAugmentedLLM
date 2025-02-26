<?php

namespace App;
class ParallelRequest
{
    private array $urls = [];
    private int $maxConcurrent, $maxRedirections;
    private string $userAgent;
    private int $timeout = 3;
    private array $errors = [];
    private int $maxFileSize = 5242880; // 5MB in bytes (5 * 1024 * 1024)


    public function __construct(int $maxConcurrent = 10, int $maxRedirections = 3)
    {
        if ($maxRedirections > 7) {
            exit("Maximum redirects are too high");
        }
        $this->maxConcurrent = $maxConcurrent;
        $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36";
        $this->userAgent = $userAgent;
        $this->maxRedirections = $maxRedirections;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function setMaxFileSize(int $sizeInBytes): void
    {
        $this->maxFileSize = $sizeInBytes;
    }

    /**
     * @param string $url
     **/
    public function addUrl(string $url): void
    {


        if ($this->isValidURL($url) && !$this->isLocalhostOrIP($url)) {
            $this->urls[] = ['url' => $url];
        } else {
            $this->errors['bad_urls'][] = $url;
        }
    }

    public function listURLs()
    {
        return $this->urls;
    }

    public function getError(): array
    {
        return $this->errors;
    }


    private function isValidURL(string $url): bool
    {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        return $url !== false && preg_match('/^(https?:\/\/)/', $url) === 1;
    }


    public function request(): array
    {
        $multiHandle = curl_multi_init();
        $handles = [];

        $all_results = [];
        $running = 0;
        $urlIndex = 0;

        do {
            while (count($handles) < $this->maxConcurrent && $urlIndex < count($this->urls)) {
                $url = $this->urls[$urlIndex]['url'];
                $handle = curl_init($url);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($handle, CURLOPT_USERAGENT, $this->userAgent);
                curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $this->timeout);
                curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($handle, CURLOPT_MAXREDIRS, $this->maxRedirections);
                curl_setopt($handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
                curl_setopt($handle, CURLOPT_HEADERFUNCTION, [$this, 'handleRedirect']);
                curl_setopt($handle, CURLOPT_HEADERFUNCTION, [$this, 'handleHeader']);


                curl_multi_add_handle($multiHandle, $handle);
                $handles[$urlIndex] = $handle;
                $urlIndex++;
            }

            do {
                $status = curl_multi_exec($multiHandle, $running);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            if ($running) {
                curl_multi_select($multiHandle);
            }

            while ($done = curl_multi_info_read($multiHandle)) {
                $info = curl_getinfo($done['handle']);
                $content_type = $info['content_type'] ?? $info['Content_Type'] ?? $info['Content_type'] ?? '';
                $type = explode('/', $content_type)[0] ?? '';
                $type = strtolower($type);
                $isTextType = false;
                if ($type == "text") {
                    // This is not completely reliable, as external servers can manipulate the headers as they wish.
                    $isTextType = true;
                }

                $content = curl_multi_getcontent($done['handle']);
                $error = curl_error($done['handle']);
                $httpCode = $info['http_code'];

                $private = curl_getinfo($done['handle'], CURLINFO_PRIVATE);
                $fileSize = $private['size'] ?? 0;

                if ($fileSize > $this->maxFileSize) {
                    $this->errors['size_error'][] = [
                        "url" => $info['url'],
                        'time' => $info['total_time'],
                        'http_code' => $httpCode,
                        'content_type' => $content_type,
                        'error' => "File size ($fileSize) exceeds ($this->maxFileSize) maximum allowed size",
                    ];
                } else if ($httpCode < 200 || $httpCode > 299 || $error || !$isTextType) {
                    $this->errors['curl_error'][] = [
                        "url" => $info['url'],
                        'time' => $info['total_time'],
                        'http_code' => $httpCode,
                        'error' => $error,
                        'content_type' => $content_type
                    ];
                } else {
                    $all_results[] = (object)[
                        'url' => $info['url'],
                        'time' => $info['total_time'],
                        'http_code' => $httpCode,
                        'html' => $content
                    ];
                }


                $index = array_search($done['handle'], $handles);
                curl_multi_remove_handle($multiHandle, $done['handle']);
                curl_close($done['handle']);
                unset($handles[$index]);
            }

        } while ($running > 0 || count($handles) > 0);

        curl_multi_close($multiHandle);
        $this->urls = []; // reset
        return $all_results;
    }


    /**
     * Checks if string is a localhost address or an IP
     **/
    public function isLocalhostOrIP($url): bool
    {
        $parsedUrl = parse_url($url);
        $host = strtolower($parsedUrl['host'] ?? '');
        $host = trim($host, '[].');
        if (str_ends_with($host, 'localhost')) {
            return true;
        }
        if (str_starts_with($host, ':')) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }
        return false;
    }


    private function handleRedirect($ch, $header): int
    {
        if (stripos($header, 'Location:') === 0) {
            $location = trim(substr($header, 9));
            if ($this->isLocalhostOrIP($location)) {
                // Prevent redirecting to localhost or any IP
                return 0; // Returns 0 to stop the redirect
            }
        }
        return strlen($header);
    }

    private function handleHeader($ch, $header): int
    {
        $headerLength = strlen($header);

        // Check for Content-Length header
        if (stripos($header, 'Content-Length:') === 0) {
            $length = (int)trim(substr($header, 15));
            $private = curl_getinfo($ch, CURLINFO_PRIVATE);
            $private['size'] = $length;
            curl_setopt($ch, CURLOPT_PRIVATE, $private);

            // If file is too large, abort the transfer
            if ($length > $this->maxFileSize) {
                return 0; // Abort transfer
            }
        }

        return $headerLength;
    }
}
