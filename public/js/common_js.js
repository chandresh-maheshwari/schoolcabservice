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
