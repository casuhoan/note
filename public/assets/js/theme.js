document.addEventListener('DOMContentLoaded', () => {
    const themeSwitch = document.querySelector('.theme-switch');
    const themeSwitchLabel = document.querySelector('#themeSwitchLabel');
    const doc = document.documentElement;

    const applyTheme = (theme) => {
        doc.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (theme === 'dark') {
            themeSwitch.checked = true;
            themeSwitchLabel.textContent = '☀️';
        } else {
            themeSwitch.checked = false;
            themeSwitchLabel.textContent = '🌙';
        }
    };

    themeSwitch.addEventListener('change', () => {
        const newTheme = themeSwitch.checked ? 'dark' : 'light';
        applyTheme(newTheme);
        // TODO: Inviare la preferenza al backend per salvarla nel profilo utente
    });

    // Applica il tema salvato (o quello dal PHP) al caricamento
    const currentTheme = doc.getAttribute('data-theme') || 'light';
    applyTheme(currentTheme);
});

