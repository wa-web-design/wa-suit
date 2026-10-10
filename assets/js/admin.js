/**
 * WA Suit - Modern Admin JavaScript
 * Handles AJAX toggling, sidebar tabs visibility, filters, and notifications.
 */

(function ($) {
	'use strict';

	$(function () {
		const $toast = $('#wa-suit-toast');
		let toastTimer = null;

		/**
		 * Displays a floating toast notification.
		 *
		 * @param {string} message Text to display.
		 * @param {string} type 'success' or 'error'.
		 */
		function showToast(message, type) {
			type = type || 'success';

			clearTimeout(toastTimer);

			$toast
				.text(message)
				.removeClass('wa-suit-toast--success wa-suit-toast--error')
				.addClass('wa-suit-toast--' + type)
				.addClass('is-visible');

			toastTimer = setTimeout(function () {
				$toast.removeClass('is-visible');
			}, 3500);
		}

		/**
		 * Handles module toggle switch change.
		 */
		$(document).on('change', '.wa-suit-module-toggle', function () {
			const $checkbox = $(this);
			const moduleId = $checkbox.data('module-id');
			const isChecked = $checkbox.is(':checked');
			const state = isChecked ? 1 : 0;

			// Sync all toggles for this module if multiple exist (e.g. card & tab top)
			$('.wa-suit-module-toggle[data-module-id="' + moduleId + '"]')
				.not($checkbox)
				.prop('checked', isChecked);

			$checkbox.prop('disabled', true);

			$.ajax({
				url: waSuitAdminData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wa_suit_toggle_module',
					module_id: moduleId,
					state: state,
					nonce: waSuitAdminData.nonce
				},
				success: function (response) {
					$checkbox.prop('disabled', false);

					if (response.success) {
						const data = response.data;

						// 1. Update Card styling in Dashboard
						const $card = $('[data-module-card="' + moduleId + '"]');
						const $statusLabel = $('[data-status-label="' + moduleId + '"]');
						const $actionArea = $('[data-action-area="' + moduleId + '"]');
						const $inactiveNotice = $('[data-inactive-notice="' + moduleId + '"]');

						if (data.state) {
							$card.removeClass('is-inactive').addClass('is-active');
							$statusLabel
								.removeClass('is-inactive')
								.addClass('is-active')
								.text(waSuitAdminData.strings.active);
							$actionArea.fadeIn(200);
							$inactiveNotice.slideUp(200);
						} else {
							$card.removeClass('is-active').addClass('is-inactive');
							$statusLabel
								.removeClass('is-active')
								.addClass('is-inactive')
								.text(waSuitAdminData.strings.inactive);
							$actionArea.fadeOut(200);
							$inactiveNotice.slideDown(200);
						}

						// 2. Update Header Counter
						$('.wa-suit-stat-active-count').text(data.active_count);

						// 3. Update Left Sidebar dynamic Tab item
						const $navItem = $('[data-nav-module="' + moduleId + '"]');
						if (data.state) {
							$navItem.slideDown(200);
						} else {
							$navItem.slideUp(200);
						}

						// 4. Update Empty State in Sidebar
						setTimeout(function () {
							const visibleTabsCount = $('.wa-suit-nav__item--module:visible').length;
							if (visibleTabsCount > 0) {
								$('#wa-suit-nav-empty').hide();
							} else {
								$('#wa-suit-nav-empty').fadeIn(200);
							}
						}, 250);

						// 5. Show Toast
						showToast(data.message, 'success');
					} else {
						// Revert checkbox state on error
						$checkbox.prop('checked', !isChecked);
						showToast(response.data && response.data.message ? response.data.message : waSuitAdminData.strings.error, 'error');
					}
				},
				error: function () {
					$checkbox.prop('disabled', false);
					$checkbox.prop('checked', !isChecked);
					showToast(waSuitAdminData.strings.error, 'error');
				}
			});
		});

		/**
		 * Filter cards by Category
		 */
		$('.wa-suit-filter-btn').on('click', function () {
			const $btn = $(this);
			const category = $btn.data('filter');

			$('.wa-suit-filter-btn').removeClass('is-active');
			$btn.addClass('is-active');

			filterModules();
		});

		/**
		 * Filter cards by Search Query
		 */
		$('#wa-suit-search-input').on('input', function () {
			filterModules();
		});

		/**
		 * Combined filter execution (category + search)
		 */
		function filterModules() {
			const activeCategory = $('.wa-suit-filter-btn.is-active').data('filter') || 'all';
			const query = $.trim($('#wa-suit-search-input').val().toLowerCase());
			let visibleCount = 0;

			$('.wa-suit-card--module').each(function () {
				const $card = $(this);
				const cardCat = $card.data('category');
				const title = String($card.data('title') || '');
				const desc = String($card.data('description') || '');

				const matchesCat = (activeCategory === 'all' || cardCat === activeCategory);
				const matchesQuery = (!query || title.indexOf(query) !== -1 || desc.indexOf(query) !== -1);

				if (matchesCat && matchesQuery) {
					$card.show();
					visibleCount++;
				} else {
					$card.hide();
				}
			});

			if (visibleCount === 0) {
				$('#wa-suit-empty-search').show();
			} else {
				$('#wa-suit-empty-search').hide();
			}
		}
	});
})(jQuery);
