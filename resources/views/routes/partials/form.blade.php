<style>
    .route-builder-layout {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 1rem;
        align-items: stretch;
    }

    select.route-native-select {
        display: block !important;
        visibility: visible !important;
    }

    .route-builder-shell-collapsed {
        grid-template-columns: minmax(0, 1fr);
    }

    .route-builder-shell-collapsed .route-directions-sidebar {
        display: none;
    }

    .route-directions-sidebar {
        background: #fff;
        border: 1px solid #dbe7f1;
        border-radius: 18px;
        overflow: hidden;
        min-height: 620px;
        max-height: 620px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .route-directions-header {
        padding: 0.8rem 1rem 0.45rem;
        border-bottom: 1px solid #edf2f7;
        background: #ffffff;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .route-directions-header-copy {
        min-width: 0;
    }

    .route-directions-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #0f4c5c;
    }

    .route-directions-subtitle {
        margin-top: 0.2rem;
        font-size: 0.82rem;
        color: #6b7f90;
    }

    .route-sidebar-toggle-btn {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 12px;
        background: #f8fafc;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: inset 0 0 0 1px #d9e4ec;
        transition: background-color 0.18s ease, transform 0.18s ease;
    }

    .route-sidebar-toggle-btn:hover {
        background: #eef6fb;
        transform: translateY(-1px);
    }

    .route-sidebar-toggle-btn svg {
        width: 18px;
        height: 18px;
        display: block;
    }

    .route-directions-body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 0.75rem 0.85rem 1rem;
        background: #ffffff;
    }

    .route-direction-row {
        display: grid;
        grid-template-columns: 22px minmax(0, 1fr);
        gap: 0.7rem;
        align-items: start;
        position: relative;
    }

    .route-direction-row + .route-direction-row {
        margin-top: 0.45rem;
    }

    .route-direction-row-end {
        margin-top: 0.65rem;
    }

    .route-direction-row-add {
        margin-top: 0.85rem;
    }

    .route-direction-marker-col {
        position: relative;
        display: flex;
        justify-content: center;
        min-height: 52px;
    }

    .route-direction-marker-col::after {
        content: '';
        position: absolute;
        top: 18px;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        border-left: 2px dotted #b8c8d6;
    }

    .route-direction-row.route-direction-row-end .route-direction-marker-col::after,
    .route-direction-row.route-direction-row-add .route-direction-marker-col::after {
        display: none;
    }

    .route-direction-marker {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        margin-top: 8px;
        z-index: 1;
        background: #1971c2;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #b3cde0;
    }

    .route-direction-row-start .route-direction-marker {
        background: #ffffff;
        border: 2px solid #111827;
        box-shadow: none;
    }

    .route-direction-row-end .route-direction-marker {
        background: #ffffff;
        border: 2px solid #ef4444;
        box-shadow: none;
    }

    .route-direction-row-add .route-direction-marker {
        width: 18px;
        height: 18px;
        margin-top: 5px;
        border: 2px solid #374151;
        background: #fff;
        box-shadow: none;
        position: relative;
    }

    .route-direction-row-add .route-direction-marker::before,
    .route-direction-row-add .route-direction-marker::after {
        content: '';
        position: absolute;
        background: #374151;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    .route-direction-row-add .route-direction-marker::before {
        width: 10px;
        height: 2px;
    }

    .route-direction-row-add .route-direction-marker::after {
        width: 2px;
        height: 10px;
    }

    .route-direction-card {
        border: 1px solid #d8e1e8;
        border-radius: 10px;
        background: #fff;
        padding: 0.2rem 0.3rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .route-direction-card:focus-within {
        border-color: #0ea5b7;
        box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.12);
    }

    .route-direction-inputbar {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.35rem;
        align-items: center;
    }

    .route-direction-input {
        border: 0;
        box-shadow: none;
        height: 36px;
        padding: 0 0.45rem;
        font-size: 0.95rem;
        background: transparent;
    }

    .route-direction-input:focus {
        box-shadow: none;
    }

    .route-point-meta {
        margin: 0.15rem 0.45rem 0.35rem;
        font-size: 0.77rem;
        color: #5b7083;
    }

    .route-direction-actions {
        display: flex;
        gap: 0.25rem;
        align-items: center;
        flex-shrink: 0;
    }

    .route-direction-btn {
        border: 0;
        background: transparent;
        color: #0b7285;
        min-width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .route-direction-btn:hover {
        background: #eef9fb;
        color: #075985;
    }

    .route-direction-btn.route-direction-btn-danger {
        color: #e11d48;
    }

    .route-direction-btn.route-direction-btn-danger:hover {
        background: #fff1f2;
    }

    .route-direction-btn.route-drag-handle {
        cursor: grab;
        color: #475569;
        font-size: 1rem;
    }

    .route-direction-btn.route-drag-handle:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .route-direction-row.route-dragging {
        opacity: 0.45;
    }

    .route-direction-row.route-drop-target .route-direction-card {
        border-color: #0ea5b7;
        box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.12);
    }

    .route-search-wrap {
        position: relative;
    }

    .route-search-results {
        position: absolute;
        top: calc(100% + 0.25rem);
        left: 0;
        right: 0;
        z-index: 1000;
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid #dbe7f1;
        border-radius: 12px;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    }

    .route-direction-add-btn {
        width: 100%;
        border: 0;
        background: transparent;
        color: #334155;
        text-align: left;
        padding: 0.35rem 0 0.15rem;
        font-size: 0.95rem;
    }

    .route-direction-add-btn:hover {
        color: #0f766e;
    }

    #pickupPointsContainer {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    #pickupPointsContainer:not(:empty) {
        margin-top: 0.55rem;
    }

    .route-options-panel {
        margin-top: 0.9rem;
        border-top: 1px solid #edf2f7;
        padding-top: 0.8rem;
    }

    .route-options-title {
        margin: 0 0 0.45rem;
        font-size: 0.92rem;
        font-weight: 700;
        color: #0f4c5c;
    }

    .route-options-add-btn {
        border: 1px dashed #93c5fd;
        background: #f8fbff;
        color: #075985;
        border-radius: 12px;
        min-height: 38px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        font-size: 0.84rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 0.65rem;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
    }

    .route-options-add-btn:hover {
        background: #f0f9ff;
        color: #0f766e;
        border-color: #5eead4;
    }

    .route-options-add-btn-icon {
        width: 22px;
        height: 22px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        border: 1px solid rgba(14, 165, 183, 0.18);
        font-size: 0.95rem;
        line-height: 1;
    }

    .route-options-add-btn-text {
        display: inline-block;
    }

    .route-custom-location-panel {
        margin-bottom: 0.7rem;
        border: 1px solid #dbe7f1;
        border-radius: 12px;
        background: #f8fbfd;
        padding: 0.75rem;
    }

    .route-custom-location-panel.d-none {
        display: none !important;
    }

    .route-custom-location-title {
        margin: 0 0 0.6rem;
        font-size: 0.84rem;
        font-weight: 700;
        color: #0f172a;
    }

    .route-custom-location-field + .route-custom-location-field {
        margin-top: 0.55rem;
    }

    .route-custom-location-field-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.55rem;
        align-items: center;
    }

    .route-custom-location-label {
        display: block;
        margin-bottom: 0.2rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #475569;
    }

    .route-custom-location-input {
        width: 100%;
        border: 1px solid #d6e2ea;
        border-radius: 8px;
        min-height: 36px;
        padding: 0.45rem 0.6rem;
        font-size: 0.84rem;
        background: #ffffff;
        color: #0f172a;
    }

    .route-custom-location-input:focus {
        outline: none;
        border-color: #0ea5b7;
        box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.12);
    }

    .route-custom-location-coords {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
        gap: 0.55rem;
        align-items: center;
        margin-top: 0.55rem;
    }

    .route-custom-location-coord {
        min-width: 0;
    }

    .route-custom-location-coord-label {
        display: block;
        margin-bottom: 0.2rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #475569;
    }

    .route-custom-location-coord-input {
        margin-top: 0;
    }

    .route-custom-location-actions {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-wrap: wrap;
        margin-top: 0.65rem;
    }

    .route-custom-location-save-btn {
        min-width: 110px;
    }

    .route-custom-location-map-popup .leaflet-popup-content-wrapper {
        border-radius: 14px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
    }

    .route-custom-location-map-popup .leaflet-popup-content {
        margin: 0;
        min-width: 220px;
    }

    .route-custom-location-popup {
        padding: 0.85rem 0.95rem;
    }

    .route-custom-location-popup-title {
        font-size: 0.96rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
    }

    .route-custom-location-popup-address {
        margin-top: 0.35rem;
        font-size: 0.82rem;
        color: #475569;
        line-height: 1.5;
    }

    .route-custom-location-status {
        margin-top: 0.45rem;
        font-size: 0.72rem;
        color: #0f766e;
        line-height: 1.35;
    }

    .route-options-list {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        margin-bottom: 0.9rem;
    }

    .route-option-card {
        border: 1px solid #dbe7f1;
        border-radius: 12px;
        background: #fff;
        padding: 0.7rem 0.8rem;
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .route-option-card:hover {
        border-color: #94d2db;
        background: #fbfeff;
    }

    .route-option-card.route-option-card-active {
        border-color: #0b7285;
        box-shadow: 0 0 0 3px rgba(11, 114, 133, 0.12);
        background: #f4fbfc;
    }

    .route-option-top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .route-option-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }

    .route-option-duration {
        font-size: 1rem;
        font-weight: 700;
        color: #16a34a;
        white-space: nowrap;
    }

    .route-option-distance {
        margin-top: 0.1rem;
        font-size: 0.84rem;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 0.38rem;
    }

    .route-option-distance-icon {
        color: #0f172a;
        font-size: 0.8rem;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .route-option-distance-icon svg {
        width: 14px;
        height: 14px;
        display: block;
    }

    .route-option-subtext {
        margin-top: 0.35rem;
        font-size: 0.82rem;
        color: #475569;
    }

    .route-option-empty {
        font-size: 0.84rem;
        color: #64748b;
    }

    .route-map-panel {
        border: 1px solid #d6e8f5;
        border-radius: 18px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        min-height: 620px;
    }

    .route-map-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.85rem;
        border-bottom: 1px solid #e6f1f7;
        background: #ffffff;
    }

    .route-map-toolbar-left {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-width: 0;
    }

    .route-map-toolbar-text {
        font-size: 0.82rem;
        color: #64748b;
    }

    .route-map-stage {
        position: relative;
    }

    .route-map-intro {
        position: absolute;
        top: 0.35rem;
        left: 0.35rem;
        bottom: 0.35rem;
        z-index: 620;
        width: min(400px, calc(100% - 0.7rem));
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        pointer-events: none;
    }

    .route-map-intro > * {
        pointer-events: auto;
    }

    .route-map-intro-searchbar {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr) 44px 44px;
        align-items: center;
        border-radius: 28px;
        overflow: visible;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.16);
    }

    .route-map-intro-menu-btn,
    .route-map-intro-back-btn,
    .route-map-intro-planner-btn,
    .route-map-intro-close-btn {
        width: 44px;
        height: 52px;
        border: 0;
        background: transparent;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .route-map-intro-menu-btn svg,
    .route-map-intro-back-btn svg,
    .route-map-intro-planner-btn svg,
    .route-map-intro-close-btn svg {
        width: 22px;
        height: 22px;
        display: block;
    }

    .route-map-intro-planner-btn {
        color: #0f8ea2;
    }

    .route-map-intro-planner-btn:hover {
        color: #0b7285;
    }

    .route-map-intro-search-wrap {
        position: relative;
        padding-right: 0.2rem;
    }

    .route-map-intro-search-input {
        width: 100%;
        height: 52px;
        border: 0;
        background: transparent;
        padding: 0 2.7rem 0 0.15rem;
        font-size: 1rem;
        color: #0f172a;
        box-shadow: none;
    }

    .route-map-intro-search-input:focus {
        outline: none;
        box-shadow: none;
    }

    .route-map-intro-search-icon {
        position: absolute;
        right: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        color: #475569;
        pointer-events: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .route-map-intro-search-icon svg {
        width: 18px;
        height: 18px;
        display: block;
    }

    .route-map-intro-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(214, 232, 245, 0.92);
        border-radius: 24px;
        padding: 0;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
        backdrop-filter: blur(10px);
        overflow: hidden;
        max-height: none;
        min-height: 0;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        position: relative;
        isolation: isolate;
    }

    .route-map-place-state.d-none {
        display: none !important;
    }

    .route-map-place-hero {
        position: relative;
        min-height: 190px;
        flex: 0 0 auto;
        overflow: hidden;
        background: #e2e8f0;
    }

    .route-map-place-hero .route-marker-popup-hero-media,
    .route-map-place-hero .route-marker-popup-hero-media-photo,
    .route-map-place-hero .route-marker-popup-hero-media-placeholder {
        min-height: 190px;
        border-radius: 0;
    }

    .route-map-place-content {
        padding: 1rem 0 0;
        overflow-y: auto;
        min-height: 0;
        position: relative;
        z-index: 1;
        background: #ffffff;
    }

    .route-map-place-name {
        margin: 0;
        padding: 0 1rem;
        font-size: 1.4rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }

    .route-map-place-subname {
        margin-top: 0.25rem;
        padding: 0 1rem;
        font-size: 0.95rem;
        color: #475569;
    }

    .route-map-place-subname:empty {
        display: none;
    }

    .route-map-place-address {
        margin-top: 0.45rem;
        padding: 0 1rem;
        font-size: 0.92rem;
        line-height: 1.55;
        color: #475569;
    }

    .route-map-place-meta {
        margin-top: 0.85rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0 1rem;
    }

    .route-map-place-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        min-height: 34px;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #0f172a;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .route-map-place-chip-muted {
        color: #475569;
        background: #f8fafc;
        font-weight: 500;
    }

    .route-map-place-actions {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.2rem;
        margin-top: 1.05rem;
        padding: 0.9rem 0.55rem 0.75rem;
        border-top: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }

    .route-map-place-btn {
        border: 0;
        background: transparent;
        min-height: 82px;
        padding: 0.35rem 0.2rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #0f172a;
        transition: transform 0.18s ease, color 0.18s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 0.42rem;
        text-align: center;
    }

    .route-map-place-btn:hover {
        transform: translateY(-1px);
    }

    .route-map-place-btn-icon {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dff4fb;
        color: #0f6d7b;
    }

    .route-map-place-btn-icon svg {
        width: 18px;
        height: 18px;
        display: block;
    }

    .route-map-place-btn-text {
        line-height: 1.2;
    }

    .route-map-place-btn-primary .route-map-place-btn-icon {
        background: #0f8ea2;
        color: #ffffff;
    }

    .route-map-place-btn-secondary .route-map-place-btn-icon,
    .route-map-place-btn-ghost .route-map-place-btn-icon {
        background: #dff4fb;
        color: #0f6d7b;
    }

    .route-map-place-btn-danger .route-map-place-btn-icon {
        background: #f3f4f6;
        color: #475569;
    }

    .route-map-place-note {
        margin: 0;
        padding: 0.95rem 1rem 1.1rem;
        font-size: 0.79rem;
        line-height: 1.45;
        color: #64748b;
    }

    .route-map-place-section {
        padding: 1rem 1rem 1.15rem;
        border-bottom: 1px solid #eef2f7;
    }

    .route-map-place-section:last-child {
        border-bottom: 0;
    }

    .route-map-place-section-title {
        margin: 0 0 0.7rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }

    .route-map-place-section-text {
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.6;
        color: #334155;
    }

    .route-map-place-details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.7rem;
    }

    .route-map-place-detail {
        padding: 0.8rem 0.9rem;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .route-map-place-detail-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .route-map-place-detail-value {
        margin-top: 0.3rem;
        font-size: 0.92rem;
        color: #0f172a;
        word-break: break-word;
    }

    .route-share-modal {
        position: fixed;
        inset: 0;
        z-index: 4000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .route-share-modal.d-none {
        display: none !important;
    }

    .route-share-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.42);
    }

    .route-share-modal-dialog {
        position: relative;
        width: min(460px, calc(100vw - 2rem));
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 24px 52px rgba(15, 23, 42, 0.24);
        overflow: hidden;
    }

    .route-share-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.9rem 1rem 0.5rem;
    }

    .route-share-modal-title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
    }

    .route-share-modal-close {
        border: 0;
        background: transparent;
        color: #0f172a;
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        line-height: 1;
    }

    .route-share-modal-tabs {
        display: flex;
        align-items: center;
        gap: 1.4rem;
        padding: 0 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .route-share-modal-tab {
        border: 0;
        background: transparent;
        color: #334155;
        font-size: 0.95rem;
        font-weight: 500;
        padding: 0.75rem 0 0.7rem;
        border-bottom: 3px solid transparent;
    }

    .route-share-modal-tab.route-share-modal-tab-active {
        color: #0f172a;
        border-bottom-color: #0f8ea2;
    }

    .route-share-modal-body {
        padding: 1rem;
    }

    .route-share-modal-pane.d-none {
        display: none !important;
    }

    .route-share-place {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr);
        gap: 0.8rem;
        align-items: center;
        margin-bottom: 1rem;
    }

    .route-share-place-thumb {
        width: 56px;
        height: 56px;
        border-radius: 8px;
        overflow: hidden;
        background: #e2e8f0;
        position: relative;
    }

    .route-share-place-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .route-share-place-thumb-fallback {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
    }

    .route-share-place-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }

    .route-share-place-address {
        margin-top: 0.2rem;
        font-size: 0.82rem;
        line-height: 1.45;
        color: #475569;
    }

    .route-share-field-label {
        font-size: 0.82rem;
        color: #475569;
        margin-bottom: 0.45rem;
    }

    .route-share-field-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: center;
        padding: 0 0 0.85rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .route-share-field-value {
        font-size: 0.82rem;
        color: #0f172a;
        line-height: 1.45;
        word-break: break-all;
    }

    .route-share-copy-btn {
        border: 0;
        background: transparent;
        color: #0f8ea2;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .route-share-apps {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        padding: 1rem 1rem 1.1rem;
        background: #f8fafc;
    }

    .route-share-app-btn {
        border: 0;
        background: transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.45rem;
        color: #0f172a;
        font-size: 0.82rem;
        font-weight: 500;
    }

    .route-share-app-icon {
        width: 44px;
        height: 44px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        box-shadow: inset 0 0 0 1px #dbe7f1;
    }

    .route-share-app-icon svg {
        width: 24px;
        height: 24px;
        display: block;
    }

    .route-share-embed-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.55rem;
    }

    .route-share-embed-size {
        border: 0;
        background: transparent;
        font-size: 0.82rem;
        color: #0f172a;
        padding: 0;
        min-width: 110px;
    }

    .route-share-embed-code-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: center;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .route-share-embed-code {
        font-size: 0.72rem;
        line-height: 1.5;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .route-share-embed-box {
        width: 100%;
        min-height: 120px;
        border: 1px solid #dbe7f1;
        border-radius: 12px;
        padding: 1rem;
        resize: vertical;
        font-size: 0.82rem;
        line-height: 1.5;
        color: #0f172a;
        background: #f8fafc;
    }

    .route-share-embed-preview {
        margin-top: 0.8rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        background: #f8fafc;
    }

    .route-share-embed-preview iframe {
        width: 100%;
        height: 240px;
        border: 0;
        display: block;
    }

    .route-send-modal {
        position: fixed;
        inset: 0;
        z-index: 4010;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .route-send-modal.d-none {
        display: none !important;
    }

    .route-send-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.42);
    }

    .route-send-modal-dialog {
        position: relative;
        width: min(430px, calc(100vw - 2rem));
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 24px 52px rgba(15, 23, 42, 0.24);
        overflow: hidden;
    }

    .route-send-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.2rem 0.75rem;
    }

    .route-send-modal-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 500;
        color: #0f172a;
    }

    .route-send-modal-close {
        border: 0;
        background: transparent;
        color: #0f172a;
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.7rem;
        line-height: 1;
    }

    .route-send-modal-body {
        padding: 0.2rem 1.2rem 1rem;
    }

    .route-send-modal-option {
        width: 100%;
        border: 0;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding: 0.85rem 0.05rem;
        text-align: left;
    }

    .route-send-modal-option + .route-send-modal-option {
        border-top: 1px solid #eef2f7;
    }

    .route-send-modal-option-icon {
        width: 32px;
        height: 32px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #0f8ea2;
        color: #ffffff;
        flex: 0 0 auto;
    }

    .route-send-modal-option-icon svg {
        width: 17px;
        height: 17px;
        display: block;
    }

    .route-send-modal-option-text {
        min-width: 0;
    }

    .route-send-modal-option-title {
        font-size: 0.92rem;
        font-weight: 500;
        color: #0f172a;
        line-height: 1.3;
    }

    .route-send-modal-option-subtitle {
        margin-top: 0.15rem;
        font-size: 0.8rem;
        color: #64748b;
        line-height: 1.4;
        word-break: break-word;
    }

    .route-send-modal-footer {
        padding: 0.8rem 1.2rem 1rem;
        border-top: 1px solid #eef2f7;
    }

    .route-send-modal-note {
        font-size: 0.8rem;
        color: #0f8ea2;
    }

    .route-send-modal-note-link {
        border: 0;
        background: transparent;
        padding: 0;
        color: inherit;
        font: inherit;
        cursor: pointer;
        text-decoration: none;
    }

    .route-send-modal-note-link:hover {
        text-decoration: underline;
        color: #0b7285;
    }

    .route-streetview-trigger {
        position: absolute;
        right: 0.9rem;
        bottom: 0.9rem;
        z-index: 520;
        width: 132px;
        border: 0;
        padding: 0;
        border-radius: 14px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.28);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .route-streetview-trigger:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.34);
    }

    .route-streetview-trigger.d-none {
        display: none !important;
    }

    .route-streetview-trigger-thumb {
        position: relative;
        display: block;
        width: 100%;
        height: 78px;
        background:
            linear-gradient(135deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.24)),
            linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
    }

    .route-streetview-trigger-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .route-streetview-trigger-badge {
        position: absolute;
        left: 0.45rem;
        bottom: 0.45rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.18rem 0.45rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.72);
        color: #ffffff;
        font-size: 0.62rem;
        font-weight: 700;
        line-height: 1;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .route-streetview-trigger-caption {
        display: block;
        padding: 0.5rem 0.6rem 0.58rem;
        font-size: 0.74rem;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.25;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .route-streetview-modal {
        position: fixed;
        inset: 0;
        z-index: 4020;
        background: rgba(2, 6, 23, 0.94);
        padding: 1rem;
    }

    .route-streetview-modal.d-none {
        display: none !important;
    }

    .route-streetview-modal-shell {
        position: relative;
        width: min(1280px, 100%);
        height: min(92vh, 780px);
        margin: 0 auto;
        display: grid;
        grid-template-rows: minmax(0, 1fr) 220px;
        border-radius: 18px;
        overflow: hidden;
        background: #020617;
        box-shadow: 0 28px 60px rgba(2, 6, 23, 0.45);
    }

    .route-streetview-panorama-wrap {
        position: relative;
        min-height: 0;
        background: #000000;
    }

    .route-streetview-panorama {
        width: 100%;
        height: 100%;
        min-height: 0;
    }

    .route-streetview-panorama-empty {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.95rem;
        letter-spacing: 0.01em;
    }

    .route-streetview-modal-topbar {
        position: absolute;
        top: 1rem;
        left: 1rem;
        right: 1rem;
        z-index: 3;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        pointer-events: none;
    }

    .route-streetview-modal-info,
    .route-streetview-modal-actions {
        pointer-events: auto;
    }

    .route-streetview-modal-info {
        min-width: 0;
        max-width: min(480px, calc(100% - 160px));
        padding: 0.95rem 1rem;
        border-radius: 16px;
        background: rgba(15, 23, 42, 0.86);
        color: #ffffff;
        backdrop-filter: blur(10px);
        box-shadow: 0 16px 32px rgba(2, 6, 23, 0.32);
    }

    .route-streetview-modal-kicker {
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.72);
    }

    .route-streetview-modal-title {
        margin-top: 0.35rem;
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .route-streetview-modal-subtitle {
        margin-top: 0.3rem;
        font-size: 0.88rem;
        line-height: 1.45;
        color: rgba(255, 255, 255, 0.82);
    }

    .route-streetview-modal-meta {
        margin-top: 0.55rem;
        font-size: 0.76rem;
        color: rgba(255, 255, 255, 0.68);
    }

    .route-streetview-modal-actions {
        display: flex;
        align-items: center;
        gap: 0.55rem;
    }

    .route-streetview-modal-action-btn {
        min-width: 42px;
        height: 42px;
        border: 0;
        border-radius: 999px;
        padding: 0 0.95rem;
        background: rgba(15, 23, 42, 0.86);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        font-size: 0.84rem;
        font-weight: 600;
        backdrop-filter: blur(10px);
        box-shadow: 0 14px 28px rgba(2, 6, 23, 0.28);
    }

    .route-streetview-modal-action-btn svg {
        width: 16px;
        height: 16px;
        display: block;
    }

    .route-streetview-modal-action-btn.route-streetview-modal-close {
        width: 42px;
        padding: 0;
        font-size: 1.65rem;
        line-height: 1;
    }

    .route-streetview-modal-bottom {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 260px;
        background: #f8fafc;
        border-top: 1px solid rgba(148, 163, 184, 0.18);
    }

    .route-streetview-map {
        min-height: 220px;
    }

    .route-streetview-sidebar {
        padding: 1rem;
        border-left: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.9rem;
        background: #ffffff;
    }

    .route-streetview-sidebar-title {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 700;
        color: #0f172a;
    }

    .route-streetview-sidebar-text {
        margin: 0.35rem 0 0;
        font-size: 0.82rem;
        line-height: 1.5;
        color: #475569;
    }

    .route-streetview-sidebar-coords {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }

    .route-streetview-sidebar-coord {
        padding: 0.7rem 0.75rem;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .route-streetview-sidebar-coord-label {
        font-size: 0.66rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .route-streetview-sidebar-coord-value {
        margin-top: 0.22rem;
        font-size: 0.82rem;
        color: #0f172a;
        line-height: 1.35;
        word-break: break-word;
    }

    .route-map-recenter-btn {
        position: absolute;
        top: 5.5rem;
        left: 0.7rem;
        z-index: 500;
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.96);
        color: #0f172a;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .route-map-recenter-btn:hover {
        transform: translateY(-1px);
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.2);
    }

    .route-map-recenter-btn:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.2), 0 12px 28px rgba(15, 23, 42, 0.2);
    }

    .route-map-recenter-btn svg {
        width: 18px;
        height: 18px;
        display: block;
    }

    .route-map-stage .leaflet-top.leaflet-left {
        top: 5.5rem;
        left: auto;
        right: 0.7rem;
    }

    .route-map-stage .leaflet-control-zoom {
        margin-top: 0;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
        border: 0;
        overflow: hidden;
        border-radius: 14px;
    }

    .route-map-stage .leaflet-control-zoom a {
        width: 42px;
        height: 42px;
        line-height: 42px;
        border: 0;
        color: #0f172a;
    }

    .route-map-layer-switcher {
        position: absolute;
        bottom: 0.9rem;
        left: 0.9rem;
        z-index: 500;
        display: flex;
        gap: 0.45rem;
        flex-wrap: nowrap;
        align-items: flex-end;
        padding: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        pointer-events: none;
    }

    .route-map-layer-btn {
        pointer-events: auto;
        border: 0;
        background: transparent;
        border-radius: 14px;
        padding: 0;
        width: 78px;
        display: block;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        text-align: left;
        border: 2px solid transparent;
    }

    .route-map-layer-btn:hover {
        transform: translateY(-1px);
    }

    .route-map-layer-btn.route-map-layer-btn-active {
        width: 84px;
    }

    .route-map-layer-thumb {
        display: block;
        width: 100%;
        height: 58px;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.24), 0 8px 18px rgba(15, 23, 42, 0.18);
        border: 2px solid transparent;
    }

    .route-map-layer-thumb::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(15, 23, 42, 0.38), rgba(15, 23, 42, 0.04));
    }

    .route-map-layer-btn.route-map-layer-btn-active .route-map-layer-thumb {
        border-color: #ffffff;
        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08), 0 10px 22px rgba(15, 23, 42, 0.16);
    }

    .route-map-layer-thumb-roadmap {
        background:
            linear-gradient(90deg, rgba(234, 179, 8, 0.92) 0 8%, transparent 8% 92%, rgba(34, 197, 94, 0.75) 92% 100%),
            linear-gradient(0deg, transparent 0 42%, rgba(248, 113, 113, 0.8) 42% 52%, transparent 52% 100%),
            linear-gradient(135deg, #dbeafe 0%, #f8fafc 100%);
    }

    .route-map-layer-thumb-satellite {
        background:
            radial-gradient(circle at 18% 20%, rgba(190, 242, 100, 0.28), transparent 18%),
            radial-gradient(circle at 72% 56%, rgba(96, 165, 250, 0.18), transparent 22%),
            linear-gradient(135deg, #6b7280 0%, #334155 36%, #3f6212 68%, #1f2937 100%);
    }

    .route-map-layer-thumb-terrain {
        background:
            linear-gradient(135deg, rgba(34, 197, 94, 0.82) 0 25%, rgba(132, 204, 22, 0.72) 25% 48%, rgba(148, 163, 184, 0.78) 48% 68%, rgba(15, 118, 110, 0.84) 68% 100%);
    }

    .route-map-layer-label {
        position: absolute;
        left: 50%;
        bottom: 0.38rem;
        transform: translateX(-50%);
        z-index: 1;
        font-size: 0.73rem;
        font-weight: 700;
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(15, 23, 42, 0.45);
        white-space: nowrap;
    }

    #routeBuilderMap {
        height: 570px;
    }

    .route-marker-badge {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 700;
        border: 2px solid #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.16);
    }

    .route-marker-start {
        background: #2f9e44;
    }

    .route-marker-pickup {
        background: #1971c2;
    }

    .route-marker-end {
        background: #e03131;
    }

    .route-marker-popup-title {
        font-weight: 700;
        color: #134b5f;
    }

    .route-marker-popup .leaflet-popup-content-wrapper {
        padding: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 44px rgba(15, 23, 42, 0.24);
    }

    .route-marker-popup .leaflet-popup-content {
        margin: 0;
        width: 212px !important;
    }

    .route-marker-popup .leaflet-popup-tip {
        background: #ffffff;
        box-shadow: none;
    }

    .route-marker-popup .leaflet-popup-close-button {
        top: 12px;
        right: 12px;
        width: 30px;
        height: 30px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.98) !important;
        border: 1px solid rgba(148, 163, 184, 0.45);
        color: #0f172a !important;
        font-size: 22px;
        line-height: 30px;
        text-align: center;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.18);
        opacity: 1 !important;
        z-index: 20;
        font-weight: 700;
        text-indent: 0;
        overflow: hidden;
    }

    .route-marker-popup .leaflet-popup-close-button span {
        display: block;
        width: 100%;
        height: 100%;
        line-height: 28px;
        color: inherit !important;
        text-shadow: none;
    }

    .route-marker-popup-card {
        background: #ffffff;
    }

    .route-marker-popup-hero {
        min-height: 72px;
        padding: 0.58rem 0.62rem 0.56rem;
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 32%),
            linear-gradient(135deg, #0f766e 0%, #155e75 55%, #0f172a 100%);
        display: flex;
        align-items: flex-end;
        position: relative;
        overflow: hidden;
    }

    .route-marker-popup-hero.route-marker-popup-hero-start {
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 32%),
            linear-gradient(135deg, #15803d 0%, #166534 55%, #0f172a 100%);
    }

    .route-marker-popup-hero.route-marker-popup-hero-pickup {
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 32%),
            linear-gradient(135deg, #2563eb 0%, #1d4ed8 55%, #172554 100%);
    }

    .route-marker-popup-hero.route-marker-popup-hero-end {
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 32%),
            linear-gradient(135deg, #ef4444 0%, #dc2626 55%, #7f1d1d 100%);
    }

    .route-marker-popup-hero-media {
        position: absolute;
        inset: 0;
        overflow: hidden;
    }

    .route-marker-popup-hero-media-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transform: scale(1.02);
    }

    .route-marker-popup-hero-media-placeholder {
        background:
            linear-gradient(135deg, rgba(15, 23, 42, 0.34) 0%, rgba(15, 23, 42, 0.1) 100%),
            linear-gradient(135deg, #0f766e 0%, #1d4ed8 55%, #0f172a 100%);
    }

    .route-marker-popup-hero-media-caption {
        position: absolute;
        left: 0.55rem;
        right: 0.55rem;
        bottom: 0.55rem;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 0.18rem;
        color: #ffffff;
        text-shadow: 0 2px 10px rgba(15, 23, 42, 0.28);
    }

    .route-marker-popup-hero-media-caption strong {
        display: block;
        font-size: 0.82rem;
        line-height: 1.15;
        font-weight: 700;
    }

    .route-marker-popup-hero-media-attribution {
        display: block;
        font-size: 0.58rem;
        line-height: 1.2;
        color: rgba(255, 255, 255, 0.92);
    }

    .route-marker-popup-hero-media-attribution a {
        color: inherit;
        text-decoration: underline;
    }

    .route-marker-popup-hero-media-badge {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.18rem 0.45rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.52);
        backdrop-filter: blur(10px);
        font-size: 0.56rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .route-marker-popup-hero-stage {
        position: absolute;
        width: 768px;
        height: 768px;
        transform: scale(1.03);
        transform-origin: top left;
    }

    .route-marker-popup-hero-tile {
        position: absolute;
        width: 256px;
        height: 256px;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
    }

    .route-marker-popup-hero-pin {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 18px;
        height: 18px;
        border-radius: 999px 999px 999px 2px;
        transform: translate(-50%, -100%) rotate(-45deg);
        border: 2px solid rgba(255, 255, 255, 0.96);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.28);
        z-index: 1;
    }

    .route-marker-popup-hero-pin::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: #ffffff;
        transform: translate(-50%, -50%) rotate(45deg);
    }

    .route-marker-popup-hero-pin.route-marker-popup-hero-pin-start {
        background: #16a34a;
    }

    .route-marker-popup-hero-pin.route-marker-popup-hero-pin-pickup {
        background: #2563eb;
    }

    .route-marker-popup-hero-pin.route-marker-popup-hero-pin-end {
        background: #ef4444;
    }

    .route-marker-popup-hero-overlay {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(15, 23, 42, 0.08) 0%, rgba(15, 23, 42, 0.2) 42%, rgba(15, 23, 42, 0.58) 100%),
            linear-gradient(135deg, rgba(15, 118, 110, 0.2) 0%, rgba(15, 23, 42, 0.1) 100%);
    }

    .route-marker-popup-hero-inner {
        width: 100%;
        color: #ffffff;
        position: relative;
        z-index: 1;
    }

    .route-marker-popup-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.24rem;
        padding: 0.22rem 0.48rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(8px);
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .route-marker-popup-chip-icon {
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: #ffffff;
        display: inline-block;
    }

    .route-marker-popup-body {
        padding: 0.6rem 0.65rem 0.65rem;
    }

    .route-marker-popup-name {
        margin: 0;
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.22;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
        overflow: hidden;
    }

    .route-marker-popup-subtitle {
        margin-top: 0.16rem;
        font-size: 0.68rem;
        color: #64748b;
    }

    .route-marker-popup-address {
        margin-top: 0.42rem;
        font-size: 0.7rem;
        color: #475569;
        line-height: 1.28;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
        overflow: hidden;
    }

    .route-marker-popup-stats {
        margin-top: 0.48rem;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.35rem;
    }

    .route-marker-popup-stat {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.38rem 0.42rem;
        background: #f8fafc;
    }

    .route-marker-popup-stat-label {
        font-size: 0.56rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #64748b;
    }

    .route-marker-popup-stat-value {
        margin-top: 0.12rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
    }

    .route-marker-popup-route {
        margin-top: 0.5rem;
        border-top: 1px solid #e2e8f0;
        padding-top: 0.48rem;
    }

    .route-marker-popup-route-label {
        font-size: 0.58rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #64748b;
    }

    .route-marker-popup-route-value {
        margin-top: 0.16rem;
        font-size: 0.74rem;
        font-weight: 700;
        color: #ea580c;
        display: flex;
        align-items: center;
        gap: 0.28rem;
    }

    .route-marker-popup-route-meta {
        margin-top: 0.1rem;
        font-size: 0.66rem;
        color: #475569;
        line-height: 1.25;
    }

    .route-marker-popup-route-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #475569;
    }

    .route-marker-popup-route-icon svg {
        width: 10px;
        height: 10px;
        display: block;
    }

    .route-map-leg-tooltip {
        background: transparent;
        border: 0;
        box-shadow: none;
        padding: 0;
        white-space: nowrap;
    }

    .route-map-leg-tooltip::before {
        display: none;
    }

    .route-map-leg-card {
        min-width: 0;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dbe7f1;
        border-radius: 8px;
        padding: 0.2rem 0.28rem;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
    }

    .route-map-leg-top {
        display: flex;
        align-items: center;
        gap: 0.18rem;
    }

    .route-map-leg-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #4b5563;
        flex-shrink: 0;
    }

    .route-map-leg-icon svg {
        width: 10px;
        height: 10px;
        display: block;
    }

    .route-map-leg-text {
        display: flex;
        flex-direction: column;
        gap: 0.02rem;
        line-height: 1.05;
    }

    .route-map-leg-duration {
        font-size: 0.75rem;
        font-weight: 700;
        color: #ea580c;
    }

    .route-map-leg-distance {
        font-size: 0.62rem;
        color: #475569;
    }

    @media (max-width: 1199px) {
        .route-builder-layout {
            grid-template-columns: 330px minmax(0, 1fr);
        }
    }

    @media (max-width: 991px) {
        .route-builder-layout {
            grid-template-columns: 1fr;
        }

        .route-directions-sidebar,
        .route-map-panel {
            min-height: auto;
            max-height: none;
        }

        #routeBuilderMap {
            height: 420px;
        }

        .route-map-layer-switcher {
            bottom: 0.7rem;
            left: 0.7rem;
            gap: 0.35rem;
        }

        .route-map-recenter-btn {
            top: 5.2rem;
            left: 0.7rem;
            width: 40px;
            height: 40px;
        }

        .route-map-layer-btn {
            width: 68px;
        }

        .route-map-layer-btn.route-map-layer-btn-active {
            width: 72px;
        }

        .route-map-layer-thumb {
            height: 50px;
        }

        .route-map-layer-label {
            font-size: 0.68rem;
        }

        .route-map-intro {
            top: 0.7rem;
            left: 0.7rem;
            width: calc(100% - 1.4rem);
        }

        .route-streetview-trigger {
            right: 0.7rem;
            bottom: 0.7rem;
            width: 118px;
        }

        .route-streetview-trigger-thumb {
            height: 70px;
        }

        .route-streetview-trigger-caption {
            font-size: 0.7rem;
            padding: 0.42rem 0.52rem 0.5rem;
        }

        .route-map-place-actions {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .route-share-modal-dialog {
            width: calc(100vw - 1rem);
        }

        .route-share-apps {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .route-map-stage .leaflet-top.leaflet-left {
            top: 5.2rem;
            right: 0.7rem;
        }

        .route-map-stage .leaflet-control-zoom a {
            width: 40px;
            height: 40px;
            line-height: 40px;
        }

        .route-streetview-modal {
            padding: 0.5rem;
        }

        .route-streetview-modal-shell {
            height: min(94vh, 760px);
            grid-template-rows: minmax(0, 1fr) 200px;
        }

        .route-streetview-modal-topbar {
            top: 0.75rem;
            left: 0.75rem;
            right: 0.75rem;
            flex-direction: column;
            align-items: stretch;
        }

        .route-streetview-modal-info {
            max-width: none;
        }

        .route-streetview-modal-actions {
            justify-content: flex-end;
        }

        .route-streetview-modal-bottom {
            grid-template-columns: minmax(0, 1fr);
        }

        .route-streetview-map {
            min-height: 120px;
        }

        .route-streetview-sidebar {
            border-left: 0;
            border-top: 1px solid #e2e8f0;
        }
    }
</style>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4>{{ $formHeading }}</h4>
        </div>

        <div class="card-body">
            <form id="{{ $formId }}">
                @csrf
                @if (($formMethod ?? 'POST') !== 'POST')
                    @method($formMethod)
                @endif

                <div class="form-group">
                    <label><b>School</b> <span class="text-danger">*</span></label>
                    @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                        <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                        <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                    @else
                        <select class="form-control route-native-select" name="school_id" id="school_id">
                            <option value="">Select School</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" {{ (int) old('school_id', $routeRecord->school_id ?? $defaultSchoolId ?? 0) === (int) $school->id ? 'selected' : '' }}>
                                    {{ $school->school_name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    <span class="error-message text-danger"></span>
                </div>

                <div class="form-group">
                    <label><b>Route Name</b> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $routeRecord->name ?? '') }}">
                    <span class="error-message text-danger"></span>
                </div>

                <div class="form-group">
                    <label><b>Driver</b> <span class="text-danger">*</span></label>
                    <select class="form-control route-native-select" name="driver_id" id="driver_id" onchange="window.routeVehicleDriverSync && window.routeVehicleDriverSync()">
                        <option value="">Select Driver</option>
                        @foreach ($drivers as $driver)
                            @php
                                $selectedDriverId = (int) old('driver_id', $routeRecord->driver_id ?? 0);
                                $selectedBusId = (int) old('bus_id', $routeRecord->bus_id ?? 0);
                                $driverVehicleId = (int) ($driver->vehicle_id ?? 0);

                                if ($driverVehicleId <= 0) {
                                    $mappedBus = $buses->firstWhere('driver_id', (int) $driver->id);
                                    $driverVehicleId = (int) ($mappedBus->id ?? 0);
                                }

                                if ($driverVehicleId <= 0 && $selectedDriverId === (int) $driver->id && $selectedBusId > 0) {
                                    $driverVehicleId = $selectedBusId;
                                }
                            @endphp
                            <option
                                value="{{ $driver->id }}"
                                data-vehicle-id="{{ $driverVehicleId > 0 ? $driverVehicleId : '' }}"
                                data-school-id="{{ (int) ($driver->effective_school_id ?? 0) }}"
                                {{ (int) old('driver_id', $routeRecord->driver_id ?? 0) === (int) $driver->id ? 'selected' : '' }}
                            >
                                {{ $driver->driver_name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error-message text-danger"></span>
                </div>

                <div class="form-group">
                    <label><b>Vehicle</b> <span class="text-danger">*</span></label>
                    <select class="form-control route-native-select" name="bus_id" id="bus_id">
                        <option value="">Select Driver First</option>
                        @foreach ($buses as $bus)
                            @php
                                $mappedDriver = $drivers->firstWhere('id', (int) ($bus->driver_id ?? 0));

                                if (! $mappedDriver) {
                                    $mappedDriver = $drivers->firstWhere('vehicle_id', (int) $bus->id);
                                }
                            @endphp
                            <option
                                value="{{ $bus->id }}"
                                data-driver-id="{{ (int) ($mappedDriver->id ?? 0) > 0 ? (int) $mappedDriver->id : '' }}"
                                data-driver-name="{{ $mappedDriver->driver_name ?? '' }}"
                                data-school-id="{{ (int) ($bus->effective_school_id ?? 0) }}"
                                {{ (int) old('bus_id', $routeRecord->bus_id ?? 0) === (int) $bus->id ? 'selected' : '' }}
                            >
                                {{ $bus->vehicle_number }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error-message text-danger"></span>
                </div>

                <div class="route-builder-layout" id="routeBuilderShell">
                    <div class="route-directions-sidebar" id="routeBuilderSidebar">
                        <div class="route-directions-header">
                            <div class="route-directions-header-copy">
                                <h5 class="route-directions-title">Route Points</h5>
                                <div class="route-directions-subtitle">Search, reorder destinations, or pick directly from map.</div>
                            </div>
                            <button type="button" class="route-sidebar-toggle-btn" id="closeRouteSidebarBtn" title="Hide route panel" aria-label="Hide route panel">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>

                        <div class="route-directions-body">
                            <div class="route-direction-row route-direction-row-start">
                                <div class="route-direction-marker-col">
                                    <div class="route-direction-marker"></div>
                                </div>
                                <div class="route-direction-card route-search-wrap">
                                    <div class="route-direction-inputbar">
                                        <input type="text" class="form-control route-direction-input" id="startPointInput" placeholder="Choose starting point" autocomplete="off">
                                        <div class="route-direction-actions">
                                            <button type="button" class="route-direction-btn" id="startPointMapBtn" title="Pick on map">+</button>
                                            <button type="button" class="route-direction-btn" id="startPointClearBtn" title="Clear">x</button>
                                        </div>
                                    </div>
                                    <div class="route-point-meta d-none" id="startPointMeta"></div>
                                    <div class="route-search-results list-group d-none" id="startPointResults"></div>
                                </div>
                            </div>

                            <div id="pickupPointsContainer"></div>

                            <div class="route-direction-row route-direction-row-end">
                                <div class="route-direction-marker-col">
                                    <div class="route-direction-marker"></div>
                                </div>
                                <div class="route-direction-card route-search-wrap">
                                    <div class="route-direction-inputbar">
                                        <input type="text" class="form-control route-direction-input" id="endPointInput" placeholder="Choose ending point" autocomplete="off">
                                        <div class="route-direction-actions">
                                            <button type="button" class="route-direction-btn" id="endPointMapBtn" title="Pick on map">+</button>
                                            <button type="button" class="route-direction-btn" id="endPointClearBtn" title="Clear">x</button>
                                        </div>
                                    </div>
                                    <div class="route-point-meta d-none" id="endPointMeta"></div>
                                    <div class="route-search-results list-group d-none" id="endPointResults"></div>
                                </div>
                            </div>

                            <div class="route-direction-row route-direction-row-add d-none" id="addDestinationRow">
                                <div class="route-direction-marker-col">
                                    <div class="route-direction-marker"></div>
                                </div>
                                <div>
                                    <button type="button" class="route-direction-add-btn" id="addPickupPointBtn">Add destination</button>
                                </div>
                            </div>

                            <div class="route-options-panel">
                                <h6 class="route-options-title">Route Options</h6>
                                <div id="routeOptionsContainer" class="route-options-list">
                                    <div class="route-option-empty">Add start and end points to see distance and time.</div>
                                </div>
                                <button type="button" class="route-options-add-btn" id="toggleCustomLocationBtn" title="Add custom location" aria-label="Add custom location">
                                    <span class="route-options-add-btn-icon">+</span>
                                    <span class="route-options-add-btn-text">Add Custom Location</span>
                                </button>
                                <div class="route-custom-location-panel d-none" id="customLocationPanel">
                                    <div class="route-custom-location-title">Add Location (Address OR Lat/Lng)</div>
                                    <input type="hidden" id="customLocationName" value="">
                                    <div class="route-custom-location-field">
                                        <div class="route-custom-location-field-row">
                                            <input type="text" class="route-custom-location-input" id="customLocationAddress" placeholder="Enter address name">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="searchCustomLocationBtn">Search Address</button>
                                        </div>
                                    </div>
                                    <div class="route-custom-location-coords">
                                        <div class="route-custom-location-coord">
                                            <label class="route-custom-location-coord-label" for="customLocationLatInput">Latitude</label>
                                            <input type="text" class="route-custom-location-input route-custom-location-coord-input" id="customLocationLatInput" placeholder="e.g. 23.0225">
                                        </div>
                                        <div class="route-custom-location-coord">
                                            <label class="route-custom-location-coord-label" for="customLocationLngInput">Longitude</label>
                                            <input type="text" class="route-custom-location-input route-custom-location-coord-input" id="customLocationLngInput" placeholder="e.g. 72.5714">
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-info" id="previewCustomLocationLatLngBtn">Show Lat/Lng</button>
                                    </div>
                                    <div class="route-custom-location-actions">
                                        <button type="button" class="btn btn-sm btn-primary route-custom-location-save-btn" id="saveCustomLocationBtn">Save Location</button>
                                    </div>
                                    <div class="route-custom-location-status d-none" id="customLocationStatus"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="route-map-panel">
                        <div class="route-map-toolbar">
                            <div class="route-map-toolbar-left">
                                <button type="button" class="route-sidebar-toggle-btn d-none" id="openRouteSidebarBtn" title="Open route panel" aria-label="Open route panel">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M4 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </button>
                                <div class="route-map-toolbar-text" id="routeMapSelectionStatus">Open route planner to start selecting points.</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="fitRouteBtn">Focus Route</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllRoutePointsBtn">Clear All</button>
                            </div>
                        </div>
                        <div class="route-map-stage">
                            <div class="route-map-intro d-none" id="routeMapIntro">
                                <div class="route-map-intro-searchbar">
                                    <button type="button" class="route-map-intro-back-btn d-none" id="routeMapIntroBackBtn" title="Back" aria-label="Back">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="route-map-intro-menu-btn" id="routeMapIntroOpenBtn" title="Open route planner" aria-label="Open route planner">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M4 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                    <div class="route-map-intro-search-wrap route-search-wrap">
                                        <input type="text" class="route-map-intro-search-input" id="routeMapIntroSearchInput" placeholder="Search places" autocomplete="off">
                                        <span class="route-map-intro-search-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="M16 16L20 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            </svg>
                                        </span>
                                        <div class="route-search-results list-group d-none" id="routeMapIntroSearchResults"></div>
                                    </div>
                                    <button type="button" class="route-map-intro-planner-btn" id="routeMapIntroPlannerBtn" title="Open directions" aria-label="Open directions">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M12 3L21 12L12 21L10.35 19.35L16.55 13H3V11H16.55L10.35 4.65L12 3Z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="route-map-intro-close-btn d-none" id="routeMapIntroCloseSearchBtn" title="Clear search" aria-label="Clear search">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="route-map-intro-card route-map-place-state d-none" id="routeMapIntroPlaceState">
                                    <div class="route-map-place-hero" id="routeMapIntroPlaceHero"></div>
                                    <div class="route-map-place-content">
                                        <h6 class="route-map-place-name" id="routeMapIntroPlaceName">Selected place</h6>
                                        <div class="route-map-place-subname" id="routeMapIntroPlaceSubname"></div>
                                        <div class="route-map-place-address" id="routeMapIntroPlaceAddress"></div>
                                        <div class="route-map-place-meta" id="routeMapIntroPlaceMeta"></div>
                                        <div class="route-map-place-actions">
                                            <button type="button" class="route-map-place-btn route-map-place-btn-primary" id="routeMapIntroDirectionsBtn">
                                                <span class="route-map-place-btn-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4L20 12L12 20L10.6 18.6L16.2 13H4V11H16.2L10.6 5.4L12 4Z" fill="currentColor"/></svg>
                                                </span>
                                                <span class="route-map-place-btn-text">Directions</span>
                                            </button>
                                            <button type="button" class="route-map-place-btn route-map-place-btn-secondary" id="routeMapIntroSaveBtn">
                                                <span class="route-map-place-btn-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 4H17C18.1046 4 19 4.89543 19 6V20L12 16.5L5 20V6C5 4.89543 5.89543 4 7 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                                </span>
                                                <span class="route-map-place-btn-text">Save</span>
                                            </button>
                                            <button type="button" class="route-map-place-btn route-map-place-btn-secondary" id="routeMapIntroNearbyBtn">
                                                <span class="route-map-place-btn-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="6" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="1.8" fill="currentColor"/><path d="M12 3V5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 19V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M3 12H5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M19 12H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                                </span>
                                                <span class="route-map-place-btn-text">Nearby</span>
                                            </button>
                                            <button type="button" class="route-map-place-btn route-map-place-btn-secondary" id="routeMapIntroSendBtn">
                                                <span class="route-map-place-btn-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="7" y="3" width="10" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M10 17H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                                </span>
                                                <span class="route-map-place-btn-text">Send to phone</span>
                                            </button>
                                            <button type="button" class="route-map-place-btn route-map-place-btn-secondary" id="routeMapIntroShareBtn">
                                                <span class="route-map-place-btn-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="18" cy="5" r="2.5" stroke="currentColor" stroke-width="1.8"/><circle cx="6" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/><circle cx="18" cy="19" r="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M8.2 11L15.6 6.6" stroke="currentColor" stroke-width="1.8"/><path d="M8.2 13L15.6 17.4" stroke="currentColor" stroke-width="1.8"/></svg>
                                                </span>
                                                <span class="route-map-place-btn-text">Share</span>
                                            </button>
                                        </div>
                                        <div class="route-map-place-note">Directions se route planner khulega. Baaki buttons place actions ke liye hain, Google Maps style me.</div>
                                        <div class="route-map-place-section">
                                            <h6 class="route-map-place-section-title">Quick facts</h6>
                                            <p class="route-map-place-section-text" id="routeMapIntroQuickFacts">Selected place details will appear here after search.</p>
                                        </div>
                                        <div class="route-map-place-section">
                                            <h6 class="route-map-place-section-title">Location details</h6>
                                            <div class="route-map-place-details-grid">
                                                <div class="route-map-place-detail">
                                                    <div class="route-map-place-detail-label">Latitude</div>
                                                    <div class="route-map-place-detail-value" id="routeMapIntroLatValue">--</div>
                                                </div>
                                                <div class="route-map-place-detail">
                                                    <div class="route-map-place-detail-label">Longitude</div>
                                                    <div class="route-map-place-detail-value" id="routeMapIntroLngValue">--</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="route-map-recenter-btn" id="recenterRouteMapBtn" title="Re-center map" aria-label="Re-center map">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M12 3V6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M12 18V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M3 12H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M18 12H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.8"/>
                                    <circle cx="12" cy="12" r="1.6" fill="currentColor"/>
                                </svg>
                            </button>
                            <div class="route-map-layer-switcher" aria-label="Map style switcher">
                                <button type="button" class="route-map-layer-btn route-map-layer-btn-active" data-route-map-layer="roadmap" aria-pressed="true">
                                    <span class="route-map-layer-thumb route-map-layer-thumb-roadmap">
                                        <span class="route-map-layer-label">Map</span>
                                    </span>
                                </button>
                                <button type="button" class="route-map-layer-btn" data-route-map-layer="satellite" aria-pressed="false">
                                    <span class="route-map-layer-thumb route-map-layer-thumb-satellite">
                                        <span class="route-map-layer-label">Satellite</span>
                                    </span>
                                </button>
                                <button type="button" class="route-map-layer-btn" data-route-map-layer="terrain" aria-pressed="false">
                                    <span class="route-map-layer-thumb route-map-layer-thumb-terrain">
                                        <span class="route-map-layer-label">Terrain</span>
                                    </span>
                                </button>
                            </div>
                            <div id="routeBuilderMap"></div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="route_json" id="route_json">

                <div class="mt-3">
                    <button type="button" class="btn btn-primary" id="{{ $submitButtonId }}">{{ $submitButtonText }}</button>
                    <a href="{{ $routesIndexUrl }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="route-share-modal d-none" id="routeShareModal" aria-hidden="true">
    <div class="route-share-modal-backdrop" id="routeShareModalBackdrop"></div>
    <div class="route-share-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="routeShareModalTitle">
        <div class="route-share-modal-header">
            <h4 class="route-share-modal-title" id="routeShareModalTitle">Share</h4>
            <button type="button" class="route-share-modal-close" id="routeShareModalCloseBtn" aria-label="Close">&times;</button>
        </div>
        <div class="route-share-modal-tabs">
            <button type="button" class="route-share-modal-tab route-share-modal-tab-active" id="routeShareLinkTabBtn">Send a link</button>
            <button type="button" class="route-share-modal-tab" id="routeShareEmbedTabBtn">Embed a map</button>
        </div>
        <div class="route-share-modal-body">
            <div class="route-share-modal-pane" id="routeShareLinkPane">
                <div class="route-share-place">
                    <div class="route-share-place-thumb" id="routeSharePlaceThumb">
                        <div class="route-share-place-thumb-fallback"></div>
                    </div>
                    <div>
                        <div class="route-share-place-name" id="routeSharePlaceName">Selected place</div>
                        <div class="route-share-place-address" id="routeSharePlaceAddress">Address unavailable</div>
                    </div>
                </div>
                <div class="route-share-field-label">Link to share</div>
                <div class="route-share-field-row">
                    <div class="route-share-field-value" id="routeShareLinkValue"></div>
                    <button type="button" class="route-share-copy-btn" id="routeShareCopyLinkBtn">Copy link</button>
                </div>
            </div>
            <div class="route-share-modal-pane d-none" id="routeShareEmbedPane">
                <div class="route-share-embed-toolbar">
                    <select class="route-share-embed-size" id="routeShareEmbedSizeSelect">
                        <option value="small">Small</option>
                        <option value="medium" selected>Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>
                <div class="route-share-embed-code-row">
                    <div class="route-share-embed-code" id="routeShareEmbedCodeValue"></div>
                    <button type="button" class="route-share-copy-btn" id="routeShareCopyEmbedBtn">Copy HTML</button>
                </div>
                <textarea class="route-share-embed-box d-none" id="routeShareEmbedValue" readonly></textarea>
                <div class="route-share-embed-preview">
                    <iframe id="routeShareEmbedPreview" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
        <div class="route-share-apps">
            <button type="button" class="route-share-app-btn" id="routeShareWhatsappBtn">
                <span class="route-share-app-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 11.9C20 16.2 16.4 19.7 12 19.7C10.7 19.7 9.5 19.4 8.4 18.8L4.5 19.8L5.6 16.1C4.9 14.9 4.5 13.4 4.5 11.9C4.5 7.6 8.1 4.1 12.5 4.1C16.4 4.1 20 7.6 20 11.9Z" stroke="#22c55e" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.1 8.9C9.3 8.5 9.5 8.5 9.8 8.5C10 8.5 10.2 8.5 10.4 8.5C10.6 8.5 10.9 8.4 11.2 9.1C11.5 9.8 12.1 11.1 12.2 11.2C12.3 11.4 12.4 11.6 12.2 11.8C12 12 11.9 12.2 11.7 12.3C11.6 12.5 11.4 12.6 11.6 12.9C11.8 13.2 12.4 14 13.1 14.6C14 15.3 14.7 15.5 15 15.6C15.3 15.7 15.5 15.7 15.7 15.4C15.9 15.2 16.4 14.6 16.6 14.3C16.8 14.1 17 14.1 17.3 14.2C17.6 14.3 19.1 15 19.4 15.2C19.7 15.3 19.9 15.5 20 15.6C20.1 15.7 20.1 16.4 19.6 17C19.1 17.6 18.2 18 17.1 18C16 18 15.1 17.8 13.4 17.1C11.7 16.4 10.5 15 9.8 14.2C9.1 13.4 8.3 12.1 8.3 10.8C8.3 9.5 8.9 9 9.1 8.9Z" fill="#22c55e"/></svg>
                </span>
                <span>WhatsApp</span>
            </button>
            <button type="button" class="route-share-app-btn" id="routeShareXBtn">
                <span class="route-share-app-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 4L19 20" stroke="#111827" stroke-width="2" stroke-linecap="round"/><path d="M19 4L5 20" stroke="#111827" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <span>X</span>
            </button>
            <button type="button" class="route-share-app-btn" id="routeShareGmailBtn">
                <span class="route-share-app-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7.5L12 13.5L20 7.5" stroke="#0f8ea2" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 6H18.5C19.3284 6 20 6.67157 20 7.5V16.5C20 17.3284 19.3284 18 18.5 18H5.5C4.67157 18 4 17.3284 4 16.5V7.5C4 6.67157 4.67157 6 5.5 6Z" stroke="#0f8ea2" stroke-width="1.8"/></svg>
                </span>
                <span>Gmail</span>
            </button>
        </div>
    </div>
</div>

<div class="route-send-modal d-none" id="routeSendModal" aria-hidden="true">
    <div class="route-send-modal-backdrop" id="routeSendModalBackdrop"></div>
    <div class="route-send-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="routeSendModalTitle">
        <div class="route-send-modal-header">
            <h4 class="route-send-modal-title" id="routeSendModalTitle">Send to your phone</h4>
            <button type="button" class="route-send-modal-close" id="routeSendModalCloseBtn" aria-label="Close">&times;</button>
        </div>
        <div class="route-send-modal-body">
            <button type="button" class="route-send-modal-option" id="routeSendDeviceBtn">
                <span class="route-send-modal-option-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 7L13 12L9 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12H13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <span class="route-send-modal-option-text">
                    <span class="route-send-modal-option-title" id="routeSendDeviceTitle">This device</span>
                </span>
            </button>
            <button type="button" class="route-send-modal-option" id="routeSendEmailBtn">
                <span class="route-send-modal-option-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7.5L12 13.5L20 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 6H18.5C19.3284 6 20 6.67157 20 7.5V16.5C20 17.3284 19.3284 18 18.5 18H5.5C4.67157 18 4 17.3284 4 16.5V7.5C4 6.67157 4.67157 6 5.5 6Z" stroke="currentColor" stroke-width="1.8"/></svg>
                </span>
                <span class="route-send-modal-option-text">
                    <span class="route-send-modal-option-title" id="routeSendEmailTitle">Email to you</span>
                    <span class="route-send-modal-option-subtitle" id="routeSendEmailValue">{{ $sendToPhoneEmail ?: 'No email available' }}</span>
                </span>
            </button>
        </div>
        <div class="route-send-modal-footer">
            <div class="route-send-modal-note">
                <a
                    class="route-send-modal-note-link"
                    href="https://support.google.com/maps/answer/11471036"
                    target="_blank"
                    rel="noopener noreferrer"
                >Device not shown? Learn more.</a>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/route-builder.js') }}?v={{ filemtime(public_path('js/route-builder.js')) }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.initRouteBuilder !== 'function') {
            return;
        }

        window.initRouteBuilder({
            formId: @json($formId),
            mapId: 'routeBuilderMap',
            layoutId: 'routeBuilderShell',
            sidebarId: 'routeBuilderSidebar',
            routeJsonInputId: 'route_json',
            submitButtonId: @json($submitButtonId),
            clearAllButtonId: 'clearAllRoutePointsBtn',
            fitRouteButtonId: 'fitRouteBtn',
            recenterButtonId: 'recenterRouteMapBtn',
            openSidebarButtonId: 'openRouteSidebarBtn',
            closeSidebarButtonId: 'closeRouteSidebarBtn',
            introCardId: 'routeMapIntro',
            introSearchInputId: 'routeMapIntroSearchInput',
            introSearchResultsId: 'routeMapIntroSearchResults',
            introOpenButtonId: 'routeMapIntroOpenBtn',
            introBackButtonId: 'routeMapIntroBackBtn',
            introCloseSearchButtonId: 'routeMapIntroCloseSearchBtn',
            introPlannerButtonId: 'routeMapIntroPlannerBtn',
            introPickStartButtonId: 'routeMapIntroPickStartBtn',
            introEmptyStateId: 'routeMapIntroEmptyState',
            introPlaceStateId: 'routeMapIntroPlaceState',
            introPlaceHeroId: 'routeMapIntroPlaceHero',
            introPlaceNameId: 'routeMapIntroPlaceName',
            introPlaceSubnameId: 'routeMapIntroPlaceSubname',
            introPlaceAddressId: 'routeMapIntroPlaceAddress',
            introPlaceMetaId: 'routeMapIntroPlaceMeta',
            introQuickFactsId: 'routeMapIntroQuickFacts',
            introLatValueId: 'routeMapIntroLatValue',
            introLngValueId: 'routeMapIntroLngValue',
            introDirectionsButtonId: 'routeMapIntroDirectionsBtn',
            introUseStartButtonId: 'routeMapIntroUseStartBtn',
            introSaveButtonId: 'routeMapIntroSaveBtn',
            introNearbyButtonId: 'routeMapIntroNearbyBtn',
            introSendButtonId: 'routeMapIntroSendBtn',
            introShareButtonId: 'routeMapIntroShareBtn',
            introClosePlaceButtonId: 'routeMapIntroClosePlaceBtn',
            shareModalId: 'routeShareModal',
            shareModalBackdropId: 'routeShareModalBackdrop',
            shareModalCloseButtonId: 'routeShareModalCloseBtn',
            shareLinkTabButtonId: 'routeShareLinkTabBtn',
            shareEmbedTabButtonId: 'routeShareEmbedTabBtn',
            shareLinkPaneId: 'routeShareLinkPane',
            shareEmbedPaneId: 'routeShareEmbedPane',
            shareEmbedSizeSelectId: 'routeShareEmbedSizeSelect',
            shareEmbedCodeValueId: 'routeShareEmbedCodeValue',
            sharePlaceThumbId: 'routeSharePlaceThumb',
            sharePlaceNameId: 'routeSharePlaceName',
            sharePlaceAddressId: 'routeSharePlaceAddress',
            shareLinkValueId: 'routeShareLinkValue',
            shareCopyLinkButtonId: 'routeShareCopyLinkBtn',
            shareEmbedValueId: 'routeShareEmbedValue',
            shareEmbedPreviewId: 'routeShareEmbedPreview',
            shareCopyEmbedButtonId: 'routeShareCopyEmbedBtn',
            shareWhatsappButtonId: 'routeShareWhatsappBtn',
            shareXButtonId: 'routeShareXBtn',
            shareGmailButtonId: 'routeShareGmailBtn',
            sendToPhoneEmail: @json($sendToPhoneEmail ?? ''),
            sendModalId: 'routeSendModal',
            sendModalBackdropId: 'routeSendModalBackdrop',
            sendModalCloseButtonId: 'routeSendModalCloseBtn',
            sendDeviceButtonId: 'routeSendDeviceBtn',
            sendDeviceTitleId: 'routeSendDeviceTitle',
            sendEmailButtonId: 'routeSendEmailBtn',
            sendEmailTitleId: 'routeSendEmailTitle',
            sendEmailValueId: 'routeSendEmailValue',
            addPickupButtonId: 'addPickupPointBtn',
            customLocationToggleButtonId: 'toggleCustomLocationBtn',
            customLocationPanelId: 'customLocationPanel',
            customLocationNameInputId: 'customLocationName',
            customLocationAddressInputId: 'customLocationAddress',
            customLocationLatInputId: 'customLocationLatInput',
            customLocationLngInputId: 'customLocationLngInput',
            customLocationStatusId: 'customLocationStatus',
            customLocationSearchButtonId: 'searchCustomLocationBtn',
            customLocationPreviewButtonId: 'previewCustomLocationLatLngBtn',
            customLocationSaveButtonId: 'saveCustomLocationBtn',
            pickupsContainerId: 'pickupPointsContainer',
            addDestinationRowId: 'addDestinationRow',
            routeOptionsContainerId: 'routeOptionsContainer',
            mapSelectionStatusId: 'routeMapSelectionStatus',
            startPointPrefix: 'startPoint',
            endPointPrefix: 'endPoint',
            submitUrl: @json($routesActionUrl),
            indexUrl: @json($routesIndexUrl),
            routePreviewUrl: @json(filled(config('services.google_maps.api_key')) ? ($routePreviewUrl ?? null) : null),
            customLocationSearchUrl: @json($customLocationSearchUrl ?? null),
            customLocationStoreUrl: @json($customLocationStoreUrl ?? null),
            googleMapsApiKey: @json(filled(config('services.google_maps.api_key')) ? config('services.google_maps.api_key') : null),
            csrfToken: @json(csrf_token()),
            initialRouteJson: @json($routeRecord->route_json ?? null),
            loadingText: @json($loadingText),
            successText: @json($successText)
        });

    });
