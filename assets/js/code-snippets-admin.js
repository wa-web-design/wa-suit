document.addEventListener('DOMContentLoaded', function () {
	const list = document.querySelector('[data-wa-snippet-list]');
	const template = document.querySelector('[data-wa-snippet-template]');
	const addButton = document.querySelector('[data-wa-snippet-add]');

	if (!list || !template || !addButton) {
		return;
	}

	const updateLineNumbers = function (editor) {
		const input = editor.querySelector('[data-wa-code-input]');
		const lines = editor.querySelector('[data-wa-code-lines]');
		if (!input || !lines) {
			return;
		}

		const lineCount = input.value.split('\n').length;
		lines.textContent = Array.from({ length: lineCount }, function (_, index) {
			return index + 1;
		}).join('\n');
		lines.scrollTop = input.scrollTop;
	};

	const updateSnippet = function (snippet) {
		const target = snippet.querySelector('[data-wa-snippet-target]');
		const page = snippet.querySelector('[data-wa-snippet-page]');
		const url = snippet.querySelector('[data-wa-snippet-url]');
		const language = snippet.querySelector('[data-wa-snippet-language]');
		const location = snippet.querySelector('[data-wa-snippet-location]');
		const phpNote = snippet.querySelector('[data-wa-snippet-php-note]');
		const shortcodeNote = snippet.querySelector('[data-wa-snippet-shortcode-note]');
		const phpDeviceNote = snippet.querySelector('[data-wa-snippet-php-device-note]');
		const titleInput = snippet.querySelector('[data-wa-snippet-title-input]');
		const title = snippet.querySelector('[data-wa-snippet-title]');
		const summary = snippet.querySelector('[data-wa-snippet-summary]');
		const pageSelect = snippet.querySelector('[data-wa-snippet-posts]');
		const urlInput = snippet.querySelector('[data-wa-snippet-url] textarea');
		const devices = Array.from(snippet.querySelectorAll('[data-wa-snippet-device]'));
		const allDevices = devices.find(function (device) {
			return device.value === 'all';
		});
		const isPhp = language && language.value === 'php';
		const isHtmlInHead = language && language.value === 'html' && location && location.value === 'head';
		const isShortcode = language && language.value === 'shortcode';
		const headOption = location && location.querySelector('option[value="head"]');

		if (headOption) {
			headOption.disabled = Boolean(isShortcode);
		}
		if (isShortcode && location && location.value === 'head') {
			location.value = 'body_open';
		}

		if (target && page && url) {
			page.hidden = target.value !== 'page';
			url.hidden = target.value !== 'url_contains';
		}

		if (phpNote) {
			phpNote.hidden = !isPhp;
		}
		if (shortcodeNote) {
			shortcodeNote.hidden = !isShortcode;
		}

		if ((isPhp || isHtmlInHead) && allDevices) {
			allDevices.checked = true;
			devices.forEach(function (device) {
				if (device !== allDevices) {
					device.checked = false;
				}
			});
		}

		devices.forEach(function (device) {
			device.disabled = (isPhp || isHtmlInHead) && device !== allDevices;
		});

		if (phpDeviceNote) {
			phpDeviceNote.hidden = !isPhp && !isHtmlInHead;
			phpDeviceNote.textContent = isPhp
				? 'PHP executa no servidor e não pode ser limitado por dispositivo.'
				: 'HTML no head não pode ser ocultado por dispositivo. Escolha body ou footer para usar esse filtro.';
		}

		if (titleInput && title) {
			title.textContent = titleInput.value.trim() || 'Novo código';
			const toggle = snippet.querySelector('[data-wa-snippet-enabled]');
			if (toggle) {
				toggle.setAttribute('aria-label', 'Ativar ' + (titleInput.value.trim() || 'Novo código'));
			}
		}

		if (summary) {
			const targetLabel = target
				? target.options[target.selectedIndex].text
				: 'Todo o site';
			const targetDetail = target && target.value === 'page'
				? (pageSelect && pageSelect.selectedOptions.length
					? pageSelect.selectedOptions.length + ' página(s)/artigo(s)'
					: targetLabel)
				: target && target.value === 'url_contains'
					? targetLabel + ': ' + (urlInput && urlInput.value.trim() ? urlInput.value.trim().split(/\r?\n/).filter(Boolean).join(', ') : '...')
					: targetLabel;
			const selectedDevices = devices
				.filter(function (device) { return device.checked; })
				.map(function (device) { return device.parentElement.textContent.trim(); });

			summary.textContent = targetDetail + ' · ' + (selectedDevices.join(', ') || 'Selecione dispositivo');
		}
	};

	const bindSnippet = function (snippet, collapse) {
		const target = snippet.querySelector('[data-wa-snippet-target]');
		const language = snippet.querySelector('[data-wa-snippet-language]');
		const location = snippet.querySelector('[data-wa-snippet-location]');
		const titleInput = snippet.querySelector('[data-wa-snippet-title-input]');
		const page = snippet.querySelector('[data-wa-snippet-posts]');
		const url = snippet.querySelector('[data-wa-snippet-url] textarea');
		const devices = Array.from(snippet.querySelectorAll('[data-wa-snippet-device]'));
		const toggle = snippet.querySelector('[data-wa-snippet-enabled]');
		const toggleLabel = snippet.querySelector('[data-wa-snippet-enabled-label]');
		const codeEditor = snippet.querySelector('[data-wa-code-editor]');
		const codeInput = codeEditor && codeEditor.querySelector('[data-wa-code-input]');
		const removeButton = snippet.querySelector('[data-wa-snippet-remove]');

		[target, language, location, titleInput, page, url].forEach(function (control) {
			if (control) {
				control.addEventListener('input', function () { updateSnippet(snippet); });
				control.addEventListener('change', function () { updateSnippet(snippet); });
			}
		});

		devices.forEach(function (device) {
			device.addEventListener('change', function () {
				const allDevices = devices.find(function (item) { return item.value === 'all'; });

				if (device.value === 'all' && device.checked) {
					devices.forEach(function (item) {
						if (item !== device) {
							item.checked = false;
						}
					});
				} else if (device.value !== 'all' && device.checked && allDevices) {
					allDevices.checked = false;
				}

				if (!devices.some(function (item) { return item.checked; }) && allDevices) {
					allDevices.checked = true;
				}
				updateSnippet(snippet);
			});
		});

		if (codeEditor && codeInput) {
			updateLineNumbers(codeEditor);
			codeInput.addEventListener('input', function () { updateLineNumbers(codeEditor); });
			codeInput.addEventListener('scroll', function () { updateLineNumbers(codeEditor); });
		}

		if (toggle) {
			toggle.addEventListener('click', function (event) {
				event.stopPropagation();
			});
			toggle.addEventListener('change', function () {
				const previousState = !toggle.checked;
				if (toggle.dataset.snippetPersisted === '0') {
					if (toggleLabel) {
						toggleLabel.textContent = toggle.checked ? 'Ativo' : 'Inativo';
					}
					return;
				}

				toggle.disabled = true;
				toggle.setAttribute('aria-busy', 'true');
				if (toggleLabel) {
					toggleLabel.textContent = waSuitCodeSnippets.strings.saving;
				}

				const body = new URLSearchParams({
					action: 'wa_suit_toggle_code_snippet',
					nonce: waSuitCodeSnippets.nonce,
					snippet_id: toggle.dataset.snippetId || '',
					enabled: toggle.checked ? '1' : '0'
				});

				fetch(waSuitCodeSnippets.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				})
					.then(function (response) {
						return response.json().then(function (data) {
							if (!response.ok || !data.success) {
								throw new Error(data.data && data.data.message ? data.data.message : waSuitCodeSnippets.strings.error);
							}
							return data;
						});
					})
					.then(function (data) {
						toggle.checked = Boolean(data.data.enabled);
						if (toggleLabel) {
							toggleLabel.textContent = data.data.enabled ? 'Ativo' : 'Inativo';
						}
						toggle.title = data.data.message;
					})
					.catch(function (error) {
						toggle.checked = previousState;
						if (toggleLabel) {
							toggleLabel.textContent = previousState ? 'Ativo' : 'Inativo';
						}
						toggle.title = error.message;
						window.alert(error.message);
					})
					.finally(function () {
						toggle.disabled = false;
						toggle.removeAttribute('aria-busy');
					});
			});
		}

		if (removeButton) {
			removeButton.addEventListener('click', function (event) {
				event.preventDefault();
				snippet.remove();
			});
		}

		updateSnippet(snippet);
		if (collapse) {
			snippet.open = false;
		}
	};

	list.querySelectorAll('[data-wa-snippet]').forEach(function (snippet) {
		bindSnippet(snippet, true);
	});

	addButton.addEventListener('click', function () {
		const key = 'new-' + Date.now() + '-' + Math.random().toString(36).slice(2);
		const html = template.innerHTML.split('__SNIPPET_KEY__').join(key);
		const wrapper = document.createElement('div');
		wrapper.innerHTML = html.trim();
		const snippet = wrapper.firstElementChild;

		if (snippet) {
			list.appendChild(snippet);
			bindSnippet(snippet, false);
			snippet.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	});
});
