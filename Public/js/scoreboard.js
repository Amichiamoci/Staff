async function requestScoreboardFullscreen(target) {
    try {
        await target.requestFullscreen({navigationUI: 'hide'});
    } catch {
        // The scoreboard already fills the viewport without native fullscreen.
    }
}

function initScoreboard($board, fullscreenTarget, reload, openedFromCalendar = false) {
    const $status = $board.find('[data-board-status]');
    const $sets = $board.find('[data-board-sets]');
    const $newSet = $board.find('[data-board-new-set]');
    const $fullscreen = $board.find('[data-board-fullscreen]');
    const $retry = $board.find('[data-board-retry]');
    const $reload = $board.find('[data-board-reload]');
    const saved = JSON.parse($board.attr('data-scores'));
    const ids = JSON.parse($board.attr('data-score-ids'));
    const scores = saved.slice();
    const changes = new Set();
    let position = $sets.length ? Math.max(scores.length - 1, 0) : 0;
    let request = null;
    let conflict = false;

    function render() {
        const score = scores[position] ?? ['0', '0'];
        $board.find('[data-board-score]').each(function (side) {
            const value = String(Number(score[side]));
            if ($(this).text().trim() !== value) {
                $(this).text(value);
            }
        });
        $board.find('[data-board-step]').each(function () {
            const $button = $(this);
            const next = Number(score[$button.attr('data-board-side')]) + Number($button.attr('data-board-step'));
            $button.prop('disabled', conflict || next < 0 || next > 99999999);
        });

        if ($sets.length) {
            const setCount = Math.max(scores.length, position + 1);
            for (let set = $sets.children().length; set < setCount; set++) {
                $('<button>', {
                    type: 'button',
                    class: 'btn btn-outline-light',
                    title: 'Mostra il set ' + (set + 1),
                    'data-board-set': set,
                }).appendTo($sets);
            }
            $sets.children().each(function (set) {
                const pair = scores[set];
                const total = pair ? ' · ' + Number(pair[0]) + '–' + Number(pair[1]) : '';
                $(this).text('Set ' + (set + 1) + total)
                    .toggleClass('active', set === position)
                    .attr('aria-pressed', String(set === position));
            });
            $newSet.prop('disabled', conflict || !scores.length || position === scores.length);
        }
    }

    function save() {
        if (!request) {
            if (!changes.size) {
                return;
            }
            const set = changes.values().next().value;
            changes.delete(set);
            request = {
                position: set,
                score_id: ids[set] ?? null,
                previous: saved[set] ?? null,
                score: scores[set],
            };
        }

        $retry.prop('hidden', true);
        $status.removeClass('text-success text-success-emphasis text-warning').text('Salvataggio in corso…');
        $.ajax({
            method: 'POST',
            url: BasePath + '/sport/match_board_score',
            dataType: 'json',
            timeout: 10000,
            data: {
                match_id: $board.attr('data-match'),
                csrf_token: $board.attr('data-csrf'),
                score_change: JSON.stringify(request),
            },
        }).done(function (response) {
            if (response.result !== 'success' || !Number.isInteger(response.id)) {
                $status.addClass('text-warning').text('Salvataggio non confermato.');
                $retry.prop('hidden', false);
                return;
            }
            saved[request.position] = request.score;
            ids[request.position] = response.id;
            request = null;
            if (changes.size) {
                save();
            } else {
                $status.addClass('text-success text-success-emphasis').text('Punteggio salvato');
            }
            render();
        }).fail(function (error) {
            conflict = error.responseJSON?.conflict === true;
            $status.addClass('text-warning').text(conflict
                ? 'La partita è cambiata sul server. Ricarica per aggiornare il segnapunti.'
                : 'Salvataggio non confermato. Riprova per inviare i punti in attesa.');
            $retry.prop('hidden', conflict);
            $reload.prop('hidden', !conflict);
            if (conflict) {
                render();
            }
        });
    }

    $board.on('click', '[data-board-step]', function () {
        const $button = $(this);
        const side = Number($button.attr('data-board-side'));
        const pair = (scores[position] ?? ['0', '0']).map(value => String(Number(value)));
        pair[side] = String(Number(pair[side]) + Number($button.attr('data-board-step')));
        scores[position] = pair;
        changes.add(position);
        $board.trigger('scoreboard:edit');
        if (!request) {
            save();
        }
        render();
    });

    $sets.on('click', '[data-board-set]', function () {
        position = Number($(this).attr('data-board-set'));
        render();
    });

    $newSet.on('click', function () {
        position = scores.length;
        render();
        $sets.children().last().trigger('focus');
    });

    $retry.on('click', save);
    $reload.on('click', reload);
    $board.data('hasPendingScores', () => request !== null || changes.size > 0);

    if (document.fullscreenEnabled) {
        let triedFullscreen = openedFromCalendar;

        function enterFullscreen() {
            triedFullscreen = true;
            requestScoreboardFullscreen(fullscreenTarget);
        }

        $fullscreen.prop('hidden', false).on('click', function () {
            if (document.fullscreenElement === fullscreenTarget) {
                document.exitFullscreen().catch(() => {});
            } else {
                enterFullscreen();
            }
        });

        function updateFullscreenButton() {
            const active = document.fullscreenElement === fullscreenTarget;
            $fullscreen.attr('aria-pressed', String(active))
                .find('i').toggleClass('bi-arrows-fullscreen', !active).toggleClass('bi-fullscreen-exit', active);
        }

        $(fullscreenTarget).off('fullscreenchange.scoreboard').on('fullscreenchange.scoreboard', updateFullscreenButton);
        updateFullscreenButton();

        $board.on('click', function (event) {
            if (!triedFullscreen && event.originalEvent?.isTrusted
                && !$(event.target).closest('a, [data-board-fullscreen]').length) {
                enterFullscreen();
            }
        });
    }

    render();
}

