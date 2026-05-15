// Attach auth headers for all same-origin fetch requests used by module forms.
(function () {
	if (window.__authFetchPatched) return;
	window.__authFetchPatched = true;

	const nativeFetch = window.fetch ? window.fetch.bind(window) : null;
	if (!nativeFetch) return;

	window.fetch = function (input, init = {}) {
		const requestUrl = typeof input === 'string' ? input : (input && input.url ? input.url : '');
		const isSameOrigin = !/^https?:\/\//i.test(requestUrl) || requestUrl.startsWith(window.location.origin);
		const isApiRequest = /\/api(\/|$)/i.test(requestUrl);
		const shouldAttachAuth = isSameOrigin || isApiRequest;

		if (!shouldAttachAuth) {
			return nativeFetch(input, init);
		}

		const headers = new Headers(init.headers || (input && input.headers) || {});

		const token = localStorage.getItem('token');
		if (token && !headers.has('Authorization')) {
			headers.set('Authorization', 'Bearer ' + token);
		}

		const csrfMeta = document.querySelector('meta[name="csrf-token"]');
		const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
		if (csrfToken && !headers.has('X-CSRF-TOKEN')) {
			headers.set('X-CSRF-TOKEN', csrfToken);
		}

		const authUserMeta = document.querySelector('meta[name="auth-user-id"]');
		const authUserId = authUserMeta ? authUserMeta.getAttribute('content') : null;
		if (authUserId && !headers.has('X-Auth-User-Id')) {
			headers.set('X-Auth-User-Id', authUserId);
		}

		let body = init.body;
		if (authUserId && body instanceof FormData && !body.has('user_id')) {
			body.append('user_id', authUserId);
		}

		if (authUserId && typeof body === 'string') {
			const contentType = headers.get('Content-Type') || '';
			if (contentType.indexOf('application/json') === 0) {
				try {
					const parsed = JSON.parse(body);
					if (parsed && (parsed.user_id === undefined || parsed.user_id === null || parsed.user_id === '')) {
						parsed.user_id = authUserId;
						body = JSON.stringify(parsed);
					}
				} catch (e) {
					// Ignore JSON parse errors and keep original body.
				}
			}
		}

		return nativeFetch(input, { ...init, headers, body });
	};
})();

(function () {
	if (window.__authUserFieldPatched) return;
	window.__authUserFieldPatched = true;

	document.addEventListener('DOMContentLoaded', function () {
		const authUserMeta = document.querySelector('meta[name="auth-user-id"]');
		const authUserId = authUserMeta ? authUserMeta.getAttribute('content') : null;
		if (!authUserId) return;

		document.querySelectorAll('form').forEach(function (form) {
			if (form.querySelector('input[name="user_id"]')) return;

			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'user_id';
			input.value = authUserId;
			form.appendChild(input);
		});
	});
})();

(function () {
	if (window.__authAjaxPatched) return;
	if (!window.jQuery) return;

	window.__authAjaxPatched = true;
	window.jQuery.ajaxSetup({
		beforeSend: function (xhr) {
			const token = localStorage.getItem('token');
			if (token) {
				xhr.setRequestHeader('Authorization', 'Bearer ' + token);
			}

			const csrfMeta = document.querySelector('meta[name="csrf-token"]');
			const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
			if (csrfToken) {
				xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
			}

			const authUserMeta = document.querySelector('meta[name="auth-user-id"]');
			const authUserId = authUserMeta ? authUserMeta.getAttribute('content') : null;
			if (authUserId) {
				xhr.setRequestHeader('X-Auth-User-Id', authUserId);
			}
		}
	});
})();

