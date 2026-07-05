<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
$pageTitle = 'Track Issue | ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-card track-card">
    <h2 data-i18n="track_title">Check Issue Status</h2>
    <form id="trackForm" class="form-grid">
        <label><span data-i18n="track_code_label">Tracking Code</span>
            <input type="text" name="tracking_code" id="trackingCode" placeholder="Example: ICT-AB12CD34-260426" data-i18n-placeholder="track_code_placeholder" required>
        </label>
        <button type="submit" class="btn btn-primary" data-i18n="track_submit_btn">Track Issue</button>
    </form>
    <div id="trackResult" class="track-result hidden" aria-live="polite"></div>
</section>
<script>
const trackForm = document.getElementById('trackForm');
const trackResult = document.getElementById('trackResult');
let lastTrackedData = null;

function t(key, fallback) {
    if (window.appI18n && typeof window.appI18n.t === 'function') {
        return window.appI18n.t(key, fallback || '');
    }
    return fallback || '';
}

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char];
    });
}

function statusClass(status) {
    return 'status-' + String(status || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}

function statusLabel(status) {
    const raw = String(status || '').trim().replace(/[\s.]+$/g, '');
    const normalized = raw.toLowerCase().replace(/[_\-]+/g, ' ').replace(/\s+/g, ' ');
    const labels = {
        submitted: 'Submitted',
        assigned: 'Assigned',
        in: 'In Progress',
        ip: 'In Progress',
        inprogress: 'In Progress',
        'in progress': 'In Progress',
        resolved: 'Resolved',
        closed: 'Closed',
        cancelled: 'Cancelled',
        rejected: 'Rejected'
    };

    return labels[normalized] || raw || '-';
}

function parseTimelineStatus(message) {
    const text = String(message || '').trim();
    const match = text.match(/^Status(?:\s+updated|\s+changed)?\s*(?:to|:)\s+(.+?)(?:\s+by ICT staff\.)?(?:\s+(.*))?$/i);

    if (!match) {
        return null;
    }

    return {
        status: statusLabel(match[1]),
        note: String(match[2] || '').trim()
    };
}

function getDisplayStatus(ticketStatus, timeline) {
    if (Array.isArray(timeline)) {
        for (let index = timeline.length - 1; index >= 0; index -= 1) {
            const update = parseTimelineStatus(timeline[index] && timeline[index].comment_text);
            if (update && update.status) {
                return update.status;
            }
        }
    }

    return statusLabel(ticketStatus);
}

function formatValue(value, fallback) {
    const text = String(value || '').trim();
    return text !== '' ? escapeHtml(text) : escapeHtml(fallback || '-');
}

function renderTrackResult(data) {
    if (!data.success) {
        trackResult.classList.remove('hidden');
        trackResult.innerHTML = '<div class="alert alert-danger track-alert">' + escapeHtml(data.message) + '</div>';
        return;
    }

    const ticket = data.ticket || {};
    const attachment = data.attachment || {};
    const timeline = Array.isArray(data.timeline) ? data.timeline : [];
    const displayStatus = getDisplayStatus(ticket.status, timeline);
    const status = escapeHtml(displayStatus);
    let html = '<div class="track-table-block">';
    html += '<table class="track-table track-summary-table">';
    html += '<caption>' + escapeHtml(t('track_result_heading', 'Ticket Overview')) + ' - ' + escapeHtml(ticket.tracking_code || '') + '</caption>';
    html += '<tbody>';
    html += '<tr><th>' + escapeHtml(t('track_result_status', 'Status')) + '</th><td><span class="status-badge ' + statusClass(displayStatus) + '">' + status + '</span></td></tr>';
    html += '<tr><th>' + escapeHtml(t('track_result_category', 'Category')) + '</th><td>' + formatValue(ticket.category, '-') + ' - ' + formatValue(ticket.subcategory, '-') + '</td></tr>';
    html += '<tr><th>' + escapeHtml(t('track_result_department', 'Department')) + '</th><td>' + formatValue(ticket.department, '-') + '</td></tr>';
    html += '<tr><th>' + escapeHtml(t('track_result_created', 'Submitted On')) + '</th><td>' + formatValue(ticket.created_at, '-') + '</td></tr>';
    html += '</tbody>';
    html += '</table>';

    if (attachment.file_path) {
        html += '<div class="track-photo-preview">';
        html += '<div class="track-section-head">';
        html += '<h4>Latest Uploaded Photo</h4>';
        html += '<p>This is the newest evidence or resolution photo attached to the ticket.</p>';
        html += '</div>';
        html += '<button type="button" class="btn btn-secondary track-evidence-btn" data-track-evidence-url="' + escapeHtml(attachment.file_path) + '" data-track-evidence-name="' + escapeHtml(attachment.file_name || '') + '" data-track-evidence-code="' + escapeHtml(ticket.tracking_code || '') + '" data-track-evidence-status="' + status + '">View Photo</button>';
        html += '</div>';
    }

    html += '<div class="track-section-head">';
    html += '<h4>' + escapeHtml(t('track_result_timeline', 'Timeline')) + '</h4>';
    html += '<p>' + escapeHtml(t('track_result_timeline_note', 'Latest updates and ticket activity.')) + '</p>';
    html += '</div>';

    if (timeline.length === 0) {
        html += '<div class="track-empty-state">' + escapeHtml(t('track_result_no_updates', 'No updates yet.')) + '</div>';
    } else {
        html += '<table class="track-table track-timeline-table">';
        html += '<thead><tr><th>' + escapeHtml(t('track_result_time', 'Date & Time')) + '</th><th>' + escapeHtml(t('track_result_activity', 'Activity')) + '</th></tr></thead>';
        html += '<tbody>';
        timeline.forEach(function (item) {
            const statusUpdate = parseTimelineStatus(item.comment_text);
            html += '<tr>';
            html += '<td class="track-time-cell">' + escapeHtml(item.created_at) + '</td>';
            html += '<td>';
            if (statusUpdate) {
                html += '<span class="status-badge ' + statusClass(statusUpdate.status) + '">' + escapeHtml(statusUpdate.status) + '</span>';
                if (statusUpdate.note !== '') {
                    html += '<div class="small-text">' + escapeHtml(statusUpdate.note) + '</div>';
                }
            } else {
                html += escapeHtml(item.comment_text);
            }
            html += '</td>';
            html += '</tr>';
        });
        html += '</tbody>';
        html += '</table>';
    }

    html += '</div>';

    trackResult.classList.remove('hidden');
    trackResult.innerHTML = html;

    trackResult.querySelectorAll('.track-evidence-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const imageUrl = button.dataset.trackEvidenceUrl || '';
            const fileName = button.dataset.trackEvidenceName || '';
            const trackingCode = button.dataset.trackEvidenceCode || '-';
            const ticketStatus = button.dataset.trackEvidenceStatus || '-';

            const drawer = document.getElementById('trackEvidenceDrawer');
            const image = document.getElementById('trackEvidenceImage');
            const nameBox = document.getElementById('trackEvidenceName');
            const codeBox = document.getElementById('trackEvidenceCode');
            const statusBox = document.getElementById('trackEvidenceStatus');

            if (!drawer || !image || !nameBox || !codeBox || !statusBox) {
                return;
            }

            image.src = imageUrl;
            nameBox.textContent = fileName;
            codeBox.textContent = trackingCode;
            statusBox.textContent = ticketStatus;
            drawer.classList.remove('hidden');
            drawer.setAttribute('aria-hidden', 'false');
        });
    });
}

trackForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    trackResult.innerHTML = '<p class="small-text">' + t('track_searching', 'Searching...') + '</p>';

    const formData = new FormData(trackForm);
    const response = await fetch('api/track_ticket.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    lastTrackedData = data;
    renderTrackResult(data);
});

window.addEventListener('app-language-changed', function () {
    if (lastTrackedData !== null) {
        renderTrackResult(lastTrackedData);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const drawer = document.getElementById('trackEvidenceDrawer');
    if (!drawer) {
        return;
    }

    function closeDrawer() {
        drawer.classList.add('hidden');
        drawer.setAttribute('aria-hidden', 'true');
        const image = document.getElementById('trackEvidenceImage');
        if (image) {
            image.src = '';
        }
    }

    drawer.querySelectorAll('[data-track-evidence-close]').forEach(function (el) {
        el.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });
});
</script>
<div id="trackEvidenceDrawer" class="evidence-drawer hidden" aria-hidden="true">
    <div class="evidence-drawer-backdrop" data-track-evidence-close></div>
    <aside class="evidence-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="trackEvidenceDrawerTitle">
        <div class="evidence-drawer-header">
            <div>
                <div class="evidence-drawer-kicker">Ticket Evidence</div>
                <h3 id="trackEvidenceDrawerTitle">Latest Uploaded Photo</h3>
            </div>
            <button type="button" class="evidence-drawer-close" data-track-evidence-close aria-label="Close preview">&times;</button>
        </div>
        <div class="evidence-drawer-meta">
            <p><strong>Tracking Code:</strong> <span id="trackEvidenceCode">-</span></p>
            <p><strong>Status:</strong> <span id="trackEvidenceStatus">-</span></p>
        </div>
        <div class="evidence-drawer-preview">
            <img id="trackEvidenceImage" alt="Ticket evidence preview" src="">
        </div>
        <p class="evidence-drawer-name" id="trackEvidenceName"></p>
    </aside>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
