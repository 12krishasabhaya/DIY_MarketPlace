/**
 * Global form validation logic
 * Uses Bootstrap's HTML5 form validation styles.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    const forms = document.querySelectorAll('.needs-validation, form:not(.no-validate)');

    // Loop over them and prevent submission
    Array.from(forms).forEach(form => {
        // Enforce novalidate on forms so the browser doesn't show default tooltips,
        // letting Bootstrap handle the UI instead.
        if (!form.hasAttribute('novalidate')) {
            form.setAttribute('novalidate', '');
        }

        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
});
