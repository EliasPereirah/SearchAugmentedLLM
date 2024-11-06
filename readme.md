# SearchAugmentedLLM
SearchAugmentedLLM empowers Large Language Models (LLMs) with relevant, up-to-date information from the web.  
Given a user query, it performs a Google search, processes the top search results, chunks the content, 
ranks by relevance, and returns the most pertinent text to provide context for improved LLM responses. 
This tool is ideal for Retrieval Augmented Generation (RAG) applications.
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

All parameters are optional except `query`

| Parameter | Description | Required | Default |
|---|---|---|---|
| `query` | The search query | Yes |  |
| `time_out` | Maximum time (seconds) for a request to each link | No | 5 |
| `max_results` | Maximum number of Google Search results | No | 5 |
| `max_chunks` | Maximum number of chunks to generate | No | 100 |
| `do_rerank` | Rerank results for better quality (requires Cohere API key) | No | true |
| `max_seq` | Maximum word length inside a chunk (longer sequences are removed) | No | 51 |
| `min_char` | Minimum number of characters per chunk | No | 300 |
| `max_char` | Maximum number of characters per chunk (must be > `min_char` + `max_seq`) | No | 450 |
| `max_characters_output` | Maximum number of characters in the output | No | 2500 |


## Google CSE API Key
To search using Google, you will need the Google CSE (Custom Search Engine) `API Key` and `CX ID`

First, create a custom search here [Google CSE Panel](https://programmablesearchengine.google.com/controlpanel/all]) 

Copy your `CX ID` -> go to this page on [Google Developers](https://developers.google.com/custom-search/v1/introduction) 
and click `Get a Key` to get your API key.
Rename the `.env.example` file to `.env` and put your CX and API key in the appropriate variable


## Rerank With Cohere
To rerank you will also need to configure a Cohere API key in .env.

Get your Cohere API key here: https://dashboard.cohere.com/api-keys

## License
**MIT** - This project is licensed under the MIT License.  
Please note that this project is currently in **beta** and is provided "as is" without warranty of any kind.

## Acknowledgements
This project leverages the following resources:

**Readability** PHP library by FiveFilters - https://github.com/fivefilters/readability.php

Thanks to FiveFilters for their valuable work!

**Cohere API**: Used for re-ranking content.

**Google CSE** for search the web.