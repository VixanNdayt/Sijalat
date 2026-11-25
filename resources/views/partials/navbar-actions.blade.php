<div class="navbar-actions">
    @include('partials.theme-toggle')

    <div class="notif-wrap">
        <button class="notif-btn"
                id="notifBtn"
                type="button"
                aria-label="Buka notifikasi"
                aria-expanded="false"
                aria-controls="notifPanel"
                data-notif-url="{{ $notifUrl ?? route('notifikasi') }}">
            <x-icon name="bell" size="18" />
            <span class="notif-badge" id="notifBadge" hidden></span>
        </button>

        <div class="notif-panel" id="notifPanel" hidden>
            <div class="notif-head">
                <strong>Notifikasi</strong>
                <button class="notif-refresh" id="notifRefresh" type="button" aria-label="Muat ulang notifikasi">
                    <x-icon name="refresh" size="14" />
                </button>
            </div>
            <div class="notif-body" id="notifBody" aria-live="polite">
                <p class="notif-empty">Memuat notifikasi...</p>
            </div>
            <div class="notif-foot" id="notifFoot" hidden></div>
        </div>
    </div>

    <button class="toggle-btn" id="toggleBtn" aria-label="Buka menu"><x-icon name="menu" size="18" /></button>
</div>

@once
    <script src="{{ asset('js/notif.js') }}" defer></script>
@endonce
