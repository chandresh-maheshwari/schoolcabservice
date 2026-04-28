<style>
    .route-builder-layout {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 1rem;
        align-items: stretch;
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

    .route-direction-row:last-child .route-direction-marker-col::after,
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

    .route-map-toolbar-text {
        font-size: 0.82rem;
        color: #64748b;
    }

    .route-map-stage {
        position: relative;
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
                    <label><b>Route Name</b> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $routeRecord->name ?? '') }}">
                    <span class="error-message text-danger"></span>
                </div>

                <div class="form-group">
                    <label><b>Vehicle</b> <span class="text-danger">*</span></label>
                    <select class="form-control" name="bus_id" id="bus_id">
                        <option value="">Select Vehicle</option>
                        @foreach ($buses as $bus)
                            <option value="{{ $bus->id }}" {{ (int) old('bus_id', $routeRecord->bus_id ?? 0) === (int) $bus->id ? 'selected' : '' }}>
                                {{ $bus->vehicle_number }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error-message text-danger"></span>
                </div>

                <div class="form-group">
                    <label><b>Driver</b> <span class="text-danger">*</span></label>
                    <select class="form-control" name="driver_id" id="driver_id">
                        <option value="">Select Driver</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ (int) old('driver_id', $routeRecord->driver_id ?? 0) === (int) $driver->id ? 'selected' : '' }}>
                                {{ $driver->driver_name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error-message text-danger"></span>
                </div>

                <div class="route-builder-layout">
                    <div class="route-directions-sidebar">
                        <div class="route-directions-header">
                            <h5 class="route-directions-title">Route Points</h5>
                            <div class="route-directions-subtitle">Search, reorder destinations, or pick directly from map.</div>
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
                            <div class="route-map-toolbar-text" id="routeMapSelectionStatus">Search a place or click the add buttons, then choose points on map.</div>
                            <div class="d-flex gap-2 flex-wrap justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="fitRouteBtn">Focus Route</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllRoutePointsBtn">Clear All</button>
                            </div>
                        </div>
                        <div class="route-map-stage">
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/route-builder.js') }}?v={{ filemtime(public_path('js/route-builder.js')) }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initRouteBuilder({
            formId: @json($formId),
            mapId: 'routeBuilderMap',
            routeJsonInputId: 'route_json',
            submitButtonId: @json($submitButtonId),
            clearAllButtonId: 'clearAllRoutePointsBtn',
            fitRouteButtonId: 'fitRouteBtn',
            recenterButtonId: 'recenterRouteMapBtn',
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
