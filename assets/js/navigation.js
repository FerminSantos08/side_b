const publicHeader = document.querySelector('.public-header');

if (publicHeader) {
    const toggle = publicHeader.querySelector('.menu-button');
    const navigation = document.getElementById(toggle.getAttribute('aria-controls'));

    const setMenu = (open) => {
        toggle.setAttribute('aria-expanded', String(open));
        navigation.classList.toggle('is-open', open);
    };

    publicHeader.classList.add('has-menu-toggle');
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        setMenu(open);
        if (open) navigation.querySelector('a').focus();
    });

    publicHeader.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setMenu(false);
            toggle.focus();
        }
    });

    document.addEventListener('click', (event) => {
        if (!publicHeader.contains(event.target)) setMenu(false);
    });

    window.matchMedia('(max-width: 940px)').addEventListener('change', () => setMenu(false));
}
