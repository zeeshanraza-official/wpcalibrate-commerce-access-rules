/**
 * WPCalibrate Content Restriction - Admin Scripts
 *
 * @package WPCalibrate\CommerceAccessRules
 */

(function ($) {
	'use strict';

	$(function () {
		// 1. Condition Type Change Handling
		$(document).on('change', '.wpcal-cond-type-select', function () {
			var $select = $(this);
			var type = $select.val();
			var $row = $select.closest('.wpcal-condition-row');

			$row.find('.wpcal-param-panel').hide();

			if (type === 'roles') {
				$row.find('.wpcal-param-roles').show();
			} else if (type === 'users') {
				$row.find('.wpcal-param-users').show();
			} else if (type === 'wc_product' || type === 'wc_subscription') {
				$row.find('.wpcal-param-wc_product').show();
			} else if (type === 'wc_category') {
				$row.find('.wpcal-param-wc_category').show();
			} else if (type === 'fixed_date') {
				$row.find('.wpcal-param-fixed_date').show();
			}
		});

		// 2. Add Condition within Group
		$(document).on('click', '.wpcal-btn-add-cond', function () {
			var $group = $(this).closest('.wpcal-condition-group-card');
			var groupIdx = $group.data('group-index');
			var $list = $group.find('.wpcal-conditions-list');
			var condIdx = $list.find('.wpcal-condition-row').length;

			var html = `
				<div class="wpcal-condition-row" data-cond-index="${condIdx}">
					<div class="wpcal-cond-field-type">
						<select name="condition_groups[${groupIdx}][conditions][${condIdx}][type]" class="wpcal-cond-type-select">
							<option value="logged_in">User is Logged In</option>
							<option value="logged_out">User is Logged Out (Inverse)</option>
							<option value="roles">User has Specific Role(s)</option>
							<option value="users">Specific WordPress User(s)</option>
							<option value="wc_product">Purchased WooCommerce Product(s)</option>
							<option value="wc_category">Purchased in WooCommerce Category(ies)</option>
							<option value="wc_subscription">Active WooCommerce Subscription</option>
							<option value="fixed_date">Fixed Date Window</option>
						</select>
					</div>
					<div class="wpcal-cond-params">
						<div class="wpcal-param-panel wpcal-param-roles" style="display:none;">
							<select name="condition_groups[${groupIdx}][conditions][${condIdx}][roles][]" multiple style="width: 100%; min-height: 80px;">
								<option value="administrator">Administrator (administrator)</option>
								<option value="editor">Editor (editor)</option>
								<option value="author">Author (author)</option>
								<option value="subscriber">Subscriber (subscriber)</option>
								<option value="customer">Customer (customer)</option>
								<option value="shop_manager">Shop Manager (shop_manager)</option>
							</select>
							<span class="description" style="font-size: 11px;">Hold Ctrl/Cmd to select multiple roles.</span>
						</div>
						<div class="wpcal-param-panel wpcal-param-users" style="display:none;">
							<input type="text" name="condition_groups[${groupIdx}][conditions][${condIdx}][users]" placeholder="Comma-separated User IDs, e.g. 1, 14, 25" style="width: 100%;" />
						</div>
						<div class="wpcal-param-panel wpcal-param-wc_product" style="display:none;">
							<input type="text" name="condition_groups[${groupIdx}][conditions][${condIdx}][products]" placeholder="Comma-separated Product IDs, e.g. 101, 102" style="width: 100%;" />
						</div>
						<div class="wpcal-param-panel wpcal-param-wc_category" style="display:none;">
							<input type="text" name="condition_groups[${groupIdx}][conditions][${condIdx}][categories]" placeholder="Comma-separated Category Term IDs, e.g. 15, 20" style="width: 100%;" />
						</div>
						<div class="wpcal-param-panel wpcal-param-fixed_date" style="display:none;">
							<div style="display: flex; gap: 8px;">
								<div>
									<label style="font-size: 11px;">Start (Site Time):</label>
									<input type="datetime-local" name="condition_groups[${groupIdx}][conditions][${condIdx}][start]" />
								</div>
								<div>
									<label style="font-size: 11px;">End (Site Time):</label>
									<input type="datetime-local" name="condition_groups[${groupIdx}][conditions][${condIdx}][end]" />
								</div>
							</div>
						</div>
					</div>
					<div class="wpcal-cond-actions">
						<button type="button" class="button-link wpcal-btn-remove-cond" title="Remove condition">✕</button>
					</div>
				</div>
			`;

			$list.append(html);
		});

		// 3. Remove Condition
		$(document).on('click', '.wpcal-btn-remove-cond', function () {
			var $list = $(this).closest('.wpcal-conditions-list');
			if ($list.find('.wpcal-condition-row').length > 1) {
				$(this).closest('.wpcal-condition-row').remove();
			} else {
				alert('A condition group must have at least one condition. Remove the group instead if not needed.');
			}
		});

		// 4. Add Condition Group
		$('#wpcal-btn-add-group').on('click', function () {
			var $container = $('#wpcal-condition-groups-container');
			var groupIdx = $container.find('.wpcal-condition-group-card').length;

			var html = `
				<div class="wpcal-condition-group-card" data-group-index="${groupIdx}">
					<div class="wpcal-group-header">
						<span class="wpcal-group-title">Condition Group #${groupIdx + 1}</span>
						<div class="wpcal-group-relation">
							<label>
								Conditions logic:
								<select name="condition_groups[${groupIdx}][relation]">
									<option value="AND">Match ALL Conditions (AND)</option>
									<option value="OR">Match ANY Condition (OR)</option>
								</select>
							</label>
						</div>
						<button type="button" class="button-link wpcal-btn-remove-group" title="Remove Group">✕</button>
					</div>
					<div class="wpcal-conditions-list">
						<div class="wpcal-condition-row" data-cond-index="0">
							<div class="wpcal-cond-field-type">
								<select name="condition_groups[${groupIdx}][conditions][0][type]" class="wpcal-cond-type-select">
									<option value="logged_in">User is Logged In</option>
									<option value="logged_out">User is Logged Out (Inverse)</option>
									<option value="roles">User has Specific Role(s)</option>
									<option value="users">Specific WordPress User(s)</option>
									<option value="wc_product">Purchased WooCommerce Product(s)</option>
									<option value="wc_category">Purchased in WooCommerce Category(ies)</option>
									<option value="wc_subscription">Active WooCommerce Subscription</option>
									<option value="fixed_date">Fixed Date Window</option>
								</select>
							</div>
							<div class="wpcal-cond-params">
								<div class="wpcal-param-panel wpcal-param-roles" style="display:none;">
									<select name="condition_groups[${groupIdx}][conditions][0][roles][]" multiple style="width: 100%; min-height: 80px;">
										<option value="administrator">Administrator (administrator)</option>
										<option value="editor">Editor (editor)</option>
										<option value="author">Author (author)</option>
										<option value="subscriber">Subscriber (subscriber)</option>
										<option value="customer">Customer (customer)</option>
										<option value="shop_manager">Shop Manager (shop_manager)</option>
									</select>
								</div>
								<div class="wpcal-param-panel wpcal-param-users" style="display:none;">
									<input type="text" name="condition_groups[${groupIdx}][conditions][0][users]" placeholder="Comma-separated User IDs, e.g. 1, 14, 25" style="width: 100%;" />
								</div>
								<div class="wpcal-param-panel wpcal-param-wc_product" style="display:none;">
									<input type="text" name="condition_groups[${groupIdx}][conditions][0][products]" placeholder="Comma-separated Product IDs, e.g. 101, 102" style="width: 100%;" />
								</div>
								<div class="wpcal-param-panel wpcal-param-wc_category" style="display:none;">
									<input type="text" name="condition_groups[${groupIdx}][conditions][0][categories]" placeholder="Comma-separated Category Term IDs, e.g. 15, 20" style="width: 100%;" />
								</div>
								<div class="wpcal-param-panel wpcal-param-fixed_date" style="display:none;">
									<div style="display: flex; gap: 8px;">
										<div>
											<label style="font-size: 11px;">Start (Site Time):</label>
											<input type="datetime-local" name="condition_groups[${groupIdx}][conditions][0][start]" />
										</div>
										<div>
											<label style="font-size: 11px;">End (Site Time):</label>
											<input type="datetime-local" name="condition_groups[${groupIdx}][conditions][0][end]" />
										</div>
									</div>
								</div>
							</div>
							<div class="wpcal-cond-actions">
								<button type="button" class="button-link wpcal-btn-remove-cond" title="Remove condition">✕</button>
							</div>
						</div>
					</div>
					<div class="wpcal-group-footer">
						<button type="button" class="button button-small wpcal-btn-add-cond">+ Add Condition</button>
					</div>
				</div>
			`;

			$container.append(html);
		});

		// 5. Remove Group
		$(document).on('click', '.wpcal-btn-remove-group', function () {
			var $container = $('#wpcal-condition-groups-container');
			if ($container.find('.wpcal-condition-group-card').length > 1) {
				$(this).closest('.wpcal-condition-group-card').remove();
			} else {
				alert('At least one condition group is required.');
			}
		});

		// 6. Duration Type Change
		$('#wpcal-duration-type').on('change', function () {
			var val = $(this).val();
			if (val === 'days' || val === 'weeks' || val === 'months') {
				$('#wpcal-duration-value-wrap').show();
				$('#wpcal-fixed-dates-wrap').hide();
			} else if (val === 'fixed') {
				$('#wpcal-duration-value-wrap').hide();
				$('#wpcal-fixed-dates-wrap').show();
			} else {
				$('#wpcal-duration-value-wrap').hide();
				$('#wpcal-fixed-dates-wrap').hide();
			}
		});

		// 7. Denied Action Change
		$('#wpcal-denied-action').on('change', function () {
			var val = $(this).val();
			$('#wpcal-page-redirect-wrap').toggle(val === 'page_redirect');
			$('#wpcal-product-redirect-wrap').toggle(val === 'product_redirect' || val === 'purchase_button');
			$('#wpcal-btn-text-wrap').toggle(val === 'purchase_button');
			$('#wpcal-message-wrap').toggle(val !== 'hide');
		});

		// 8. Access Inspector AJAX Execution
		$('#wpcal-btn-inspect-user').on('click', function (e) {
			e.preventDefault();
			var userId = $('#wpcal-inspect-user-id').val();
			if (!userId || parseInt(userId, 10) <= 0) {
				alert('Please enter a valid User ID.');
				return;
			}

			var $results = $('#wpcal-inspector-results');
			var $loading = $('#wpcal-inspector-loading');
			var $tbody = $('#wpcal-inspector-tbody');
			var $userCard = $('#wpcal-user-summary-card');

			$results.hide();
			$loading.show();

			$.ajax({
				url: wpcalibrateAccessRules.restUrl + 'inspect',
				method: 'GET',
				data: { user_id: userId },
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wpcalibrateAccessRules.nonce);
				},
				success: function (res) {
					$loading.hide();
					$tbody.empty();

					// Render User Info Card
					var userHtml = `
						<h3>${res.user.name} (ID: ${res.user.id})</h3>
						<p><strong>Email:</strong> ${res.user.email} &nbsp;|&nbsp; <strong>Roles:</strong> ${res.user.roles.join(', ')} &nbsp;|&nbsp; <strong>Registered:</strong> ${res.user.registered}</p>
					`;
					$userCard.html(userHtml);

					// Render Rules Table
					if (!res.rules || res.rules.length === 0) {
						$tbody.append('<tr><td colspan="5">No restriction rules exist to evaluate.</td></tr>');
					} else {
						$.each(res.rules, function (i, item) {
							var allowedBadge = item.result.allowed
								? '<span class="wpcal-badge wpcal-badge-success">Allowed</span>'
								: '<span class="wpcal-badge wpcal-badge-danger">Denied</span>';

							var sourceLink = item.result.grant_source || '—';
							if (item.order_edit_url) {
								sourceLink = `<a href="${item.order_edit_url}" target="_blank">Order #${item.result.grant_source.replace('order_', '')} ↗</a>`;
							}

							var expiry = item.result.expiry_formatted || (item.result.allowed ? 'Permanent' : '—');

							var row = `
								<tr>
									<td><strong>${item.rule_title}</strong> (#${item.rule_id})</td>
									<td>${allowedBadge}</td>
									<td><code>${item.result.reason_code}</code><br><small style="color:#666;">${item.result.reason_label}</small></td>
									<td>${sourceLink}</td>
									<td>${expiry}</td>
								</tr>
							`;
							$tbody.append(row);
						});
					}

					$results.fadeIn();
				},
				error: function (xhr) {
					$loading.hide();
					var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error evaluating access for this customer.';
					alert(msg);
				}
			});
		});
	});
})(jQuery);
