import './bootstrap';
import './lazyload';

// Handle Livewire CSRF token expiration (419 errors)
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                // CSRF token expired - silently reload the page
                preventDefault();
                window.location.reload();
            }
        });
    });
});
