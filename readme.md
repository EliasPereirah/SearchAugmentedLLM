# SearchAugmentedLLM
SearchAugmentedLLM empowers Large Language Models (LLMs) information from the web.  
Given a user query, it performs a Google search, processes the top search results, chunks the content, 
ranks by relevance, and returns the most pertinent text to provide context for improved LLM responses. 
This tool is ideal for Retrieval Augmented Generation (RAG) applications.

## Beta Warning
This project is in beta, and may not have the quality you desire, consider other alternatives or test before implementing.

## Installation
1. Clone the repository
```shell
git clone https://github.com/EliasPereirah/SearchAugmentedLLM.git
```
2. Change directory to the project folder
```shell
cd SearchAugmentedLLM
```

2. Run 
```shell
composer install
``` 

3. Rename `.env.example` to `.env` and configure your Google CSE API key and CX ID
4. If you want to use Cohere Rerank, you will need to get an API key from Cohere and add it to the .env file


## Features

* **Google Search Integration:**  Leverages the Google Search API to retrieve relevant web pages.
* **Content Extraction and Chunking:** Extracts text content from web pages and divides it into chunks.
* **Relevance Ranking:** Re-ranks chunks based on relevance to the initial query using Cohere Rerank model 
(default: rerank-multilingual-v3.0)
* **Contextualized LLM Responses:** Delivers the most pertinent information to the LLM, enabling more accurate and informed responses.

# API
This project was programmed to be used via REST API, you can use it either on localhost or on an external hosting.

## API Parameters
When making an HTTP request to the API, you can pass the following parameters (GET and POST are supported):

| Parameter               | Description                                                               | Required | Default |
|-------------------------|---------------------------------------------------------------------------|----------|---------|
| `query`                 | The search query                                                          | No       |         |
| `url`                   | URL to extract content                                                    | No       |         |
| `time_out`              | Maximum time (seconds) for a request to each link                         | No       | 6       |
| `max_results`           | Maximum number of Google Search results                                   | No       | 9       |
| `max_chunks`            | Maximum number of chunks to generate                                      | No       | 100     |
| `do_rerank`             | Rerank results for better quality (requires Cohere API key)               | No       | true    |
| `max_seq`               | Maximum word length inside a chunk (longer sequences are removed)         | No       | 51      |
| `min_char`              | Minimum number of characters per chunk                                    | No       | 300     |
| `max_char`              | Maximum number of characters per chunk (must be > `min_char` + `max_seq`) | No       | 450     |
| `max_characters_output` | Maximum number of characters in the output                                | No       | 14000    |

**Update**: Now, if you want, instead of passing a search term in the `query` parameter, you can pass the `url` you want to get the content from.

## Response

You can expect a JSON response with the following keys

`text` - (string) This is the set of all the chunks in paragraph form which should be passed to LLM.

`errors` - (array) with error information if there are any.

`reranked` - (bool) Whether the results were reranked.

`snippets` - (array) list of snippets returned by Google.

`url_references` - (object) which references the text by numbering in square brackets

## Google CSE API Key
To search using Google, you will need the Google CSE (Custom Search Engine) `API Key` and `CX ID`

First, create a custom search here [Google CSE Panel](https://programmablesearchengine.google.com/controlpanel/all]) 

Copy your `CX ID` -> go to this page on [Google Developers](https://developers.google.com/custom-search/v1/introduction) 
and click `Get a Key` to get your API key.
Rename the `.env.example` file to `.env` and put your CX and API key in the appropriate variable


## Rerank With Cohere
To rerank you will also need to configure a Cohere API key in .env.

Get your Cohere API key here: https://dashboard.cohere.com/api-keys

# Security
This project was developed mainly for home use via localhost.
If you want to use it on a public hosting, it is recommended to add some restriction layer with login.

## License
**MIT** - This project is licensed under the MIT License.  
Please note that this project is currently in **beta** and is provided "as is" without warranty of any kind.

## Acknowledgements
This project leverages the following resources:

**Readability** PHP library by FiveFilters - https://github.com/fivefilters/readability.php

**Cohere API**: Used for re-ranking content.

**Google CSE** for search the web.