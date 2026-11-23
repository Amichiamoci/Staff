$(function () {
    const feedbackTimers = new WeakMap();

    $('[data-copy-ip]').on('click', async function () {
        const feedback = $(this).siblings('[role="status"]')[0];
        clearTimeout(feedbackTimers.get(feedback));
        feedback.textContent = '';

        try {
            await navigator.clipboard.writeText(this.textContent.trim());
            feedback.textContent = 'IP copiato';
        } catch {
            feedback.textContent = 'Copia non riuscita';
        }

        clearTimeout(feedbackTimers.get(feedback));
        feedbackTimers.set(feedback, setTimeout(() => {
            feedback.textContent = '';
        }, 2500));
    });
});
