<!-- Mobile Header Component -->
<div class="mobile-header">
    <a href="/explore" class="mobile-header-logo">
        <img src="/images/image-3-148.png" alt="Logo">
    </a>
    <form action="/search" method="GET" class="mobile-header-search">
        <img src="/images/icon/search.png" alt="Search">
        <input type="text" name="q" placeholder="Search...">
    </form>
    @auth
    <a href="{{ route('profile.show', auth()->user()->username ?? auth()->id()) }}" class="mobile-header-user">
        <img src="{{ auth()->user()->avatar_url ?? '/images/icon/profil.png' }}" alt="Avatar" class="mobile-header-avatar">
        <div class="mobile-header-info">
            <div class="mobile-header-username">{{ Str::limit(auth()->user()->username ?? auth()->user()->name, 8) }}</div>
            @if(auth()->user()->role)
            <div class="mobile-header-badge">{{ ucfirst(auth()->user()->role) }}</div>
            @endif
        </div>
    </a>
    @else
    <a href="/login" class="mobile-header-user">
        <img src="/images/icon/profil.png" alt="Avatar" class="mobile-header-avatar">
    </a>
    @endauth
</div>
