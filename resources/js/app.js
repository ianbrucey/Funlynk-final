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
