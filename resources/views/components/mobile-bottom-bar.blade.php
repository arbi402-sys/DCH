<!-- Mobile Bottom Navigation -->
<div class="bottom-nav">
    <a href="/explore" class="bottom-nav-item {{ request()->is('explore') || request()->is('/') ? 'active' : '' }}">
        <img src="/images/icon/home.png" alt="Home">
        <span>Home</span>
    </a>
    <button class="bottom-nav-item" onclick="showMobileUpload()" type="button">
        <img src="/images/icon/upload.png" alt="Upload">
        <span>Upload</span>
    </button>
    <button class="bottom-nav-item" onclick="toggleNotificationPanel()" type="button">
        <img src="/images/icon/notifikasi.png" alt="Notification">
        <span>Notifikasi</span>
    </button>
    <button class="bottom-nav-item" onclick="showMobileUserMenu()" type="button">
        <img src="/images/icon/profil.png" alt="Profil">
        <span>Profil</span>
    </button>
</div>

<!-- Mobile Upload Modal -->
<div class="mobile-upload-backdrop" id="mobileUploadBackdrop" onclick="hideMobileUpload()"></div>
<div class="mobile-upload-modal" id="mobileUploadModal">
    @auth
    <div class="mobile-upload-option" onclick="showMobileArtworkForm(); hideMobileUpload();">
        <img src="/images/icon/karya.png" alt="Karya">
        <div class="mobile-upload-option-text">
            <div class="mobile-upload-option-title">Upload Karya</div>
            <div class="mobile-upload-option-desc">Bagikan karya kreatif Anda</div>
        </div>
    </div>
    <div class="mobile-upload-option" onclick="showMobileArticleForm(); hideMobileUpload();">
        <img src="/images/icon/artikel.png" alt="Artikel">
        <div class="mobile-upload-option-text">
            <div class="mobile-upload-option-title">Tulis Artikel</div>
            <div class="mobile-upload-option-desc">Berbagi cerita dan tutorial</div>
        </div>
    </div>
    @else
    <div class="mobile-upload-option" onclick="window.location.href='/login'; hideMobileUpload();">
        <img src="/images/icon/upload.png" alt="Login">
        <div class="mobile-upload-option-text">
            <div class="mobile-upload-option-title">Login Required</div>
            <div class="mobile-upload-option-desc">Silakan login untuk upload</div>
        </div>
    </div>
    @endauth
</div>

@auth
<!-- Mobile Upload Artwork Form Modal -->
<div class="mobile-modal-backdrop" id="mobileArtworkBackdrop" onclick="hideMobileArtworkForm()"></div>
<div class="mobile-upload-form-modal" id="mobileArtworkFormModal">
    <div class="mobile-form-header">
        <h3>Upload Karya</h3>
        <button onclick="hideMobileArtworkForm()" style="background: transparent; border: none; font-size: 24px; cursor: pointer; padding: 0; width: 32px; height: 32px;">&times;</button>
    </div>
    <form method="POST" action="{{ route('upload.artwork') }}" enctype="multipart/form-data" style="flex: 1; overflow-y: auto; padding: 16px;">
        @csrf
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Title</label>
            <input name="title" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;">Category</label>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @php
                    $desired = ['graphic','illustration','photography','typography','uiux','identity','semua'];
                @endphp
                @foreach($desired as $slug)
                    @php $c = \App\Models\Category::where('slug', $slug)->first(); @endphp
                    @if($c)
                        <label style="display: flex; align-items: center; gap: 6px; padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 20px; cursor: pointer; background: #fff; transition: all 0.2s; font-size: 13px;">
                            <input type="radio" name="category_id" value="{{ $c->id }}" style="cursor: pointer;">
                            <span>{{ $c->name }}</span>
                        </label>
                    @endif
                @endforeach
            </div>
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Image</label>
            <input type="file" name="image" accept="image/*" required style="width: 100%; padding: 8px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Description</label>
            <textarea name="description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
        </div>
        <div style="display: flex; gap: 8px; padding-top: 8px;">
            <button type="button" onclick="hideMobileArtworkForm()" style="flex: 1; padding: 12px; background: #f0f0f0; color: #333; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
            <button type="submit" style="flex: 1; padding: 12px; background: #ff6ec4; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Upload</button>
        </div>
    </form>
