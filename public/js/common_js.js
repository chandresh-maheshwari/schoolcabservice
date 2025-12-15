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


function previewImage(event) {
	// console.log(event);
	var reader = new FileReader();

	reader.onload = function () {
		var output = document.getElementById('imagePreview');
		var removeImageBtn = document.getElementById('removeImageBtn');
		var deleteImageBtn = document.getElementById('deleteImageBtn');
		// var dltBtnDiv = document.getElementById('dlt_btn_div');

		output.src = reader.result;
		output.style.display = 'block'; // Show the image
		removeImageBtn.style.display = 'inline-block'; // Show delete button
		deleteImageBtn.style.display = 'none'; // Show delete button
		// dltBtnDiv.style.display = 'ruby'; // Show the whole container
	}

	if (event.target.files && event.target.files[0]) {
		reader.readAsDataURL(event.target.files[0]);

		// Show filename
		var imageName = document.getElementById('imageName');
		imageName.textContent = event.target.files[0].name;
	}
}

function previewImage1(event) {
	// console.log(event);
	var reader = new FileReader();

	reader.onload = function () {
		var output = document.getElementById('imagePreview1');
		var removeImageBtn = document.getElementById('removeImageBtn1');
		var deleteImageBtn = document.getElementById('deleteImageBtn1');
		// var dltBtnDiv = document.getElementById('dlt_btn_div');

		output.src = reader.result;
		output.style.display = 'block'; // Show the image
		removeImageBtn.style.display = 'inline-block'; // Show delete button
		deleteImageBtn.style.display = 'none'; // Show delete button
		// dltBtnDiv.style.display = 'ruby'; // Show the whole container
	}

	if (event.target.files && event.target.files[0]) {
		reader.readAsDataURL(event.target.files[0]);

		// Show filename
		var imageName = document.getElementById('imageName1');
		imageName.textContent = event.target.files[0].name;
	}
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