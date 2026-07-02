/* AI chat for refining the announcement listing search.
 * Posts to actionChat on the announcement DefaultController and renders the reply inline.
 * Conversation history is persisted to localStorage so it survives navigation within the listing.
 */
(function ($) {
	'use strict';

	function AnnouncementChat(config) {
		this.url = config.url;
		this.storageKey = 'announcement_chat_history_v1';
		this.conversationKey = 'announcement_chat_conversation_id_v1';
		this.maxHistory = 20;

		this.$root = $(config.root);
		this.$toggle = config.toggle ? $(config.toggle) : $();
		this.$bubble = config.bubble ? $(config.bubble) : $();
		this.$body = this.$root.find('.announcement-chat-body');
		this.$input = this.$root.find('.announcement-chat-input');
		this.$send = this.$root.find('.announcement-chat-send');
		this.$mic = this.$root.find('.announcement-chat-mic');
		this.$reset = this.$root.find('.announcement-chat-reset');
		this.$close = this.$root.find('.announcement-chat-close');
		this.openStateKey = 'announcement_chat_open_v1';

		this.language = config.language || 'ro-RO';
		this.history = this.loadHistory();
		this.conversationId = this.loadConversationId();
		this.busy = false;
		this.recognition = null;
		this.recording = false;
	}

	AnnouncementChat.prototype.loadConversationId = function () {
		try {
			var raw = localStorage.getItem(this.conversationKey);
			return raw ? parseInt(raw, 10) || null : null;
		} catch (e) {
			return null;
		}
	};

	AnnouncementChat.prototype.saveConversationId = function (id) {
		this.conversationId = id || null;
		try {
			if (this.conversationId) {
				localStorage.setItem(this.conversationKey, String(this.conversationId));
			} else {
				localStorage.removeItem(this.conversationKey);
			}
		} catch (e) { /* ignore */ }
	};

	AnnouncementChat.prototype.init = function () {
		var self = this;
		this.renderHistory();

		this.$send.on('click', function (e) {
			e.preventDefault();
			self.send();
		});

		this.$input.on('keypress', function (e) {
			if (e.which === 13) {
				e.preventDefault();
				self.send();
			}
		});

		this.$reset.on('click', function (e) {
			e.preventDefault();
			self.reset();
		});

		this.$toggle.on('click', function (e) {
			e.preventDefault();
			self.toggle();
		});

		this.$bubble.on('click', function (e) {
			e.preventDefault();
			self.setOpen(true);
		});

		this.$close.on('click', function (e) {
			e.preventDefault();
			self.setOpen(false);
		});

		this.setOpen(this.loadOpenState());

		this.setupSpeechRecognition();
	};

	AnnouncementChat.prototype.loadOpenState = function () {
		try {
			return localStorage.getItem(this.openStateKey) === 'true';
		} catch (e) {
			return false;
		}
	};

	AnnouncementChat.prototype.saveOpenState = function (open) {
		try {
			localStorage.setItem(this.openStateKey, open ? 'true' : 'false');
		} catch (e) { /* ignore */ }
	};

	AnnouncementChat.prototype.toggle = function () {
		this.setOpen(!this.$root.hasClass('visible'));
	};

	AnnouncementChat.prototype.setOpen = function (open) {
		this.$root.toggleClass('visible', !!open);
		this.$bubble.toggleClass('hidden', !!open);
		this.saveOpenState(!!open);
		if (open) {
			this.scrollBottom();
			var $input = this.$input;
			setTimeout(function () { $input.focus(); }, 50);
		}
	};

	AnnouncementChat.prototype.setupSpeechRecognition = function () {
		var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
		if (!SR) {
			this.$mic.addClass('unsupported').prop('disabled', true);
			return;
		}
		var self = this;
		var rec = new SR();
		rec.lang = this.language;
		rec.interimResults = false;
		rec.maxAlternatives = 1;
		rec.continuous = false;

		rec.onresult = function (event) {
			var transcript = '';
			for (var i = 0; i < event.results.length; i++) {
				transcript += event.results[i][0].transcript;
			}
			transcript = $.trim(transcript);
			if (transcript) {
				self.$input.val(transcript);
				self.send();
			}
		};
		rec.onerror = function () {
			self.stopRecording();
		};
		rec.onend = function () {
			self.stopRecording();
		};

		this.recognition = rec;

		this.$mic.on('click', function (e) {
			e.preventDefault();
			if (self.recording) {
				self.stopRecording();
			} else {
				self.startRecording();
			}
		});
	};

	AnnouncementChat.prototype.startRecording = function () {
		if (!this.recognition || this.recording) return;
		try {
			this.recognition.start();
			this.recording = true;
			this.$mic.addClass('recording').find('i').removeClass('fa-microphone').addClass('fa-microphone-slash');
		} catch (e) {
			this.stopRecording();
		}
	};

	AnnouncementChat.prototype.stopRecording = function () {
		if (this.recognition && this.recording) {
			try { this.recognition.stop(); } catch (e) { /* ignore */ }
		}
		this.recording = false;
		this.$mic.removeClass('recording').find('i').removeClass('fa-microphone-slash').addClass('fa-microphone');
	};

	AnnouncementChat.prototype.loadHistory = function () {
		try {
			var raw = localStorage.getItem(this.storageKey);
			if (!raw) return [];
			var parsed = JSON.parse(raw);
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			return [];
		}
	};

	AnnouncementChat.prototype.saveHistory = function () {
		try {
			var trimmed = this.history.slice(-this.maxHistory);
			localStorage.setItem(this.storageKey, JSON.stringify(trimmed));
		} catch (e) { /* quota / disabled — ignore */ }
	};

	AnnouncementChat.prototype.renderHistory = function () {
		this.$body.empty();
		if (this.history.length === 0) {
			return;
		}
		for (var i = 0; i < this.history.length; i++) {
			this.appendMessage(this.history[i]);
		}
		this.scrollBottom();
	};

	AnnouncementChat.prototype.appendMessage = function (msg) {
		var $msg = $('<div class="announcement-chat-message"></div>').addClass(msg.role === 'user' ? 'user' : 'bot');
		$msg.text(msg.content);
		this.$body.append($msg);
	};

	AnnouncementChat.prototype.escape = function (s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
		});
	};

	AnnouncementChat.prototype.scrollBottom = function () {
		this.$body.scrollTop(this.$body[0].scrollHeight);
	};

	AnnouncementChat.prototype.send = function () {
		if (this.busy) return;
		var text = $.trim(this.$input.val());
		if (!text) return;

		// Clear the empty placeholder when present.

		var userMsg = { role: 'user', content: text };
		this.history.push(userMsg);
		this.appendMessage(userMsg);

		this.$input.val('');
		this.busy = true;
		this.$send.prop('disabled', true);

		var $loader = $('<div class="announcement-chat-message bot"><div class="announcement-chat-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div></div>');
		this.$body.append($loader);
		this.scrollBottom();

		var self = this;
		var historyForServer = this.history.slice(0, -1).map(function (m) {
			return { role: m.role, content: m.content };
		});

		$.ajax({
			url: this.url,
			type: 'POST',
			contentType: 'application/json; charset=utf-8',
			dataType: 'json',
			data: JSON.stringify({
				message: text,
				history: historyForServer,
				context: this.collectContext(),
				conversation_id: this.conversationId
			})
		}).done(function (res) {
			$loader.remove();
			if (!res || res.error) {
				var errMsg = (res && res.error) || 'Error';
				self.history.pop();
				self.saveHistory();
				$('<div class="announcement-chat-message bot announcement-chat-error"></div>').text(errMsg).appendTo(self.$body);
				self.scrollBottom();
				return;
			}
			if (res.conversation_id) {
				self.saveConversationId(res.conversation_id);
			}
			var botMsg = {
				role: 'assistant',
				content: res.reply || '',
				suggested_query: res.suggested_query || null,
				search_url: res.search_url || null
			};
			self.history.push(botMsg);
			self.saveHistory();
			self.appendMessage(botMsg);
			self.scrollBottom();

			// Apply the AI result immediately — the listing reloads with `?ai=<token>` and shows exactly
			// the announcements the chat found (or zero results when nothing matched). The chat history
			// persists in localStorage, so the assistant reply is still visible after the navigation.
			if (res.search_url) {
				window.location.href = res.search_url;
			}
		}).fail(function (xhr) {
			$loader.remove();
			self.history.pop();
			self.saveHistory();
			var msg = xhr && xhr.responseJSON && xhr.responseJSON.error
				? xhr.responseJSON.error
				: 'Network error. Please try again.';
			$('<div class="announcement-chat-message bot announcement-chat-error"></div>').text(msg).appendTo(self.$body);
			self.scrollBottom();
		}).always(function () {
			self.busy = false;
			self.$send.prop('disabled', false);
			self.$input.focus();
		});
	};

	AnnouncementChat.prototype.collectContext = function () {
		// Active filters from the listing URL (?cautare / ?category / ?county / ?tag / ?year, etc.).
		// We send only known scoping keys so the controller can build the system prompt cleanly.
		var allowed = ['cautare', 'search', 'category', 'county', 'tag', 'year', 'locality', 'location', 'limit'];
		var params = {};
		try {
			var search = window.location.search.replace(/^\?/, '');
			if (search) {
				search.split('&').forEach(function (pair) {
					if (!pair) return;
					var eq = pair.indexOf('=');
					var key = eq === -1 ? pair : pair.substring(0, eq);
					var val = eq === -1 ? '' : decodeURIComponent(pair.substring(eq + 1).replace(/\+/g, ' '));
					key = decodeURIComponent(key);
					if (allowed.indexOf(key) !== -1 && val !== '') {
						params[key] = val;
					}
				});
			}
		} catch (e) { /* ignore */ }

		// Pretty-route fallback: pull category/county slug from path segments when the URL uses /categorie/<slug> etc.
		try {
			var path = window.location.pathname || '';
			var marker = function (token) {
				var idx = path.indexOf('/' + token + '/');
				if (idx === -1) return null;
				var rest = path.substring(idx + token.length + 2);
				var endSlash = rest.indexOf('/');
				return endSlash === -1 ? rest : rest.substring(0, endSlash);
			};
			['categorie', 'category', 'judet', 'county', 'tag'].forEach(function (token) {
				var slug = marker(token);
				if (!slug) return;
				if (token === 'categorie' || token === 'category') {
					params.category = params.category || slug;
				} else if (token === 'judet' || token === 'county') {
					params.county = params.county || slug;
				} else if (token === 'tag') {
					params.tag = params.tag || slug;
				}
			});
		} catch (e) { /* ignore */ }

		// Page title — a useful human-readable anchor.
		var pageTitle = (document.querySelector('.page-title') || {}).textContent || document.title || '';
		params.pageTitle = pageTitle.replace(/\s+/g, ' ').trim().slice(0, 200);
		return params;
	};

	// Drop the ?ai=<token> from the URL (no-op when absent — no useless reload).
	AnnouncementChat.prototype.removeAiParam = function () {
		try {
			var url = new URL(window.location.href);
			if (url.searchParams.has('ai')) {
				url.searchParams.delete('ai');
				var qs = url.searchParams.toString();
				window.location.href = url.pathname + (qs ? '?' + qs : '') + url.hash;
			}
		} catch (e) {
			// Older browsers without URL — fall back to a plain string strip of the ai param.
			var here = window.location.href;
			var stripped = here.replace(/([?&])ai=[^&#]*(&|(?=#)|$)/, function (_, before, after) {
				return after === '&' ? before : '';
			}).replace(/[?&]$/, '');
			if (stripped !== here) {
				window.location.href = stripped;
			}
		}
	};

	AnnouncementChat.prototype.reset = function () {
		this.history = [];
		this.saveHistory();
		this.saveConversationId(null);
		this.renderHistory();

		// The listing is no longer scoped to the AI IDs from the cleared conversation.
		this.removeAiParam();
	};

	window.AnnouncementChat = AnnouncementChat;
})(jQuery);