// Reusable delete with confirmation for images or files
window.deleteImageWithConfirm = function (options) {
	const {
		url,
		csrfToken,
		imagePreviewSelector,
		buttonSelector,
		nameSelector,
		successMessage = 'Deleted successfully.',
		errorMessage = 'Failed to delete.',
		extraHideSelectors = []
	} = options || {};

	if (!url || !csrfToken) {
		console.error('deleteImageWithConfirm: url and csrfToken are required');
		return;
	}

	Swal.fire({
		title: 'Are you sure?',
		text: 'Do you want to delete this image?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#d33',
		cancelButtonColor: '#3085d6',
		confirmButtonText: 'Yes, delete it!'
	}).then((result) => {
		if (!result.isConfirmed) return;

		Swal.fire({
			title: 'Deleting...',
			allowOutsideClick: false,
			didOpen: () => Swal.showLoading()
		});

		fetch(url, {
			method: 'DELETE',
			headers: {
				'X-CSRF-TOKEN': csrfToken,
				'Content-Type': 'application/json'
			}
		})
			.then(r => r.json())
			.then(data => {
				Swal.close();
				if (data && data.success) {
					if (typeof notify === 'function') notify('success', successMessage);
					// Hide/update elements
					if (imagePreviewSelector) {
						const el = document.querySelector(imagePreviewSelector);
						if (el) { el.src = ''; el.style.display = 'none'; }
					}
					if (buttonSelector) {
						const el = document.querySelector(buttonSelector);
						if (el) el.style.display = 'none';
					}
					if (nameSelector) {
						const el = document.querySelector(nameSelector);
						if (el) el.textContent = 'No image selected';
					}
					(extraHideSelectors || []).forEach(sel => {
						const el = document.querySelector(sel);
						if (el) el.style.display = 'none';
					});
				} else {
					if (typeof notify === 'function') notify('error', (data && data.message) || errorMessage);
				}
			})
			.catch(() => {
				Swal.close();
				if (typeof notify === 'function') notify('error', 'An error occurred while deleting.');
			});
	});
};


window.clearImageSelection = function ({
	imagePreviewSelector,
	imageNameSelector,
	imageInputSelector,
	removeImageBtnSelector
}) {
	// Clear preview
	const imagePreview = document.querySelector(imagePreviewSelector);
	if (imagePreview) {
		imagePreview.src = '#';
		imagePreview.style.display = 'none';
		imagePreview.removeAttribute('data-file-type');
	}

	// Clear filename label
	const imageName = document.querySelector(imageNameSelector);
	if (imageName) imageName.textContent = '';

	// Reset file input
	const imageInput = document.querySelector(imageInputSelector);
	if (imageInput) imageInput.value = '';

	// Hide remove button
	const removeImageBtn = document.querySelector(removeImageBtnSelector);
	if (removeImageBtn) removeImageBtn.style.display = 'none';
};


window.pdfPreviewPlaceholder = window.pdfPreviewPlaceholder || '/images/pdf-placeholder.svg';

function renderSelectedFilePreview(event, config) {
	const file = event.target.files && event.target.files[0];
	if (!file) return;

	const output = document.getElementById(config.previewId);
	const imageName = document.getElementById(config.nameId);
	const removeImageBtn = document.getElementById(config.removeBtnId);
	const deleteImageBtn = document.getElementById(config.deleteBtnId);

	if (imageName) {
		imageName.textContent = file.name;
	}

	if (!output) return;

	const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

	if (isPdf) {
		output.src = window.pdfPreviewPlaceholder;
		output.style.display = 'block';
		output.setAttribute('data-file-type', 'pdf');
		if (removeImageBtn) removeImageBtn.style.display = 'inline-block';
		if (deleteImageBtn) deleteImageBtn.style.display = 'none';
		return;
	}

	const reader = new FileReader();
	reader.onload = function () {
		output.src = reader.result;
		output.style.display = 'block';
		output.setAttribute('data-file-type', 'image');
		if (removeImageBtn) removeImageBtn.style.display = 'inline-block';
		if (deleteImageBtn) deleteImageBtn.style.display = 'none';
	};

	reader.readAsDataURL(file);
}

function previewImage(event) {
	renderSelectedFilePreview(event, {
		previewId: 'imagePreview',
		nameId: 'imageName',
		removeBtnId: 'removeImageBtn',
		deleteBtnId: 'deleteImageBtn'
	});
}

function previewImage1(event) {
	renderSelectedFilePreview(event, {
		previewId: 'imagePreview1',
		nameId: 'imageName1',
		removeBtnId: 'removeImageBtn1',
		deleteBtnId: 'deleteImageBtn1'
	});
}

