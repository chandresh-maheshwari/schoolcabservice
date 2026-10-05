/**
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function (config) {
	// Define changes to default configuration here.
	// For complete reference see:
	// https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html

	// Add extra plugins (comma separated)
	config.extraPlugins = 'colorbutton,colordialog,justify,font';

	// Define a fully custom toolbar with all standard tools plus color picker, background color, and justify tools
	config.toolbar = [
		{ name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
		{ name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt'] },
		{ name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
		{ name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar', 'PageBreak', 'Iframe'] },
		{ name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'] },
		{ name: 'tools', items: ['Maximize', 'ShowBlocks', 'Source'] },
		{ name: 'document', items: ['Mode', 'Document', 'Templates'] },
		{ name: 'others', items: ['-'] },
		'/',
		{ name: 'basicstyles', items: ['Bold', 'Italic', 'Strike', 'RemoveFormat', 'TextColor', 'BGColor', 'Superscript', 'Subscript', 'Underline'] },
		{ name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language'] },
		{ name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
		// { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
		{ name: 'about', items: ['About'] }
	];

	// Remove some buttons provided by the standard plugins, which are not needed in the Standard(s) toolbar.
	config.removeButtons = '';

	// Set the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';

	// Simplify the dialog windows.
	config.removeDialogTabs = 'image:advanced;link:advanced';

	// Enable file upload
	var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : (document.querySelector('input[name="_token"]') ? document.querySelector('input[name="_token"]').value : '');

	// Dynamic base URL detection or just root relative
	var uploadUrl = '/ckeditor/upload';

	// Check if we are in a subdirectory (heuristic)
	if (window.location.pathname.includes('/schoolcabservice/')) {
		uploadUrl = '/schoolcabservice/ckeditor/upload';
	}

	// Since the user is on schoolcabservice.localhost.com (Virtual Host), the path is likely at root.
	// Overriding previous attempt.
	config.filebrowserUploadUrl = '/ckeditor/upload?type=Files&_token=' + csrfToken;
	config.filebrowserImageUploadUrl = '/ckeditor/upload?type=Images&_token=' + csrfToken;

	// Remove default "Lorem ipsum" preview text
	config.image_previewText = ' ';
};

// CKEditor toolbar menus briefly move focus to a
// floating panel. In the dashboard layout that can make the whole page jump.
// Preserve the current scroll while those menus are opened or an item is chosen.
(function () {
	var restoreFrame;
	var preserveUntil = 0;
	var originalFocus = CKEDITOR.dom.element.prototype.focus;
	var openPanels = [];

	function positionPanels() {
		openPanels = openPanels.filter(function (panel) {
			if (!panel.visible || !panel._.showBlockParams) {
				return false;
			}
			var anchor = panel._.showBlockParams[1].$;
			var toolbarControl = anchor.closest('.cke_combo, .cke_button');
			if (!toolbarControl || !toolbarControl.closest('.cke_top')) {
				return false;
			}
			anchor = toolbarControl;
			var element = panel.element.$;
			var anchorRect = anchor.getBoundingClientRect();
			var rect = element.getBoundingClientRect();
			var scaleX = element.offsetWidth ? rect.width / element.offsetWidth : 1;
			var scaleY = element.offsetHeight ? rect.height / element.offsetHeight : 1;
			var left = panel._.dir === 'rtl' ? anchorRect.right - rect.width : anchorRect.left;
			left = Math.max(0, Math.min(left, document.documentElement.clientWidth - rect.width));
			var top = anchorRect.bottom;
			if (top + rect.height > window.innerHeight && anchorRect.top >= rect.height) {
				top = anchorRect.top - rect.height;
			}
			// Convert viewport displacement into the panel's positioning coordinates.
			element.style.left = (parseFloat(element.style.left) || 0) + (left - rect.left) / (scaleX || 1) + 'px';
			element.style.top = (parseFloat(element.style.top) || 0) + (top - rect.top) / (scaleY || 1) + 'px';
			return true;
		});
	}

	// Keep toolbar focus changes from scrolling the page in the first place.
	CKEDITOR.dom.element.prototype.focus = function (defer) {
		if (Date.now() >= preserveUntil) {
			return originalFocus.apply(this, arguments);
		}
		var element = this.$;
		function focus() {
			try {
				element.focus({ preventScroll: true });
			} catch (error) {
				originalFocus.call(new CKEDITOR.dom.element(element));
			}
		}
		if (defer) {
			CKEDITOR.tools.setTimeout(focus, 100);
		} else {
			focus();
		}
	};
	var scrollTargets = [
		window,
		document.documentElement,
		document.body
	];
	var targetSelectors = ['.main-panel', '.main-panel > .content', '#sidebar-wrapper'];

	function collectTargets() {
		var targets = scrollTargets.slice();
		for (var i = 0; i < targetSelectors.length; i++) {
			var element = document.querySelector(targetSelectors[i]);
			if (element) {
				targets.push(element);
			}
		}
		return targets;
	}

	function readScroll(target) {
		if (target === window) {
			return {
				target: target,
				left: window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0,
				top: window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0
			};
		}

		return {
			target: target,
			left: target.scrollLeft || 0,
			top: target.scrollTop || 0
		};
	}

	function writeScroll(state) {
		if (!state || !state.target) {
			return;
		}

		if (state.target === window) {
			if (window.pageXOffset !== state.left || window.pageYOffset !== state.top) {
				window.scrollTo({ left: state.left, top: state.top, behavior: 'instant' });
			}
			return;
		}

		if (state.target.scrollLeft !== state.left || state.target.scrollTop !== state.top) {
			state.target.scrollTo({ left: state.left, top: state.top, behavior: 'instant' });
		}
	}

	function preserveScroll() {
		if (restoreFrame) {
			cancelAnimationFrame(restoreFrame);
		}
		preserveUntil = Date.now() + 250;
		var targets = collectTargets();
		var states = [];

		for (var i = 0; i < targets.length; i++) {
			states.push(readScroll(targets[i]));
		}

		function restore() {
			for (var j = 0; j < states.length; j++) {
				writeScroll(states[j]);
			}
		}

		function beforePaint() {
			bindPanelDocuments();
			restore();
			positionPanels();
			restoreFrame = Date.now() < preserveUntil ? requestAnimationFrame(beforePaint) : null;
		}
		restoreFrame = requestAnimationFrame(beforePaint);
	}

	function shouldPreserveScroll(target) {
		return target && target.closest && (
			target.closest('.cke_combo') ||
			target.closest('.cke_button') ||
			target.closest('.cke_combo_button') ||
			target.closest('.cke_panel') ||
			target.closest('.cke_panel_frame')
		);
	}

	function bindScrollPreserver(doc) {
		if (!doc || doc._ckeditorScrollPreserverBound) {
			return;
		}

		doc._ckeditorScrollPreserverBound = true;
		['mousedown', 'mouseup', 'click', 'keydown'].forEach(function (eventName) {
			doc.addEventListener(eventName, function (event) {
				if (doc !== document || shouldPreserveScroll(event.target)) {
					preserveScroll();
				}
			}, true);
		});
	}

	function bindPanelDocuments() {
		var frames = document.querySelectorAll('iframe.cke_panel_frame');

		for (var i = 0; i < frames.length; i++) {
			try {
				bindScrollPreserver(frames[i].contentDocument || frames[i].contentWindow.document);
			} catch (error) {
				// Ignore inaccessible frames; local CKEditor panels are same-origin.
			}
		}
	}

	bindScrollPreserver(document);

	CKEDITOR.on('instanceReady', function (event) {
		bindScrollPreserver(document);
		event.editor.on('panelShow', function (panelEvent) {
			var panel = panelEvent.data;
			if (openPanels.indexOf(panel) === -1) {
				openPanels.push(panel);
			}
			positionPanels();
			bindPanelDocuments();
		});
	});
})();

CKEDITOR.on('dialogDefinition', function (ev) {
	var dialogName = ev.data.name;
	var dialogDefinition = ev.data.definition;

	if (dialogName === 'image') {
		var infoTab = dialogDefinition.getContents('info');
		// Hide URL field and remove validation
		if (infoTab) {
			var urlField = infoTab.get('txtUrl');
			if (urlField) {
				urlField.hidden = true;
				urlField.label = '';
				// Remove the validation so empty URL doesn't alert "Image source URL is missing"
				urlField.validate = null;
			}
		}

		// Default to Upload tab
		var originalOnShow = dialogDefinition.onShow;
		dialogDefinition.onShow = function () {
			if (originalOnShow) {
				originalOnShow.apply(this, arguments);
			}
			this.selectPage('Upload');
		};

		// Auto-Click "Send to Server" when file is selected
		var uploadTab = dialogDefinition.getContents('Upload');
		if (uploadTab) {
			var fileInput = uploadTab.get('upload');
			if (fileInput) {
				fileInput.onChange = function () {
					// Trigger the upload button click
					var dialog = this.getDialog();
					var uploadButton = dialog.getContentElement('Upload', 'uploadButton');
					if (uploadButton) {
						uploadButton.click();
					}
				};
			}
		}

		// CSS to fit image in preview box
		var originalOnLoad = dialogDefinition.onLoad;
		dialogDefinition.onLoad = function () {
			if (originalOnLoad) {
				originalOnLoad.apply(this, arguments);
			}

			// Inject styles for the preview box
			var css = '.ImagePreviewBox { overflow: hidden !important; height: auto !important; min-height: 150px; text-align: center; } .ImagePreviewBox img { max-width: 100% !important; max-height: 200px !important; width: auto !important; height: auto !important; }';
			var head = document.getElementsByTagName('head')[0];
			var style = document.createElement('style');

			if (style.styleSheet) {
				style.styleSheet.cssText = css;
			} else {
				style.appendChild(document.createTextNode(css));
			}

			head.appendChild(style);
		};

		// Enforce Single Image Policy: Remove old images when a new one is added
		var originalOnOk = dialogDefinition.onOk;
		dialogDefinition.onOk = function () {
			var editor = this.getParentEditor();

			// Run the default behavior (insert/update)
			if (originalOnOk) {
				originalOnOk.apply(this, arguments);
			}

			// Post-insertion cleanup to remove other images
			setTimeout(function () {
				var images = editor.document.getElementsByTag('img');
				var count = images.count();

				if (count > 1) {
					var selection = editor.getSelection();
					var selectedElement = selection.getSelectedElement();

					if (selectedElement && selectedElement.getName() === 'img') {
						// Keep the selected (newly inserted) image, remove others
						for (var i = count - 1; i >= 0; i--) {
							var img = images.getItem(i);
							// Compare using standard CKEditor DOM comparison if possible, or just native element
							if (!img.equals(selectedElement)) {
								img.remove();
							}
						}
					}
				}
			}, 0);
		};
	}
});