</div>

<!-- Mobile Upload Article Form Modal -->
<div class="mobile-modal-backdrop" id="mobileArticleBackdrop" onclick="hideMobileArticleForm()"></div>
<div class="mobile-upload-form-modal" id="mobileArticleFormModal">
    <div class="mobile-form-header">
        <h3>Tulis Artikel</h3>
        <button onclick="hideMobileArticleForm()" style="background: transparent; border: none; font-size: 24px; cursor: pointer; padding: 0; width: 32px; height: 32px;">&times;</button>
    </div>
    <form method="POST" action="{{ route('upload.article') }}" enctype="multipart/form-data" style="flex: 1; overflow-y: auto; padding: 16px;">
        @csrf
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Title</label>
            <input name="title" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Cover Image (optional)</label>
            <input type="file" name="image" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Body</label>
            <textarea name="body" rows="10" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
        </div>
        <div style="display: flex; gap: 8px; padding-top: 8px;">
            <button type="button" onclick="hideMobileArticleForm()" style="flex: 1; padding: 12px; background: #f0f0f0; color: #333; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
            <button type="submit" style="flex: 1; padding: 12px; background: #ff6ec4; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Publish</button>
        </div>
    </form>
</div>
@endauth

<!-- Mobile Notification Panel -->
<div class="mobile-panel-backdrop" id="mobileNotifBackdrop" onclick="hideMobileNotification()"></div>
<div class="mobile-notification-panel" id="mobileNotificationPanel">
    <div style="padding: 16px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Notifikasi</h3>
        <button onclick="hideMobileNotification()" style="background: transparent; border: none; font-size: 24px; cursor: pointer;">&times;</button>
    </div>
    <div id="mobileNotificationList" style="overflow-y: auto; max-height: calc(70vh - 60px); padding-bottom: 10px;">
        <!-- Notifications will be loaded here -->
        <div style="padding: 20px; text-align: center; color: #999;">Loading notifications...</div>
    </div>
</div>

<!-- Mobile User Menu -->
<div class="mobile-panel-backdrop" id="mobileUserBackdrop" onclick="hideMobileUserMenu()"></div>
<div class="mobile-user-menu" id="mobileUserMenu">
    <div style="padding: 16px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Menu</h3>
        <button onclick="hideMobileUserMenu()" style="background: transparent; border: none; font-size: 24px; cursor: pointer;">&times;</button>
    </div>
    <div style="padding: 16px;">
        @auth
        <a href="{{ route('profile.show', auth()->user()->username ?? auth()->id()) }}" class="mobile-menu-item">
            <img src="/images/icon/profil.png" alt="Profile" style="width: 24px; height: 24px;">
            <span>Profile</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="mobile-menu-item" style="width: 100%; text-align: left;">
                <img src="/images/icon/logout.png" alt="Logout" style="width: 24px; height: 24px;">
                <span>Logout</span>
            </button>
        </form>
        @else
        <a href="/login" class="mobile-menu-item">
            <img src="/images/icon/profil.png" alt="Login" style="width: 24px; height: 24px;">
            <span>Login</span>
        </a>
        @endauth
    </div>
</div>

<script>
function closeAllMobileModals() {
    // Close upload modal
    const uploadModal = document.getElementById('mobileUploadModal');
    const uploadBackdrop = document.getElementById('mobileUploadBackdrop');
    if (uploadModal && uploadBackdrop) {
        uploadBackdrop.classList.remove('show');
        uploadModal.classList.remove('show');
    }
    
    // Close notification panel
    const notifPanel = document.getElementById('mobileNotificationPanel');
    const notifBackdrop = document.getElementById('mobileNotifBackdrop');
    if (notifPanel && notifBackdrop) {
        notifBackdrop.classList.remove('show');
        notifPanel.classList.remove('show');
    }
    
    // Close user menu
    const userMenu = document.getElementById('mobileUserMenu');
    const userBackdrop = document.getElementById('mobileUserBackdrop');
    if (userMenu && userBackdrop) {
        userBackdrop.classList.remove('show');
        userMenu.classList.remove('show');
    }
}