function previewImage2(event) {
	renderSelectedFilePreview(event, {
		previewId: 'imagePreview2',
		nameId: 'imageName2',
		removeBtnId: 'removeImageBtn2',
		deleteBtnId: 'deleteImageBtn2'
	});
}


// document.getElementById('uploadImageBtn').addEventListener('click', function () {
// 	const removeImageBtn = document.getElementById('removeImageBtn');
// 	// When upload button clicked, ensure delete button is hidden
// 	removeImageBtn.style.display = 'none';
// });
// removeImageBtn.addEventListener('click', function () {
// 	// Clear preview
// 	const imagePreview = document.getElementById('imagePreview');
// 	imagePreview.src = '#';
// 	imagePreview.style.display = 'none';

// 	// Clear filename label
// 	document.getElementById('imageName').textContent = '';

// 	// Reset file input
// 	document.getElementById('image').value = '';

// 	// Hide delete button
// 	removeImageBtn.style.display = 'none';
// });

document.addEventListener('click', function(e) {
    const link = e.target.closest('.toggle-front-description');
    if (!link) return;
    e.preventDefault();

    const wrapper = link.closest('.description-wrapper');
    if (!wrapper) return;

    const shortEl = wrapper.querySelector('.description-text');
    const fullEl = wrapper.querySelector('.full-description');
    const isExpanded = wrapper.getAttribute('data-expanded') === 'true';

    if (isExpanded) {
        if (fullEl) fullEl.style.display = 'none';
        if (shortEl) shortEl.style.display = '';
        link.textContent = 'Read More';
        wrapper.setAttribute('data-expanded', 'false');
    } else {
        if (shortEl) shortEl.style.display = 'none';
        if (fullEl) fullEl.style.display = '';
        link.textContent = 'Read Less';
        wrapper.setAttribute('data-expanded', 'true');
    }
});