(() => {
    function hasPendingScores() {
        return $('#scoreboard').data('hasPendingScores')?.();
    }

    function confirmDiscard() {
        return !hasPendingScores() || window.confirm('Ci sono punti in attesa di salvataggio. Vuoi continuare?');
    }

    $(window).on('beforeunload.scoreboard', function (event) {
        if (hasPendingScores()) {
            event.preventDefault();
            event.originalEvent.returnValue = true;
        }
    });

    const $board = $('#scoreboard');
    if ($board.length) {
        initScoreboard($board, $('#scoreboard-view')[0], () => window.location.reload());
    }

    $(document).on('click', '[data-board-open]', function (event) {
        if (event.isDefaultPrevented() || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }
        const link = this;
        const matchCard = link.closest('article');
        const results = matchCard?.querySelector('[data-match-results]');
        let edited = false;
        const $dialog = $('<dialog>', {
            class: 'match-scoreboard-dialog border-0 p-0',
            'aria-label': 'Segnapunti',
            'data-bs-theme': 'dark',
        });
        const dialog = $dialog[0];
        if (!dialog.showModal) {
            return;
        }
        event.preventDefault();

        const $view = $('<div>', {class: 'match-scoreboard-view d-flex flex-column bg-body text-body'});
        const view = $view[0];
        $dialog.append($view).appendTo($(link).closest('#swup'));
        dialog.showModal();

        if (document.fullscreenEnabled) {
            // Request fullscreen before fetching the board, while the opening click still grants activation.
            requestScoreboardFullscreen(view);
        }

        function showBoard(content) {
            $view.empty().append(content);
            const $loadedBoard = $view.find('#scoreboard');
            if ($loadedBoard.length) {
                initScoreboard($loadedBoard, view, () => {
                    if (confirmDiscard()) {
                        loadBoard();
                    }
                }, true);
            }
            $view.find('[data-board-back]').trigger('focus');
        }

        function loadBoard() {
            $(view).off('fullscreenchange.scoreboard');
            const $close = $('<button>', {
                type: 'button',
                class: 'btn btn-outline-light',
                title: 'Chiudi il segnapunti',
                text: 'Chiudi',
            }).on('click', () => dialog.close());
            const $retry = $('<button>', {
                type: 'button',
                class: 'btn btn-primary',
                title: 'Riprova a caricare il segnapunti',
                text: 'Riprova',
                hidden: true,
            }).on('click', loadBoard);
            const $status = $('<p>', {class: 'px-3', role: 'status', text: 'Caricamento del segnapunti…'});
            $view.empty().append($('<div>', {class: 'd-flex justify-content-end gap-2 m-2'}).append($retry, $close), $status);
            $close.trigger('focus');

            function showError() {
                $status.text('Impossibile caricare il segnapunti.');
                $retry.prop('hidden', false);
            }

            $.ajax({url: link.href, dataType: 'html', timeout: 10000}).done(function (html) {
                if (!dialog.open || !dialog.isConnected) {
                    return;
                }
                const page = new DOMParser().parseFromString(html, 'text/html');
                const content = page.getElementById('scoreboard-view');
                if (content) {
                    showBoard($(content).contents());
                } else {
                    showError();
                }
            }).fail(showError);
        }

        $dialog.on('scoreboard:edit', () => {
            edited = true;
        }).on('click', '[data-board-back]', function (backEvent) {
            backEvent.preventDefault();
            if (confirmDiscard()) {
                dialog.close();
            }
        }).on('cancel', function (cancelEvent) {
            if (!confirmDiscard()) {
                cancelEvent.preventDefault();
            }
        }).one('close', function () {
            if (document.fullscreenElement === view) {
                document.exitFullscreen().catch(() => {});
            }
            $dialog.remove();
            $(link).trigger('focus');

            if (edited && results?.isConnected) {
                $(results).find('[data-board-template]').remove();
                const calendarUrl = window.location.href;
                $.ajax({url: calendarUrl, dataType: 'html', timeout: 10000}).done(function (html) {
                    if (!results.isConnected || window.location.href !== calendarUrl) {
                        return;
                    }
                    const page = new DOMParser().parseFromString(html, 'text/html');
                    const updated = page.getElementById(matchCard.id)?.querySelector('[data-match-results]');
                    if (!updated) {
                        window.location.reload();
                        return;
                    }
                    $(results).replaceWith(updated);
                }).fail(function () {
                    if (results.isConnected && window.location.href === calendarUrl) {
                        window.location.reload();
                    }
                });
            }
        });

        const template = results?.querySelector('[data-board-template]');
        if (template) {
            showBoard(template.content.cloneNode(true));
        } else {
            loadBoard();
        }
    });
})();
