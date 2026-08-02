(function () {
    'use strict';

    var scriptSrc = (document.currentScript && document.currentScript.src) || '';
    var baseUrl = '';
    if (scriptSrc) {
        var marker = '/assets/js/';
        var idx = scriptSrc.lastIndexOf(marker);
        if (idx !== -1) {
            baseUrl = scriptSrc.slice(0, idx);
        }
    }

    var API_URL = baseUrl + '/api/ai_support';
    var history = [];
    var awaiting = false;

    var ICON_LAUNCHER = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a7 7 0 0 0-4 12.7V17a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-2.3A7 7 0 0 0 12 2z"></path><path d="M9 19v2"></path><path d="M15 19v2"></path><path d="M2 13h2"></path><path d="M20 13h2"></path></svg>';
    var ICON_CLOSE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>';
    var ICON_SEND = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13"></path><path d="M22 2l-7 20-4-9-9-4 20-7z"></path></svg>';
    var ICON_AVATAR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"></rect><circle cx="12" cy="12" r="2.5"></circle><path d="M8 9l1.5 1.5M16 9l-1.5 1.5M8 15l1.5-1.5M16 15l-1.5-1.5"></path></svg>';

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[c];
        });
    }

    function renderText(text) {
        return escapeHtml(text).replace(/\n/g, '<br>');
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function addMessage(text, who) {
        var messages = document.querySelector('.ai-support-messages');
        if (!messages) return;
        var bubble = document.createElement('div');
        bubble.className = 'ai-msg ai-msg-' + who;
        if (who === 'ai') {
            var avatar = document.createElement('span');
            avatar.className = 'ai-msg-avatar';
            avatar.innerHTML = ICON_AVATAR;
            bubble.appendChild(avatar);
        }
        var body = document.createElement('div');
        body.className = 'ai-msg-body';
        body.innerHTML = renderText(text);
        bubble.appendChild(body);
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    }

    function showTyping() {
        var messages = document.querySelector('.ai-support-messages');
        if (!messages) return;
        var bubble = document.createElement('div');
        bubble.className = 'ai-msg ai-msg-ai ai-msg-typing';
        var avatar = document.createElement('span');
        avatar.className = 'ai-msg-avatar';
        avatar.innerHTML = ICON_AVATAR;
        var body = document.createElement('div');
        body.className = 'ai-msg-body';
        body.innerHTML = '<span class="ai-typing-dot"></span><span class="ai-typing-dot"></span><span class="ai-typing-dot"></span>';
        bubble.appendChild(avatar);
        bubble.appendChild(body);
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    }

    function setWaiting(state) {
        awaiting = state;
        var send = document.querySelector('.ai-support-send');
        var input = document.querySelector('.ai-support-input');
        if (send) send.disabled = state;
        if (input) input.disabled = state;
    }

    async function sendMessage(text) {
        if (awaiting) return;
        addMessage(text, 'user');
        history.push({ role: 'user', content: text });
        var typing = showTyping();
        setWaiting(true);

        var payload = {
            message: text,
            history: history.slice(-20),
            _csrf_token: getCsrfToken()
        };

        try {
            var response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            var data = await response.json();
            if (typing) typing.remove();
            if (data && data.success && data.reply) {
                addMessage(data.reply, 'ai');
                history.push({ role: 'model', content: data.reply });
            } else {
                addMessage(data && data.reply ? data.reply : 'Sorry, something went wrong. Please try again.', 'ai');
            }
        } catch (error) {
            if (typing) typing.remove();
            addMessage('Sorry, I could not reach the assistant right now. Please try again in a moment.', 'ai');
        } finally {
            setWaiting(false);
        }
    }

    function injectWidget() {
        if (document.getElementById('aiSupport')) return;

        var root = document.createElement('div');
        root.className = 'ai-support';
        root.id = 'aiSupport';

        root.innerHTML =
            '<button type="button" class="ai-support-launcher" aria-label="Open Tech Support Remini AI" aria-expanded="false">' +
                '<span class="ai-launcher-open">' + ICON_LAUNCHER + '</span>' +
                '<span class="ai-launcher-close">' + ICON_CLOSE + '</span>' +
            '</button>' +
            '<div class="ai-support-panel" hidden>' +
                '<div class="ai-support-header">' +
                    '<span class="ai-avatar">' + ICON_AVATAR + '</span>' +
                    '<div class="ai-support-title">' +
                        '<strong>Tech Support Remini AI</strong>' +
                        '<span class="ai-support-status"><span class="ai-status-dot"></span>Online</span>' +
                    '</div>' +
                    '<button type="button" class="ai-support-close" aria-label="Close assistant">' + ICON_CLOSE + '</button>' +
                '</div>' +
                '<div class="ai-support-messages" role="log" aria-live="polite"></div>' +
                '<form class="ai-support-form">' +
                    '<textarea class="ai-support-input" rows="1" placeholder="Ask me about this system..." aria-label="Ask Tech Support Remini AI"></textarea>' +
                    '<button type="submit" class="ai-support-send" aria-label="Send message">' + ICON_SEND + '</button>' +
                '</form>' +
            '</div>';

        document.body.appendChild(root);

        var launcher = root.querySelector('.ai-support-launcher');
        var panel = root.querySelector('.ai-support-panel');
        var closeBtn = root.querySelector('.ai-support-close');
        var form = root.querySelector('.ai-support-form');
        var input = root.querySelector('.ai-support-input');
        var opened = false;

        function openPanel() {
            opened = true;
            panel.hidden = false;
            launcher.classList.add('is-open');
            launcher.setAttribute('aria-expanded', 'true');
            var msgs = root.querySelector('.ai-support-messages');
            if (msgs && msgs.children.length === 0) {
                addMessage('Hello! I\u2019m Tech Support Remini AI, your assistant for this ICT support system. How can I help you today?', 'ai');
            }
            setTimeout(function () { input.focus(); }, 50);
        }

        function closePanel() {
            opened = false;
            panel.hidden = true;
            launcher.classList.remove('is-open');
            launcher.setAttribute('aria-expanded', 'false');
        }

        launcher.addEventListener('click', function () {
            if (opened) closePanel(); else openPanel();
        });
        closeBtn.addEventListener('click', closePanel);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var text = input.value.trim();
            if (!text || awaiting) return;
            input.value = '';
            input.style.height = '';
            sendMessage(text);
        });

        input.addEventListener('input', function () {
            input.style.height = '';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectWidget);
    } else {
        injectWidget();
    }
})();
