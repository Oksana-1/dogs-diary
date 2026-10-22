const toggle = document.querySelector('.header-menu-toggle');
const panel = document.getElementById(toggle?.getAttribute('aria-controls'));

if (toggle && panel) {
    const mobile = window.matchMedia('(max-width: 799px)');

    const setExpanded = (expanded) => {
        toggle.setAttribute('aria-expanded', String(expanded));
        panel.hidden = !expanded;
    };

    const syncLayout = () => {
        const focusedToggle = document.activeElement === toggle;
        const focusedPanel = panel.contains(document.activeElement);

        toggle.hidden = !mobile.matches;
        setExpanded(!mobile.matches);

        if (mobile.matches && focusedPanel) {
            toggle.focus();
        } else if (!mobile.matches && focusedToggle) {
            panel.querySelector('nav a')?.focus();
        }
    };

    toggle.addEventListener('click', () => {
        setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
    });

    toggle.closest('header').addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobile.matches && !panel.hidden) {
            event.preventDefault();
            setExpanded(false);
            toggle.focus();
        }
    });

    mobile.addEventListener('change', syncLayout);
    syncLayout();
}
