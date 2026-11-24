$(function () {
    const feedbackTimers = new WeakMap();

    $(document).on('click', '[data-copy-ip]', async function () {
        const $feedback = $(this).siblings('[role="status"]');
        clearTimeout(feedbackTimers.get($feedback[0]));
        $feedback.empty();

        try {
            await navigator.clipboard.writeText($(this).text().trim());
            $feedback.text('IP copiato');
        } catch {
            $feedback.text('Copia non riuscita');
        }

        clearTimeout(feedbackTimers.get($feedback[0]));
        feedbackTimers.set($feedback[0], setTimeout(() => {
            $feedback.empty();
        }, 2500));
    });
});
