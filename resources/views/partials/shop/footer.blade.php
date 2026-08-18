<footer class="mfa-footer" id="kapcsolat">
    <div class="container mfa-footer-inner">
        <div class="mfa-footer-item">
            <span class="mfa-footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </span>
            <div>
                <strong>Kérdése van?</strong>
                <span>Keressen minket bizalommal!</span>
            </div>
        </div>
        <a href="tel:+36709411982" class="mfa-footer-item">
            <span class="mfa-footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
            </span>
            <div>
                <strong>Telefon</strong>
                <span>+36-70-941-1982</span>
            </div>
        </a>
        <a href="mailto:{{ config('shop.store_email') ?: 'csomor@magyarfa.hu' }}" class="mfa-footer-item">
            <span class="mfa-footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z" fill="none"/><path d="m22 6-10 7L2 6"/><path d="M2 6h20v12H2z"/></svg>
            </span>
            <div>
                <strong>E-mail</strong>
                <span>{{ config('shop.store_email') ?: 'csomor@magyarfa.hu' }}</span>
            </div>
        </a>
        <div class="mfa-footer-item">
            <span class="mfa-footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            </span>
            <div>
                <strong>Címünk</strong>
                <span>2141 Csömör, Major út</span>
            </div>
        </div>
    </div>
    <div class="mfa-footer-copyright">
        &copy; {{ date('Y') }} Magyar Fa – Faanyag kereskedés. Minden jog fenntartva.
    </div>
</footer>