function showMobileUpload() {
    closeAllMobileModals();
    const modal = document.getElementById('mobileUploadModal');
    const backdrop = document.getElementById('mobileUploadBackdrop');
    if (modal && backdrop) {
        backdrop.classList.add('show');
        modal.classList.add('show');
    }
}

function hideMobileUpload() {
    const modal = document.getElementById('mobileUploadModal');
    const backdrop = document.getElementById('mobileUploadBackdrop');
    if (modal && backdrop) {
        backdrop.classList.remove('show');
        modal.classList.remove('show');
    }
}

function toggleNotificationPanel() {
    const panel = document.getElementById('mobileNotificationPanel');
    const backdrop = document.getElementById('mobileNotifBackdrop');
    if (panel && backdrop) {
        const isOpen = panel.classList.contains('show');
        if (isOpen) {
            hideMobileNotification();
        } else {
            closeAllMobileModals();
            backdrop.classList.add('show');
            panel.classList.add('show');
            loadMobileNotifications();
        }
    }
}

function hideMobileNotification() {
    const panel = document.getElementById('mobileNotificationPanel');
    const backdrop = document.getElementById('mobileNotifBackdrop');
    if (panel && backdrop) {
        backdrop.classList.remove('show');
        panel.classList.remove('show');
    }
}

function showMobileUserMenu() {
    closeAllMobileModals();
    const menu = document.getElementById('mobileUserMenu');
    const backdrop = document.getElementById('mobileUserBackdrop');
    if (menu && backdrop) {
        backdrop.classList.add('show');
        menu.classList.add('show');
    }
}

function hideMobileUserMenu() {
    const menu = document.getElementById('mobileUserMenu');
    const backdrop = document.getElementById('mobileUserBackdrop');
    if (menu && backdrop) {
        backdrop.classList.remove('show');
        menu.classList.remove('show');
    }
}

function loadMobileNotifications() {
    const list = document.getElementById('mobileNotificationList');
    if (!list) return;
    
    fetch('/api/notifications')
        .then(res => res.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                list.innerHTML = data.notifications.map(n => `
                    <div style="padding: 12px 16px; border-bottom: 1px solid #f0f0f0; display: flex; gap: 12px;">
                        <img src="${n.avatar || '/images/icon/profil.png'}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div style="flex: 1;">
                            <div style="font-size: 14px; color: #333;">${n.message}</div>
                            <div style="font-size: 12px; color: #999; margin-top: 4px;">${n.time}</div>
                        </div>
                    </div>
                `).join('');
            } else {
                list.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Tidak ada notifikasi</div>';
            }
        })
        .catch(() => {
            list.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Tidak ada notifikasi</div>';
        });
}

function showMobileArtworkForm() {
    const modal = document.getElementById('mobileArtworkFormModal');
    const backdrop = document.getElementById('mobileArtworkBackdrop');
    if (modal && backdrop) {
        backdrop.classList.add('show');
        modal.classList.add('show');
    }
}

function hideMobileArtworkForm() {
    const modal = document.getElementById('mobileArtworkFormModal');
    const backdrop = document.getElementById('mobileArtworkBackdrop');
    if (modal && backdrop) {
        backdrop.classList.remove('show');
        modal.classList.remove('show');
    }
}

function showMobileArticleForm() {
    const modal = document.getElementById('mobileArticleFormModal');
    const backdrop = document.getElementById('mobileArticleBackdrop');
    if (modal && backdrop) {
        backdrop.classList.add('show');
        modal.classList.add('show');
    }
}

function hideMobileArticleForm() {
    const modal = document.getElementById('mobileArticleFormModal');
    const backdrop = document.getElementById('mobileArticleBackdrop');
    if (modal && backdrop) {
        backdrop.classList.remove('show');
        modal.classList.remove('show');
    }
}
</script>
