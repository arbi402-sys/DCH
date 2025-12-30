<!-- Mobile Styles Component -->
<style>
/* Mobile Header */
.mobile-header {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: white;
    padding: 8px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.mobile-header-logo {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.mobile-header-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.mobile-header-search {
    flex: 1;
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    padding: 8px 12px;
    border-radius: 20px;
    gap: 8px;
}

.mobile-header-search input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 14px;
    color: #333;
}

.mobile-header-search input::placeholder {
    color: #999;
}

.mobile-header-search img {
    width: 18px;
    height: 18px;
}

.mobile-header-user {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    text-decoration: none;
}

.mobile-header-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.mobile-header-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.mobile-header-username {
    font-size: 12px;
    font-weight: 600;
    color: #333;
    line-height: 1;
}

.mobile-header-badge {
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 4px;
    background: #4caf50;
    color: white;
    line-height: 1;
}

/* Mobile Bottom Navigation */
.bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #e0e0e0;
    padding: 8px 0;
    z-index: 200;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
    justify-content: space-around;
    align-items: center;
}

.bottom-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px 16px;
    cursor: pointer;
    text-decoration: none;
    color: #666;
    transition: color 0.2s;
    background: transparent;
    border: none;
}

.bottom-nav-item:hover,
.bottom-nav-item.active {
    color: #ea9066;
}

.bottom-nav-item img {
    width: 24px;
    height: 24px;
}

.bottom-nav-item span {
    font-size: 11px;
    font-weight: 500;
}

/* Mobile Upload Modal */
.mobile-upload-modal {
    display: none;
    position: fixed;
    bottom: 70px;
    left: 0;
    right: 0;
    background: white;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    z-index: 199;
    padding: 20px;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

.mobile-upload-modal.show {
    display: block;
    transform: translateY(0);
}

.mobile-upload-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.mobile-upload-option:hover {
    background: #e9ecef;
}

.mobile-upload-option:last-child {
    margin-bottom: 0;
}

.mobile-upload-option img {
    width: 32px;
    height: 32px;
}

.mobile-upload-option-text {
    flex: 1;
}

.mobile-upload-option-title {
    font-size: 15px;
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.mobile-upload-option-desc {
    font-size: 12px;
    color: #666;
}

.mobile-upload-backdrop {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 198;
    pointer-events: none;
}

/* Mobile Notification Panel & User Menu - slide from bottom */
.mobile-panel-backdrop {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 250;
    pointer-events: none;
}

/* Generic mobile modal backdrop for additional modals */
.mobile-modal-backdrop {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 250;
    pointer-events: none;
}

@media (max-width: 768px) {
    .mobile-upload-backdrop.show {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto;
    }

    .mobile-panel-backdrop.show {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto;
    }

    .mobile-modal-backdrop.show {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto;
    }
}

.mobile-notification-panel,
.mobile-user-menu {
    display: none;
    position: fixed;
    bottom: 70px;
    left: 0;
    right: 0;
    background: white;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    z-index: 251;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

.mobile-notification-panel {
    max-height: 70vh;
}

.mobile-user-menu {
    max-height: 50vh;
}

.mobile-notification-panel.show,
.mobile-user-menu.show {
    display: block;
    transform: translateY(0);
}

.mobile-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: white;
    border: none;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    color: #333;
    font-size: 15px;
    font-weight: 500;
}

.mobile-menu-item:hover {
    background: #f8f9fa;
}

/* Mobile Responsive - Apply to all pages */
@media (max-width: 768px) {
    /* Hide desktop navigation */
    nav,
    x-navbar,
    .navbar,
    header,
    .desktop-only,
    .app-sidebar,
    footer {
        display: none !important;
    }

    body > x-navbar,
    body > nav,
    body > header {
        display: none !important;
    }

    /* Show mobile elements */
    .mobile-header {
        display: flex !important;
    }

    .bottom-nav {
        display: flex !important;
    }

    /* Adjust body and container for fixed header/bottom */
    body {
        padding: 0;
        margin: 0;
        overflow-x: hidden;
    }

    .container {
        padding: 0 !important;
        padding-bottom: 70px !important;
        margin: 0 !important;
    }

    /* Content padding */
    main,
    .main-content {
        padding-top: 60px;
        padding-bottom: 70px;
        margin: 0;
    }
}

/* Hide mobile elements on desktop */
@media (min-width: 769px) {
    .mobile-header,
    .mobile-upload-modal,
    .mobile-upload-backdrop,
    .mobile-upload-form-modal,
    .mobile-panel-backdrop,
    .mobile-modal-backdrop,
    .mobile-notification-panel,
    .mobile-user-menu,
    .bottom-nav {
        display: none !important;
    }
}

/* Mobile Upload Form Modal */
@media (max-width: 768px) {
    .mobile-upload-form-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        z-index: 252;
        flex-direction: column;
        overflow: hidden;
    }

    .mobile-upload-form-modal.show {
        display: flex;
    }

    .mobile-form-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px;
        border-bottom: 1px solid #e0e0e0;
        background: white;
        flex-shrink: 0;
    }

    .mobile-form-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #333;
    }
}
</style>
