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

    function RouteBuilder(config) {
        this.config = config || {};
        this.form = document.getElementById(config.formId);
        this.mapElement = document.getElementById(config.mapId);
        this.hiddenRouteJsonInput = document.getElementById(config.routeJsonInputId);
        this.submitButton = document.getElementById(config.submitButtonId);
        this.clearAllButton = document.getElementById(config.clearAllButtonId);
        this.fitRouteButton = document.getElementById(config.fitRouteButtonId);
        this.recenterButton = document.getElementById(config.recenterButtonId);
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
        this.customLocationDraftPoint = null;
        this.customLocationDraftMarker = null;
        this.defaultCenter = [23.0225, 72.5714];
        this.defaultZoom = 12;
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
        this.bindStaticPoint(this.startBindings);
        this.bindStaticPoint(this.endBindings);
        this.bindMapLayerControls();

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

        this.submitButton.addEventListener('click', this.submitForm.bind(this));

        this.loadInitialData(this.config.initialRouteJson || null);
        this.updateAddDestinationVisibility();
        this.renderRouteOptions([]);
        this.updateMapSelectionStatus();
        this.refreshRoutePreview();
    };

    RouteBuilder.prototype.initMap = function () {
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
                self.activeMapSelection = { type: 'custom-location' };
                self.setCustomLocationStatus('Map par click karke custom location point choose karo.', false);
                self.updateMapSelectionStatus();
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
            pushQuery(name + ', ' + address + ', Ahmedabad');
            pushQuery(address + ', Ahmedabad');
        }

        if (address) {
            pushQuery(address);
            pushQuery(address + ', Gujarat');
        }

        if (name) {
            pushQuery(name);
            pushQuery(name + ', Ahmedabad');
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
                self.activeMapSelection = { type: binding.type };
                self.updateMapSelectionStatus();
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

        var searchPlaces = debounce(function () {
            var query = inputEl.value.trim();
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

                    self.renderSearchResults(resultsEl, results, function (selectedPoint) {
                        onSelect(selectedPoint);
                        self.hideResults(resultsEl);
                    });
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
            if (inputEl.value.trim().length >= 3) {
                searchPlaces();
            }
        });

        document.addEventListener('click', function (event) {
            if (!resultsEl.contains(event.target) && event.target !== inputEl) {
                self.hideResults(resultsEl);
            }
        });
    };

    RouteBuilder.prototype.searchPlaces = function (query, signal) {
        var self = this;
        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6&addressdetails=1&q=' + encodeURIComponent(query);
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
                return window.isFinite(item.lat) && window.isFinite(item.lng);
            });
        }).catch(function () {
            return [];
        });

        return window.Promise.all([
            this.fetchCustomLocationResults(query, signal),
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
                '<div class="fw-semibold">' + escapeHtml(items[i].name) + (items[i].is_custom ? ' <span class="badge bg-info text-dark">Custom</span>' : '') + '</div>' +
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
    };

    RouteBuilder.prototype.clearPointForBinding = function (binding, clearInput) {
        binding.point = null;
        if (clearInput) {
            binding.input.value = '';
        }

        this.renderPointMeta(binding.meta, null);
        this.updateAddDestinationVisibility();
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
            self.activeMapSelection = { type: 'pickup', id: entry.id };
            self.updateMapSelectionStatus();
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
    };

    RouteBuilder.prototype.clearPickupPoint = function (entry, clearInput) {
        entry.point = null;
        if (clearInput) {
            entry.input.value = '';
        }

        this.renderPointMeta(entry.meta, null);
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
        var target = this.resolveMapTarget();

        if (!target) {
            if (typeof window.notify === 'function') {
                window.notify('error', 'Choose Start, Pickup, or End point before clicking on the map.');
            }
            return;
        }

        var lat = event.latlng.lat;
        var lng = event.latlng.lng;

        if (target.type === 'custom-location') {
            this.reverseGeocode(lat, lng)
                .then(function (point) {
                    self.setCustomLocationDraftPoint(point);
                    self.activeMapSelection = null;
                    self.updateMapSelectionStatus();
                    self.setCustomLocationStatus('Point selected. Ab Save & Add par click karo.', false);
                })
                .catch(function () {
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

        this.reverseGeocode(lat, lng)
            .then(function (point) {
                self.applyMapPoint(target, point);
                self.activeMapSelection = null;
                self.updateMapSelectionStatus();
                self.refreshRoutePreview();
            })
            .catch(function () {
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

    RouteBuilder.prototype.updateMapSelectionStatus = function () {
        if (!this.mapSelectionStatus) {
            return;
        }

        var text = 'Use the left panel to search places or click + and choose a point from the map.';
        if (this.activeMapSelection) {
            if (this.activeMapSelection.type === 'start') {
                text = 'Map selection active for Start Point.';
            } else if (this.activeMapSelection.type === 'end') {
                text = 'Map selection active for End Point.';
            } else if (this.activeMapSelection.type === 'custom-location') {
                text = 'Map selection active for Custom Location. Click on the map to choose its point.';
            } else {
                text = 'Map selection active for Pickup Point ' + (this.getPickupEntryIndex(this.activeMapSelection.id) + 1) + '.';
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
                return self.currentGeojson;
            });
    };

    RouteBuilder.prototype.fetchPreferredRouteGeometry = function (orderedPoints) {
        var self = this;

        if (this.config.googleMapsApiKey) {
            return this.fetchGoogleBrowserRoute(orderedPoints).catch(function () {
                if (self.config.routePreviewUrl) {
                    return self.fetchGoogleTrafficAwareRoute(orderedPoints).catch(function () {
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

        if (!this.config.routePreviewUrl) {
            return this.fetchRouteGeometry(orderedPoints).then(function (payload) {
                return self.normalizeFallbackTrafficDurations(payload);
            });
        }

        return this.fetchGoogleTrafficAwareRoute(orderedPoints).catch(function () {
            return self.fetchRouteGeometry(orderedPoints).then(function (payload) {
                return self.normalizeFallbackTrafficDurations(payload);
            });
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
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(this.config.googleMapsApiKey) + '&v=weekly';
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
                    legs: adjustedLegs
                };
            }, this)
        };
    };

    RouteBuilder.prototype.estimateUrbanTrafficDuration = function (distanceMeters, baseDurationSeconds) {
        var distance = Number(distanceMeters || 0);
        var baseDuration = Number(baseDurationSeconds || 0);

        if (distance <= 0) {
            return baseDuration;
        }

        var avgUrbanSpeedMetersPerSecond = 7;
        var urbanDuration = Math.round(distance / avgUrbanSpeedMetersPerSecond);

        if (baseDuration <= 0) {
            return urbanDuration;
        }

        return Math.max(baseDuration, urbanDuration);
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
            var routeHint = 'Calculated route';
            var vehicleIconMarkup = buildVehicleIconSvgMarkup('route-option-distance-icon');

            html += '<button type="button" class="route-option-card' + activeClass + '" data-route-option-index="' + index + '">' +
                '<div class="route-option-top">' +
                    '<div class="route-option-name">' + escapeHtml(routeName) + '</div>' +
                    '<div class="route-option-duration">' + escapeHtml(self.formatDuration(route.duration)) + '</div>' +
                '</div>' +
                '<div class="route-option-distance">' + vehicleIconMarkup + '<span>' + escapeHtml(self.formatDistance(route.distance)) + '</span></div>' +
                '<div class="route-option-subtext">' + escapeHtml(routeHint) + '</div>' +
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

    RouteBuilder.prototype.getPopupHeroMediaHtml = function (point, pointType) {
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

            this.markers.push(
                L.marker([point.lat, point.lng], { icon: icon, title: point.name })
                    .addTo(this.map)
                    .bindPopup(popupHtml, { className: 'route-marker-popup', maxWidth: 320 })
            );
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
                duration_seconds: selectedRouteOption.duration,
                duration_text: this.formatDuration(selectedRouteOption.duration),
                summary: selectedRouteOption.summary || null,
                selected_route_index: this.selectedRouteIndex
            } : null,
            route_alternatives: this.currentRouteOptions.slice(0, 1).map(function (route, index) {
                return {
                    index: index,
                    distance_meters: route.distance,
                    distance_text: this.formatDistance(route.distance),
                    duration_seconds: route.duration,
                    duration_text: this.formatDuration(route.duration),
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
                        duration_seconds: leg.duration,
                        duration_text: this.formatDuration(leg.duration),
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
        this.updateMapSelectionStatus();
        this.updateRouteJsonField();
    };

    RouteBuilder.prototype.validateForm = function () {
        var formData = new window.FormData(this.form);
        var valid = true;
        var errors = this.form.querySelectorAll('.error-message');

        errors.forEach(function (errorEl) {
            errorEl.textContent = '';
        });

        if (!formData.get('name')) {
            document.getElementById('name').nextElementSibling.textContent = 'Route name required';
            valid = false;
        }
        if (!formData.get('bus_id')) {
            document.getElementById('bus_id').nextElementSibling.textContent = 'Vehicle required';
            valid = false;
        }
        if (!formData.get('driver_id')) {
            document.getElementById('driver_id').nextElementSibling.textContent = 'Driver required';
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
