(function (window, document, L) {
    'use strict';

    if (!window || !document || !L) {
        return;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function debounce(callback, wait) {
        var timerId = null;

        return function () {
            var context = this;
            var args = arguments;

            window.clearTimeout(timerId);
            timerId = window.setTimeout(function () {
                callback.apply(context, args);
            }, wait);
        };
    }

    function buildVehicleIconSvgMarkup(className) {
        return '<span class="' + className + '" aria-hidden="true">' +
            '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                '<path d="M7 16.5C7.82843 16.5 8.5 17.1716 8.5 18C8.5 18.8284 7.82843 19.5 7 19.5C6.17157 19.5 5.5 18.8284 5.5 18C5.5 17.1716 6.17157 16.5 7 16.5Z" fill="currentColor"/>' +
                '<path d="M17 16.5C17.8284 16.5 18.5 17.1716 18.5 18C18.5 18.8284 17.8284 19.5 17 19.5C16.1716 19.5 15.5 18.8284 15.5 18C15.5 17.1716 16.1716 16.5 17 16.5Z" fill="currentColor"/>' +
                '<path d="M5.4 8.5C5.71998 7.56673 6.59857 6.94 7.58521 6.94H16.4148C17.4014 6.94 18.28 7.56673 18.6 8.5L19.27 10.46C20.3046 10.6305 21 11.3983 21 12.42V15.5C21 15.7761 20.7761 16 20.5 16H19.3C18.7964 15.3927 17.9945 15 17.1 15C16.2055 15 15.4036 15.3927 14.9 16H9.1C8.59639 15.3927 7.79447 15 6.9 15C6.00553 15 5.20361 15.3927 4.7 16H3.5C3.22386 16 3 15.7761 3 15.5V12.42C3 11.3983 3.69538 10.6305 4.73 10.46L5.4 8.5ZM6.78 10.15H17.22L16.75 8.77C16.64 8.45 16.34 8.24 16 8.24H8C7.66 8.24 7.36 8.45 7.25 8.77L6.78 10.15Z" fill="currentColor"/>' +
            '</svg>' +
        '</span>';
    }

    function getFieldErrorElement(field) {
        if (!field || !field.closest) {
            return null;
        }

        var formGroup = field.closest('.form-group');
        return formGroup ? formGroup.querySelector('.error-message') : null;
    }

    function RouteBuilder(config) {
        this.config = config || {};
        this.form = document.getElementById(config.formId);
        this.mapElement = document.getElementById(config.mapId);
        this.layoutElement = document.getElementById(config.layoutId);
        this.sidebarElement = document.getElementById(config.sidebarId);
        this.hiddenRouteJsonInput = document.getElementById(config.routeJsonInputId);
        this.submitButton = document.getElementById(config.submitButtonId);
        this.clearAllButton = document.getElementById(config.clearAllButtonId);
        this.fitRouteButton = document.getElementById(config.fitRouteButtonId);
        this.recenterButton = document.getElementById(config.recenterButtonId);
        this.openSidebarButton = document.getElementById(config.openSidebarButtonId);
        this.closeSidebarButton = document.getElementById(config.closeSidebarButtonId);
        this.introCard = document.getElementById(config.introCardId);
        this.introSearchInput = document.getElementById(config.introSearchInputId);
        this.introSearchResults = document.getElementById(config.introSearchResultsId);
        this.introOpenButton = document.getElementById(config.introOpenButtonId);
        this.introBackButton = document.getElementById(config.introBackButtonId);
        this.introCloseSearchButton = document.getElementById(config.introCloseSearchButtonId);
        this.introPlannerButton = document.getElementById(config.introPlannerButtonId);
        this.introPickStartButton = document.getElementById(config.introPickStartButtonId);
        this.introEmptyState = document.getElementById(config.introEmptyStateId);
        this.introPlaceState = document.getElementById(config.introPlaceStateId);
        this.introPlaceHero = document.getElementById(config.introPlaceHeroId);
        this.introPlaceName = document.getElementById(config.introPlaceNameId);
        this.introPlaceSubname = document.getElementById(config.introPlaceSubnameId);
        this.introPlaceAddress = document.getElementById(config.introPlaceAddressId);
        this.introPlaceMeta = document.getElementById(config.introPlaceMetaId);
        this.introQuickFacts = document.getElementById(config.introQuickFactsId);
        this.introLatValue = document.getElementById(config.introLatValueId);
        this.introLngValue = document.getElementById(config.introLngValueId);
        this.introDirectionsButton = document.getElementById(config.introDirectionsButtonId);
        this.introUseStartButton = document.getElementById(config.introUseStartButtonId);
        this.introSaveButton = document.getElementById(config.introSaveButtonId);
        this.introNearbyButton = document.getElementById(config.introNearbyButtonId);
        this.introSendButton = document.getElementById(config.introSendButtonId);
        this.introShareButton = document.getElementById(config.introShareButtonId);
        this.introClosePlaceButton = document.getElementById(config.introClosePlaceButtonId);
        this.shareModal = document.getElementById(config.shareModalId);
        this.shareModalBackdrop = document.getElementById(config.shareModalBackdropId);
        this.shareModalCloseButton = document.getElementById(config.shareModalCloseButtonId);
        this.shareLinkTabButton = document.getElementById(config.shareLinkTabButtonId);
        this.shareEmbedTabButton = document.getElementById(config.shareEmbedTabButtonId);
        this.shareLinkPane = document.getElementById(config.shareLinkPaneId);
        this.shareEmbedPane = document.getElementById(config.shareEmbedPaneId);
        this.shareEmbedSizeSelect = document.getElementById(config.shareEmbedSizeSelectId);
        this.shareEmbedCodeValue = document.getElementById(config.shareEmbedCodeValueId);
        this.sharePlaceThumb = document.getElementById(config.sharePlaceThumbId);
        this.sharePlaceName = document.getElementById(config.sharePlaceNameId);
        this.sharePlaceAddress = document.getElementById(config.sharePlaceAddressId);
        this.shareLinkValue = document.getElementById(config.shareLinkValueId);
        this.shareCopyLinkButton = document.getElementById(config.shareCopyLinkButtonId);
        this.shareEmbedValue = document.getElementById(config.shareEmbedValueId);
        this.shareEmbedPreview = document.getElementById(config.shareEmbedPreviewId);
        this.shareCopyEmbedButton = document.getElementById(config.shareCopyEmbedButtonId);
        this.shareWhatsappButton = document.getElementById(config.shareWhatsappButtonId);
        this.shareXButton = document.getElementById(config.shareXButtonId);
        this.shareGmailButton = document.getElementById(config.shareGmailButtonId);
        this.sendToPhoneEmail = String(config.sendToPhoneEmail || '').trim();
        this.sendModal = document.getElementById(config.sendModalId);
        this.sendModalBackdrop = document.getElementById(config.sendModalBackdropId);
        this.sendModalCloseButton = document.getElementById(config.sendModalCloseButtonId);
        this.sendDeviceButton = document.getElementById(config.sendDeviceButtonId);
        this.sendDeviceTitle = document.getElementById(config.sendDeviceTitleId);
        this.sendEmailButton = document.getElementById(config.sendEmailButtonId);
        this.sendEmailTitle = document.getElementById(config.sendEmailTitleId);
        this.sendEmailValue = document.getElementById(config.sendEmailValueId);
        this.streetViewTrigger = document.getElementById(config.streetViewTriggerId);
        this.streetViewTriggerThumb = document.getElementById(config.streetViewTriggerThumbId);
        this.streetViewTriggerCaption = document.getElementById(config.streetViewTriggerCaptionId);
        this.streetViewModal = document.getElementById(config.streetViewModalId);
        this.streetViewCloseButton = document.getElementById(config.streetViewCloseButtonId);
        this.streetViewOpenMapsButton = document.getElementById(config.streetViewOpenMapsButtonId);
        this.streetViewPanoramaElement = document.getElementById(config.streetViewPanoramaId);
        this.streetViewMapElement = document.getElementById(config.streetViewMapId);
        this.streetViewTitle = document.getElementById(config.streetViewTitleId);
        this.streetViewSubtitle = document.getElementById(config.streetViewSubtitleId);
        this.streetViewMeta = document.getElementById(config.streetViewMetaId);
        this.streetViewLatValue = document.getElementById(config.streetViewLatValueId);
        this.streetViewLngValue = document.getElementById(config.streetViewLngValueId);
        this.addPickupButton = document.getElementById(config.addPickupButtonId);
        this.customLocationToggleButton = document.getElementById(config.customLocationToggleButtonId);
        this.customLocationPanel = document.getElementById(config.customLocationPanelId);
        this.customLocationNameInput = document.getElementById(config.customLocationNameInputId);
        this.customLocationAddressInput = document.getElementById(config.customLocationAddressInputId);
        this.customLocationLatInput = document.getElementById(config.customLocationLatInputId);
        this.customLocationLngInput = document.getElementById(config.customLocationLngInputId);
        this.customLocationStatus = document.getElementById(config.customLocationStatusId);
        this.customLocationSearchButton = document.getElementById(config.customLocationSearchButtonId);
        this.customLocationPreviewButton = document.getElementById(config.customLocationPreviewButtonId);
        this.customLocationPickButton = document.getElementById(config.customLocationPickButtonId);
        this.customLocationSaveButton = document.getElementById(config.customLocationSaveButtonId);
        this.customLocationCancelButton = document.getElementById(config.customLocationCancelButtonId);
        this.pickupsContainer = document.getElementById(config.pickupsContainerId);
        this.mapSelectionStatus = document.getElementById(config.mapSelectionStatusId);
        this.addDestinationRow = document.getElementById(config.addDestinationRowId);
        this.routeOptionsContainer = document.getElementById(config.routeOptionsContainerId);
        this.mapLayerButtons = document.querySelectorAll('[data-route-map-layer]');

        this.startBindings = this.createStaticBinding(config.startPointPrefix, 'start');
        this.endBindings = this.createStaticBinding(config.endPointPrefix, 'end');

        this.map = null;
        this.baseLayers = {};
        this.referenceLayers = {};
        this.activeBaseLayer = null;
        this.activeReferenceLayer = null;
        this.activeBaseLayerKey = 'roadmap';
        this.polyline = null;
        this.markers = [];
        this.pickupEntries = [];
        this.activeMapSelection = null;
        this.draggedPickupId = null;
        this.routeRequestToken = 0;
        this.currentGeojson = null;
        this.currentRouteOptions = [];
        this.currentRouteLegs = [];
        this.currentOrderedPoints = [];
        this.selectedRouteIndex = 0;
        this.pickupCounter = 0;
        this.isPlannerExpanded = true;
        this.introSearchAbortController = null;
        this.introSelectedPlace = null;
        this.introPreviewMarker = null;
        this.customLocationDraftPoint = null;
        this.customLocationDraftMarker = null;
        this.popupHeroImageCache = {};
        this.popupHeroImageRequests = {};
        this.defaultCenter = [23.0225, 72.5714];
        this.defaultZoom = 12;
        this.locationContext = {
            state: String((config.initialLocationContext || {}).state || '').trim(),
            city: String((config.initialLocationContext || {}).city || '').trim()
        };
        this.cityBounds = null;
        this.recentSearchStorageKey = 'route_builder_recent_places_v1';
        this.recentSearchesCache = [];
        this.streetViewPreviewData = null;
        this.streetViewPreviewLookupKey = '';
        this.streetViewPreviewRequestToken = 0;
        this.streetViewMiniMap = null;
        this.streetViewMiniMarker = null;
        this.streetPreviewRenderToken = 0;
        this.panoramaxViewerElement = null;
        this.streetViewPreviewUpdater = debounce(this.updateStreetViewPreview.bind(this), 320);
    }

    RouteBuilder.googleMapsApiPromise = null;
    RouteBuilder.customLocationPinIcon = null;

    RouteBuilder.prototype.createStaticBinding = function (prefix, type) {
        return {
            type: type,
            input: document.getElementById(prefix + 'Input'),
            results: document.getElementById(prefix + 'Results'),
            meta: document.getElementById(prefix + 'Meta'),
            mapButton: document.getElementById(prefix + 'MapBtn'),
            clearButton: document.getElementById(prefix + 'ClearBtn'),
            point: null,
            searchAbortController: null
        };
    };

    RouteBuilder.prototype.init = function () {
        if (!this.form || !this.mapElement || !this.hiddenRouteJsonInput || !this.submitButton) {
            return;
        }

        this.initMap();
        if (this.locationContext.city) {
            this.setLocationContext(this.locationContext.state, this.locationContext.city);
        }
        this.bindStaticPoint(this.startBindings);
        this.bindStaticPoint(this.endBindings);
        this.bindMapLayerControls();
        this.bindPlannerControls();
        this.bindShareModalControls();
        this.bindSendModalControls();
        if (this.addPickupButton) {
            this.addPickupButton.addEventListener('click', this.handleAddPickupClick.bind(this));
        }
        if (this.clearAllButton) {
            this.clearAllButton.addEventListener('click', this.clearAllPoints.bind(this));
        }
        if (this.fitRouteButton) {
            this.fitRouteButton.addEventListener('click', this.fitMapToCurrentRoute.bind(this));
        }
        if (this.recenterButton) {
            this.recenterButton.addEventListener('click', this.recenterMap.bind(this));
        }
        this.bindCustomLocationPanel();
        this.bindInlineValidation();

        this.submitButton.addEventListener('click', this.submitForm.bind(this));

        this.loadInitialData(this.config.initialRouteJson || null);
        this.setPlannerExpanded(this.hasAnyRoutePoints());
        this.renderIntroCardState();
        this.updateAddDestinationVisibility();
        this.renderRouteOptions([]);
        this.updateMapSelectionStatus();
        this.refreshRoutePreview();
    };

    RouteBuilder.prototype.bindInlineValidation = function () {
        var fieldIds = ['school_id', 'name', 'driver_id', 'bus_id'];

        fieldIds.forEach(function (fieldId) {
            var field = document.getElementById(fieldId);
            if (!field) {
                return;
            }

            var clearError = function () {
                var value = String(field.value || '').trim();
                if (value === '') {
                    return;
                }

                var errorElement = getFieldErrorElement(field);
                if (errorElement) {
                    errorElement.textContent = '';
                }
            };

            field.addEventListener('change', clearError);
            field.addEventListener('input', clearError);
        });
    };

    RouteBuilder.prototype.clearFieldError = function (fieldId) {
        var field = document.getElementById(fieldId);
        var errorElement = getFieldErrorElement(field);
        if (errorElement) {
            errorElement.textContent = '';
        }
    };

    RouteBuilder.prototype.initMap = function () {
        var self = this;

        this.map = L.map(this.mapElement).setView(this.defaultCenter, this.defaultZoom);

        this.initBaseLayers();
        this.switchBaseLayer(this.activeBaseLayerKey);

        this.polyline = L.polyline([], {
            color: '#0b7285',
            weight: 5,
            opacity: 0.85
        }).addTo(this.map);

        this.map.on('click', this.handleMapClick.bind(this));
    };

    RouteBuilder.prototype.initBaseLayers = function () {
        var self = this;
        this.baseLayers = {
            roadmap: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                crossOrigin: true,
                attribution: '&copy; OpenStreetMap contributors'
            }),
            satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                crossOrigin: true,
                attribution: 'Tiles &copy; Esri'
            }),
            terrain: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                maxZoom: 17,
                crossOrigin: true,
                attribution: 'Map data: &copy; OpenStreetMap contributors, SRTM | Map style: &copy; OpenTopoMap'
            })
        };

        this.referenceLayers = {
            satellite: L.layerGroup([
                L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    crossOrigin: true,
                    attribution: 'Roads &copy; Esri',
                    opacity: 0.85
                }),
                L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    crossOrigin: true,
                    attribution: 'Labels &copy; Esri',
                    opacity: 0.92
                })
            ])
        };

        Object.keys(this.baseLayers).forEach(function (layerKey) {
            self.baseLayers[layerKey].on('tileerror', function () {
                if (typeof window.notify === 'function') {
                    window.notify('error', 'Map tiles failed to load. Please hard refresh and try again.');
                }
            });
        });

        Object.keys(this.referenceLayers).forEach(function (layerKey) {
            var referenceLayer = self.referenceLayers[layerKey];
            if (referenceLayer && typeof referenceLayer.eachLayer === 'function') {
                referenceLayer.eachLayer(function (innerLayer) {
                    if (innerLayer && typeof innerLayer.on === 'function') {
                        innerLayer.on('tileerror', function () {
                            if (typeof window.notify === 'function') {
                                window.notify('error', 'Map labels failed to load. Please hard refresh and try again.');
                            }
                        });
                    }
                });
                return;
            }

            if (referenceLayer && typeof referenceLayer.on === 'function') {
                referenceLayer.on('tileerror', function () {
                    if (typeof window.notify === 'function') {
                        window.notify('error', 'Map labels failed to load. Please hard refresh and try again.');
                    }
                });
            }
        });
    };

    RouteBuilder.prototype.bindMapLayerControls = function () {
        var self = this;
        if (!this.mapLayerButtons || !this.mapLayerButtons.length) {
            return;
        }

        this.mapLayerButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var layerKey = String(button.getAttribute('data-route-map-layer') || '').trim();
                if (!layerKey) {
                    return;
                }

                self.switchBaseLayer(layerKey);
            });
        });
    };

    RouteBuilder.prototype.bindPlannerControls = function () {
        var self = this;

        if (this.openSidebarButton) {
            this.openSidebarButton.addEventListener('click', function () {
                self.setPlannerExpanded(true);
                if (self.startBindings.input && !self.startBindings.point) {
                    self.startBindings.input.focus();
                }
            });
        }

        if (this.closeSidebarButton) {
            this.closeSidebarButton.addEventListener('click', function () {
                self.setPlannerExpanded(false);
            });
        }

        if (this.introOpenButton) {
            this.introOpenButton.addEventListener('click', function () {
                self.setPlannerExpanded(true);
                if (self.startBindings.input && !self.startBindings.point) {
                    self.startBindings.input.focus();
                }
            });
        }

        if (this.introBackButton) {
            this.introBackButton.addEventListener('click', function () {
                self.clearIntroSelectedPlace(false, false);
                if (self.introSearchInput) {
                    self.introSearchInput.focus();
                }
            });
        }

        if (this.introCloseSearchButton) {
            this.introCloseSearchButton.addEventListener('click', function () {
                self.clearIntroSelectedPlace(true, false);
                if (self.introSearchInput) {
                    self.introSearchInput.focus();
                }
            });
        }

        if (this.introPlannerButton) {
            this.introPlannerButton.addEventListener('click', function () {
                self.setPlannerExpanded(true);
                if (self.startBindings.input && !self.startBindings.point) {
                    self.startBindings.input.focus();
                }
            });
        }

        if (this.introPickStartButton) {
            this.introPickStartButton.addEventListener('click', function () {
                self.activateMapSelection({ type: 'start' });
            });
        }

        if (this.introDirectionsButton) {
            this.introDirectionsButton.addEventListener('click', function () {
                self.applyIntroSelectedPlaceAsStart(true);
            });
        }

        if (this.introUseStartButton) {
            this.introUseStartButton.addEventListener('click', function () {
                self.applyIntroSelectedPlaceAsStart(true);
            });
        }

        if (this.introSaveButton) {
            this.introSaveButton.addEventListener('click', function () {
                self.handleIntroSaveAction();
            });
        }

        if (this.introNearbyButton) {
            this.introNearbyButton.addEventListener('click', function () {
                self.handleIntroNearbyAction();
            });
        }

        if (this.introSendButton) {
            this.introSendButton.addEventListener('click', function () {
                self.handleIntroSendAction();
            });
        }

        if (this.introShareButton) {
            this.introShareButton.addEventListener('click', function () {
                self.handleIntroShareAction();
            });
        }

        if (this.introClosePlaceButton) {
            this.introClosePlaceButton.addEventListener('click', function () {
                self.clearIntroSelectedPlace(true, false);
            });
        }

        if (this.introSearchInput && this.introSearchResults) {
            this.bindAutocomplete(this.introSearchInput, this.introSearchResults, function () {
                self.clearIntroSelectedPlace(false, true);
            }, function (selectedPoint) {
                self.setIntroSelectedPlace(selectedPoint);
            }, function () {
                return self.introSearchAbortController;
            }, function (controller) {
                self.introSearchAbortController = controller;
            });
        }
    };

    RouteBuilder.prototype.bindShareModalControls = function () {
        var self = this;

        if (this.shareModalBackdrop) {
            this.shareModalBackdrop.addEventListener('click', function () {
                self.hideShareModal();
            });
        }

        if (this.shareModalCloseButton) {
            this.shareModalCloseButton.addEventListener('click', function () {
                self.hideShareModal();
            });
        }

        if (this.shareLinkTabButton) {
            this.shareLinkTabButton.addEventListener('click', function () {
                self.setShareModalTab('link');
            });
        }

        if (this.shareEmbedTabButton) {
            this.shareEmbedTabButton.addEventListener('click', function () {
                self.setShareModalTab('embed');
            });
        }

        if (this.shareCopyLinkButton) {
            this.shareCopyLinkButton.addEventListener('click', function () {
                self.copyShareText(self.shareLinkValue ? self.shareLinkValue.textContent : '', 'Share link copied.');
            });
        }

        if (this.shareCopyEmbedButton) {
            this.shareCopyEmbedButton.addEventListener('click', function () {
                self.copyShareText(self.shareEmbedValue ? self.shareEmbedValue.value : '', 'Embed code copied.');
            });
        }

        if (this.shareEmbedSizeSelect) {
            this.shareEmbedSizeSelect.addEventListener('change', function () {
                self.updateShareEmbedPresentation();
            });
        }

        if (this.shareWhatsappButton) {
            this.shareWhatsappButton.addEventListener('click', function () {
                self.openShareTarget('whatsapp');
            });
        }

        if (this.shareXButton) {
            this.shareXButton.addEventListener('click', function () {
                self.openShareTarget('x');
            });
        }

        if (this.shareGmailButton) {
            this.shareGmailButton.addEventListener('click', function () {
                self.openShareTarget('gmail');
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                self.hideShareModal();
                self.hideSendModal();
                self.hideStreetViewModal();
            }
        });
    };

    RouteBuilder.prototype.bindSendModalControls = function () {
        var self = this;

        if (this.sendModalBackdrop) {
            this.sendModalBackdrop.addEventListener('click', function () {
                self.hideSendModal();
            });
        }

        if (this.sendModalCloseButton) {
            this.sendModalCloseButton.addEventListener('click', function () {
                self.hideSendModal();
            });
        }

        if (this.sendDeviceButton) {
            this.sendDeviceButton.addEventListener('click', function () {
                self.handleSendDeviceOption();
            });
        }

        if (this.sendEmailButton) {
            this.sendEmailButton.addEventListener('click', function () {
                self.handleSendEmailOption();
            });
        }
    };

    RouteBuilder.prototype.bindStreetViewControls = function () {
        var self = this;

        if (this.streetViewTrigger) {
            this.streetViewTrigger.addEventListener('click', function () {
                self.showStreetViewModal();
            });
        }

        if (this.streetViewCloseButton) {
            this.streetViewCloseButton.addEventListener('click', function () {
                self.hideStreetViewModal();
            });
        }

        if (this.streetViewOpenMapsButton) {
            this.streetViewOpenMapsButton.addEventListener('click', function () {
                self.hideStreetViewModal();
            });
        }
    };

    RouteBuilder.prototype.hasAnyRoutePoints = function () {
        if (this.startBindings.point || this.endBindings.point) {
            return true;
        }

        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            if (this.pickupEntries[index].point) {
                return true;
            }
        }

        return false;
    };

    RouteBuilder.prototype.syncIntroSearchInput = function () {
        if (!this.introSearchInput) {
            return;
        }

        if (this.introSelectedPlace) {
            this.introSearchInput.value = this.introSelectedPlace.address;
            return;
        }

        this.introSearchInput.value = this.startBindings.point ? this.startBindings.point.address : '';
    };

    RouteBuilder.prototype.renderIntroCardState = function () {
        var hasSelectedPlace = !!this.introSelectedPlace;

        if (this.introOpenButton) {
            this.introOpenButton.classList.toggle('d-none', hasSelectedPlace);
        }

        if (this.introPlannerButton) {
            this.introPlannerButton.classList.toggle('d-none', hasSelectedPlace);
        }

        if (this.introBackButton) {
            this.introBackButton.classList.toggle('d-none', !hasSelectedPlace);
        }

        if (this.introCloseSearchButton) {
            this.introCloseSearchButton.classList.toggle('d-none', !hasSelectedPlace && !(this.introSearchInput && this.introSearchInput.value.trim() !== ''));
        }

        if (this.introPlaceState) {
            this.introPlaceState.classList.toggle('d-none', !hasSelectedPlace);
        }

        if (!hasSelectedPlace) {
            if (this.introPlaceHero) {
                this.introPlaceHero.innerHTML = '';
            }
            if (this.introPlaceName) {
                this.introPlaceName.textContent = 'Selected place';
            }
            if (this.introPlaceSubname) {
                this.introPlaceSubname.textContent = '';
            }
            if (this.introPlaceAddress) {
                this.introPlaceAddress.textContent = '';
            }
            if (this.introPlaceMeta) {
                this.introPlaceMeta.innerHTML = '';
            }
            if (this.introQuickFacts) {
                this.introQuickFacts.textContent = 'Selected place details will appear here after search.';
            }

            if (this.introLatValue) {
                this.introLatValue.textContent = '--';
            }
            if (this.introLngValue) {
                this.introLngValue.textContent = '--';
            }
            return;
        }

        if (this.introPlaceName) {
            this.introPlaceName.textContent = this.introSelectedPlace.name || 'Selected place';
        }
        if (this.introPlaceSubname) {
            this.introPlaceSubname.textContent = '';
        }
        if (this.introPlaceAddress) {
            this.introPlaceAddress.textContent = this.introSelectedPlace.address || 'Address unavailable';
        }
        if (this.introPlaceMeta) {
            this.introPlaceMeta.innerHTML =
                '<span class="route-map-place-chip">Search Result</span>' +
                '<span class="route-map-place-chip route-map-place-chip-muted">' +
                    escapeHtml(Number(this.introSelectedPlace.lat).toFixed(5) + ', ' + Number(this.introSelectedPlace.lng).toFixed(5)) +
                '</span>';
        }
        if (this.introQuickFacts) {
            this.introQuickFacts.textContent = this.buildIntroQuickFacts(this.introSelectedPlace); 
        }
        if (this.introLatValue) {
            this.introLatValue.textContent = this.isFiniteNumber(this.introSelectedPlace.lat)
                ? Number(this.introSelectedPlace.lat).toFixed(5)
                : '--';
        }
        if (this.introLngValue) {
            this.introLngValue.textContent = this.isFiniteNumber(this.introSelectedPlace.lng)
                ? Number(this.introSelectedPlace.lng).toFixed(5)
                : '--';
        }

        this.renderIntroPlaceHero();
        this.ensureIntroSelectedPlaceHeroMedia();
    };

    RouteBuilder.prototype.buildIntroQuickFacts = function (point) {
        if (!point) {
            return 'Selected place details will appear here after search.';
        }

        var name = String(point.name || 'This place').trim();
        var address = String(point.address || '').trim();
        var parts = address ? address.split(',').map(function (part) {
            return String(part || '').trim();
        }).filter(Boolean) : [];
        var locality = parts.length ? parts.slice(0, 3).join(', ') : 'the selected area';

        return name + ' is a selected location in ' + locality + '. You can review the map preview, save this place, explore nearby spots, or use Directions to start building the route.';
    };

    RouteBuilder.prototype.renderIntroPlaceHero = function () {
        if (!this.introPlaceHero) {
            return;
        }

        if (!this.introSelectedPlace) {
            this.introPlaceHero.innerHTML = '';
            return;
        }

        this.introPlaceHero.innerHTML = this.getPopupHeroMediaHtml(this.introSelectedPlace, 'place');
        this.syncStreetViewPreviewFromIntroHero();
    };

    RouteBuilder.prototype.syncStreetViewPreviewFromIntroHero = function () {
        var heroImage = null;

        if (!this.introSelectedPlace || !this.introPlaceHero || !this.streetViewTriggerThumb || !this.streetViewTriggerCaption || !this.streetViewTrigger) {
            return;
        }

        heroImage = this.introPlaceHero.querySelector('img');
        if (!heroImage || !heroImage.getAttribute('src')) {
            return;
        }

        if (!this.streetViewPreviewData) {
            this.streetViewPreviewData = this.normalizeStreetViewPreviewData(this.introSelectedPlace, null);
        }

        this.streetViewPreviewData.imageUrl = String(heroImage.getAttribute('src') || '').trim();
        this.streetViewPreviewData.fallbackImageUrl = this.streetViewPreviewData.imageUrl;
        this.streetViewPreviewData.title = this.introSelectedPlace.name || this.streetViewPreviewData.title || 'Selected place';

        this.streetViewTriggerThumb.innerHTML =
            '<img src="' + escapeHtml(this.streetViewPreviewData.imageUrl) + '" alt="' + escapeHtml(this.streetViewPreviewData.title || 'Street View preview') + '" loading="lazy">' +
            '<span class="route-streetview-trigger-badge">Street View</span>';
        this.streetViewTriggerCaption.textContent = this.streetViewPreviewData.title || 'Preview nearby';
        this.streetViewTrigger.title = this.streetViewPreviewData.title || 'Open Street View';
        this.streetViewTrigger.classList.remove('d-none');
    };

    RouteBuilder.prototype.ensureIntroSelectedPlaceHeroMedia = function () {
        var self = this;
        if (!this.introSelectedPlace) {
            return;
        }

        var cacheKey = this.getPointHeroCacheKey(this.introSelectedPlace);
        if (cacheKey === '') {
            return;
        }

        this.ensurePointHeroMedia(null, this.introSelectedPlace, 0, 1);

        if (this.popupHeroImageRequests[cacheKey]) {
            this.popupHeroImageRequests[cacheKey].then(function () {
                if (!self.introSelectedPlace) {
                    return;
                }

                if (self.getPointHeroCacheKey(self.introSelectedPlace) !== cacheKey) {
                    return;
                }

                self.renderIntroPlaceHero();
                self.syncStreetViewPreviewFromIntroHero();
            });
        }
    };

    RouteBuilder.prototype.setIntroSelectedPlace = function (point) {
        var normalized = this.normalizePoint(point, 'place', null);
        if (!normalized) {
            return;
        }

        normalized.type = 'place';
        this.introSelectedPlace = normalized;
        this.syncIntroSearchInput();
        this.renderIntroCardState();
        this.showIntroPreviewMarker(normalized);

        if (this.map) {
            this.map.flyTo([normalized.lat, normalized.lng], Math.max(this.map.getZoom(), 14), {
                animate: true,
                duration: 0.75
            });
        }

        this.streetViewPreviewUpdater();
    };

    RouteBuilder.prototype.showIntroPreviewMarker = function (point) {
        if (!this.map || !point || !this.isFiniteNumber(point.lat) || !this.isFiniteNumber(point.lng)) {
            return;
        }

        this.clearIntroPreviewMarker();
        this.introPreviewMarker = L.marker([point.lat, point.lng], {
            title: point.name || 'Selected place'
        }).addTo(this.map);
    };

    RouteBuilder.prototype.clearIntroPreviewMarker = function () {
        if (this.introPreviewMarker && this.map && this.map.hasLayer(this.introPreviewMarker)) {
            this.map.removeLayer(this.introPreviewMarker);
        }

        this.introPreviewMarker = null;
    };

    RouteBuilder.prototype.clearIntroSelectedPlace = function (clearInput, preserveInputValue) {
        this.introSelectedPlace = null;
        this.clearIntroPreviewMarker();

        if (clearInput && this.introSearchInput && !preserveInputValue) {
            this.introSearchInput.value = '';
        }

        if (!preserveInputValue) {
            this.syncIntroSearchInput();
        }

        this.renderIntroCardState();
        this.streetViewPreviewUpdater();
    };

    RouteBuilder.prototype.handleIntroSaveAction = function () {
        if (!this.introSelectedPlace) {
            return;
        }

        if (typeof window.notify === 'function') {
            window.notify('success', 'Place saved in preview for this route.');
        }
    };

    RouteBuilder.prototype.handleIntroNearbyAction = function () {
        if (!this.introSelectedPlace || !this.map) {
            return;
        }

        this.map.flyTo([this.introSelectedPlace.lat, this.introSelectedPlace.lng], 16, {
            animate: true,
            duration: 0.75
        });
    };

    RouteBuilder.prototype.handleIntroSendAction = function () {
        if (!this.introSelectedPlace) {
            return;
        }

        this.showSendModal();
    };

    RouteBuilder.prototype.showSendModal = function () {
        if (!this.introSelectedPlace || !this.sendModal) {
            return;
        }

        if (this.sendDeviceTitle) {
            this.sendDeviceTitle.textContent = this.getSendDeviceLabel();
        }

        if (this.sendEmailTitle) {
            this.sendEmailTitle.textContent = this.sendToPhoneEmail ? 'Email to ' + this.sendToPhoneEmail : 'Email option unavailable';
        }

        if (this.sendEmailValue) {
            this.sendEmailValue.textContent = this.sendToPhoneEmail || 'Add your email in profile to use this option.';
        }

        if (this.sendEmailButton) {
            this.sendEmailButton.disabled = this.sendToPhoneEmail === '';
        }

        this.sendModal.classList.remove('d-none');
        this.sendModal.setAttribute('aria-hidden', 'false');
    };

    RouteBuilder.prototype.getSendDeviceLabel = function () {
        var userAgent = String((window.navigator && window.navigator.userAgent) || '').toLowerCase();
        if (userAgent.indexOf('iphone') !== -1) {
            return 'iPhone';
        }
        if (userAgent.indexOf('ipad') !== -1) {
            return 'iPad';
        }
        if (userAgent.indexOf('android') !== -1) {
            return 'Android phone';
        }
        return 'This device';
    };

    RouteBuilder.prototype.hideSendModal = function () {
        if (!this.sendModal || this.sendModal.classList.contains('d-none')) {
            return;
        }

        this.sendModal.classList.add('d-none');
        this.sendModal.setAttribute('aria-hidden', 'true');
    };

    RouteBuilder.prototype.handleSendEmailOption = function () {
        if (!this.introSelectedPlace || this.sendToPhoneEmail === '') {
            if (typeof window.notify === 'function') {
                window.notify('error', 'No email available for send to phone.');
            }
            return;
        }

        var shareUrl = this.buildShareLink(this.introSelectedPlace);
        var subject = encodeURIComponent('Location: ' + (this.introSelectedPlace.name || 'Selected place'));
        var body = encodeURIComponent(
            (this.introSelectedPlace.name || 'Selected place') +
            '\n' + (this.introSelectedPlace.address || '') +
            '\n\n' + shareUrl
        );

        window.location.href = 'mailto:' + encodeURIComponent(this.sendToPhoneEmail) + '?subject=' + subject + '&body=' + body;
        this.hideSendModal();
    };

    RouteBuilder.prototype.handleSendDeviceOption = function () {
        if (!this.introSelectedPlace) {
            return;
        }

        window.open(this.buildShareLink(this.introSelectedPlace), '_blank', 'noopener');
        this.hideSendModal();
    };

    RouteBuilder.prototype.handleIntroShareAction = function () {
        if (!this.introSelectedPlace) {
            return;
        }

        this.showShareModal();
    };

    RouteBuilder.prototype.getStreetViewFocusPoint = function () {
        if (this.introSelectedPlace && this.isFiniteNumber(this.introSelectedPlace.lat) && this.isFiniteNumber(this.introSelectedPlace.lng)) {
            return {
                name: this.introSelectedPlace.name || 'Selected place',
                address: this.introSelectedPlace.address || '',
                lat: Number(this.introSelectedPlace.lat),
                lng: Number(this.introSelectedPlace.lng)
            };
        }

        if (this.endBindings && this.endBindings.point && this.isFiniteNumber(this.endBindings.point.lat) && this.isFiniteNumber(this.endBindings.point.lng)) {
            return {
                name: this.endBindings.point.name || 'Destination',
                address: this.endBindings.point.address || '',
                lat: Number(this.endBindings.point.lat),
                lng: Number(this.endBindings.point.lng)
            };
        }

        if (this.startBindings && this.startBindings.point && this.isFiniteNumber(this.startBindings.point.lat) && this.isFiniteNumber(this.startBindings.point.lng)) {
            return {
                name: this.startBindings.point.name || 'Start point',
                address: this.startBindings.point.address || '',
                lat: Number(this.startBindings.point.lat),
                lng: Number(this.startBindings.point.lng)
            };
        }

        return null;
    };

    RouteBuilder.prototype.buildStreetViewLookupKey = function (point) {
        if (!point || !this.isFiniteNumber(point.lat) || !this.isFiniteNumber(point.lng)) {
            return '';
        }

        return [
            Number(point.lat).toFixed(4),
            Number(point.lng).toFixed(4),
            String(point.name || '').trim().toLowerCase()
        ].join('|');
    };

    RouteBuilder.prototype.updateStreetViewPreview = function (forceRefresh) {
        var self = this;
        var point = this.getStreetViewFocusPoint();
        var lookupKey = this.buildStreetViewLookupKey(point);

        if (!this.streetViewTrigger || !point) {
            this.clearStreetViewPreview();
            return;
        }

        if (!forceRefresh && this.streetViewPreviewData && lookupKey !== '' && lookupKey === this.streetViewPreviewLookupKey) {
            return;
        }

        this.streetViewPreviewRequestToken += 1;
        this.streetViewPreviewLookupKey = lookupKey;
        this.ensurePointHeroMedia(null, point, 0, 1);
        self.streetViewPreviewData = self.normalizeStreetViewPreviewData(point, null);
        self.renderStreetViewTrigger(self.streetViewPreviewData);

        if (self.streetViewPreviewData && self.streetViewPreviewData.heroMedia && self.streetViewPreviewData.heroMedia.pending) {
            var cacheKey = self.getPointHeroCacheKey(point);
            if (cacheKey && self.popupHeroImageRequests[cacheKey]) {
                self.popupHeroImageRequests[cacheKey].then(function () {
                    if (self.streetViewPreviewLookupKey !== lookupKey) {
                        return;
                    }

                    self.streetViewPreviewData = self.normalizeStreetViewPreviewData(point, null);
                    self.renderStreetViewTrigger(self.streetViewPreviewData);
                });
            }
        }
    };

    RouteBuilder.prototype.setStreetViewTriggerLoading = function (point) {
        if (!this.streetViewTrigger) {
            return;
        }

        if (this.streetViewTriggerThumb) {
            this.streetViewTriggerThumb.innerHTML = '<span class="route-streetview-trigger-badge">Street View</span>';
        }

        if (this.streetViewTriggerCaption) {
            this.streetViewTriggerCaption.textContent = point && point.name ? point.name : 'Preview nearby';
        }

        this.streetViewTrigger.classList.remove('d-none');
    };

    RouteBuilder.prototype.clearStreetViewPreview = function () {
        this.streetViewPreviewData = null;
        this.streetViewPreviewLookupKey = '';

        if (this.streetViewTrigger) {
            this.streetViewTrigger.classList.add('d-none');
        }
    };

    RouteBuilder.prototype.normalizeStreetViewPreviewData = function (point, panoramaData) {
        var heroMedia = this.getPointHeroMedia(point);
        var heroImageUrl = this.getStreetPreviewImageUrl(point, heroMedia);
        var latLng = panoramaData && panoramaData.location && panoramaData.location.latLng
            ? panoramaData.location.latLng
            : null;
        var lat = latLng && typeof latLng.lat === 'function' ? Number(latLng.lat()) : Number(point.lat);
        var lng = latLng && typeof latLng.lng === 'function' ? Number(latLng.lng()) : Number(point.lng);
        var heading = panoramaData && panoramaData.tiles && this.isFiniteNumber(panoramaData.tiles.centerHeading)
            ? Number(panoramaData.tiles.centerHeading)
            : 0;

        return {
            pano: panoramaData && panoramaData.location && panoramaData.location.pano ? String(panoramaData.location.pano) : '',
            title: String(point.name || 'Current map area').trim(),
            address: String(point.address || 'Street View preview near the selected map area.').trim(),
            lat: lat,
            lng: lng,
            heading: heading,
            imageUrl: heroImageUrl,
            fallbackImageUrl: heroImageUrl,
            heroMedia: heroMedia || null
        };
    };

    RouteBuilder.prototype.getStreetPreviewImageUrl = function (point, heroMedia) {
        var panelImage = null;
        var cacheKey = this.getPointHeroCacheKey(point);

        if (heroMedia && heroMedia.url) {
            return heroMedia.url;
        }

        if (this.introSelectedPlace && point &&
            this.getPointHeroCacheKey(this.introSelectedPlace) === cacheKey &&
            this.introPlaceHero) {
            panelImage = this.introPlaceHero.querySelector('img');
            if (panelImage && panelImage.getAttribute('src')) {
                return String(panelImage.getAttribute('src') || '').trim();
            }
        }

        if (cacheKey !== '' && this.popupHeroImageCache[cacheKey] && this.popupHeroImageCache[cacheKey].url) {
            return String(this.popupHeroImageCache[cacheKey].url || '').trim();
        }

        return '';
    };

    RouteBuilder.prototype.renderStreetViewTrigger = function (streetViewData) {
        var thumbUrl = '';
        var fallbackThumbHtml = '<div style="width:100%;height:100%;display:flex;align-items:flex-end;justify-content:flex-start;padding:0.5rem;background:linear-gradient(135deg, rgba(15,23,42,0.14), rgba(15,23,42,0.34)), linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);"></div>';

        if (!this.streetViewTrigger || !streetViewData) {
            return;
        }

        thumbUrl = streetViewData.heroMedia && streetViewData.heroMedia.url
            ? streetViewData.heroMedia.url
            : streetViewData.imageUrl;

        if (this.streetViewTriggerThumb) {
            this.streetViewTriggerThumb.innerHTML = (thumbUrl
                ? '<img src="' + escapeHtml(thumbUrl) + '" alt="' + escapeHtml((streetViewData.title || 'Street View') + ' street view preview') + '" loading="lazy">'
                : fallbackThumbHtml) +
                '<span class="route-streetview-trigger-badge">Street View</span>';
        }

        if (this.streetViewTriggerCaption) {
            this.streetViewTriggerCaption.textContent = streetViewData.title || 'Preview nearby';
        }

        this.streetViewTrigger.title = streetViewData.title || 'Open Street View';
        this.streetViewTrigger.classList.remove('d-none');
    };

    RouteBuilder.prototype.showStreetViewModal = function () {
        var self = this;

        if (!this.streetViewPreviewData || !this.streetViewModal) {
            return;
        }

        this.streetViewModal.classList.remove('d-none');
        this.streetViewModal.setAttribute('aria-hidden', 'false');
        this.renderStreetViewModalContent(this.streetViewPreviewData);

        window.setTimeout(function () {
            self.invalidateStreetViewLayouts();
        }, 80);
    };

    RouteBuilder.prototype.hideStreetViewModal = function () {
        if (!this.streetViewModal || this.streetViewModal.classList.contains('d-none')) {
            return;
        }

        this.streetViewModal.classList.add('d-none');
        this.streetViewModal.setAttribute('aria-hidden', 'true');
    };

    RouteBuilder.prototype.renderStreetViewModalContent = function (streetViewData) {
        var fallbackImageUrl = streetViewData.fallbackImageUrl || streetViewData.imageUrl || (streetViewData.heroMedia && streetViewData.heroMedia.url ? streetViewData.heroMedia.url : '');

        if (!streetViewData) {
            return;
        }

        if (this.streetViewTitle) {
            this.streetViewTitle.textContent = streetViewData.title || 'Street View';
        }
        if (this.streetViewSubtitle) {
            this.streetViewSubtitle.textContent = streetViewData.address || 'Street View preview near the selected place.';
        }
        if (this.streetViewMeta) {
            this.streetViewMeta.textContent = 'Coordinates ' +
                Number(streetViewData.lat).toFixed(5) +
                ', ' +
                Number(streetViewData.lng).toFixed(5);
        }
        if (this.streetViewLatValue) {
            this.streetViewLatValue.textContent = Number(streetViewData.lat).toFixed(6);
        }
        if (this.streetViewLngValue) {
            this.streetViewLngValue.textContent = Number(streetViewData.lng).toFixed(6);
        }

        this.renderPanoramaxStreetPreview(streetViewData, fallbackImageUrl);
        this.renderStreetViewMiniMap(streetViewData);
    };

    RouteBuilder.prototype.invalidateStreetViewLayouts = function () {
        if (this.streetViewMiniMap) {
            this.streetViewMiniMap.invalidateSize();
        }

        if (this.panoramaxViewerElement && typeof this.panoramaxViewerElement.moveCenter === 'function') {
            try {
                this.panoramaxViewerElement.moveCenter();
            } catch (error) {
                // Ignore initial resize/recenter hiccups from the custom element.
            }
        }
    };

    RouteBuilder.prototype.renderStreetViewMiniMap = function (streetViewData) {
        if (!this.streetViewMapElement || !streetViewData) {
            return;
        }

        if (!this.streetViewMiniMap) {
            this.streetViewMiniMap = L.map(this.streetViewMapElement, {
                zoomControl: false,
                attributionControl: true
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                crossOrigin: true,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.streetViewMiniMap);
        }

        this.streetViewMiniMap.setView([streetViewData.lat, streetViewData.lng], 17);

        if (!this.streetViewMiniMarker) {
            this.streetViewMiniMarker = L.marker([streetViewData.lat, streetViewData.lng], {
                title: streetViewData.title || 'Street View point'
            }).addTo(this.streetViewMiniMap);
        } else {
            this.streetViewMiniMarker.setLatLng([streetViewData.lat, streetViewData.lng]);
            this.streetViewMiniMarker.options.title = streetViewData.title || 'Street View point';
        }

        if (Array.isArray(this.currentGeojson && this.currentGeojson.coordinates) && this.currentGeojson.coordinates.length >= 2) {
            try {
                var routeBounds = L.geoJSON(this.currentGeojson).getBounds();
                if (routeBounds.isValid()) {
                    this.streetViewMiniMap.fitBounds(routeBounds.pad(0.08));
                }
            } catch (error) {
                this.streetViewMiniMap.setView([streetViewData.lat, streetViewData.lng], 17);
            }
        }
    };

    RouteBuilder.prototype.renderPanoramaxStreetPreview = function (streetViewData, fallbackImageUrl) {
        var self = this;
        var endpoint = String(this.config.panoramaxEndpoint || '').trim();
        var renderToken = this.streetPreviewRenderToken + 1;

        this.streetPreviewRenderToken = renderToken;
        this.panoramaxViewerElement = null;

        if (!this.streetViewPanoramaElement) {
            return;
        }

        if (!window.customElements || typeof window.customElements.whenDefined !== 'function') {
            this.renderStreetPreviewFallbackImage(streetViewData, fallbackImageUrl, 'Street preview viewer browser me support nahi hai.');
            return;
        }

        if (endpoint === '') {
            this.renderStreetPreviewFallbackImage(streetViewData, fallbackImageUrl, 'Street preview endpoint configured nahi hai.');
            return;
        }

        this.streetViewPanoramaElement.innerHTML = '<div class="route-streetview-panorama-empty">Open street imagery load ho rahi hai...</div>';

        window.customElements.whenDefined('pnx-photo-viewer').then(function () {
            var viewer = null;

            if (renderToken !== self.streetPreviewRenderToken || !self.streetViewPanoramaElement) {
                return;
            }

            viewer = document.createElement('pnx-photo-viewer');
            viewer.setAttribute('endpoint', endpoint);
            viewer.setAttribute('url-parameters', 'false');
            viewer.setAttribute('style', 'width:100%;height:100%;display:block;');
            viewer.setAttribute('psv-options', JSON.stringify({
                position: [Number(streetViewData.lat), Number(streetViewData.lng)],
                picturesNavigation: 'seq',
                transitionDuration: 450
            }));

            viewer.addEventListener('broken', function () {
                if (renderToken !== self.streetPreviewRenderToken) {
                    return;
                }

                self.renderStreetPreviewFallbackImage(streetViewData, fallbackImageUrl, 'Is area me open street imagery available nahi hai.');
            }, { once: true });

            self.streetViewPanoramaElement.innerHTML = '';
            self.streetViewPanoramaElement.appendChild(viewer);
            self.panoramaxViewerElement = viewer;
            self.trySelectNearestPanoramaxPicture(viewer, streetViewData, renderToken, fallbackImageUrl);
        }).catch(function () {
            self.renderStreetPreviewFallbackImage(streetViewData, fallbackImageUrl, 'Street preview viewer load nahi ho paya.');
        });
    };

    RouteBuilder.prototype.trySelectNearestPanoramaxPicture = function (viewer, streetViewData, renderToken, fallbackImageUrl) {
        var self = this;

        if (!viewer || typeof viewer.onceAPIReady !== 'function') {
            return;
        }

        viewer.onceAPIReady().then(function () {
            if (renderToken !== self.streetPreviewRenderToken || !viewer.api || typeof viewer.api.getPicturesAroundCoordinates !== 'function') {
                return null;
            }

            return viewer.api.getPicturesAroundCoordinates(Number(streetViewData.lat), Number(streetViewData.lng), 0.0012, 16);
        }).then(function (featureCollection) {
            var features = featureCollection && Array.isArray(featureCollection.features)
                ? featureCollection.features
                : [];
            var bestFeature = null;
            var sequenceId = '';
            var pictureId = '';

            if (renderToken !== self.streetPreviewRenderToken) {
                return;
            }

            if (!features.length) {
                self.renderStreetPreviewFallbackImage(streetViewData, fallbackImageUrl, 'Is area me open street imagery available nahi hai.');
                return;
            }

            bestFeature = self.getNearestPanoramaxFeature(features, streetViewData.lat, streetViewData.lng);
            sequenceId = bestFeature && bestFeature.collection ? String(bestFeature.collection).trim() : '';
            pictureId = bestFeature && bestFeature.id ? String(bestFeature.id).trim() : '';

            if (sequenceId === '' || pictureId === '' || typeof viewer.select !== 'function') {
                return;
            }

            viewer.select(sequenceId, pictureId, true);
            window.setTimeout(function () {
                self.invalidateStreetViewLayouts();
            }, 220);
        }).catch(function () {
            self.renderStreetPreviewFallbackImage(streetViewData, fallbackImageUrl, 'Street preview load nahi ho paya.');
        });
    };

    RouteBuilder.prototype.getNearestPanoramaxFeature = function (features, lat, lng) {
        var bestFeature = null;
        var bestDistance = Number.POSITIVE_INFINITY;

        features.forEach(function (feature) {
            var coordinates = feature && feature.geometry && Array.isArray(feature.geometry.coordinates)
                ? feature.geometry.coordinates
                : [];
            var featureLng = Number(coordinates[0]);
            var featureLat = Number(coordinates[1]);
            var distance = 0;

            if (!window.isFinite(featureLat) || !window.isFinite(featureLng)) {
                return;
            }

            distance = Math.pow(featureLat - Number(lat), 2) + Math.pow(featureLng - Number(lng), 2);
            if (distance < bestDistance) {
                bestDistance = distance;
                bestFeature = feature;
            }
        });

        return bestFeature;
    };

    RouteBuilder.prototype.renderStreetPreviewFallbackImage = function (streetViewData, fallbackImageUrl, message) {
        if (!this.streetViewPanoramaElement) {
            return;
        }

        this.panoramaxViewerElement = null;

        if (fallbackImageUrl) {
            this.streetViewPanoramaElement.innerHTML = '<div style="position:relative;width:100%;height:100%;">' +
                '<img src="' + escapeHtml(fallbackImageUrl) + '" alt="' + escapeHtml(streetViewData.title || 'Street Preview') + '" style="width:100%;height:100%;object-fit:cover;display:block;">' +
                '<div class="route-streetview-panorama-empty" style="position:absolute;left:1rem;right:1rem;bottom:1rem;height:auto;padding:0.65rem 0.85rem;background:rgba(15,23,42,0.72);border-radius:12px;">' + escapeHtml(message || 'Street preview image shown as fallback.') + '</div>' +
            '</div>';
            return;
        }

        this.streetViewPanoramaElement.innerHTML = '<div class="route-streetview-panorama-empty">' + escapeHtml(message || 'Street preview available nahi hai.') + '</div>';
    };

    RouteBuilder.prototype.showShareModal = function () {
        if (!this.introSelectedPlace || !this.shareModal) {
            return;
        }

        this.populateShareModal();
        this.setShareModalTab('link');
        this.shareModal.classList.remove('d-none');
        this.shareModal.setAttribute('aria-hidden', 'false');
    };

    RouteBuilder.prototype.hideShareModal = function () {
        if (!this.shareModal || this.shareModal.classList.contains('d-none')) {
            return;
        }

        this.shareModal.classList.add('d-none');
        this.shareModal.setAttribute('aria-hidden', 'true');
    };

    RouteBuilder.prototype.setShareModalTab = function (tab) {
        var isEmbed = tab === 'embed';

        if (this.shareLinkTabButton) {
            this.shareLinkTabButton.classList.toggle('route-share-modal-tab-active', !isEmbed);
        }
        if (this.shareEmbedTabButton) {
            this.shareEmbedTabButton.classList.toggle('route-share-modal-tab-active', isEmbed);
        }
        if (this.shareLinkPane) {
            this.shareLinkPane.classList.toggle('d-none', isEmbed);
        }
        if (this.shareEmbedPane) {
            this.shareEmbedPane.classList.toggle('d-none', !isEmbed);
        }
    };

    RouteBuilder.prototype.populateShareModal = function () {
        var point = this.introSelectedPlace;
        if (!point) {
            return;
        }

        var shareUrl = this.buildShareLink(point);
        var heroMedia = this.getPointHeroMedia(point);

        if (this.sharePlaceName) {
            this.sharePlaceName.textContent = point.name || 'Selected place';
        }
        if (this.sharePlaceAddress) {
            this.sharePlaceAddress.textContent = point.address || 'Address unavailable';
        }
        if (this.shareLinkValue) {
            this.shareLinkValue.textContent = shareUrl;
        }
        if (this.sharePlaceThumb) {
            if (heroMedia && heroMedia.url) {
                this.sharePlaceThumb.innerHTML = '<img src="' + escapeHtml(heroMedia.url) + '" alt="' + escapeHtml(point.name || 'Place image') + '" loading="lazy">';
            } else {
                this.sharePlaceThumb.innerHTML = '<div class="route-share-place-thumb-fallback"></div>';
            }
        }

        this.updateShareEmbedPresentation();
    };

    RouteBuilder.prototype.buildShareLink = function (point) {
        var lat = this.isFiniteNumber(point && point.lat) ? Number(point.lat) : 0;
        var lng = this.isFiniteNumber(point && point.lng) ? Number(point.lng) : 0;
        return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(lat + ',' + lng);
    };

    RouteBuilder.prototype.buildShareEmbedCode = function (point) {
        var size = String((this.shareEmbedSizeSelect && this.shareEmbedSizeSelect.value) || 'medium').toLowerCase();
        var dimensions = {
            small: { width: 400, height: 300 },
            medium: { width: 600, height: 450 },
            large: { width: 800, height: 600 }
        };
        var selected = dimensions[size] || dimensions.medium;
        var src = this.buildShareEmbedUrl(point);

        return '<iframe src="' + src + '" width="' + selected.width + '" height="' + selected.height + '" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
    };

    RouteBuilder.prototype.buildShareEmbedUrl = function (point) {
        var lat = this.isFiniteNumber(point && point.lat) ? Number(point.lat).toFixed(6) : '0';
        var lng = this.isFiniteNumber(point && point.lng) ? Number(point.lng).toFixed(6) : '0';
        return 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=15&output=embed';
    };

    RouteBuilder.prototype.updateShareEmbedPresentation = function () {
        if (!this.introSelectedPlace) {
            return;
        }

        var embedCode = this.buildShareEmbedCode(this.introSelectedPlace);
        var embedUrl = this.buildShareEmbedUrl(this.introSelectedPlace);

        if (this.shareEmbedValue) {
            this.shareEmbedValue.value = embedCode;
        }
        if (this.shareEmbedCodeValue) {
            this.shareEmbedCodeValue.textContent = embedCode;
        }
        if (this.shareEmbedPreview) {
            this.shareEmbedPreview.src = embedUrl;
        }
    };

    RouteBuilder.prototype.copyShareText = function (text, successMessage) {
        if (!text) {
            return;
        }

        if (window.navigator && window.navigator.clipboard && typeof window.navigator.clipboard.writeText === 'function') {
            window.navigator.clipboard.writeText(text).then(function () {
                if (typeof window.notify === 'function') {
                    window.notify('success', successMessage);
                }
            }).catch(function () {
                RouteBuilder.copyTextFallback(text, successMessage);
            });
            return;
        }

        RouteBuilder.copyTextFallback(text, successMessage);
    };

    RouteBuilder.copyTextFallback = function (text, successMessage) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.style.pointerEvents = 'none';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }

        document.body.removeChild(textarea);

        if (typeof window.notify === 'function') {
            if (copied) {
                window.notify('success', successMessage);
            } else {
                window.notify('error', 'Copy failed. Please copy it manually.');
            }
        }
    };

    RouteBuilder.prototype.openShareTarget = function (target) {
        var point = this.introSelectedPlace;
        if (!point) {
            return;
        }

        var shareUrl = this.buildShareLink(point);
        var shareText = (point.name || 'Selected place') + ' - ' + (point.address || '');
        var targetUrl = '';

        if (target === 'whatsapp') {
            targetUrl = 'https://wa.me/?text=' + encodeURIComponent(shareText + ' ' + shareUrl);
        } else if (target === 'x') {
            targetUrl = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(shareText) + '&url=' + encodeURIComponent(shareUrl);
        } else if (target === 'gmail') {
            targetUrl = 'https://mail.google.com/mail/?view=cm&fs=1&su=' + encodeURIComponent(point.name || 'Location') + '&body=' + encodeURIComponent(shareText + '\n\n' + shareUrl);
        }

        if (targetUrl) {
            window.open(targetUrl, '_blank', 'noopener');
        }
    };

    RouteBuilder.prototype.applyIntroSelectedPlaceAsStart = function (openPlanner) {
        if (!this.introSelectedPlace) {
            return;
        }

        this.setStaticPoint(this.startBindings, this.introSelectedPlace);
        this.clearIntroSelectedPlace(false, false);
        this.setPlannerExpanded(openPlanner !== false);
        this.activeMapSelection = this.resolveMapTarget();
        this.updateMapSelectionStatus();
        this.refreshRoutePreview();

        if (this.endBindings.input && !this.endBindings.point) {
            this.endBindings.input.focus();
        }
    };

    RouteBuilder.prototype.setPlannerExpanded = function (expanded) {
        var shouldShowIntro = !expanded && !this.hasAnyRoutePoints();

        this.isPlannerExpanded = !shouldShowIntro;

        if (this.layoutElement) {
            this.layoutElement.classList.toggle('route-builder-shell-collapsed', shouldShowIntro);
        }

        if (this.introCard) {
            this.introCard.classList.toggle('d-none', !shouldShowIntro);
        }

        if (this.openSidebarButton) {
            this.openSidebarButton.classList.toggle('d-none', !shouldShowIntro);
        }

        if (this.closeSidebarButton) {
            this.closeSidebarButton.classList.toggle('d-none', !this.isPlannerExpanded || this.hasAnyRoutePoints());
        }

        this.syncIntroSearchInput();
        this.updateMapSelectionStatus();

        if (this.map) {
            window.setTimeout(this.map.invalidateSize.bind(this.map), 120);
        }
    };

    RouteBuilder.prototype.activateMapSelection = function (target) {
        if (!target) {
            return;
        }

        this.setPlannerExpanded(true);
        this.activeMapSelection = target;
        this.updateMapSelectionStatus();
    };

    RouteBuilder.prototype.switchBaseLayer = function (layerKey) {
        if (!this.map || !this.baseLayers[layerKey]) {
            return;
        }

        if (this.activeBaseLayer && this.map.hasLayer(this.activeBaseLayer)) {
            this.map.removeLayer(this.activeBaseLayer);
        }

        if (this.activeReferenceLayer && this.map.hasLayer(this.activeReferenceLayer)) {
            this.map.removeLayer(this.activeReferenceLayer);
        }

        this.activeBaseLayer = this.baseLayers[layerKey];
        this.activeBaseLayerKey = layerKey;
        this.activeBaseLayer.addTo(this.map);

        this.activeReferenceLayer = this.referenceLayers[layerKey] || null;
        if (this.activeReferenceLayer) {
            this.activeReferenceLayer.addTo(this.map);
        }

        this.updateMapLayerButtons();
    };

    RouteBuilder.prototype.bindCustomLocationPanel = function () {
        var self = this;

        if (this.customLocationToggleButton) {
            this.customLocationToggleButton.addEventListener('click', function () {
                self.toggleCustomLocationPanel();
            });
        }

        if (this.customLocationPickButton) {
            this.customLocationPickButton.addEventListener('click', function () {
                self.showCustomLocationPanel();
                self.activateMapSelection({ type: 'custom-location' });
                self.setCustomLocationStatus('Map par click karke custom location point choose karo.', false);
            });
        }

        if (this.customLocationSearchButton) {
            this.customLocationSearchButton.addEventListener('click', this.searchCustomLocationAddress.bind(this));
        }

        if (this.customLocationPreviewButton) {
            this.customLocationPreviewButton.addEventListener('click', this.previewCustomLocationLatLng.bind(this));
        }

        if (this.customLocationSaveButton) {
            this.customLocationSaveButton.addEventListener('click', this.saveCustomLocation.bind(this));
        }

        if (this.customLocationCancelButton) {
            this.customLocationCancelButton.addEventListener('click', function () {
                self.hideCustomLocationPanel();
            });
        }
    };

    RouteBuilder.prototype.toggleCustomLocationPanel = function () {
        if (!this.customLocationPanel) {
            return;
        }

        if (this.customLocationPanel.classList.contains('d-none')) {
            this.showCustomLocationPanel();
            return;
        }

        this.hideCustomLocationPanel();
    };

    RouteBuilder.prototype.showCustomLocationPanel = function () {
        if (!this.customLocationPanel) {
            return;
        }

        this.customLocationPanel.classList.remove('d-none');
        if (this.customLocationNameInput) {
            this.customLocationNameInput.focus();
        }
    };

    RouteBuilder.prototype.hideCustomLocationPanel = function () {
        if (!this.customLocationPanel) {
            return;
        }

        this.customLocationPanel.classList.add('d-none');
        this.clearCustomLocationDraft();

        if (this.customLocationNameInput) {
            this.customLocationNameInput.value = '';
        }
        if (this.customLocationAddressInput) {
            this.customLocationAddressInput.value = '';
        }
        if (this.customLocationLatInput) {
            this.customLocationLatInput.value = '';
        }
        if (this.customLocationLngInput) {
            this.customLocationLngInput.value = '';
        }

        this.setCustomLocationStatus('', false);

        if (this.activeMapSelection && this.activeMapSelection.type === 'custom-location') {
            this.activeMapSelection = null;
            this.updateMapSelectionStatus();
        }
    };

    RouteBuilder.prototype.clearCustomLocationDraft = function () {
        this.customLocationDraftPoint = null;

        if (this.customLocationDraftMarker && this.map && this.map.hasLayer(this.customLocationDraftMarker)) {
            this.map.removeLayer(this.customLocationDraftMarker);
        }

        this.customLocationDraftMarker = null;

        if (this.customLocationLatInput) {
            this.customLocationLatInput.value = '';
        }
        if (this.customLocationLngInput) {
            this.customLocationLngInput.value = '';
        }
    };

    RouteBuilder.prototype.setCustomLocationStatus = function (message, isError) {
        if (!this.customLocationStatus) {
            return;
        }

        this.customLocationStatus.textContent = String(message || '');
        this.customLocationStatus.classList.toggle('d-none', !message);
        this.customLocationStatus.style.color = isError ? '#dc2626' : '#0f766e';
    };

    RouteBuilder.prototype.getCustomLocationPinIcon = function () {
        if (RouteBuilder.customLocationPinIcon) {
            return RouteBuilder.customLocationPinIcon;
        }

        RouteBuilder.customLocationPinIcon = L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        return RouteBuilder.customLocationPinIcon;
    };

    RouteBuilder.prototype.buildCustomLocationPopupHtml = function (point) {
        var title = String(point && (point.name || point.address) ? (point.name || point.address) : 'Selected Location').trim();
        var address = String(point && point.address ? point.address : title).trim();
        var normalizedTitle = title.replace(/\s+/g, ' ').trim().toLowerCase();
        var normalizedAddress = address.replace(/\s+/g, ' ').trim().toLowerCase();
        var showAddress = normalizedAddress !== '' && normalizedAddress !== normalizedTitle;

        return '<div class="route-custom-location-popup">' +
            '<div class="route-custom-location-popup-title">' + escapeHtml(title) + '</div>' +
            (showAddress
                ? '<div class="route-custom-location-popup-address">' + escapeHtml(address) + '</div>'
                : '') +
            '</div>';
    };

    RouteBuilder.prototype.setCustomLocationDraftPoint = function (point) {
        if (!point || !this.isFiniteNumber(point.lat) || !this.isFiniteNumber(point.lng) || !this.map) {
            return;
        }

        this.customLocationDraftPoint = {
            name: String(point.name || '').trim(),
            address: String(point.address || '').trim(),
            lat: Number(point.lat),
            lng: Number(point.lng)
        };

        if (this.customLocationLatInput) {
            this.customLocationLatInput.value = Number(point.lat).toFixed(6);
        }
        if (this.customLocationLngInput) {
            this.customLocationLngInput.value = Number(point.lng).toFixed(6);
        }

        if (this.customLocationAddressInput && this.customLocationDraftPoint.address) {
            this.customLocationAddressInput.value = this.customLocationDraftPoint.address;
        }

        if (!this.customLocationDraftMarker) {
            this.customLocationDraftMarker = L.marker([point.lat, point.lng], {
                icon: this.getCustomLocationPinIcon(),
                title: this.customLocationDraftPoint.name || this.customLocationDraftPoint.address || 'Selected Location'
            }).addTo(this.map);
        } else {
            this.customLocationDraftMarker.setLatLng([point.lat, point.lng]);
            if (!this.map.hasLayer(this.customLocationDraftMarker)) {
                this.customLocationDraftMarker.addTo(this.map);
            }
        }

        this.customLocationDraftMarker
            .bindPopup(this.buildCustomLocationPopupHtml(this.customLocationDraftPoint), {
                maxWidth: 320,
                className: 'route-custom-location-map-popup'
            })
            .openPopup();

        this.map.setView([point.lat, point.lng], Math.max(this.map.getZoom() || 0, 16));
    };

    RouteBuilder.prototype.getCustomLocationManualPoint = function () {
        var lat = this.customLocationLatInput ? this.customLocationLatInput.value.trim() : '';
        var lng = this.customLocationLngInput ? this.customLocationLngInput.value.trim() : '';

        if (!this.isFiniteNumber(lat) || !this.isFiniteNumber(lng)) {
            return null;
        }

        return {
            name: this.customLocationNameInput ? this.customLocationNameInput.value.trim() : '',
            address: this.customLocationAddressInput ? this.customLocationAddressInput.value.trim() : '',
            lat: Number(lat),
            lng: Number(lng)
        };
    };

    RouteBuilder.prototype.resolveCustomLocationName = function (point) {
        var explicitName = this.customLocationNameInput ? this.customLocationNameInput.value.trim() : '';
        var address = this.customLocationAddressInput ? this.customLocationAddressInput.value.trim() : '';

        if (explicitName) {
            return explicitName;
        }

        if (address) {
            return address;
        }

        if (point && point.address) {
            return String(point.address).trim();
        }

        if (point && this.isFiniteNumber(point.lat) && this.isFiniteNumber(point.lng)) {
            return Number(point.lat).toFixed(5) + ', ' + Number(point.lng).toFixed(5);
        }

        return 'Custom Location';
    };

    RouteBuilder.prototype.searchCustomLocationAddress = function () {
        var self = this;

        this.setCustomLocationStatus('Address se location search ki ja rahi hai...', false);

        this.geocodeCustomLocationInput()
            .then(function (point) {
                self.setCustomLocationDraftPoint(point);
                self.setCustomLocationStatus('Address found. Click Save Location to continue.', false);
            })
            .catch(function (error) {
                self.setCustomLocationStatus((error && error.message) || 'Address search failed.', true);
            });
    };

    RouteBuilder.prototype.previewCustomLocationLatLng = function () {
        var manualPoint = this.getCustomLocationManualPoint();

        if (!manualPoint) {
            this.setCustomLocationStatus('Please enter a valid latitude and longitude.', true);
            return;
        }

        manualPoint.name = manualPoint.name || 'Custom Point';
        manualPoint.address = manualPoint.address || 'Custom Lat/Lng Location';
        this.setCustomLocationDraftPoint(manualPoint);
        this.setCustomLocationStatus('Location previewed on the map. Click Save Location to continue.', false);
    };

    RouteBuilder.prototype.buildCustomLocationQueries = function () {
        var address = this.customLocationAddressInput ? this.customLocationAddressInput.value.trim() : '';
        var name = this.customLocationNameInput ? this.customLocationNameInput.value.trim() : '';
        var locationSuffix = this.locationContext.city
            ? ', ' + this.locationContext.city + (this.locationContext.state ? ', ' + this.locationContext.state : '') + ', India'
            : '';
        var queries = [];
        var seen = {};

        function pushQuery(value) {
            var normalized = String(value || '').trim();
            var key = normalized.toLowerCase();

            if (normalized.length < 2 || seen[key]) {
                return;
            }

            seen[key] = true;
            queries.push(normalized);
        }

        if (name && address) {
            pushQuery(name + ', ' + address);
            pushQuery(address + ', ' + name);
            pushQuery(name + ', ' + address + locationSuffix);
            pushQuery(address + locationSuffix);
        }

        if (address) {
            pushQuery(address);
            pushQuery(address + locationSuffix);
        }

        if (name) {
            pushQuery(name);
            pushQuery(name + locationSuffix);
        }

        return queries;
    };

    RouteBuilder.prototype.fetchNominatimGeocode = function (query, fallbackName, fallbackAddress) {
        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&q=' + encodeURIComponent(query);

        return window.fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Location search failed');
            }

            return response.json();
        }).then(function (items) {
            if (!Array.isArray(items) || !items.length) {
                return null;
            }

            var item = items[0];
            if (!window.isFinite(Number(item.lat)) || !window.isFinite(Number(item.lon))) {
                return null;
            }

            return {
                name: String(item.name || fallbackName || item.display_name || 'Custom Point').trim(),
                address: String(item.display_name || fallbackAddress || fallbackName || 'Custom Point').trim(),
                lat: Number(item.lat),
                lng: Number(item.lon)
            };
        }).catch(function () {
            return null;
        });
    };

    RouteBuilder.prototype.fetchPhotonGeocode = function (query, fallbackName, fallbackAddress) {
        var url = 'https://photon.komoot.io/api/?limit=1&q=' + encodeURIComponent(query);

        return window.fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Photon search failed');
            }

            return response.json();
        }).then(function (payload) {
            var feature = payload && Array.isArray(payload.features) && payload.features.length
                ? payload.features[0]
                : null;

            var coordinates = feature && feature.geometry && Array.isArray(feature.geometry.coordinates)
                ? feature.geometry.coordinates
                : null;

            if (!coordinates || coordinates.length < 2 || !window.isFinite(Number(coordinates[0])) || !window.isFinite(Number(coordinates[1]))) {
                return null;
            }

            var properties = feature.properties || {};
            var resolvedName = String(properties.name || fallbackName || 'Custom Point').trim();
            var resolvedAddress = [
                properties.name,
                properties.street,
                properties.city,
                properties.state,
                properties.country
            ].filter(function (part) {
                return String(part || '').trim() !== '';
            }).join(', ');

            return {
                name: resolvedName || 'Custom Point',
                address: String(resolvedAddress || fallbackAddress || resolvedName || 'Custom Point').trim(),
                lat: Number(coordinates[1]),
                lng: Number(coordinates[0])
            };
        }).catch(function () {
            return null;
        });
    };

    RouteBuilder.prototype.geocodeCustomLocationInput = function () {
        var self = this;
        var address = this.customLocationAddressInput ? this.customLocationAddressInput.value.trim() : '';
        var name = this.customLocationNameInput ? this.customLocationNameInput.value.trim() : '';
        var queries = this.buildCustomLocationQueries();
        var sequence = window.Promise.resolve(null);

        if (!queries.length) {
            return window.Promise.reject(new Error('Please enter an address to search for the location.'));
        }

        queries.forEach(function (query) {
            sequence = sequence.then(function (resolvedPoint) {
                if (resolvedPoint) {
                    return resolvedPoint;
                }

                return self.fetchNominatimGeocode(query, name, address)
                    .then(function (point) {
                        if (point) {
                            return point;
                        }

                        return self.fetchPhotonGeocode(query, name, address);
                    });
            });
        });

        return sequence.then(function (resolvedPoint) {
            if (resolvedPoint) {
                return resolvedPoint;
            }

            throw new Error('Address se exact location nahi mili. Thoda aur specific address ya valid lat/lng do.');
        });
    };

    RouteBuilder.prototype.saveCustomLocation = function () {
        var self = this;
        var customAddress = this.customLocationAddressInput ? this.customLocationAddressInput.value.trim() : '';

        if (!this.config.customLocationStoreUrl) {
            this.setCustomLocationStatus('Custom location save URL configure nahi hai.', true);
            return;
        }

        var manualPoint = this.getCustomLocationManualPoint();
        var hasDraftPoint = this.customLocationDraftPoint && this.isFiniteNumber(this.customLocationDraftPoint.lat) && this.isFiniteNumber(this.customLocationDraftPoint.lng);

        if (!manualPoint && !hasDraftPoint && !customAddress) {
            this.setCustomLocationStatus('Please enter an address or valid latitude and longitude.', true);
            return;
        }

        var pointPromise = manualPoint
            ? window.Promise.resolve(manualPoint)
            : (hasDraftPoint
                ? window.Promise.resolve(this.customLocationDraftPoint)
                : this.geocodeCustomLocationInput().then(function (point) {
                    self.setCustomLocationDraftPoint(point);
                    return point;
                }));

        this.setCustomLocationStatus(
            manualPoint || this.customLocationDraftPoint ? 'Custom location save ho rahi hai...' : 'Address se location search ki ja rahi hai...',
            false
        );

        pointPromise.then(function (resolvedPoint) {
            var customName = self.resolveCustomLocationName(resolvedPoint);
            self.setCustomLocationDraftPoint(resolvedPoint);
            self.setCustomLocationStatus('Custom location save ho rahi hai...', false);

            return window.fetch(self.config.customLocationStoreUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': self.config.csrfToken
            },
            body: JSON.stringify({
                name: customName,
                address: customAddress || resolvedPoint.address || customName,
                lat: Number(resolvedPoint.lat),
                lng: Number(resolvedPoint.lng)
            })
            });
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                if (!response.ok || !payload || !payload.location) {
                    throw new Error(payload && payload.message ? payload.message : 'Custom location save failed');
                }

                return payload;
            });
        }).then(function (payload) {
            if (self.activeMapSelection && self.activeMapSelection.type === 'custom-location') {
                self.activeMapSelection = null;
            }

            var target = self.resolveMapTarget();
            if (!target || target.type === 'custom-location') {
                throw new Error('Custom location add karne ke liye route target nahi mila.');
            }

            self.applyMapPoint(target, payload.location);
            self.activeMapSelection = null;
            self.updateMapSelectionStatus();
            self.hideCustomLocationPanel();
            self.refreshRoutePreview();

            if (typeof window.notify === 'function') {
                window.notify('success', payload.message || 'Custom location added successfully');
            }
        }).catch(function (error) {
            self.setCustomLocationStatus((error && error.message) || 'Custom location save failed.', true);
        });
    };

    RouteBuilder.prototype.updateMapLayerButtons = function () {
        if (!this.mapLayerButtons || !this.mapLayerButtons.length) {
            return;
        }

        var activeKey = this.activeBaseLayerKey;
        this.mapLayerButtons.forEach(function (button) {
            var isActive = button.getAttribute('data-route-map-layer') === activeKey;
            button.classList.toggle('route-map-layer-btn-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    RouteBuilder.prototype.loadInitialData = function (routeJson) {
        var normalized = this.normalizeIncomingRouteJson(routeJson);

        if (normalized.start_point) {
            this.setStaticPoint(this.startBindings, normalized.start_point);
        }

        if (Array.isArray(normalized.pickup_points) && normalized.pickup_points.length > 0) {
            for (var i = 0; i < normalized.pickup_points.length; i += 1) {
                this.addPickupRow(normalized.pickup_points[i]);
            }
        }

        if (normalized.end_point) {
            this.setStaticPoint(this.endBindings, normalized.end_point);
        }
    };

    RouteBuilder.prototype.normalizeIncomingRouteJson = function (routeJson) {
        if (!routeJson || typeof routeJson !== 'object') {
            return { start_point: null, pickup_points: [], end_point: null };
        }

        var normalizedStart = this.normalizePoint(routeJson.start_point, 'start', 1);
        var normalizedEnd = this.normalizePoint(routeJson.end_point, 'end', null);
        var pickupPoints = this.normalizePointList(routeJson.pickup_points || [], 'pickup', 2);

        if (!normalizedStart && !normalizedEnd && pickupPoints.length === 0 && Array.isArray(routeJson.stops)) {
            var legacyStops = this.normalizePointList(routeJson.stops, 'pickup', 1);
            if (legacyStops.length > 0) {
                normalizedStart = this.normalizePoint(legacyStops.shift(), 'start', 1);
                if (legacyStops.length > 0) {
                    normalizedEnd = this.normalizePoint(legacyStops.pop(), 'end', legacyStops.length + 2);
                    pickupPoints = this.normalizePointList(legacyStops, 'pickup', 2);
                }
            }
        }

        return {
            start_point: normalizedStart,
            pickup_points: pickupPoints,
            end_point: normalizedEnd
        };
    };

    RouteBuilder.prototype.normalizePointList = function (points, type, sequenceStart) {
        if (!Array.isArray(points)) {
            return [];
        }

        var normalized = [];
        for (var index = 0; index < points.length; index += 1) {
            var point = this.normalizePoint(points[index], type, sequenceStart + index);
            if (point) {
                normalized.push(point);
            }
        }

        return normalized;
    };

    RouteBuilder.prototype.normalizePoint = function (point, defaultType, sequence) {
        if (!point || typeof point !== 'object') {
            return null;
        }

        var lat = this.isFiniteNumber(point.lat) ? point.lat : point.latitude;
        var lng = this.isFiniteNumber(point.lng) ? point.lng : (this.isFiniteNumber(point.lon) ? point.lon : point.longitude);
        if (!this.isFiniteNumber(lat) || !this.isFiniteNumber(lng)) {
            return null;
        }

        var fallbackLabel = defaultType === 'pickup' ? 'Pickup Point' : (defaultType === 'start' ? 'Start Point' : 'End Point');
        var name = String(point.name || point.title || point.address || point.display_name || fallbackLabel).trim();
        var address = String(point.address || point.display_name || name).trim();
        var pointSequence = this.isFiniteNumber(point.sequence) ? Number(point.sequence) : sequence;

        return {
            name: name || fallbackLabel,
            address: address || name || fallbackLabel,
            lat: Number(lat),
            lng: Number(lng),
            type: String(point.type || defaultType || 'pickup'),
            sequence: pointSequence
        };
    };

    RouteBuilder.prototype.isFiniteNumber = function (value) {
        return value !== null && value !== '' && window.isFinite(Number(value));
    };

    RouteBuilder.prototype.bindStaticPoint = function (binding) {
        var self = this;
        if (!binding || !binding.input) {
            return;
        }

        this.bindAutocomplete(binding.input, binding.results, function () {
            self.clearPointForBinding(binding, false);
            self.refreshRoutePreview();
        }, function (selectedPoint) {
            self.setStaticPoint(binding, selectedPoint);
            self.refreshRoutePreview();
        }, function () {
            return binding.searchAbortController;
        }, function (controller) {
            binding.searchAbortController = controller;
        });

        if (binding.mapButton) {
            binding.mapButton.addEventListener('click', function () {
                self.activateMapSelection({ type: binding.type });
            });
        }

        if (binding.clearButton) {
            binding.clearButton.addEventListener('click', function () {
                self.clearPointForBinding(binding, true);
                self.refreshRoutePreview();
            });
        }
    };

    RouteBuilder.prototype.bindAutocomplete = function (inputEl, resultsEl, onTyping, onSelect, getAbortController, setAbortController) {
        var self = this;
        if (!inputEl || !resultsEl) {
            return;
        }

        var handleSelection = function (selectedPoint) {
            self.saveRecentSearch(selectedPoint);
            onSelect(selectedPoint);
            self.hideResults(resultsEl);
        };

        var searchPlaces = debounce(function () {
            var query = inputEl.value.trim();
            if (query.length === 0) {
                self.renderRecentSearchResults(resultsEl, handleSelection);
                return;
            }

            if (query.length < 3) {
                self.hideResults(resultsEl);
                return;
            }

            var existingController = getAbortController();
            if (existingController) {
                existingController.abort();
            }

            var abortController = new window.AbortController();
            setAbortController(abortController);

            resultsEl.innerHTML = '<button type="button" class="list-group-item list-group-item-action disabled">Searching...</button>';
            resultsEl.classList.remove('d-none');

            self.searchPlaces(query, abortController.signal)
                .then(function (results) {
                    if (abortController.signal.aborted) {
                        return;
                    }

                    self.renderSearchResults(resultsEl, results, handleSelection);
                })
                .catch(function () {
                    if (abortController.signal.aborted) {
                        return;
                    }

                    resultsEl.innerHTML = '<button type="button" class="list-group-item list-group-item-action disabled">No places found.</button>';
                    resultsEl.classList.remove('d-none');
                });
        }, 350);

        inputEl.addEventListener('input', function () {
            onTyping();
            searchPlaces();
        });

        inputEl.addEventListener('focus', function () {
            window.setTimeout(function () {
                if (inputEl.value.trim().length >= 3) {
                    searchPlaces();
                    return;
                }

                self.renderRecentSearchResults(resultsEl, handleSelection);
            }, 0);
        });

        inputEl.addEventListener('click', function () {
            window.setTimeout(function () {
                if (inputEl.value.trim().length >= 3) {
                    searchPlaces();
                    return;
                }

                self.renderRecentSearchResults(resultsEl, handleSelection);
            }, 0);
        });

        inputEl.addEventListener('mousedown', function () {
            if (inputEl.value.trim() !== '') {
                return;
            }

            self.renderRecentSearchResults(resultsEl, handleSelection);
        });

        document.addEventListener('click', function (event) {
            if (!resultsEl.contains(event.target) && event.target !== inputEl) {
                self.hideResults(resultsEl);
            }
        });
    };

    RouteBuilder.prototype.buildLocationScopedQuery = function (query) {
        var parts = [String(query || '').trim()];
        if (this.locationContext.city) parts.push(this.locationContext.city);
        if (this.locationContext.state) parts.push(this.locationContext.state);
        if (this.locationContext.city || this.locationContext.state) parts.push('India');
        return parts.filter(Boolean).join(', ');
    };

    RouteBuilder.prototype.isResultInLocationContext = function (result) {
        if (!this.locationContext.city) return true;

        var haystack = (String(result.name || '') + ' ' + String(result.address || '')).toLowerCase();
        var city = this.locationContext.city.toLowerCase();
        var state = this.locationContext.state.toLowerCase();
        return haystack.indexOf(city) !== -1 && (!state || haystack.indexOf(state) !== -1);
    };

    RouteBuilder.prototype.setLocationContext = function (state, city) {
        this.locationContext = {
            state: String(state || '').trim(),
            city: String(city || '').trim()
        };

        if (!this.map) return;
        if (!this.locationContext.city) {
            this.cityBounds = null;
            this.map.setMaxBounds(null);
            return;
        }

        var self = this;
        var cityQuery = this.buildLocationScopedQuery('');
        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&q=' + encodeURIComponent(cityQuery);

        window.fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        }).then(function (response) {
            if (!response.ok) throw new Error('City lookup failed');
            return response.json();
        }).then(function (items) {
            var cityResult = Array.isArray(items) ? items[0] : null;
            if (!cityResult) return;

            var bounds = Array.isArray(cityResult.boundingbox) && cityResult.boundingbox.length === 4
                ? L.latLngBounds([
                    [Number(cityResult.boundingbox[0]), Number(cityResult.boundingbox[2])],
                    [Number(cityResult.boundingbox[1]), Number(cityResult.boundingbox[3])]
                ])
                : null;

            if (bounds && bounds.isValid()) {
                self.cityBounds = bounds.pad(0.12);
                self.map.setMaxBounds(self.cityBounds);
                self.map.fitBounds(bounds, { padding: [24, 24], maxZoom: 14 });
            } else if (window.isFinite(Number(cityResult.lat)) && window.isFinite(Number(cityResult.lon))) {
                self.map.setView([Number(cityResult.lat), Number(cityResult.lon)], 13);
            }
        }).catch(function () {
            // The city list remains usable even if the public geocoder is temporarily unavailable.
        });
    };

    RouteBuilder.prototype.searchPlaces = function (query, signal) {
        var self = this;
        var scopedQuery = this.buildLocationScopedQuery(query);
        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6&addressdetails=1&q=' + encodeURIComponent(scopedQuery);
        var nominatimRequest = window.fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            signal: signal
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Search request failed');
            }

            return response.json();
        }).then(function (items) {
            if (!Array.isArray(items)) {
                return [];
            }

            return items.map(function (item) {
                return {
                    name: String(item.name || item.display_name || 'Selected Point').trim(),
                    address: String(item.display_name || item.name || 'Selected Point').trim(),
                    lat: Number(item.lat),
                    lng: Number(item.lon),
                    is_custom: false
                };
            }).filter(function (item) {
                return window.isFinite(item.lat) && window.isFinite(item.lng) && self.isResultInLocationContext(item);
            });
        }).catch(function () {
            return [];
        });

        return window.Promise.all([
            this.locationContext.city ? window.Promise.resolve([]) : this.fetchCustomLocationResults(query, signal),
            nominatimRequest
        ]).then(function (resultSets) {
            return self.mergeSearchResults((resultSets[0] || []).concat(resultSets[1] || []));
        });
    };

    RouteBuilder.prototype.fetchCustomLocationResults = function (query, signal) {
        if (!this.config.customLocationSearchUrl) {
            return window.Promise.resolve([]);
        }

        var url = this.config.customLocationSearchUrl + '?q=' + encodeURIComponent(query);

        return window.fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.config.csrfToken
            },
            signal: signal
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Custom location search failed');
            }

            return response.json();
        }).then(function (payload) {
            return payload && Array.isArray(payload.results) ? payload.results : [];
        }).catch(function () {
            return [];
        });
    };

    RouteBuilder.prototype.mergeSearchResults = function (items) {
        if (!Array.isArray(items)) {
            return [];
        }

        var uniqueItems = [];
        var seen = {};

        items.forEach(function (item) {
            if (!item || !window.isFinite(Number(item.lat)) || !window.isFinite(Number(item.lng))) {
                return;
            }

            var key = [
                Number(item.lat).toFixed(5),
                Number(item.lng).toFixed(5),
                String(item.address || item.name || '').trim().toLowerCase()
            ].join('|');

            if (seen[key]) {
                return;
            }

            seen[key] = true;
            uniqueItems.push(item);
        });

        return uniqueItems;
    };

    RouteBuilder.prototype.renderSearchResults = function (resultsEl, items, onSelect) {
        if (!items.length) {
            resultsEl.innerHTML = '<button type="button" class="list-group-item list-group-item-action disabled">No places found.</button>';
            resultsEl.classList.remove('d-none');
            return;
        }

        var html = '';
        for (var i = 0; i < items.length; i += 1) {
            html += '<button type="button" class="list-group-item list-group-item-action route-search-item" data-index="' + i + '">' +
                '<div class="fw-semibold">' + escapeHtml(items[i].name) +
                    (items[i].is_custom ? ' <span class="badge bg-info text-dark">Custom</span>' : '') +
                    (items[i].is_recent ? ' <span class="badge bg-light text-secondary border">Recent</span>' : '') +
                '</div>' +
                '<div class="small text-muted">' + escapeHtml(items[i].address) + '</div>' +
                '</button>';
        }

        resultsEl.innerHTML = html;
        resultsEl.classList.remove('d-none');

        var buttons = resultsEl.querySelectorAll('.route-search-item');
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var index = Number(button.getAttribute('data-index'));
                if (!window.isFinite(index) || !items[index]) {
                    return;
                }

                onSelect(items[index]);
            });
        });
    };

    RouteBuilder.prototype.getRecentSearches = function () {
        try {
            var raw = window.localStorage ? window.localStorage.getItem(this.recentSearchStorageKey) : '';
            var parsed = raw ? JSON.parse(raw) : [];
            if (Array.isArray(parsed) && parsed.length) {
                this.recentSearchesCache = parsed.slice();
                return parsed;
            }
        } catch (error) {
            return this.recentSearchesCache.slice();
        }

        return this.recentSearchesCache.slice();
    };

    RouteBuilder.prototype.saveRecentSearch = function (point) {
        var normalized = this.normalizePoint(point, 'place', null);
        if (!normalized) {
            return;
        }

        var existingItems = this.getRecentSearches();
        var key = [
            Number(normalized.lat).toFixed(5),
            Number(normalized.lng).toFixed(5),
            String(normalized.address || normalized.name || '').trim().toLowerCase()
        ].join('|');

        var filtered = existingItems.filter(function (item) {
            if (!item) {
                return false;
            }

            var itemKey = [
                Number(item.lat || 0).toFixed(5),
                Number(item.lng || 0).toFixed(5),
                String(item.address || item.name || '').trim().toLowerCase()
            ].join('|');

            return itemKey !== key;
        });

        filtered.unshift({
            name: normalized.name,
            address: normalized.address,
            lat: normalized.lat,
            lng: normalized.lng,
            is_recent: true
        });

        filtered = filtered.slice(0, 6);
        this.recentSearchesCache = filtered.slice();

        try {
            if (window.localStorage) {
                window.localStorage.setItem(this.recentSearchStorageKey, JSON.stringify(filtered));
            }
        } catch (error) {
            return;
        }
    };

    RouteBuilder.prototype.renderRecentSearchResults = function (resultsEl, onSelect) {
        var recentItems = this.getRecentSearches();
        if (!recentItems.length) {
            resultsEl.innerHTML = '<button type="button" class="list-group-item list-group-item-action disabled">No recent searches yet.</button>';
            resultsEl.classList.remove('d-none');
            return;
        }

        this.renderSearchResults(resultsEl, recentItems, onSelect);
    };

    RouteBuilder.prototype.hideResults = function (resultsEl) {
        if (!resultsEl) {
            return;
        }

        resultsEl.classList.add('d-none');
        resultsEl.innerHTML = '';
    };

    RouteBuilder.prototype.setStaticPoint = function (binding, point) {
        var normalized = this.normalizePoint(point, binding.type, binding.type === 'start' ? 1 : null);
        binding.point = normalized;
        binding.input.value = normalized ? normalized.address : '';
        this.renderPointMeta(binding.meta, normalized);
        this.updateAddDestinationVisibility();
        this.syncIntroSearchInput();
        this.setPlannerExpanded(this.isPlannerExpanded);
        this.streetViewPreviewUpdater();
    };

    RouteBuilder.prototype.clearPointForBinding = function (binding, clearInput) {
        binding.point = null;
        if (clearInput) {
            binding.input.value = '';
        }

        this.renderPointMeta(binding.meta, null);
        this.updateAddDestinationVisibility();
        this.syncIntroSearchInput();
        this.setPlannerExpanded(this.isPlannerExpanded);
        this.streetViewPreviewUpdater();
    };

    RouteBuilder.prototype.handleAddPickupClick = function () {
        if (!this.endBindings.point) {
            if (typeof window.notify === 'function') {
                window.notify('error', 'Please choose an end point first.');
            }
            return;
        }

        this.addPickupRow(null);
        this.updatePickupLabels();

        if (this.pickupEntries.length) {
            this.activeMapSelection = {
                type: 'pickup',
                id: this.pickupEntries[this.pickupEntries.length - 1].id
            };
            this.updateMapSelectionStatus();
        }
    };

    RouteBuilder.prototype.updateAddDestinationVisibility = function () {
        if (!this.addDestinationRow) {
            return;
        }

        if (this.endBindings.point) {
            this.addDestinationRow.classList.remove('d-none');
        } else {
            this.addDestinationRow.classList.add('d-none');
        }
    };

    RouteBuilder.prototype.renderPointMeta = function (metaEl, point) {
        if (!metaEl) {
            return;
        }

        if (!point) {
            metaEl.textContent = '';
            metaEl.classList.add('d-none');
            return;
        }

        var message = '';
        if (point.type === 'start') {
            message = 'Route starts here';
        }

        metaEl.textContent = message;
        metaEl.classList.toggle('d-none', message === '');
    };

    RouteBuilder.prototype.addPickupRow = function (point) {
        if (!this.pickupsContainer) {
            return;
        }

        this.pickupCounter += 1;
        var pickupId = 'pickup-' + this.pickupCounter;
        var wrapper = document.createElement('div');
        wrapper.className = 'route-direction-row route-direction-row-pickup';
        wrapper.setAttribute('data-pickup-id', pickupId);
        wrapper.innerHTML =
            '<div class="route-direction-marker-col">' +
                '<div class="route-direction-marker"></div>' +
            '</div>' +
            '<div class="route-direction-card route-search-wrap">' +
                '<div class="route-direction-inputbar">' +
                    '<input type="text" class="form-control route-search-input route-direction-input" placeholder="Add destination" autocomplete="off">' +
                    '<div class="route-direction-actions">' +
                        '<button type="button" class="route-direction-btn route-drag-handle" title="Drag to reorder" draggable="true">::</button>' +
                        '<button type="button" class="route-direction-btn route-point-map-btn" title="Pick on map">+</button>' +
                        '<button type="button" class="route-direction-btn route-point-clear-btn" title="Clear">x</button>' +
                        '<button type="button" class="route-direction-btn route-direction-btn-danger route-point-remove-btn" title="Remove">-</button>' +
                    '</div>' +
                '</div>' +
                '<div class="route-point-meta d-none"></div>' +
                '<div class="route-search-results list-group d-none"></div>' +
            '</div>';

        this.pickupsContainer.appendChild(wrapper);

        var entry = {
            id: pickupId,
            point: null,
            wrapper: wrapper,
            input: wrapper.querySelector('.route-search-input'),
            results: wrapper.querySelector('.route-search-results'),
            meta: wrapper.querySelector('.route-point-meta'),
            dragHandle: wrapper.querySelector('.route-drag-handle'),
            removeButton: wrapper.querySelector('.route-point-remove-btn'),
            mapButton: wrapper.querySelector('.route-point-map-btn'),
            clearButton: wrapper.querySelector('.route-point-clear-btn'),
            searchAbortController: null
        };

        this.pickupEntries.push(entry);
        this.bindPickupEntry(entry);
        this.updatePickupLabels();

        if (point) {
            this.setPickupPoint(entry, point);
        }
    };

    RouteBuilder.prototype.bindPickupEntry = function (entry) {
        var self = this;

        this.bindAutocomplete(entry.input, entry.results, function () {
            self.clearPickupPoint(entry, false);
            self.refreshRoutePreview();
        }, function (selectedPoint) {
            self.setPickupPoint(entry, selectedPoint);
            self.refreshRoutePreview();
        }, function () {
            return entry.searchAbortController;
        }, function (controller) {
            entry.searchAbortController = controller;
        });

        entry.removeButton.addEventListener('click', function () {
            self.removePickupEntry(entry.id);
            self.refreshRoutePreview();
        });

        entry.mapButton.addEventListener('click', function () {
            self.activateMapSelection({ type: 'pickup', id: entry.id });
        });

        entry.clearButton.addEventListener('click', function () {
            self.clearPickupPoint(entry, true);
            self.refreshRoutePreview();
        });

        if (entry.dragHandle) {
            entry.dragHandle.addEventListener('dragstart', function (event) {
                self.draggedPickupId = entry.id;
                entry.wrapper.classList.add('route-dragging');
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', entry.id);
                }
            });

            entry.dragHandle.addEventListener('dragend', function () {
                self.draggedPickupId = null;
                self.clearDragState();
            });
        }

        entry.wrapper.addEventListener('dragover', function (event) {
            if (!self.draggedPickupId || self.draggedPickupId === entry.id) {
                return;
            }

            event.preventDefault();
            entry.wrapper.classList.add('route-drop-target');
        });

        entry.wrapper.addEventListener('dragleave', function () {
            entry.wrapper.classList.remove('route-drop-target');
        });

        entry.wrapper.addEventListener('drop', function (event) {
            if (!self.draggedPickupId || self.draggedPickupId === entry.id) {
                return;
            }

            event.preventDefault();
            self.movePickupEntry(self.draggedPickupId, entry.id);
            self.clearDragState();
            self.refreshRoutePreview();
        });
    };

    RouteBuilder.prototype.updatePickupLabels = function () {
        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            var entry = this.pickupEntries[index];
            if (entry.point) {
                entry.point.sequence = index + 2;
            }

            var input = entry.input;
            if (input && !entry.point) {
                input.setAttribute('placeholder', 'Destination ' + (index + 1));
            }
        }
    };

    RouteBuilder.prototype.setPickupPoint = function (entry, point) {
        var normalized = this.normalizePoint(point, 'pickup', this.pickupEntries.indexOf(entry) + 2);
        entry.point = normalized;
        entry.input.value = normalized ? normalized.address : '';
        this.renderPointMeta(entry.meta, normalized);
        this.updatePickupLabels();
        this.setPlannerExpanded(this.isPlannerExpanded);
    };

    RouteBuilder.prototype.clearPickupPoint = function (entry, clearInput) {
        entry.point = null;
        if (clearInput) {
            entry.input.value = '';
        }

        this.renderPointMeta(entry.meta, null);
        this.setPlannerExpanded(this.isPlannerExpanded);
    };

    RouteBuilder.prototype.removePickupEntry = function (entryId) {
        var remainingEntries = [];
        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            var entry = this.pickupEntries[index];
            if (entry.id === entryId) {
                if (entry.wrapper && entry.wrapper.parentNode) {
                    entry.wrapper.parentNode.removeChild(entry.wrapper);
                }

                if (this.activeMapSelection && this.activeMapSelection.type === 'pickup' && this.activeMapSelection.id === entryId) {
                    this.activeMapSelection = null;
                }
            } else {
                remainingEntries.push(entry);
            }
        }

        this.pickupEntries = remainingEntries;
        this.updatePickupLabels();
        this.updateMapSelectionStatus();
        this.setPlannerExpanded(this.isPlannerExpanded);
    };

    RouteBuilder.prototype.movePickupEntry = function (draggedId, targetId) {
        if (draggedId === targetId) {
            return;
        }

        var draggedIndex = -1;
        var targetIndex = -1;
        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            if (this.pickupEntries[index].id === draggedId) {
                draggedIndex = index;
            }
            if (this.pickupEntries[index].id === targetId) {
                targetIndex = index;
            }
        }

        if (draggedIndex === -1 || targetIndex === -1) {
            return;
        }

        var draggedEntry = this.pickupEntries.splice(draggedIndex, 1)[0];
        this.pickupEntries.splice(targetIndex, 0, draggedEntry);

        if (draggedEntry.wrapper && this.pickupsContainer) {
            this.pickupsContainer.insertBefore(draggedEntry.wrapper, this.pickupsContainer.children[targetIndex] || null);
        }

        this.updatePickupLabels();
    };

    RouteBuilder.prototype.clearDragState = function () {
        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            this.pickupEntries[index].wrapper.classList.remove('route-dragging');
            this.pickupEntries[index].wrapper.classList.remove('route-drop-target');
        }
    };

    RouteBuilder.prototype.handleMapClick = function (event) {
        var self = this;
        var target = this.resolveMapClickTarget();
        var lat = event.latlng.lat;
        var lng = event.latlng.lng;

        if (this.cityBounds && !this.cityBounds.contains([lat, lng])) {
            this.showCityOnlyMessage();
            return;
        }

        if (!target) {
            this.getValidatedMapClickPoint(lat, lng)
                .then(function (point) {
                    self.setIntroSelectedPlace(point);
                    self.saveRecentSearch(point);
                    if (!self.hasAnyRoutePoints()) {
                        self.setPlannerExpanded(false);
                    }
                })
                .catch(function () {
                    if (self.locationContext.city) {
                        self.showCityOnlyMessage();
                        return;
                    }
                    var fallbackPoint = {
                        name: 'Selected location',
                        address: 'Selected from map',
                        lat: lat,
                        lng: lng
                    };

                    self.setIntroSelectedPlace(fallbackPoint);
                    self.saveRecentSearch(fallbackPoint);
                    if (!self.hasAnyRoutePoints()) {
                        self.setPlannerExpanded(false);
                    }
                });
            return;
        }

        if (target.type === 'custom-location') {
            this.getValidatedMapClickPoint(lat, lng)
                .then(function (point) {
                    self.setCustomLocationDraftPoint(point);
                    self.activeMapSelection = null;
                    self.updateMapSelectionStatus();
                    self.setCustomLocationStatus('Point selected. Ab Save & Add par click karo.', false);
                })
                .catch(function () {
                    if (self.locationContext.city) {
                        self.showCityOnlyMessage();
                        return;
                    }
                    self.setCustomLocationDraftPoint({
                        name: 'Custom Point',
                        address: 'Selected from map',
                        lat: lat,
                        lng: lng
                    });
                    self.activeMapSelection = null;
                    self.updateMapSelectionStatus();
                    self.setCustomLocationStatus('Point selected. Ab Save & Add par click karo.', false);
                });
            return;
        }

        this.getValidatedMapClickPoint(lat, lng)
            .then(function (point) {
                self.applyMapPoint(target, point);
                self.activeMapSelection = null;
                self.updateMapSelectionStatus();
                self.refreshRoutePreview();
            })
            .catch(function () {
                if (self.locationContext.city) {
                    self.showCityOnlyMessage();
                    return;
                }
                self.applyMapPoint(target, {
                    name: self.getMapFallbackName(target),
                    address: 'Selected from map',
                    lat: lat,
                    lng: lng
                });
                self.activeMapSelection = null;
                self.updateMapSelectionStatus();
                self.refreshRoutePreview();
            });
    };

    RouteBuilder.prototype.resolveMapClickTarget = function () {
        return this.activeMapSelection;
    };

    RouteBuilder.prototype.resolveMapTarget = function () {
        if (this.activeMapSelection) {
            return this.activeMapSelection;
        }

        if (!this.startBindings.point) {
            return { type: 'start' };
        }

        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            if (!this.pickupEntries[index].point) {
                return { type: 'pickup', id: this.pickupEntries[index].id };
            }
        }

        if (!this.endBindings.point) {
            return { type: 'end' };
        }

        if (this.endBindings.point) {
            this.addPickupRow(null);
            this.updatePickupLabels();
            if (this.pickupEntries.length) {
                return { type: 'pickup', id: this.pickupEntries[this.pickupEntries.length - 1].id };
            }
        }

        return null;
    };

    RouteBuilder.prototype.getMapFallbackName = function (target) {
        if (target.type === 'start') {
            return 'Start Point';
        }
        if (target.type === 'end') {
            return 'End Point';
        }
        return 'Pickup Point';
    };

    RouteBuilder.prototype.applyMapPoint = function (target, point) {
        if (target.type === 'start') {
            this.setStaticPoint(this.startBindings, point);
            return;
        }

        if (target.type === 'end') {
            this.setStaticPoint(this.endBindings, point);
            return;
        }

        if (target.type === 'pickup') {
            for (var index = 0; index < this.pickupEntries.length; index += 1) {
                if (this.pickupEntries[index].id === target.id) {
                    this.setPickupPoint(this.pickupEntries[index], point);
                    return;
                }
            }
        }
    };

    RouteBuilder.prototype.reverseGeocode = function (lat, lng) {
        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);

        return window.fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Reverse geocode failed');
            }

            return response.json();
        }).then(function (item) {
            return {
                name: String(item.name || item.display_name || 'Selected Point').trim(),
                address: String(item.display_name || item.name || 'Selected Point').trim(),
                lat: Number(lat),
                lng: Number(lng)
            };
        });
    };

    RouteBuilder.prototype.getValidatedMapClickPoint = function (lat, lng) {
        var self = this;
        return this.reverseGeocode(lat, lng).then(function (point) {
            if (!self.isResultInLocationContext(point)) {
                throw new Error('Selected point is outside the chosen city');
            }
            return point;
        });
    };

    RouteBuilder.prototype.showCityOnlyMessage = function () {
        var city = String(this.locationContext.city || '').trim();
        var message = city
            ? 'Please select a location inside ' + city + ' only.'
            : 'Please select a valid map location.';

        if (typeof window.notify === 'function') {
            window.notify('error', message);
        } else {
            window.alert(message);
        }
    };

    RouteBuilder.prototype.updateMapSelectionStatus = function () {
        if (!this.mapSelectionStatus) {
            return;
        }

        var text = this.isPlannerExpanded
            ? 'Use the left panel to search places or click + and then choose a point from the map.'
            : 'Open route planner to start selecting route points.';
        if (this.activeMapSelection) {
            if (this.activeMapSelection.type === 'start') {
                text = 'Map selection active for Start Point. Click on the map to place it.';
            } else if (this.activeMapSelection.type === 'end') {
                text = 'Map selection active for End Point. Click on the map to place it.';
            } else if (this.activeMapSelection.type === 'custom-location') {
                text = 'Map selection active for Custom Location. Click on the map to choose its point.';
            } else {
                text = 'Map selection active for Pickup Point ' + (this.getPickupEntryIndex(this.activeMapSelection.id) + 1) + '. Click on the map to place it.';
            }
        }

        this.mapSelectionStatus.textContent = text;
    };

    RouteBuilder.prototype.getPickupEntryIndex = function (entryId) {
        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            if (this.pickupEntries[index].id === entryId) {
                return index;
            }
        }

        return 0;
    };

    RouteBuilder.prototype.getOrderedPoints = function () {
        var points = [];
        if (this.startBindings.point) {
            points.push(this.startBindings.point);
        }

        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            if (this.pickupEntries[index].point) {
                this.pickupEntries[index].point.sequence = index + 2;
                points.push(this.pickupEntries[index].point);
            }
        }

        if (this.endBindings.point) {
            this.endBindings.point.sequence = points.length + 1;
            points.push(this.endBindings.point);
        }

        return points;
    };

    RouteBuilder.prototype.refreshRoutePreview = function () {
        var self = this;
        var orderedPoints = this.getOrderedPoints();
        var fallbackGeojson = this.buildFallbackGeojson(orderedPoints);

        this.currentOrderedPoints = orderedPoints.slice();
        this.currentGeojson = fallbackGeojson;
        this.currentRouteOptions = [];
        this.currentRouteLegs = [];
        this.selectedRouteIndex = 0;
        this.renderMarkers(orderedPoints);
        this.updateMarkerPopups();
        this.renderPolylineFromGeojson(fallbackGeojson);
        this.renderRouteOptions([]);
        this.renderLegSummaries();
        this.updateRouteJsonField();
        this.streetViewPreviewUpdater();

        if (orderedPoints.length < 2) {
            return window.Promise.resolve(this.currentGeojson);
        }

        var requestToken = this.routeRequestToken + 1;
        this.routeRequestToken = requestToken;
        this.fitMapToCurrentRoute();

        return this.fetchPreferredRouteGeometry(orderedPoints)
            .then(function (routePayload) {
                if (requestToken !== self.routeRequestToken) {
                    return self.currentGeojson;
                }

                self.currentRouteOptions = Array.isArray(routePayload.routes) ? routePayload.routes : [];
                self.selectedRouteIndex = 0;
                self.currentGeojson = self.currentRouteOptions[0] && self.currentRouteOptions[0].geometry
                    ? self.currentRouteOptions[0].geometry
                    : fallbackGeojson;
                self.currentRouteLegs = self.currentRouteOptions[0] && Array.isArray(self.currentRouteOptions[0].legs)
                    ? self.currentRouteOptions[0].legs
                    : [];
                self.updateMarkerPopups();
                self.renderPolylineFromGeojson(self.currentGeojson);
                self.renderMapLegBadges();
                self.renderRouteOptions(self.currentRouteOptions);
                self.renderLegSummaries();
                self.updateRouteJsonField();
                self.streetViewPreviewUpdater();
                return self.currentGeojson;
            })
            .catch(function () {
                if (requestToken !== self.routeRequestToken) {
                    return self.currentGeojson;
                }

                self.currentGeojson = fallbackGeojson;
                self.currentRouteOptions = [];
                self.currentRouteLegs = [];
                self.selectedRouteIndex = 0;
                self.updateMarkerPopups();
                self.renderPolylineFromGeojson(self.currentGeojson);
                self.renderMapLegBadges();
                self.renderRouteOptions([]);
                self.renderLegSummaries();
                self.updateRouteJsonField();
                self.streetViewPreviewUpdater();
                return self.currentGeojson;
            });
    };

    RouteBuilder.prototype.fetchPreferredRouteGeometry = function (orderedPoints) {
        var self = this;

        if (this.config.routePreviewUrl) {
            return this.fetchGoogleTrafficAwareRoute(orderedPoints).catch(function () {
                if (self.config.googleMapsApiKey) {
                    return self.fetchGoogleBrowserRoute(orderedPoints).catch(function () {
                        return self.fetchRouteGeometry(orderedPoints).then(function (payload) {
                            return self.normalizeFallbackTrafficDurations(payload);
                        });
                    });
                }

                return self.fetchRouteGeometry(orderedPoints).then(function (payload) {
                    return self.normalizeFallbackTrafficDurations(payload);
                });
            });
        }

        if (this.config.googleMapsApiKey) {
            return this.fetchGoogleBrowserRoute(orderedPoints).catch(function () {
                return self.fetchRouteGeometry(orderedPoints).then(function (payload) {
                    return self.normalizeFallbackTrafficDurations(payload);
                });
            });
        }

        return this.fetchRouteGeometry(orderedPoints).then(function (payload) {
            return self.normalizeFallbackTrafficDurations(payload);
        });
    };

    RouteBuilder.prototype.fetchGoogleTrafficAwareRoute = function (orderedPoints) {
        var url = this.config.routePreviewUrl;
        var payload = {
            points: orderedPoints.map(function (point) {
                return {
                    lat: Number(point.lat),
                    lng: Number(point.lng),
                    name: point.name || null,
                    address: point.address || null,
                    type: point.type || null,
                    sequence: point.sequence
                };
            })
        };

        return window.fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.config.csrfToken
            },
            body: JSON.stringify(payload)
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (body) {
                if (!response.ok) {
                    throw new Error(body && body.message ? body.message : 'Google route request failed');
                }

                return body;
            });
        }).then(function (payload) {
            if (!payload || !Array.isArray(payload.routes) || payload.routes.length === 0) {
                throw new Error('Google route geometry missing');
            }

            return payload;
        });
    };

    RouteBuilder.prototype.fetchGoogleBrowserRoute = function (orderedPoints) {
        var self = this;

        return this.ensureGoogleMapsApi().then(function () {
            if (!window.google || !window.google.maps || !window.google.maps.DirectionsService) {
                throw new Error('Google Maps JavaScript API did not load correctly.');
            }

            return new window.Promise(function (resolve, reject) {
                var directionsService = new window.google.maps.DirectionsService();
                var request = {
                    origin: {
                        lat: Number(orderedPoints[0].lat),
                        lng: Number(orderedPoints[0].lng)
                    },
                    destination: {
                        lat: Number(orderedPoints[orderedPoints.length - 1].lat),
                        lng: Number(orderedPoints[orderedPoints.length - 1].lng)
                    },
                    travelMode: window.google.maps.TravelMode.DRIVING,
                    drivingOptions: {
                        departureTime: new Date(),
                        trafficModel: window.google.maps.TrafficModel.BEST_GUESS
                    },
                    provideRouteAlternatives: false
                };

                if (orderedPoints.length > 2) {
                    request.waypoints = orderedPoints.slice(1, -1).map(function (point) {
                        return {
                            location: {
                                lat: Number(point.lat),
                                lng: Number(point.lng)
                            },
                            stopover: false
                        };
                    });
                }

                directionsService.route(request, function (result, status) {
                    if (status !== 'OK' || !result || !Array.isArray(result.routes) || !result.routes.length) {
                        reject(new Error(self.getGoogleDirectionsErrorMessage(status)));
                        return;
                    }

                    resolve(self.normalizeGoogleDirectionsResult(result.routes));
                });
            });
        });
    };

    RouteBuilder.prototype.ensureGoogleMapsApi = function () {
        if (window.google && window.google.maps && window.google.maps.DirectionsService) {
            return window.Promise.resolve(window.google.maps);
        }

        if (RouteBuilder.googleMapsApiPromise) {
            return RouteBuilder.googleMapsApiPromise;
        }

        if (!this.config.googleMapsApiKey) {
            return window.Promise.reject(new Error('Google Maps API key is not configured.'));
        }

        RouteBuilder.googleMapsApiPromise = new window.Promise(function (resolve, reject) {
            var existingScript = document.getElementById('routeBuilderGoogleMapsApi');
            if (existingScript) {
                existingScript.addEventListener('load', function () {
                    resolve(window.google.maps);
                }, { once: true });
                existingScript.addEventListener('error', function () {
                    reject(new Error('Failed to load Google Maps JavaScript API.'));
                }, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.id = 'routeBuilderGoogleMapsApi';
            script.async = true;
            script.defer = true;
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(this.config.googleMapsApiKey) + '&libraries=places&v=weekly';
            script.onload = function () {
                if (window.google && window.google.maps) {
                    resolve(window.google.maps);
                    return;
                }

                reject(new Error('Google Maps JavaScript API loaded without maps namespace.'));
            };
            script.onerror = function () {
                reject(new Error('Failed to load Google Maps JavaScript API.'));
            };
            document.head.appendChild(script);
        }.bind(this));

        return RouteBuilder.googleMapsApiPromise;
    };

    RouteBuilder.prototype.normalizeGoogleDirectionsResult = function (routes) {
        return {
            routes: routes.slice(0, 1).map(function (route, routeIndex) {
                var legs = Array.isArray(route.legs) ? route.legs : [];
                var totalDistance = 0;
                var totalDuration = 0;

                var normalizedLegs = legs.map(function (leg, legIndex) {
                    var legDistance = Number(leg.distance && leg.distance.value ? leg.distance.value : 0);
                    var legDuration = Number(
                        leg.duration_in_traffic && leg.duration_in_traffic.value
                            ? leg.duration_in_traffic.value
                            : (leg.duration && leg.duration.value ? leg.duration.value : 0)
                    );

                    totalDistance += legDistance;
                    totalDuration += legDuration;

                    return {
                        index: legIndex,
                        distance: legDistance,
                        duration: legDuration,
                        summary: null
                    };
                });

                return {
                    index: routeIndex,
                    geometry: {
                        type: 'LineString',
                        coordinates: Array.isArray(route.overview_path)
                            ? route.overview_path.map(function (latLng) {
                                return [Number(latLng.lng()), Number(latLng.lat())];
                            })
                            : []
                    },
                    distance: totalDistance,
                    duration: totalDuration,
                    summary: String(route.summary || '').trim(),
                    legs: normalizedLegs
                };
            }).filter(function (route) {
                return Array.isArray(route.geometry.coordinates) && route.geometry.coordinates.length >= 2;
            })
        };
    };

    RouteBuilder.prototype.normalizeFallbackTrafficDurations = function (payload) {
        if (!payload || !Array.isArray(payload.routes)) {
            return payload;
        }

        return {
            routes: payload.routes.map(function (route) {
                var routeDistance = Number(route && route.distance ? route.distance : 0);
                var routeDuration = Number(route && route.duration ? route.duration : 0);
                var adjustedLegs = Array.isArray(route && route.legs)
                    ? route.legs.map(function (leg) {
                        var legDistance = Number(leg && leg.distance ? leg.distance : 0);
                        var legDuration = Number(leg && leg.duration ? leg.duration : 0);

                        return {
                            index: leg.index,
                            distance: legDistance,
                            duration: this.estimateUrbanTrafficDuration(legDistance, legDuration),
                            summary: leg.summary || null
                        };
                    }, this)
                    : [];

                return {
                    index: route.index,
                    geometry: route.geometry,
                    distance: routeDistance,
                    duration: this.estimateUrbanTrafficDuration(routeDistance, routeDuration),
                    summary: route.summary || null,
                    isFallbackEstimate: true,
                    legs: adjustedLegs
                };
            }, this)
        };
    };

    RouteBuilder.prototype.estimateUrbanTrafficDuration = function (distanceMeters, baseDurationSeconds) {
        var distance = Number(distanceMeters || 0);
        var baseDuration = Number(baseDurationSeconds || 0);
        var conservativeCitySpeedMetersPerSecond = 8.33; // ~30 km/h inside city traffic
        var conservativeDuration = distance > 0
            ? Math.round(distance / conservativeCitySpeedMetersPerSecond)
            : 0;

        if (baseDuration > 0) {
            return Math.max(Math.round(baseDuration), conservativeDuration);
        }

        if (distance <= 0) {
            return 0;
        }

        // Distance-only fallback when the router does not return any usable duration.
        return conservativeDuration;
    };

    RouteBuilder.prototype.getGoogleDirectionsErrorMessage = function (status) {
        var normalizedStatus = String(status || '').toUpperCase();

        if (normalizedStatus === 'REQUEST_DENIED') {
            return 'Google Directions request was denied. Enable Directions API (Legacy) and check browser referrer restrictions for this key.';
        }
        if (normalizedStatus === 'OVER_QUERY_LIMIT') {
            return 'Google Directions quota limit reached for this API key.';
        }
        if (normalizedStatus === 'ZERO_RESULTS') {
            return 'Google could not find a driving route for these points.';
        }
        if (normalizedStatus === 'INVALID_REQUEST') {
            return 'Google Directions request was invalid.';
        }

        return normalizedStatus ? ('Google Directions error: ' + normalizedStatus) : 'Google Directions request failed.';
    };

    RouteBuilder.prototype.buildFallbackGeojson = function (orderedPoints) {
        if (!Array.isArray(orderedPoints) || orderedPoints.length < 2) {
            return null;
        }

        var coordinates = [];
        for (var index = 0; index < orderedPoints.length; index += 1) {
            coordinates.push([Number(orderedPoints[index].lng), Number(orderedPoints[index].lat)]);
        }

        return {
            type: 'LineString',
            coordinates: coordinates
        };
    };

    RouteBuilder.prototype.fetchRouteGeometry = function (orderedPoints) {
        var coordinates = [];
        for (var index = 0; index < orderedPoints.length; index += 1) {
            coordinates.push(Number(orderedPoints[index].lng) + ',' + Number(orderedPoints[index].lat));
        }

        var url = 'https://router.project-osrm.org/route/v1/driving/' + coordinates.join(';') + '?alternatives=false&overview=full&geometries=geojson&steps=false&annotations=false';

        return window.fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Route request failed');
            }

            return response.json();
        }).then(function (payload) {
            if (!payload || !Array.isArray(payload.routes) || payload.routes.length === 0) {
                throw new Error('Route geometry missing');
            }

            return {
                routes: payload.routes.map(function (route, routeIndex) {
                    return {
                        index: routeIndex,
                        geometry: {
                            type: 'LineString',
                            coordinates: Array.isArray(route.geometry && route.geometry.coordinates)
                                ? route.geometry.coordinates
                                : []
                        },
                        distance: Number(route.distance || 0),
                        duration: Number(route.duration || 0),
                        summary: String(route.legs && route.legs[0] && route.legs[0].summary ? route.legs[0].summary : ''),
                        legs: Array.isArray(route.legs) ? route.legs.map(function (leg, legIndex) {
                            return {
                                index: legIndex,
                                distance: Number(leg.distance || 0),
                                duration: Number(leg.duration || 0),
                                summary: String(leg.summary || '')
                            };
                        }) : []
                    };
                }).filter(function (route) {
                    return Array.isArray(route.geometry.coordinates) && route.geometry.coordinates.length >= 2;
                })
            };
        });
    };

    RouteBuilder.prototype.formatDistance = function (meters) {
        var distance = Number(meters || 0);
        if (distance < 1000) {
            return Math.round(distance) + ' m';
        }

        return (distance / 1000).toFixed(distance >= 10000 ? 0 : 1) + ' km';
    };

    RouteBuilder.prototype.formatDuration = function (seconds) {
        var totalMinutes = Math.round(Number(seconds || 0) / 60);
        if (totalMinutes < 60) {
            return totalMinutes + ' min';
        }

        var hours = Math.floor(totalMinutes / 60);
        var minutes = totalMinutes % 60;
        return minutes ? (hours + ' hr ' + minutes + ' min') : (hours + ' hr');
    };

    RouteBuilder.prototype.getEtaUnavailableReason = function () {
        if (!this.config.googleMapsApiKey) {
            return 'Google Maps API key is not configured';
        }

        if (!this.config.routePreviewUrl) {
            return 'Google route preview is not configured';
        }

        return 'Live ETA could not be calculated';
    };

    RouteBuilder.prototype.renderRouteOptions = function (routes) {
        if (!this.routeOptionsContainer) {
            return;
        }

        if (!Array.isArray(routes) || !routes.length) {
            this.routeOptionsContainer.innerHTML = '<div class="route-option-empty">Add start and end points to see distance and time.</div>';
            return;
        }

        var self = this;
        var html = '';
        routes.slice(0, 1).forEach(function (route, index) {
            var activeClass = index === self.selectedRouteIndex ? ' route-option-card-active' : '';
            var routeName = route.summary ? ('via ' + route.summary) : 'Best Route';
            var hasReliableDuration = self.isFiniteNumber(route.duration) && Number(route.duration) > 0;
            var routeHint = hasReliableDuration ? '' : self.getEtaUnavailableReason();
            var vehicleIconMarkup = buildVehicleIconSvgMarkup('route-option-distance-icon');
            var routeDurationText = hasReliableDuration ? self.formatDuration(route.duration) : 'ETA unavailable';

            html += '<button type="button" class="route-option-card' + activeClass + '" data-route-option-index="' + index + '">' +
                '<div class="route-option-top">' +
                    '<div class="route-option-name">' + escapeHtml(routeName) + '</div>' +
                    '<div class="route-option-duration">' + escapeHtml(routeDurationText) + '</div>' +
                '</div>' +
                '<div class="route-option-distance">' + vehicleIconMarkup + '<span>' + escapeHtml(self.formatDistance(route.distance)) + '</span></div>' +
                (routeHint ? '<div class="route-option-subtext">' + escapeHtml(routeHint) + '</div>' : '') +
            '</button>';
        });

        this.routeOptionsContainer.innerHTML = html;
        this.routeOptionsContainer.querySelectorAll('[data-route-option-index]').forEach(function (button) {
            button.addEventListener('click', function () {
                var optionIndex = Number(button.getAttribute('data-route-option-index'));
                if (!window.isFinite(optionIndex) || !self.currentRouteOptions[optionIndex]) {
                    return;
                }

                self.selectedRouteIndex = optionIndex;
                self.currentGeojson = self.currentRouteOptions[optionIndex].geometry;
                self.currentRouteLegs = Array.isArray(self.currentRouteOptions[optionIndex].legs)
                    ? self.currentRouteOptions[optionIndex].legs
                    : [];
                self.updateMarkerPopups();
                self.renderPolylineFromGeojson(self.currentGeojson);
                self.renderMapLegBadges();
                self.renderRouteOptions(self.currentRouteOptions);
                self.renderLegSummaries();
                self.updateRouteJsonField();
            });
        });
    };

    RouteBuilder.prototype.renderLegSummaries = function () {
        this.renderPointMeta(this.startBindings.meta, this.startBindings.point);

        var legIndex = 0;
        var previousLabel = 'Start Point';
        var destinationNumber = 1;

        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            var entry = this.pickupEntries[index];
            if (!entry.meta) {
                continue;
            }

            if (!entry.point) {
                entry.meta.textContent = '';
                entry.meta.classList.add('d-none');
                continue;
            }

            var leg = this.currentRouteLegs[legIndex] || null;
            var parts = ['From ' + previousLabel];
            if (leg && this.isFiniteNumber(leg.distance)) {
                parts.push(this.formatDistance(leg.distance));
            }
            if (leg && this.isFiniteNumber(leg.duration)) {
                parts.push(this.formatDuration(leg.duration));
            }

            entry.meta.textContent = parts.join(' | ');
            entry.meta.classList.remove('d-none');

            previousLabel = 'Destination ' + destinationNumber;
            destinationNumber += 1;
            legIndex += 1;
        }

        if (!this.endBindings.meta) {
            return;
        }

        if (!this.endBindings.point) {
            this.endBindings.meta.textContent = '';
            this.endBindings.meta.classList.add('d-none');
            return;
        }

        var endLeg = this.currentRouteLegs[legIndex] || null;
        var endParts = ['From ' + previousLabel];
        if (endLeg && this.isFiniteNumber(endLeg.distance)) {
            endParts.push(this.formatDistance(endLeg.distance));
        }
        if (endLeg && this.isFiniteNumber(endLeg.duration)) {
            endParts.push(this.formatDuration(endLeg.duration));
        }

        this.endBindings.meta.textContent = endParts.join(' | ');
        this.endBindings.meta.classList.remove('d-none');
    };

    RouteBuilder.prototype.renderMapLegBadges = function () {
        for (var index = 0; index < this.markers.length; index += 1) {
            if (this.markers[index] && typeof this.markers[index].unbindTooltip === 'function') {
                this.markers[index].unbindTooltip();
            }
        }

        if (!Array.isArray(this.currentRouteLegs) || !this.currentRouteLegs.length) {
            return;
        }

        for (var markerIndex = 1; markerIndex < this.markers.length; markerIndex += 1) {
            var marker = this.markers[markerIndex];
            var leg = this.currentRouteLegs[markerIndex - 1] || null;
            if (!marker || !leg) {
                continue;
            }

            var badgeHtml = this.buildMapLegBadgeHtml(leg);
            if (!badgeHtml) {
                continue;
            }

            var isEndMarker = markerIndex === this.markers.length - 1;
            var tooltipOffset = isEndMarker
                ? [8, -2]
                : [10, markerIndex % 2 === 0 ? 4 : -4];

            marker.bindTooltip(badgeHtml, {
                permanent: true,
                direction: 'right',
                className: 'route-map-leg-tooltip',
                offset: tooltipOffset,
                opacity: 1
            });
        }
    };

    RouteBuilder.prototype.buildMapLegBadgeHtml = function (leg) {
        if (!leg || !this.isFiniteNumber(leg.distance) || !this.isFiniteNumber(leg.duration)) {
            return '';
        }

        return '<div class="route-map-leg-card">' +
            '<div class="route-map-leg-top">' +
                buildVehicleIconSvgMarkup('route-map-leg-icon') +
                '<div class="route-map-leg-text">' +
                    '<span class="route-map-leg-duration">' + escapeHtml(this.formatDuration(leg.duration)) + '</span>' +
                    '<span class="route-map-leg-distance">' + escapeHtml(this.formatDistance(leg.distance)) + '</span>' +
                '</div>' +
            '</div>' +
        '</div>';
    };

    RouteBuilder.prototype.getPointTypeDetails = function (point, index, totalPoints) {
        var type = point && point.type ? String(point.type).toLowerCase() : '';
        if (index === 0 || type === 'start') {
            return { label: 'Start Point', heroClass: 'route-marker-popup-hero-start' };
        }

        if (index === totalPoints - 1 || type === 'end') {
            return { label: 'Stop Point', heroClass: 'route-marker-popup-hero-end' };
        }

        return { label: 'Pickup Point ' + index, heroClass: 'route-marker-popup-hero-pickup' };
    };

    RouteBuilder.prototype.normalizeTileCoordinate = function (value, maxTiles) {
        var normalized = Number(value || 0) % maxTiles;
        if (normalized < 0) {
            normalized += maxTiles;
        }

        return normalized;
    };

    RouteBuilder.prototype.clampTileCoordinate = function (value, min, max) {
        return Math.max(min, Math.min(max, Number(value || 0)));
    };

    RouteBuilder.prototype.getPointHeroCacheKey = function (point) {
        if (!point) {
            return '';
        }

        return 'geo:' + Number(point.lat || 0).toFixed(3) + '|' + Number(point.lng || 0).toFixed(3);
    };

    RouteBuilder.prototype.getPointHeroMedia = function (point) {
        var cacheKey = this.getPointHeroCacheKey(point);
        if (cacheKey !== '' && Object.prototype.hasOwnProperty.call(this.popupHeroImageCache, cacheKey)) {
            return this.popupHeroImageCache[cacheKey];
        }

        return {
            pending: true,
            badge: 'Nearby Photo',
            caption: String(point && point.name ? point.name : 'Nearby place').trim(),
            cacheKey: cacheKey
        };
    };

    RouteBuilder.prototype.buildGooglePhotoAttributionHtml = function (authorAttributions) {
        if (!Array.isArray(authorAttributions) || !authorAttributions.length) {
            return '';
        }

        var parts = [];
        for (var index = 0; index < authorAttributions.length; index += 1) {
            var attribution = authorAttributions[index] || {};
            var displayName = String(attribution.displayName || '').trim();
            var uri = String(attribution.uri || '').trim();

            if (displayName === '') {
                continue;
            }

            if (uri !== '') {
                parts.push('<a href="' + escapeHtml(uri) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(displayName) + '</a>');
            } else {
                parts.push('<span>' + escapeHtml(displayName) + '</span>');
            }
        }

        return parts.join(', ');
    };

    RouteBuilder.prototype.buildLocationSearchQueries = function (point) {
        if (!point || typeof point !== 'object') {
            return [];
        }

        var queries = [];
        var seen = {};
        var pushQuery = function (value) {
            var query = String(value || '').replace(/\s+/g, ' ').trim();
            var key = query.toLowerCase();
            if (query === '' || seen[key]) {
                return;
            }

            seen[key] = true;
            queries.push(query);
        };

        pushQuery(point.name);
        pushQuery(point.address);

        if (point.name && point.address && String(point.address).toLowerCase().indexOf(String(point.name).toLowerCase()) === -1) {
            pushQuery(point.name + ', ' + point.address);
        }

        return queries.slice(0, 5);
    };

    RouteBuilder.prototype.fetchGoogleNearbyPhotoWithPlaceClass = function (Place, rankPreference, point, radius) {
        var self = this;
        if (!Place || typeof Place.searchNearby !== 'function') {
            return window.Promise.resolve(null);
        }

        return Place.searchNearby({
            fields: ['displayName', 'photos'],
            locationRestriction: {
                center: {
                    lat: Number(point.lat),
                    lng: Number(point.lng)
                },
                radius: radius
            },
            maxResultCount: 8,
            rankPreference: rankPreference
        }).then(function (result) {
            var places = result && Array.isArray(result.places) ? result.places : [];
            for (var placeIndex = 0; placeIndex < places.length; placeIndex += 1) {
                var place = places[placeIndex];
                var photos = Array.isArray(place && place.photos) ? place.photos : [];
                if (!photos.length || typeof photos[0].getURI !== 'function') {
                    continue;
                }

                return {
                    url: photos[0].getURI({ maxWidth: 640, maxHeight: 360 }),
                    badge: 'Google Place Photo',
                    caption: String(place.displayName || point.name || 'Nearby place').trim(),
                    attributionHtml: self.buildGooglePhotoAttributionHtml(photos[0].authorAttributions || [])
                };
            }

            return null;
        }).catch(function () {
            return null;
        });
    };

    RouteBuilder.prototype.fetchGoogleNearbyPhotoWithPlacesService = function (service, point, radius) {
        return new window.Promise(function (resolve) {
            service.nearbySearch({
                location: {
                    lat: Number(point.lat),
                    lng: Number(point.lng)
                },
                radius: radius
            }, function (results, status) {
                var okStatus = window.google.maps.places.PlacesServiceStatus
                    ? window.google.maps.places.PlacesServiceStatus.OK
                    : 'OK';

                if (status !== okStatus || !Array.isArray(results)) {
                    resolve(null);
                    return;
                }

                for (var resultIndex = 0; resultIndex < results.length; resultIndex += 1) {
                    var result = results[resultIndex];
                    var resultPhotos = Array.isArray(result && result.photos) ? result.photos : [];
                    if (!resultPhotos.length || typeof resultPhotos[0].getUrl !== 'function') {
                        continue;
                    }

                    resolve({
                        url: resultPhotos[0].getUrl({ maxWidth: 640, maxHeight: 360 }),
                        badge: 'Google Place Photo',
                        caption: String(result.name || point.name || 'Nearby place').trim(),
                        attributionHtml: Array.isArray(resultPhotos[0].html_attributions)
                            ? resultPhotos[0].html_attributions.join(', ')
                            : ''
                    });
                    return;
                }

                resolve(null);
            });
        });
    };

    RouteBuilder.prototype.fetchGoogleTextPhotoWithPlacesService = function (service, point, query) {
        return new window.Promise(function (resolve) {
            service.textSearch({
                query: query,
                location: {
                    lat: Number(point.lat),
                    lng: Number(point.lng)
                },
                radius: 50000
            }, function (results, status) {
                var okStatus = window.google.maps.places.PlacesServiceStatus
                    ? window.google.maps.places.PlacesServiceStatus.OK
                    : 'OK';

                if (status !== okStatus || !Array.isArray(results)) {
                    resolve(null);
                    return;
                }

                for (var resultIndex = 0; resultIndex < results.length; resultIndex += 1) {
                    var result = results[resultIndex];
                    var resultPhotos = Array.isArray(result && result.photos) ? result.photos : [];
                    if (!resultPhotos.length || typeof resultPhotos[0].getUrl !== 'function') {
                        continue;
                    }

                    resolve({
                        url: resultPhotos[0].getUrl({ maxWidth: 640, maxHeight: 360 }),
                        badge: 'Google Place Photo',
                        caption: String(result.name || query || point.name || 'Nearby place').trim(),
                        attributionHtml: Array.isArray(resultPhotos[0].html_attributions)
                            ? resultPhotos[0].html_attributions.join(', ')
                            : ''
                    });
                    return;
                }

                resolve(null);
            });
        });
    };

    RouteBuilder.prototype.fetchGooglePlaceHeroImage = function (point) {
        var self = this;
        if (!point || !this.isFiniteNumber(point.lat) || !this.isFiniteNumber(point.lng) || !this.config.googleMapsApiKey) {
            return window.Promise.resolve(null);
        }

        return this.ensureGoogleMapsApi().then(function () {
            var radiusOptions = [500, 2000, 5000, 15000];
            var queryOptions = self.buildLocationSearchQueries(point);

            var tryPlaceClass = function (Place, rankPreference) {
                var sequence = window.Promise.resolve(null);

                radiusOptions.forEach(function (radius) {
                    sequence = sequence.then(function (heroMedia) {
                        return heroMedia || self.fetchGoogleNearbyPhotoWithPlaceClass(Place, rankPreference, point, radius);
                    });
                });

                return sequence;
            };

            var tryPlacesService = function (service) {
                var sequence = window.Promise.resolve(null);

                radiusOptions.forEach(function (radius) {
                    sequence = sequence.then(function (heroMedia) {
                        return heroMedia || self.fetchGoogleNearbyPhotoWithPlacesService(service, point, radius);
                    });
                });

                queryOptions.forEach(function (query) {
                    sequence = sequence.then(function (heroMedia) {
                        return heroMedia || self.fetchGoogleTextPhotoWithPlacesService(service, point, query);
                    });
                });

                return sequence;
            };

            if (!window.google || !window.google.maps) {
                return null;
            }

            var placesService = window.google.maps.places && window.google.maps.places.PlacesService
                ? new window.google.maps.places.PlacesService(document.createElement('div'))
                : null;

            if (typeof window.google.maps.importLibrary === 'function') {
                return window.google.maps.importLibrary('places').then(function (placesLibrary) {
                    var Place = placesLibrary && placesLibrary.Place ? placesLibrary.Place : null;
                    var SearchNearbyRankPreference = placesLibrary && placesLibrary.SearchNearbyRankPreference
                        ? placesLibrary.SearchNearbyRankPreference
                        : null;
                    var popularityRank = SearchNearbyRankPreference && SearchNearbyRankPreference.POPULARITY
                        ? SearchNearbyRankPreference.POPULARITY
                        : undefined;

                    return tryPlaceClass(Place, popularityRank).then(function (heroMedia) {
                        return heroMedia || (placesService ? tryPlacesService(placesService) : null);
                    });
                }).catch(function () {
                    return placesService ? tryPlacesService(placesService) : null;
                });
            }

            return placesService ? tryPlacesService(placesService) : null;
        }).catch(function () {
            return null;
        });
    };

    RouteBuilder.prototype.fetchWikipediaPageImageByPageId = function (pageId, fallbackTitle, badge) {
        var pageUrl = 'https://en.wikipedia.org/w/api.php?action=query&pageids=' +
            encodeURIComponent(String(pageId)) +
            '&prop=pageimages|info&piprop=original|thumbnail&pithumbsize=640&inprop=url&format=json&origin=*';

        return window.fetch(pageUrl, {
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (pageResponse) {
            if (!pageResponse.ok) {
                return null;
            }

            return pageResponse.json().catch(function () {
                return null;
            });
        }).then(function (pagePayload) {
            var pages = pagePayload && pagePayload.query && pagePayload.query.pages
                ? pagePayload.query.pages
                : null;
            var pageData = pages && pages[pageId] ? pages[pageId] : null;
            var imageUrl = String(
                (pageData && pageData.original && pageData.original.source) ||
                (pageData && pageData.thumbnail && pageData.thumbnail.source) ||
                ''
            ).trim();

            if (imageUrl === '') {
                return null;
            }

            return {
                url: imageUrl,
                badge: badge || 'Nearby Photo',
                caption: String((pageData && pageData.title) || fallbackTitle || 'Nearby place').trim()
            };
        }).catch(function () {
            return null;
        });
    };

    RouteBuilder.prototype.fetchWikipediaGeoHeroImage = function (point, radiusMeters) {
        var self = this;
        var lat = Number(point.lat);
        var lng = Number(point.lng);
        var geoSearchUrl = 'https://en.wikipedia.org/w/api.php?action=query&list=geosearch&gscoord=' +
            encodeURIComponent(lat + '|' + lng) +
            '&gsradius=' + encodeURIComponent(String(radiusMeters)) +
            '&gslimit=10&format=json&origin=*';

        return window.fetch(geoSearchUrl, {
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (response) {
            if (!response.ok) {
                return null;
            }

            return response.json().catch(function () {
                return null;
            });
        }).then(function (payload) {
            var results = payload && payload.query && Array.isArray(payload.query.geosearch)
                ? payload.query.geosearch
                : [];
            var sequence = window.Promise.resolve(null);

            results.slice(0, 5).forEach(function (result) {
                sequence = sequence.then(function (heroMedia) {
                    return heroMedia || self.fetchWikipediaPageImageByPageId(result.pageid, result.title, 'Nearby Photo');
                });
            });

            return sequence;
        }).catch(function () {
            return null;
        });
    };

    RouteBuilder.prototype.fetchWikipediaSearchHeroImage = function (point) {
        var self = this;
        var queries = this.buildLocationSearchQueries(point);
        var sequence = window.Promise.resolve(null);

        queries.forEach(function (query) {
            sequence = sequence.then(function (heroMedia) {
                if (heroMedia) {
                    return heroMedia;
                }

                var searchUrl = 'https://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' +
                    encodeURIComponent(query) +
                    '&srlimit=5&format=json&origin=*';

                return window.fetch(searchUrl, {
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then(function (response) {
                    if (!response.ok) {
                        return null;
                    }

                    return response.json().catch(function () {
                        return null;
                    });
                }).then(function (payload) {
                    var results = payload && payload.query && Array.isArray(payload.query.search)
                        ? payload.query.search
                        : [];
                    var innerSequence = window.Promise.resolve(null);

                    results.slice(0, 5).forEach(function (result) {
                        innerSequence = innerSequence.then(function (innerHeroMedia) {
                            return innerHeroMedia || self.fetchWikipediaPageImageByPageId(result.pageid, result.title, 'Place Photo');
                        });
                    });

                    return innerSequence;
                }).catch(function () {
                    return null;
                });
            });
        });

        return sequence;
    };

    RouteBuilder.prototype.fetchNearbyPopularPlaceHeroImage = function (point) {
        var self = this;
        if (!point || !this.isFiniteNumber(point.lat) || !this.isFiniteNumber(point.lng)) {
            return window.Promise.resolve(null);
        }

        var radiusOptions = [10000, 50000];
        var sequence = window.Promise.resolve(null);

        radiusOptions.forEach(function (radius) {
            sequence = sequence.then(function (heroMedia) {
                return heroMedia || self.fetchWikipediaGeoHeroImage(point, radius);
            });
        });

        return sequence.then(function (heroMedia) {
            return heroMedia || self.fetchWikipediaSearchHeroImage(point);
        });
    };

    RouteBuilder.prototype.ensurePointHeroMedia = function (marker, point, markerIndex, totalPoints) {
        var self = this;
        var cacheKey = this.getPointHeroCacheKey(point);
        if (cacheKey === '') {
            return;
        }

        if (Object.prototype.hasOwnProperty.call(this.popupHeroImageCache, cacheKey)) {
            if (marker && marker.isPopupOpen()) {
                marker.setPopupContent(this.buildMarkerPopupHtml(point, markerIndex, totalPoints));
            }
            return;
        }

        if (!this.popupHeroImageRequests[cacheKey]) {
            this.popupHeroImageRequests[cacheKey] = this.fetchGooglePlaceHeroImage(point)
                .then(function (heroMedia) {
                    return heroMedia || self.fetchNearbyPopularPlaceHeroImage(point);
                })
                .then(function (heroMedia) {
                    self.popupHeroImageCache[cacheKey] = heroMedia;
                    delete self.popupHeroImageRequests[cacheKey];
                    return heroMedia;
                }, function () {
                    self.popupHeroImageCache[cacheKey] = null;
                    delete self.popupHeroImageRequests[cacheKey];
                    return null;
                });
        }

        this.popupHeroImageRequests[cacheKey].then(function () {
            if (marker && marker.isPopupOpen()) {
                marker.setPopupContent(self.buildMarkerPopupHtml(point, markerIndex, totalPoints));
            }
        });
    };

    RouteBuilder.prototype.getPopupHeroMediaHtml = function (point, pointType) {
        var heroMedia = this.getPointHeroMedia(point);
        if (heroMedia && heroMedia.url) {
            return '<div class="route-marker-popup-hero-media route-marker-popup-hero-media-photo">' +
                '<img src="' + escapeHtml(heroMedia.url) + '" alt="' + escapeHtml(heroMedia.caption || point.name || 'Location image') + '" loading="lazy">' +
                '<div class="route-marker-popup-hero-media-caption">' +
                    '<span class="route-marker-popup-hero-media-badge">' + escapeHtml(heroMedia.badge || 'Nearby Photo') + '</span>' +
                    '<strong>' + escapeHtml(heroMedia.caption || point.name || 'Location image') + '</strong>' +
                    (heroMedia.attributionHtml ? '<span class="route-marker-popup-hero-media-attribution">' + heroMedia.attributionHtml + '</span>' : '') +
                '</div>' +
            '</div>';
        }

        if (heroMedia && heroMedia.pending) {
            return '<div class="route-marker-popup-hero-media route-marker-popup-hero-media-placeholder">' +
                '<div class="route-marker-popup-hero-media-caption">' +
                    '<span class="route-marker-popup-hero-media-badge">' + escapeHtml(heroMedia.badge || 'Nearby Photo') + '</span>' +
                    '<strong>' + escapeHtml(heroMedia.caption || point.name || 'Nearby place') + '</strong>' +
                '</div>' +
            '</div>';
        }

        if (!point || !this.isFiniteNumber(point.lat) || !this.isFiniteNumber(point.lng)) {
            return '';
        }

        var zoom = 14;
        var lat = Number(point.lat);
        var lng = Number(point.lng);
        var latRad = lat * Math.PI / 180;
        var tileCount = Math.pow(2, zoom);
        var worldX = ((lng + 180) / 360) * tileCount;
        var worldY = (1 - Math.log(Math.tan(latRad) + (1 / Math.cos(latRad))) / Math.PI) / 2 * tileCount;

        if (!window.isFinite(worldX) || !window.isFinite(worldY)) {
            return '';
        }

        var baseTileX = Math.floor(worldX);
        var baseTileY = Math.floor(worldY);
        var fractionalX = worldX - baseTileX;
        var fractionalY = worldY - baseTileY;
        var stageLeft = 'calc(50% - ' + (256 + (fractionalX * 256)).toFixed(2) + 'px)';
        var stageTop = 'calc(50% - ' + (256 + (fractionalY * 256)).toFixed(2) + 'px)';
        var pinClass = 'route-marker-popup-hero-pin-end';

        if (pointType === 'start') {
            pinClass = 'route-marker-popup-hero-pin-start';
        } else if (pointType !== 'end') {
            pinClass = 'route-marker-popup-hero-pin-pickup';
        }

        var tilesHtml = '';
        for (var row = -1; row <= 1; row += 1) {
            for (var col = -1; col <= 1; col += 1) {
                var tileX = this.normalizeTileCoordinate(baseTileX + col, tileCount);
                var tileY = this.clampTileCoordinate(baseTileY + row, 0, tileCount - 1);
                var left = (col + 1) * 256;
                var top = (row + 1) * 256;
                var tileUrl = 'https://tile.openstreetmap.org/' + zoom + '/' + tileX + '/' + tileY + '.png';

                tilesHtml += '<span class="route-marker-popup-hero-tile" style="left:' + left + 'px;top:' + top + 'px;background-image:url(\'' + escapeHtml(tileUrl) + '\');"></span>';
            }
        }

        return '<div class="route-marker-popup-hero-media">' +
            '<div class="route-marker-popup-hero-stage" style="left:' + stageLeft + ';top:' + stageTop + ';">' +
                tilesHtml +
            '</div>' +
            '<span class="route-marker-popup-hero-pin ' + pinClass + '"></span>' +
            '<div class="route-marker-popup-hero-overlay"></div>' +
        '</div>';
    };

    RouteBuilder.prototype.buildMarkerPopupHtml = function (point, markerIndex, totalPoints) {
        var typeDetails = this.getPointTypeDetails(point, markerIndex, totalPoints);
        var latText = this.isFiniteNumber(point && point.lat) ? Number(point.lat).toFixed(5) : '--';
        var lngText = this.isFiniteNumber(point && point.lng) ? Number(point.lng).toFixed(5) : '--';
        var leg = markerIndex > 0 ? (this.currentRouteLegs[markerIndex - 1] || null) : null;
        var pointType = point && point.type ? String(point.type).toLowerCase() : '';
        var heroMediaHtml = this.getPopupHeroMediaHtml(point, pointType);
        var routeValue = markerIndex === 0
            ? 'Route starts here'
            : (leg && this.isFiniteNumber(leg.duration) ? this.formatDuration(leg.duration) : 'Waiting for route');
        var routeMeta = markerIndex === 0
            ? 'Choose the next stop to calculate distance and time.'
            : (leg && this.isFiniteNumber(leg.distance) ? this.formatDistance(leg.distance) + ' from previous point' : 'Distance details will appear after route calculation.');

        return '<div class="route-marker-popup-card">' +
            '<div class="route-marker-popup-hero ' + typeDetails.heroClass + '">' +
                heroMediaHtml +
                '<div class="route-marker-popup-hero-inner">' +
                    '<span class="route-marker-popup-chip">' +
                        '<span class="route-marker-popup-chip-icon"></span>' +
                        escapeHtml(typeDetails.label) +
                    '</span>' +
                '</div>' +
            '</div>' +
            '<div class="route-marker-popup-body">' +
                '<h4 class="route-marker-popup-name">' + escapeHtml(point && point.name ? point.name : 'Selected Point') + '</h4>' +
                '<div class="route-marker-popup-subtitle">Route location details</div>' +
                '<div class="route-marker-popup-address">' + escapeHtml(point && point.address ? point.address : 'Address unavailable') + '</div>' +
                '<div class="route-marker-popup-stats">' +
                    '<div class="route-marker-popup-stat">' +
                        '<div class="route-marker-popup-stat-label">Latitude</div>' +
                        '<div class="route-marker-popup-stat-value">' + escapeHtml(latText) + '</div>' +
                    '</div>' +
                    '<div class="route-marker-popup-stat">' +
                        '<div class="route-marker-popup-stat-label">Longitude</div>' +
                        '<div class="route-marker-popup-stat-value">' + escapeHtml(lngText) + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="route-marker-popup-route">' +
                    '<div class="route-marker-popup-route-label">Travel Info</div>' +
                    '<div class="route-marker-popup-route-value">' +
                        buildVehicleIconSvgMarkup('route-marker-popup-route-icon') +
                        '<span>' + escapeHtml(routeValue) + '</span>' +
                    '</div>' +
                    '<div class="route-marker-popup-route-meta">' + escapeHtml(routeMeta) + '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    };

    RouteBuilder.prototype.updateMarkerPopups = function () {
        var orderedPoints = Array.isArray(this.currentOrderedPoints) ? this.currentOrderedPoints : [];
        for (var index = 0; index < this.markers.length; index += 1) {
            if (!this.markers[index] || !orderedPoints[index]) {
                continue;
            }

            this.markers[index].bindPopup(
                this.buildMarkerPopupHtml(orderedPoints[index], index, orderedPoints.length),
                { className: 'route-marker-popup', maxWidth: 320 }
            );

            if (this.markers[index].isPopupOpen()) {
                this.ensurePointHeroMedia(this.markers[index], orderedPoints[index], index, orderedPoints.length);
            }
        }
    };

    RouteBuilder.prototype.renderMarkers = function (orderedPoints) {
        while (this.markers.length > 0) {
            this.map.removeLayer(this.markers.pop());
        }

        this.currentOrderedPoints = Array.isArray(orderedPoints) ? orderedPoints.slice() : [];

        for (var index = 0; index < orderedPoints.length; index += 1) {
            var point = orderedPoints[index];
            var markerLabel = 'P' + index;
            var markerClass = 'route-marker-pickup';

            if (index === 0) {
                markerLabel = 'S';
                markerClass = 'route-marker-start';
            } else if (index === orderedPoints.length - 1) {
                markerLabel = 'E';
                markerClass = 'route-marker-end';
            }

            var icon = L.divIcon({
                className: 'route-marker-shell',
                html: '<div class="route-marker-badge ' + markerClass + '">' + escapeHtml(markerLabel) + '</div>',
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            var popupHtml = this.buildMarkerPopupHtml(point, index, orderedPoints.length);
            var marker = L.marker([point.lat, point.lng], { icon: icon, title: point.name })
                .addTo(this.map)
                .bindPopup(popupHtml, { className: 'route-marker-popup', maxWidth: 320 });
            var self = this;

            (function (boundMarker, boundPoint, boundIndex, boundTotal) {
                boundMarker.on('popupopen', function () {
                    self.ensurePointHeroMedia(boundMarker, boundPoint, boundIndex, boundTotal);
                });
            }(marker, point, index, orderedPoints.length));

            this.markers.push(marker);
        }

        this.renderMapLegBadges();
    };

    RouteBuilder.prototype.renderPolylineFromGeojson = function (geojson) {
        if (!this.polyline) {
            return;
        }

        if (!geojson || !Array.isArray(geojson.coordinates)) {
            this.polyline.setLatLngs([]);
            return;
        }

        var latLngs = [];
        for (var index = 0; index < geojson.coordinates.length; index += 1) {
            var coordinate = geojson.coordinates[index];
            if (Array.isArray(coordinate) && coordinate.length >= 2) {
                latLngs.push([Number(coordinate[1]), Number(coordinate[0])]);
            }
        }

        this.polyline.setLatLngs(latLngs);
    };

    RouteBuilder.prototype.fitMapToCurrentRoute = function () {
        var points = [];
        for (var index = 0; index < this.markers.length; index += 1) {
            points.push(this.markers[index].getLatLng());
        }

        if (!points.length) {
            return;
        }

        this.map.fitBounds(L.latLngBounds(points).pad(0.25), {
            maxZoom: 15
        });
    };

    RouteBuilder.prototype.recenterMap = function () {
        if (!this.map) {
            return;
        }

        if (this.markers.length > 0) {
            this.fitMapToCurrentRoute();
            return;
        }

        this.map.setView(this.defaultCenter, this.defaultZoom);
    };

    RouteBuilder.prototype.updateRouteJsonField = function () {
        var pickupPoints = [];
        for (var index = 0; index < this.pickupEntries.length; index += 1) {
            if (this.pickupEntries[index].point) {
                pickupPoints.push(this.pickupEntries[index].point);
            }
        }

        var orderedPoints = [];
        if (this.startBindings.point) {
            orderedPoints.push(this.startBindings.point);
        }

        Array.prototype.push.apply(orderedPoints, pickupPoints);

        if (this.endBindings.point) {
            orderedPoints.push(this.endBindings.point);
        }

        var selectedRouteOption = this.currentRouteOptions[this.selectedRouteIndex] || null;

        this.hiddenRouteJsonInput.value = JSON.stringify({
            start_point: this.startBindings.point,
            pickup_points: pickupPoints,
            end_point: this.endBindings.point,
            geojson: this.currentGeojson,
            route_summary: selectedRouteOption ? {
                distance_meters: selectedRouteOption.distance,
                distance_text: this.formatDistance(selectedRouteOption.distance),
                duration_seconds: this.isFiniteNumber(selectedRouteOption.duration) ? selectedRouteOption.duration : null,
                duration_text: this.isFiniteNumber(selectedRouteOption.duration) ? this.formatDuration(selectedRouteOption.duration) : null,
                summary: selectedRouteOption.summary || null,
                selected_route_index: this.selectedRouteIndex
            } : null,
            route_alternatives: this.currentRouteOptions.slice(0, 1).map(function (route, index) {
                return {
                    index: index,
                    distance_meters: route.distance,
                    distance_text: this.formatDistance(route.distance),
                    duration_seconds: this.isFiniteNumber(route.duration) ? route.duration : null,
                    duration_text: this.isFiniteNumber(route.duration) ? this.formatDuration(route.duration) : null,
                    summary: route.summary || null
                };
            }, this),
            route_legs: selectedRouteOption && Array.isArray(selectedRouteOption.legs)
                ? selectedRouteOption.legs.map(function (leg, index) {
                    return {
                        index: index,
                        from_sequence: index + 1,
                        to_sequence: index + 2,
                        distance_meters: leg.distance,
                        distance_text: this.formatDistance(leg.distance),
                        duration_seconds: this.isFiniteNumber(leg.duration) ? leg.duration : null,
                        duration_text: this.isFiniteNumber(leg.duration) ? this.formatDuration(leg.duration) : null,
                        summary: leg.summary || null
                    };
                }, this)
                : [],
            stops: orderedPoints
        });
    };

    RouteBuilder.prototype.clearAllPoints = function () {
        this.clearPointForBinding(this.startBindings, true);
        this.clearPointForBinding(this.endBindings, true);
        this.clearIntroSelectedPlace(true, false);

        while (this.pickupEntries.length > 0) {
            var entry = this.pickupEntries.pop();
            if (entry.wrapper && entry.wrapper.parentNode) {
                entry.wrapper.parentNode.removeChild(entry.wrapper);
            }
        }

        this.activeMapSelection = null;
        this.currentGeojson = null;
        this.currentRouteOptions = [];
        this.currentRouteLegs = [];
        this.selectedRouteIndex = 0;
        this.routeRequestToken += 1;
        this.clearCustomLocationDraft();
        this.renderMarkers([]);
        this.renderPolylineFromGeojson(null);
        this.renderRouteOptions([]);
        this.renderLegSummaries();
        this.updateAddDestinationVisibility();
        this.setPlannerExpanded(false);
        this.updateRouteJsonField();
        this.clearStreetViewPreview();
        this.hideStreetViewModal();
    };

    RouteBuilder.prototype.validateForm = function () {
        var formData = new window.FormData(this.form);
        var valid = true;
        var errors = this.form.querySelectorAll('.error-message');
        var schoolField = document.getElementById('school_id');
        var nameField = document.getElementById('name');
        var busField = document.getElementById('bus_id');
        var driverField = document.getElementById('driver_id');

        errors.forEach(function (errorEl) {
            errorEl.textContent = '';
        });

        if (schoolField && !String(formData.get('school_id') || '').trim()) {
            var schoolError = getFieldErrorElement(schoolField);
            if (schoolError) {
                schoolError.textContent = 'School required';
            }
            valid = false;
        }

        if (!formData.get('name')) {
            var nameError = getFieldErrorElement(nameField);
            if (nameError) {
                nameError.textContent = 'Route name required';
            }
            valid = false;
        }
        if (!formData.get('bus_id')) {
            var busError = getFieldErrorElement(busField);
            if (busError) {
                busError.textContent = 'Vehicle required';
            }
            valid = false;
        }
        if (!formData.get('driver_id')) {
            var driverError = getFieldErrorElement(driverField);
            if (driverError) {
                driverError.textContent = 'Driver required';
            }
            valid = false;
        }
        if (!this.startBindings.point) {
            if (typeof window.notify === 'function') {
                window.notify('error', 'Please choose a start point.');
            }
            valid = false;
        }
        if (!this.endBindings.point) {
            if (typeof window.notify === 'function') {
                window.notify('error', 'Please choose an end point.');
            }
            valid = false;
        }

        return valid;
    };

    RouteBuilder.prototype.submitForm = function () {
        var self = this;
        if (!this.validateForm()) {
            return;
        }

        this.refreshRoutePreview().then(function () {
            var formData = new window.FormData(self.form);

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: self.config.loadingText || 'Saving...',
                    didOpen: function () {
                        window.Swal.showLoading();
                    }
                });
            }

            return window.fetch(self.config.submitUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': self.config.csrfToken
                }
            });
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (window.Swal && typeof window.Swal.close === 'function') {
                window.Swal.close();
            }

            if (payload && payload.success) {
                if (typeof window.notify === 'function') {
                    window.notify('success', payload.message || self.config.successText || 'Route saved successfully');
                }

                window.setTimeout(function () {
                    window.location.href = self.config.indexUrl;
                }, 1200);
                return;
            }

            if (typeof window.notify === 'function') {
                window.notify('error', (payload && payload.message) || 'Route request failed');
            }
        }).catch(function (error) {
            if (window.Swal && typeof window.Swal.close === 'function') {
                window.Swal.close();
            }

            if (typeof window.notify === 'function') {
                window.notify('error', (error && error.message) || 'Route request failed');
            }
        });
    };

    window.initRouteBuilder = function (config) {
        var builder = new RouteBuilder(config);
        builder.init();
        return builder;
    };
})(window, document, window.L);
