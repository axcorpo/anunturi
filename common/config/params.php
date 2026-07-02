<?php
return [
	// This parameter is used to remove a specific URL segment in order to get the right uploads URL
	'upload.ignoredBasePaths' => ['admin', 'dashboard'],
    'image.extensions' => ['jpeg', 'jpg', 'png', 'gif'],
    'image.mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
    // Not begin with dot and allow only specific characters
    'sanitize.pattern' => '/^(?!^\.)[a-zA-Z 0-9\/\.\,\:\+\-\_\@\[\]\(\)\{\}\?\!\&\'(\r\n|\r|\n)]*$/i',
    'sanitize.allowedHtmlTags' => ['strong', 'em', 'ul', 'ol', 'li', 'br', 'hr', 'p', 'div', 'span'],
    'sanitize.allowedHtmlAttributes' => ['class', 'id', 'style', 'data-', 'aria-'],
	// SPV
	'spv.clientId' => '94f289f2be860d57fb8deec7746a7e8a7e3ee71d9f941767',
	'spv.clientSecret' => 'e0f4a350f6eb2c110464189cdba55cc2b17b2a2db35c7e8a7e3ee71d9f941767',
	'spv.redirect' => 'https://www.econstructii.ro/spv',
	/**
	 * Optional PK override: Knowledge base id (OpenAI) hosting announcement text files for semantic search.
	 * 0 = resolve from DB: the KB linked to the default chat Assistant, else any active OpenAI KB.
	 */
	'announcementVectorKnowledgeBaseId' => 0,
	/**
	 * Optional PK override: Assistant whose instructions/model/temperature/knowledge-bases drive the listing chat.
	 * 0 = use the default active chat Assistant from DB if one exists, else the params-based config below.
	 */
	'announcementChatAssistantId' => 0,
	/** Optional: fixed GET key for announcement text search (default: cautare for ro, search otherwise) */
	'announcementSearchRequestKey' => null,
	/**
	 * Max distinct announcements collected from vector search (cap 250). Lower = focus on strongest matches first, less tail noise.
	 * With this small target we usually finish in 1 round (50 hits per API call), keeping latency + cost low.
	 */
	'announcementVectorSemanticSearchMaxResults' => 50,
	/**
	 * Hard ceiling on iterative rounds against the vector store. Each round = up to 50 hits with `nin` filter on already-seen record_ids.
	 * Default 2 — most queries plateau in 1 round; raise only if you actually need long-tail coverage (and accept the cost).
	 */
	'announcementVectorSemanticSearchMaxRounds' => 2,
	/**
	 * TTL (seconds) for the in-app cache of `query → record_ids`. 0 disables caching.
	 */
	'announcementVectorSemanticSearchCacheTtl' => 0,
	/**
	 * Min word count in the user's query before the listing escalates to vector search.
	 * Single-word generic queries (e.g. "caramida") are dominated by SQL LIKE; vector adds latency without improving relevance.
	 */
	'announcementVectorSemanticSearchMinWords' => 2,
	/**
	 * Model used by the listing chat assistant. `gpt-5.4-mini` handles strict attribute matching
	 * and the JSON schema far better than the old `gpt-4o-mini` default.
	 */
	'announcementChatModel' => 'gpt-5.4-mini',
	/**
	 * `file_search.max_num_results` for the chat call (OpenAI accepts 1–50, default 20). Listing IDs are extracted from
	 * the same call's `file_search_call.results`, so this also caps how many announcements the chat-driven listing can show
	 * before the supplementary searchAnnouncementIds pass.
	 */
	'announcementChatFileSearchMaxResults' => 50,
	/**
	 * Score threshold passed to the supplementary `searchAnnouncementIds` call (the "extra cautari" pass that runs
	 * AFTER file_search to widen coverage). Overrides `announcementVectorSemanticSearchScoreThreshold` for this call only.
	 * Keep low (0.05–0.15) so we surface long-tail matches the model's file_search ignored; the relative-score filter
	 * on file_search results already handles noise.
	 */
	'announcementChatSupplementScoreThreshold' => 0.1,
	/**
	 * Relative score floor for chat file_search results. We keep only chunks whose score is at least
	 * `top_score * factor`. Absolute cutoffs are unreliable across KBs; the *gap* to the top result is a better signal.
	 * 0.6 = drop anything below 60% of the best match (filters cross-category leaks). 1.0 = only keep ties with top (very strict).
	 * 0.0 = disable (keep everything).
	 */
	'announcementChatFileSearchRelativeFloor' => 0.6,
	/**
	 * TTL (seconds) for cached chat replies. Same message + same history + same active filters + same vector store generation
	 * → cached response is reused, saving an API call. 0 disables. The cache auto-invalidates when announcements are sync'd / withdrawn
	 * (the generation token changes — same mechanism as `announcementVectorSemanticSearchCacheTtl`).
	 */
	'announcementChatCacheTtl' => 0,
	/**
	 * TTL (seconds) for the one-shot `ai=<token>` lookup that bridges chat → listing. The token resolves to
	 * `{query, ids, gen}` cached after the chat call. 30 min is enough for the user to click "Apply this search"
	 * and paginate without losing the AI ordering; auto-invalidated if announcements change in the meantime.
	 */
	'announcementChatAiTokenTtl' => 1800,
	/**
	 * Min relevance score (0–1) for vector store chunks; higher = stricter / more precise, fewer weak matches.
	 * If almost no hits: lower (e.g. 0.55). For even stricter retrieval: try 0.72–0.78 (may return empty often).
	 */
	'announcementVectorSemanticSearchScoreThreshold' => 0.7,
	/**
	 * OpenAI vector ranker (retrieval only, no PHP post-filter): auto | none | default-2024-11-15
	 */
	'announcementVectorSemanticSearchRanker' => 'default-2024-11-15',
	/**
	 * API-only: OpenAI may rewrite the visitor query for retrieval. Not an app-defined domain prompt.
	 * false = always search with the exact phrase the user typed.
	 */
	'announcementVectorSemanticSearchRewriteQuery' => true,
	/** When true and KB is set, public listing search uses OpenAI vector store + relevance order */
	'announcementVectorSemanticSearch' => false,
];
