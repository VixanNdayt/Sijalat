/* =========================================================
   Interaksi halaman chat: polling, kirim pesan, pencarian
   ========================================================= */
(function () {
    'use strict';

    const root = document.getElementById('chatRoot');
    if (!root) {
        return;
    }

    const mode = root.dataset.mode || 'inbox';
    const stream = document.getElementById('chatStream');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('chatInput');
    const sendButton = document.getElementById('chatSend');
    const alertBox = document.getElementById('chatAlert');
    const csrf = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrf ? csrf.content : '';

    const pollUrl = root.dataset.pollUrl || '';
    const sendUrl = root.dataset.sendUrl || '';
    const pollInterval = 4000;

    let lastId = parseInt(root.dataset.after || '0', 10) || 0;
    let lastDate = (stream && stream.dataset.lastDate) || '';
    let sending = false;
    let pollTimer = null;
    let errorTimer = null;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------- Pencarian percakapan (admin) ---------------- */

    const search = document.getElementById('chatSearch');
    const contacts = Array.from(document.querySelectorAll('.chat-contact'));
    const noResult = document.getElementById('chatNoResult');

    if (search && contacts.length) {
        search.addEventListener('input', function () {
            const keyword = search.value.trim().toLowerCase();
            let visible = 0;

            contacts.forEach(function (contact) {
                const cocok = keyword === '' || (contact.dataset.search || '').includes(keyword);
                contact.hidden = !cocok;

                if (cocok) {
                    visible += 1;
                }
            });

            if (noResult) {
                noResult.hidden = visible !== 0;
            }
        });
    }

    /* ---------------- Utilitas ---------------- */

    function initials(name) {
        return String(name || '?')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(function (word) {
                return word.charAt(0);
            })
            .join('')
            .toUpperCase() || '?';
    }

    function nearBottom() {
        if (!stream) {
            return true;
        }

        return stream.scrollHeight - stream.scrollTop - stream.clientHeight < 120;
    }

    function scrollToBottom(force) {
        if (!stream) {
            return;
        }

        if (force || nearBottom()) {
            const behavior = reduceMotion ? 'auto' : 'smooth';
            stream.scrollTo({ top: stream.scrollHeight, behavior: behavior });
        }
    }

    function showError(message) {
        if (!alertBox) {
            return;
        }

        alertBox.textContent = message;
        alertBox.classList.add('show');

        if (errorTimer) {
            clearTimeout(errorTimer);
        }

        errorTimer = setTimeout(function () {
            alertBox.classList.remove('show');
        }, 7000);
    }

    function clearError() {
        if (!alertBox) {
            return;
        }

        if (errorTimer) {
            clearTimeout(errorTimer);
            errorTimer = null;
        }

        alertBox.textContent = '';
        alertBox.classList.remove('show');
    }

    function resizeInput() {
        if (!input) {
            return;
        }

        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    /* ---------------- Render satu pesan ---------------- */

    function addMessage(message, forceScroll) {
        if (!stream || !message) {
            return false;
        }

        if (stream.querySelector('[data-id="' + message.id + '"]')) {
            return false;
        }

        const empty = document.getElementById('chatEmpty');
        if (empty) {
            empty.remove();
        }

        if (message.date && message.date !== lastDate) {
            const day = document.createElement('div');
            day.className = 'chat-day';
            day.dataset.day = message.date;

            const label = document.createElement('span');
            label.textContent = message.date_label || message.date;
            day.appendChild(label);
            stream.appendChild(day);

            lastDate = message.date;
        }

        const row = document.createElement('div');
        row.className = 'chat-row ' + (message.mine ? 'mine' : 'theirs');
        row.dataset.id = message.id;

        if (!message.mine) {
            const avatar = document.createElement('span');
            avatar.className = 'chat-avatar';
            avatar.textContent = initials(message.sender_name);
            row.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';

        if (!message.mine) {
            const sender = document.createElement('span');
            sender.className = 'chat-sender';
            sender.textContent = message.sender_name || 'Admin';
            bubble.appendChild(sender);
        }

        const body = document.createElement('p');
        body.textContent = message.body;
        bubble.appendChild(body);

        const time = document.createElement('span');
        time.className = 'chat-time';
        time.textContent = message.time || '';
        bubble.appendChild(time);

        row.appendChild(bubble);
        stream.appendChild(row);

        if (message.id > lastId) {
            lastId = message.id;
        }

        scrollToBottom(forceScroll);
        return true;
    }

    /* ---------------- Polling pesan baru ---------------- */

    async function poll() {
        if (!pollUrl || sending || document.visibilityState !== 'visible') {
            return;
        }

        const separator = pollUrl.indexOf('?') === -1 ? '?' : '&';

        try {
            const response = await fetch(pollUrl + separator + 'after=' + lastId, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const messages = Array.isArray(data.messages) ? data.messages : [];
            const wasNearBottom = nearBottom();
            let added = 0;

            messages.forEach(function (message) {
                if (addMessage(message, false)) {
                    added += 1;
                }
            });

            if (typeof data.last_id === 'number' && data.last_id > lastId) {
                lastId = data.last_id;
            }

            if (added > 0 && wasNearBottom) {
                scrollToBottom(true);
            }
        } catch (error) {
            /* Jaringan sedang bermasalah: coba lagi pada polling berikutnya. */
        }
    }

    function startPolling() {
        if (!pollUrl || pollTimer) {
            return;
        }

        pollTimer = setInterval(poll, pollInterval);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    /* ---------------- Kirim pesan ---------------- */

    async function kirimPesan(body) {
        sending = true;
        clearError();

        if (sendButton) {
            sendButton.disabled = true;
        }

        try {
            const response = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ body: body })
            });

            let data = null;

            try {
                data = await response.json();
            } catch (error) {
                data = null;
            }

            if (!response.ok) {
                if (response.status === 419) {
                    throw new Error('Sesi kamu sudah berakhir. Muat ulang halaman lalu coba lagi.');
                }

                if (response.status === 422 && data && data.errors && data.errors.body) {
                    throw new Error(data.errors.body[0]);
                }

                throw new Error((data && data.message) || 'Pesan gagal dikirim. Coba lagi.');
            }

            if (data && data.message) {
                addMessage(data.message, true);
            }

            if (input) {
                input.value = '';
                resizeInput();
                input.focus();
            }
        } catch (error) {
            showError(error.message || 'Pesan gagal dikirim. Periksa koneksi lalu coba lagi.');
        } finally {
            sending = false;

            if (sendButton) {
                sendButton.disabled = false;
            }
        }
    }

    /* ---------------- Pasang event ---------------- */

    if (form && input && sendUrl) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const body = input.value.trim();

            if (body === '' || sending) {
                return;
            }

            if (body.length > 2000) {
                showError('Pesan terlalu panjang. Maksimal 2000 karakter.');
                return;
            }

            kirimPesan(body);
        });

        input.addEventListener('input', resizeInput);

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        resizeInput();
    }

    if (stream && mode !== 'inbox') {
        window.requestAnimationFrame(function () {
            scrollToBottom(true);
        });

        startPolling();

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                poll();
                startPolling();
            } else {
                stopPolling();
            }
        });
    }
})();