</script>
<script>
    (function () {
        const initialSchoolId = @json((string) old('school_id', $routeRecord->school_id ?? $defaultSchoolId ?? ''));
        const initialDriverId = @json((string) old('driver_id', $routeRecord->driver_id ?? ''));
        const initialVehicleId = @json((string) old('bus_id', $routeRecord->bus_id ?? ''));
        const driverVehicleLookupUrlTemplate = @json($driverVehicleLookupUrl ?? '');
        let cachedDriverOptions = [];
        let cachedVehicleOptions = [];
        let vehicleRequestToken = 0;

        function destroyNiceSelect(selectElement) {
            if (!window.jQuery || !selectElement || typeof window.jQuery(selectElement).niceSelect !== 'function') {
                return;
            }

            const $select = window.jQuery(selectElement);

            if ($select.next('.nice-select').length) {
                $select.niceSelect('destroy');
            }

            $select.css('display', 'block');
        }

        function syncNiceSelect(selectElement) {
            destroyNiceSelect(selectElement);
        }

        function showSchoolEmptyAlert() {
            if (typeof window.Swal === 'undefined') {
                window.alert('School add first, after that move further.');
                return;
            }

            window.Swal.fire({
                icon: 'warning',
                title: 'Alert',
                text: 'No schools are currently available. Please add a school first to continue.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        }

        function schoolHasRealOptions() {
            const schoolField = document.getElementById('school_id');
            if (!schoolField || schoolField.tagName !== 'SELECT') {
                return true;
            }

            return Array.from(schoolField.options || []).some(function (option) {
                return String(option.value || '').trim() !== '';
            });
        }

        window.routeVehicleDriverSync = function () {
            syncVehicleForSelectedDriver('');
        };

        function getSelectedSchoolId() {
            const schoolField = document.getElementById('school_id');
            return schoolField && schoolField.value ? String(schoolField.value) : '';
        }

        function renderDriverOptionsBySchool(selectedSchoolId, preferredDriverId) {
            const driverSelect = document.getElementById('driver_id');
            if (!driverSelect) {
                return;
            }

            destroyNiceSelect(driverSelect);
            driverSelect.innerHTML = '';

            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = 'Select Driver';
            driverSelect.appendChild(placeholderOption);

            const scopedDrivers = selectedSchoolId
                ? cachedDriverOptions.filter(function (option) {
                    return String(option.dataset.schoolId || '') === String(selectedSchoolId);
                })
                : cachedDriverOptions.slice();

            scopedDrivers.forEach(function (option) {
                driverSelect.appendChild(cloneOption(option));
            });

            driverSelect.disabled = scopedDrivers.length === 0;

            if (preferredDriverId && scopedDrivers.some(function (option) {
                return option.value === String(preferredDriverId);
            })) {
                driverSelect.value = String(preferredDriverId);
            } else {
                driverSelect.value = '';
            }

            syncNiceSelect(driverSelect);
        }

        function cloneOption(option) {
            const clonedOption = document.createElement('option');
            clonedOption.value = option.value;
            clonedOption.textContent = option.textContent;
            Array.from(option.attributes).forEach(function (attribute) {
                if (attribute.name !== 'selected') {
                    clonedOption.setAttribute(attribute.name, attribute.value);
                }
            });
            return clonedOption;
        }

        function buildDriverVehicleLookupUrl(driverId) {
            if (!driverVehicleLookupUrlTemplate || !driverId) {
                return '';
            }

            return driverVehicleLookupUrlTemplate.replace('__DRIVER__', encodeURIComponent(String(driverId)));
        }

        function renderVehicleOptionsFromList(vehicles, preferredVehicleId, selectedDriverId) {
            const driverSelect = document.getElementById('driver_id');
            const vehicleSelect = document.getElementById('bus_id');

            if (!driverSelect || !vehicleSelect) {
                return;
            }

            destroyNiceSelect(driverSelect);
            destroyNiceSelect(vehicleSelect);

            vehicleSelect.innerHTML = '';

            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            if (!selectedDriverId) {
                placeholderOption.textContent = 'Select Driver First';
            } else if (!Array.isArray(vehicles) || vehicles.length === 0) {
                placeholderOption.textContent = 'No vehicle assigned to selected driver';
            } else {
                placeholderOption.textContent = 'Select Vehicle';
            }
            vehicleSelect.appendChild(placeholderOption);

            const normalizedVehicles = Array.isArray(vehicles) ? vehicles : [];
            normalizedVehicles.forEach(function (vehicle) {
                const option = document.createElement('option');
                option.value = String(vehicle.id || '');
                option.textContent = String(vehicle.vehicle_number || vehicle.vehicleNumber || 'Vehicle');
                option.dataset.driverId = String(vehicle.driver_id || selectedDriverId || '');
                vehicleSelect.appendChild(option);
            });

            const normalizedPreferredVehicleId = preferredVehicleId ? String(preferredVehicleId) : '';
            const hasPreferredSelection = normalizedVehicles.some(function (vehicle) {
                return String(vehicle.id || '') === normalizedPreferredVehicleId;
            });

            if (hasPreferredSelection) {
                vehicleSelect.value = normalizedPreferredVehicleId;
            } else if (normalizedVehicles.length === 1) {
                vehicleSelect.value = String(normalizedVehicles[0].id || '');
            } else {
                vehicleSelect.value = '';
            }

            vehicleSelect.disabled = normalizedVehicles.length === 0;
            syncNiceSelect(vehicleSelect);
        }

        function getFallbackVehicleOptionsForDriver(selectedDriverId) {
            if (!selectedDriverId) {
                return [];
            }

            return cachedVehicleOptions
                .filter(function (option) {
                    const optionDriverId = String(option.dataset.driverId || '');
                    const optionSchoolId = String(option.dataset.schoolId || '');
                    const selectedSchoolId = getSelectedSchoolId();
                    return optionDriverId === String(selectedDriverId)
                        && (!selectedSchoolId || optionSchoolId === String(selectedSchoolId));
                })
                .map(function (option) {
                    return {
                        id: option.value,
                        vehicle_number: option.textContent,
                        driver_id: option.dataset.driverId || selectedDriverId,
                    };
                });
        }

        function syncVehicleForSelectedDriver(preferredVehicleId) {
            const driverSelect = document.getElementById('driver_id');
            if (!driverSelect) {
                return;
            }

            const selectedDriverId = driverSelect.value ? String(driverSelect.value) : '';
            const requestToken = ++vehicleRequestToken;

            if (!selectedDriverId) {
                renderVehicleOptionsFromList([], preferredVehicleId, '');
                return;
            }

            const lookupUrl = buildDriverVehicleLookupUrl(selectedDriverId);
            if (!lookupUrl) {
                renderVehicleOptionsFromList(
                    getFallbackVehicleOptionsForDriver(selectedDriverId),
                    preferredVehicleId,
                    selectedDriverId
                );
                return;
            }

            window.fetch(lookupUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Vehicle lookup failed');
                    }

                    return response.json();
                })
                .then(function (payload) {
                    if (requestToken !== vehicleRequestToken) {
                        return;
                    }

                    const vehicles = Array.isArray(payload && payload.vehicles)
                        ? payload.vehicles
                        : getFallbackVehicleOptionsForDriver(selectedDriverId);

                    renderVehicleOptionsFromList(vehicles, preferredVehicleId, selectedDriverId);
                })
                .catch(function () {
                    if (requestToken !== vehicleRequestToken) {
                        return;
                    }

                    renderVehicleOptionsFromList(
                        getFallbackVehicleOptionsForDriver(selectedDriverId),
                        preferredVehicleId,
                        selectedDriverId
                    );
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const vehicleSelect = document.getElementById('bus_id');
            const driverSelect = document.getElementById('driver_id');
            const schoolField = document.getElementById('school_id');

            if (!vehicleSelect || !driverSelect) {
                return;
            }

            destroyNiceSelect(vehicleSelect);
            destroyNiceSelect(driverSelect);
            cachedDriverOptions = Array.from(driverSelect.querySelectorAll('option')).filter(function (option) {
                return option.value !== '';
            }).map(cloneOption);
            cachedVehicleOptions = Array.from(vehicleSelect.querySelectorAll('option')).filter(function (option) {
                return option.value !== '';
            }).map(cloneOption);
            driverSelect.addEventListener('change', function () {
                syncVehicleForSelectedDriver('');
            });
            driverSelect.addEventListener('input', function () {
                syncVehicleForSelectedDriver('');
            });

            if (schoolField) {
                schoolField.addEventListener('change', function () {
                    renderDriverOptionsBySchool(this.value, '');
                    renderVehicleOptionsFromList([], '', '');
                });
                schoolField.addEventListener('input', function () {
                    renderDriverOptionsBySchool(this.value, '');
                    renderVehicleOptionsFromList([], '', '');
                });
                schoolField.addEventListener('mousedown', function (event) {
                    if (!schoolHasRealOptions()) {
                        event.preventDefault();
                        this.blur();
                        showSchoolEmptyAlert();
                    }
                });
            }

            renderDriverOptionsBySchool(initialSchoolId, initialDriverId);
            syncVehicleForSelectedDriver(initialVehicleId);
        });

        document.addEventListener('change', function (event) {
            if (event.target && event.target.id === 'driver_id') {
                syncVehicleForSelectedDriver('');
            }
        });

        document.addEventListener('click', function (event) {
            const option = event.target && event.target.closest
                ? event.target.closest('.nice-select .option')
                : null;
            const schoolSelect = document.getElementById('school_id');
            const schoolNiceSelect = schoolSelect && schoolSelect.nextElementSibling && schoolSelect.nextElementSibling.classList.contains('nice-select')
                ? schoolSelect.nextElementSibling
                : null;
            const schoolNiceWrapper = event.target && event.target.closest
                ? event.target.closest('.nice-select, .common-select2, .select2-container')
                : null;
            const driverSelect = document.getElementById('driver_id');
            const niceSelect = driverSelect && driverSelect.nextElementSibling && driverSelect.nextElementSibling.classList.contains('nice-select')
                ? driverSelect.nextElementSibling
                : null;

            if (schoolSelect && schoolNiceSelect && schoolNiceWrapper === schoolNiceSelect && !schoolHasRealOptions()) {
                event.preventDefault();
                showSchoolEmptyAlert();
                return;
            }

            const commonSchoolWrapper = schoolSelect && schoolSelect.previousElementSibling && schoolSelect.previousElementSibling.classList.contains('common-select2')
                ? schoolSelect.previousElementSibling
                : null;
            if (schoolSelect && commonSchoolWrapper && schoolNiceWrapper === commonSchoolWrapper && !schoolHasRealOptions()) {
                event.preventDefault();
                event.stopPropagation();
                showSchoolEmptyAlert();
                return;
            }

            if (!option || !niceSelect || !niceSelect.contains(option)) {
                return;
            }

            window.setTimeout(function () {
                syncVehicleForSelectedDriver('');
            }, 0);
        });

        window.addEventListener('load', function () {
            destroyNiceSelect(document.getElementById('bus_id'));
            destroyNiceSelect(document.getElementById('driver_id'));
            syncVehicleForSelectedDriver(initialVehicleId);
            window.setTimeout(function () {
                destroyNiceSelect(document.getElementById('bus_id'));
                destroyNiceSelect(document.getElementById('driver_id'));
                syncVehicleForSelectedDriver(initialVehicleId);
            }, 600);
        });
    })();
</script>
