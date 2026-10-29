document.addEventListener('change', event => {
    const select = event.target;
    if (select instanceof HTMLSelectElement && select.matches('.language-selector select[name="locale"]')) {
        select.form.requestSubmit();
    }
});
