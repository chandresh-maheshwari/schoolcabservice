// Reusable delete with confirmation for images or files
window.deleteImageWithConfirm = function(options) {
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
					if (el) el.textContent = 'Image not selected';
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