(function () {
	if (window.__passwordVisibilityTogglePatched) return;
	window.__passwordVisibilityTogglePatched = true;

	const ensurePasswordToggleStyles = function () {
		if (document.getElementById('password-visibility-toggle-styles')) return;

		const style = document.createElement('style');
		style.id = 'password-visibility-toggle-styles';
		style.textContent = `
			.password-visibility-wrapper {
				position: relative;
			}

			.password-visibility-wrapper .form-control,
			.password-visibility-wrapper .input {
				padding-right: 44px;
			}

			.password-visibility-toggle {
				position: absolute;
				top: 50%;
				right: 12px;
				transform: translateY(-50%);
				border: 0;
				background: transparent;
				padding: 0;
				margin: 0;
				color: #2D336B;
				cursor: pointer;
				line-height: 1;
				z-index: 3;
			}

			.password-visibility-toggle:focus {
				outline: none;
				box-shadow: none;
			}
		`;

		document.head.appendChild(style);
	};

	const hasExistingToggle = function (input) {
		if (!input) return true;

		if (input.closest('.password-visibility-wrapper')) return true;

		const parent = input.parentElement;
		if (!parent) return false;

		if (parent.classList.contains('input-group') || parent.classList.contains('position-relative')) {
			if (parent.querySelector('.password-visibility-toggle')) return true;
			if (parent.querySelector('.input-group-append')) return true;
			if (parent.querySelector('.toggle-password')) return true;
			if (parent.querySelector('#togglePassword, #toggleConfirmPassword, #togglePasswordIcon, #toggleConfirmPasswordIcon')) return true;
		}

		return !!parent.querySelector('.password-visibility-toggle');
	};

	const createToggleButton = function (input) {
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'password-visibility-toggle';
		button.setAttribute('aria-label', 'Show password');
		button.setAttribute('title', 'Show password');
		button.innerHTML = '<i class="fa fa-eye" aria-hidden="true"></i>';

		button.addEventListener('click', function () {
			const isPassword = input.type === 'password';
			input.type = isPassword ? 'text' : 'password';
			button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
			button.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
			const icon = button.querySelector('i');
			if (icon) {
				icon.classList.toggle('fa-eye', !isPassword);
				icon.classList.toggle('fa-eye-slash', isPassword);
			}
		});

		return button;
	};

	const enhancePasswordField = function (input) {
		if (!input || hasExistingToggle(input)) return;

		const wrapper = document.createElement('div');
		wrapper.className = 'password-visibility-wrapper';

		const parent = input.parentNode;
		if (!parent) return;

		parent.insertBefore(wrapper, input);
		wrapper.appendChild(input);
		wrapper.appendChild(createToggleButton(input));
	};

	const initPasswordVisibilityToggles = function () {
		ensurePasswordToggleStyles();

		document.querySelectorAll('input[type="password"]').forEach(function (input) {
			enhancePasswordField(input);
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initPasswordVisibilityToggles);
	} else {
		initPasswordVisibilityToggles();
	}
})();

(function () {
	if (window.__globalSelect2Patched) return;
	window.__globalSelect2Patched = true;

	const selectSelector = 'select:not(.select2-hidden-accessible):not(.common-select2-source):not(.select2-no-init):not([data-select2-off="true"])';
	const nativeSelect2Selector = '#role, #social_icon, .js-example-basic-single, .js-example-basic-multiple, [data-select2-force="true"]';

	const getPlaceholderText = function ($select) {
		const explicitPlaceholder = String($select.attr('data-placeholder') || '').trim();
		if (explicitPlaceholder) {
			return explicitPlaceholder;
		}

		const $firstOption = $select.find('option').first();
		if (!$firstOption.length) {
			return $select.prop('multiple') ? 'Select options' : '';
		}

		const firstValue = $firstOption.attr('value');
		if ((firstValue === undefined || firstValue === null || firstValue === '') && !$select.prop('multiple')) {
			return String($firstOption.text() || '').trim() || 'Select an option';
		}

		return $select.prop('multiple') ? 'Select options' : '';
	};

	const getDropdownParent = function ($select) {
		const $overlayParent = $select.closest('.modal, .offcanvas, .swal2-popup');
		if ($overlayParent.length) {
			$overlayParent.addClass('select2-dropdown-host');
			return $overlayParent;
		}

		const $localParent = $select.closest('.form-group, .input-group, .col, [class*="col-"], .card-body, form');
		if ($localParent.length) {
			$localParent.addClass('select2-dropdown-host');
			return $localParent;
		}

		return $(document.body);
	};

	const closeOpenCommonSelects = function (exceptWrapper) {
		$('.common-select2.select2-container--open').each(function () {
			const $wrapper = $(this);
			if (exceptWrapper && $wrapper.is(exceptWrapper)) return;

			$wrapper.removeClass('select2-container--open');
			$wrapper.find('.common-select2-search').val('');
			$wrapper.find('.common-select2-dropdown').hide();
			$wrapper.find('.common-select2-results__option--highlighted').removeClass('common-select2-results__option--highlighted');
		});
	};

	const bindSelect2OutsideClose = function () {
		if (window.__globalSelect2OutsideCloseBound) return;
		window.__globalSelect2OutsideCloseBound = true;

		const closeOpenSelect2 = function () {
			if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;

			$('select.select2-hidden-accessible').each(function () {
				try {
					$(this).select2('close');
				} catch (error) {
					// Ignore plugin close failures.
				}
			});
		};

		document.addEventListener('pointerdown', function (event) {
			const target = event.target;
			if (!target) return;

			const isInsideSelect2 = target.closest && target.closest('.select2-container, .select2-dropdown, .common-select2');
			if (isInsideSelect2) return;

			const isInsideScrollableContent = target.closest && target.closest('.content-wrapper, .page-body-wrapper');
			if (!isInsideScrollableContent) return;

			closeOpenSelect2();
			closeOpenCommonSelects();
		}, true);
	};

	const bindSelect2WheelBridge = function ($select) {
		if (!$select || !$select.length || $select.data('select2WheelBridgeBound')) return;

		let removeOptionWheelBridge = null;
		let removeBelowPositioning = null;

		const scrollContentWrapper = function (deltaY) {
			const scrollTargets = [
				document.querySelector('.content-wrapper'),
				document.querySelector('.page-body-wrapper'),
				document.querySelector('.main-panel'),
				document.scrollingElement
			];

			for (let index = 0; index < scrollTargets.length; index += 1) {
				const target = scrollTargets[index];
				if (!target || typeof target.scrollTop !== 'number') continue;

				const maxScrollTop = Math.max(target.scrollHeight - target.clientHeight, 0);
				if (maxScrollTop <= 0) continue;

				target.scrollTop = Math.max(0, Math.min(target.scrollTop + deltaY, maxScrollTop));
				return;
			}

			window.scrollBy({
				top: deltaY,
				left: 0,
				behavior: 'auto'
			});
		};

		const forceDropdownBelow = function () {
			const applyPosition = function () {
				const select2Instance = $select.data('select2');
				const $selectionContainer = select2Instance && select2Instance.$container ? select2Instance.$container : $select.next('.select2');
				const $openContainer = $('.select2-container--open').last();
				const $dropdown = $openContainer.find('.select2-dropdown');
				const $dropdownParent = select2Instance && select2Instance.dropdown && select2Instance.dropdown.$dropdownParent
					? select2Instance.dropdown.$dropdownParent
					: getDropdownParent($select);

				if (!$selectionContainer.length || !$openContainer.length || !$dropdown.length || !$dropdownParent.length) return;

				const isBodyMounted = $dropdownParent[0] === document.body;

				if (!isBodyMounted) {
					const parentOffset = $dropdownParent.offset() || { top: 0, left: 0 };
					const selectionOffset = $selectionContainer.offset();
					if (!selectionOffset) return;

					const top = (selectionOffset.top - parentOffset.top) + $selectionContainer.outerHeight() + 6;
					const left = selectionOffset.left - parentOffset.left;
					const width = $selectionContainer.outerWidth();

					$selectionContainer
						.removeClass('select2-container--above')
						.addClass('select2-container--below');

					$openContainer
						.removeClass('select2-container--above')
						.addClass('select2-container--below')
						.css({
							top: top + 'px',
							left: left + 'px',
							width: width + 'px'
						});

					$dropdown
						.removeClass('select2-dropdown--above')
						.addClass('select2-dropdown--below')
						.css({
							top: '0',
							left: '0',
							bottom: 'auto',
							width: '100%'
						});
					return;
				}

				const offset = $selectionContainer.offset();
				if (!offset) return;

				const top = offset.top + $selectionContainer.outerHeight() + 6;
				const left = offset.left;
				const width = $selectionContainer.outerWidth();

				$selectionContainer
					.removeClass('select2-container--above')
					.addClass('select2-container--below');

				$openContainer
					.removeClass('select2-container--above')
					.addClass('select2-container--below')
					.css({
						top: top + 'px',
						left: left + 'px',
						width: width + 'px'
					});

				$dropdown
					.removeClass('select2-dropdown--above')
					.addClass('select2-dropdown--below')
					.css({
						top: '0',
						bottom: 'auto',
						width: '100%'
					});
			};

			window.requestAnimationFrame(applyPosition);
			window.setTimeout(applyPosition, 0);

			if (typeof removeBelowPositioning === 'function') {
				removeBelowPositioning();
			}

			const repositionHandler = function () {
				applyPosition();
			};

			$(window).on('resize.globalSelect2Below scroll.globalSelect2Below', repositionHandler);
			$('.content-wrapper').on('scroll.globalSelect2Below', repositionHandler);
			removeBelowPositioning = function () {
				$(window).off('resize.globalSelect2Below scroll.globalSelect2Below', repositionHandler);
				$('.content-wrapper').off('scroll.globalSelect2Below', repositionHandler);
			};
		};

		const cleanupWheelBridge = function () {
			if (typeof removeOptionWheelBridge === 'function') {
				removeOptionWheelBridge();
				removeOptionWheelBridge = null;
			}

			if (typeof removeBelowPositioning === 'function') {
				removeBelowPositioning();
				removeBelowPositioning = null;
			}
		};

		const attachWheelBridge = function () {
			cleanupWheelBridge();
			forceDropdownBelow();

			window.requestAnimationFrame(function () {
				const openContainer = document.querySelector('.select2-container--open');
				const dropdown = openContainer ? openContainer.querySelector('.select2-dropdown') : null;
				const results = dropdown ? dropdown.querySelector('.select2-results__options') : null;
				if (!dropdown || !results) return;

				const $dropdown = $(dropdown);
				const $results = $(results);

				const getScrollDelta = function (event) {
					const sourceEvent = event.originalEvent || event;
					if (!sourceEvent) return 0;

					if (typeof sourceEvent.deltaY === 'number' && sourceEvent.deltaY !== 0) {
						return sourceEvent.deltaY;
					}

					if (typeof sourceEvent.wheelDelta === 'number' && sourceEvent.wheelDelta !== 0) {
						return sourceEvent.wheelDelta * -1;
					}

					if (typeof sourceEvent.detail === 'number' && sourceEvent.detail !== 0) {
						return sourceEvent.detail * 40;
					}

					return 0;
				};

				$results.off('mousewheel');

				const optionWheelHandler = function (event) {
					const deltaY = getScrollDelta(event);
					if (!deltaY) return;

					const maxScrollTop = Math.max(results.scrollHeight - results.clientHeight, 0);
					const nextScrollTop = Math.max(0, Math.min(results.scrollTop + deltaY, maxScrollTop));
					const canScrollDropdown =
						(deltaY < 0 && results.scrollTop > 0) ||
						(deltaY > 0 && results.scrollTop < maxScrollTop);

					event.preventDefault();
					event.stopPropagation();

					if (canScrollDropdown) {
						results.scrollTop = nextScrollTop;
						return;
					}

					scrollContentWrapper(deltaY);
				};

				const shellWheelHandler = function (event) {
					const target = event.target;
					if (target && target.closest && target.closest('.select2-results__options')) {
						return;
					}

					const deltaY = getScrollDelta(event);
					if (!deltaY) return;

					event.preventDefault();
					event.stopPropagation();
					scrollContentWrapper(deltaY);
				};

				$results.on('wheel.globalSelect2Scroll mousewheel.globalSelect2Scroll DOMMouseScroll.globalSelect2Scroll', optionWheelHandler);
				$dropdown.on('wheel.globalSelect2Shell mousewheel.globalSelect2Shell DOMMouseScroll.globalSelect2Shell', shellWheelHandler);

				removeOptionWheelBridge = function () {
					$results.off('.globalSelect2Scroll');
					$dropdown.off('.globalSelect2Shell');
				};
			});
		};

		$select.on('select2:open.globalSelect2Position', forceDropdownBelow);
		$select.on('select2:open.globalSelect2Wheel', attachWheelBridge);
		$select.on('select2:close.globalSelect2Wheel', cleanupWheelBridge);
		$select.data('select2WheelBridgeBound', true);
	};

	const shouldUseCustomSelect = function ($select) {
		if (!$select || !$select.length) return false;
		if ($select.prop('multiple')) return false;
		return !$select.is(nativeSelect2Selector);
	};

	const initCustomSingleSelect = function (select) {
		if (!select) return;

		const $select = $(select);
		if (!$select.length || $select.data('commonSelectBound')) return;

		const placeholder = getPlaceholderText($select) || 'Select an option';
		const $wrapper = $('<span class="common-select2 select2 select2-container select2-container--default select2-container--below"></span>');
		const $selection = $('<span class="selection"><span class="select2-selection select2-selection--single" role="button" tabindex="0"><span class="select2-selection__rendered"></span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span>');
		const $dropdown = $('<span class="select2-dropdown select2-dropdown--below common-select2-dropdown"><span class="select2-search select2-search--dropdown"><input type="search" class="select2-search__field common-select2-search" autocomplete="off" placeholder="Search..."></span><span class="select2-results"><ul class="select2-results__options common-select2-results" role="listbox"></ul></span></span>');
		const $rendered = $selection.find('.select2-selection__rendered');
		const $search = $dropdown.find('.common-select2-search');
		const $results = $dropdown.find('.common-select2-results');

		const renderOptions = function (searchTerm) {
			const term = String(searchTerm || '').trim().toLowerCase();
			const selectedValue = $select.val();
			const fragment = document.createDocumentFragment();
			let hasHighlightedOption = false;

			$select.find('option').each(function () {
				const optionValue = $(this).attr('value');
				const optionText = String($(this).text() || '').trim();
				const isDisabled = $(this).prop('disabled');
				const matchesTerm = !term || optionText.toLowerCase().indexOf(term) !== -1;
				if (!matchesTerm) return;

				const item = document.createElement('li');
				item.className = 'select2-results__option';
				item.setAttribute('role', 'option');
				item.setAttribute('data-value', optionValue === undefined ? '' : optionValue);
				item.textContent = optionText || placeholder;

				if (isDisabled) {
					item.setAttribute('aria-disabled', 'true');
				} else {
					const isSelected = String(String(selectedValue || '') === String(optionValue || ''));
					item.setAttribute('aria-selected', String(isSelected));
					if (!hasHighlightedOption && isSelected) {
						item.className += ' common-select2-results__option--highlighted';
						hasHighlightedOption = true;
					}
				}

				fragment.appendChild(item);
			});

			$results.empty().append(fragment);

			if (!$results.children().length) {
				$results.append('<li class="select2-results__option select2-results__message" aria-disabled="true">No results found</li>');
				return;
			}

			if (!hasHighlightedOption) {
				$results
					.find('.select2-results__option[role="option"]')
					.not('[aria-disabled="true"]')
					.first()
					.addClass('common-select2-results__option--highlighted');
			}
		};

		const syncSelection = function () {
			const selectedText = String($select.find('option:selected').first().text() || '').trim();
			const value = $select.val();
			const isPlaceholder = value === null || value === undefined || value === '';

			$rendered
				.text(selectedText || placeholder)
				.toggleClass('select2-selection__placeholder', isPlaceholder);

			$wrapper.toggleClass('common-select2-disabled', !!$select.prop('disabled'));
		};

		const scrollContentWrapper = function (deltaY) {
			const scrollTargets = [
				document.querySelector('.content-wrapper'),
				document.querySelector('.page-body-wrapper'),
				document.querySelector('.main-panel'),
				document.scrollingElement
			];

			for (let index = 0; index < scrollTargets.length; index += 1) {
				const target = scrollTargets[index];
				if (!target || typeof target.scrollTop !== 'number') continue;

				const maxScrollTop = Math.max(target.scrollHeight - target.clientHeight, 0);
				if (maxScrollTop <= 0) continue;

				target.scrollTop = Math.max(0, Math.min(target.scrollTop + deltaY, maxScrollTop));
				return;
			}
		};

		const closeDropdown = function () {
			$wrapper.removeClass('select2-container--open');
			$dropdown.hide();
			$search.val('');
			renderOptions('');
		};

		const openDropdown = function () {
			if ($select.prop('disabled')) return;

			closeOpenCommonSelects($wrapper);
			$wrapper.addClass('select2-container--open');
			$dropdown.show();
			renderOptions('');
			window.requestAnimationFrame(function () {
				$search.trigger('focus');
			});
		};

		$wrapper.append($selection).append($dropdown);
		$select.before($wrapper).addClass('common-select2-source');
		$dropdown.hide();

		$selection.on('click.commonSelect2', function () {
			if ($wrapper.hasClass('select2-container--open')) {
				closeDropdown();
				return;
			}

			openDropdown();
		});

		$selection.on('keydown.commonSelect2', function (event) {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault();
				if ($wrapper.hasClass('select2-container--open')) {
					closeDropdown();
					return;
				}

				openDropdown();
			}

			if (event.key === 'Escape') {
				closeDropdown();
			}
		});

		$search.on('input.commonSelect2', function () {
			renderOptions(this.value);
		});

		$results.on('click.commonSelect2', '.select2-results__option[role="option"]', function () {
			const $option = $(this);
			if ($option.attr('aria-disabled') === 'true') return;

			$select.val($option.data('value')).trigger('input').trigger('change');
			closeDropdown();
		});

		$results.on('mouseenter.commonSelect2', '.select2-results__option[role="option"]', function () {
			const $option = $(this);
			if ($option.attr('aria-disabled') === 'true') return;

			$results.find('.common-select2-results__option--highlighted').removeClass('common-select2-results__option--highlighted');
			$option.addClass('common-select2-results__option--highlighted');
		});

		$results.on('wheel.commonSelect2 mousewheel.commonSelect2 DOMMouseScroll.commonSelect2', function (event) {
			const sourceEvent = event.originalEvent || event;
			const deltaY =
				typeof sourceEvent.deltaY === 'number' && sourceEvent.deltaY !== 0
					? sourceEvent.deltaY
					: typeof sourceEvent.wheelDelta === 'number' && sourceEvent.wheelDelta !== 0
						? sourceEvent.wheelDelta * -1
						: typeof sourceEvent.detail === 'number'
							? sourceEvent.detail * 40
							: 0;

			if (!deltaY) return;

			const maxScrollTop = Math.max(this.scrollHeight - this.clientHeight, 0);
			const canScrollDropdown =
				(deltaY < 0 && this.scrollTop > 0) ||
				(deltaY > 0 && this.scrollTop < maxScrollTop);

			if (canScrollDropdown) return;

			event.preventDefault();
			scrollContentWrapper(deltaY);
		});

		$select.on('change.commonSelect2', syncSelection);

		if (window.MutationObserver) {
			const observer = new MutationObserver(function () {
				syncSelection();
				renderOptions($search.val());
			});

			observer.observe(select, {
				childList: true,
				subtree: true,
				attributes: true,
				attributeFilter: ['disabled', 'selected']
			});
		}

		syncSelection();
		renderOptions('');
		$select.data('commonSelectBound', true);
		$select.data('commonSelectClose', closeDropdown);
	};

	const initSingleSelect = function (select) {
		if (!window.jQuery || !jQuery.fn || !select) return;

		const $select = $(select);
		if (!$select.length || $select.hasClass('select2-hidden-accessible')) return;

		if (shouldUseCustomSelect($select)) {
			initCustomSingleSelect(select);
			return;
		}

		if (!jQuery.fn.select2) return;

		const placeholder = getPlaceholderText($select);
		const config = {
			width: '100%',
			dropdownAutoWidth: true,
			dropdownParent: getDropdownParent($select)
		};

		if (placeholder) {
			config.placeholder = placeholder;
		}

		if (placeholder && !$select.prop('multiple') && !$select.prop('required')) {
			config.allowClear = true;
		}

		$select.select2(config);
		bindSelect2WheelBridge($select);
	};

	const initSelect2Dropdowns = function (context) {
		if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;

		const $context = context ? $(context) : $(document);
		if ($context.is('select') && $context.is(selectSelector)) {
			initSingleSelect($context.get(0));
		} else if ($context.is('select.select2-hidden-accessible')) {
			bindSelect2WheelBridge($context);
		}

		$context.find(selectSelector).each(function () {
			initSingleSelect(this);
		});

		$context.find('select.select2-hidden-accessible').each(function () {
			bindSelect2WheelBridge($(this));
		});
	};

	const watchForNewSelects = function () {
		if (!window.MutationObserver || !document.body) return;

		const observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				mutation.addedNodes.forEach(function (node) {
					if (!node || node.nodeType !== 1) return;

					if (node.matches && node.matches(selectSelector)) {
						initSingleSelect(node);
						return;
					}

					if (node.querySelector && node.querySelector('select')) {
						initSelect2Dropdowns(node);
					}
				});
			});
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	};

	window.initializeSelect2Dropdowns = initSelect2Dropdowns;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			bindSelect2OutsideClose();
			initSelect2Dropdowns(document);
			watchForNewSelects();
		});
	} else {
		bindSelect2OutsideClose();
		initSelect2Dropdowns(document);
		watchForNewSelects();
	}
})();
