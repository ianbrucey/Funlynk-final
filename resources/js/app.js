import './bootstrap';
import './notifications';
import './capacitor-navigation';

// After a Livewire action (e.g. submit) re-renders with validation errors,
// scroll the first error into view so the user sees why nothing happened.
document.addEventListener('livewire:init', () => {
    Livewire.hook('commit', ({ commit, succeed }) => {
        if (!commit.calls || commit.calls.length === 0) {
            return;
        }
        succeed(() => {
            setTimeout(() => {
                const error = document.querySelector('[data-field-error]');
                if (error) {
                    error.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 50);
        });
    });
});

let navLoadingTimer = null;

function setNavLoading(visible) {
    const overlay = document.getElementById('nav-loading-overlay');
    if (!overlay) {
        return;
    }
    overlay.classList.toggle('hidden', !visible);
    overlay.classList.toggle('flex', visible);
    clearTimeout(navLoadingTimer);
    if (visible) {
        navLoadingTimer = setTimeout(() => setNavLoading(false), 10000);
    }
}

document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }
    const link = event.target.closest('nav a[href]');
    if (!link || link.target === '_blank' || link.hasAttribute('download')) {
        return;
    }
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || (url.pathname === window.location.pathname && url.search === window.location.search)) {
        return;
    }
    setNavLoading(true);
});

document.addEventListener('livewire:navigated', () => setNavLoading(false));
window.addEventListener('pageshow', () => setNavLoading(false));
