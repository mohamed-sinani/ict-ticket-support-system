<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function getFlash(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function randomTrackingCode(): string
{
    return 'ICT-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('ymd');
}

function getDepartments(): array
{
    $res = db()->query('SELECT id, name FROM departments ORDER BY name ASC');
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getCategories(): array
{
    $res = db()->query('SELECT id, name FROM categories ORDER BY name ASC');
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function addTicketTimeline(int $ticketId, ?int $userId, string $message): void
{
    $conn = db();
    $sql = 'INSERT INTO comments (ticket_id, user_id, comment_text, is_timeline) VALUES (?, ?, ?, 1)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $ticketId, $userId, $message);
    $stmt->execute();
}

function absoluteUrl(string $path): string
{
    return app_absolute_url($path);
}

function buildTicketCreatedEmail(array $ticket): string
{
    $trackingCode = e((string) $ticket['tracking_code']);
    $employeeName = e((string) $ticket['employee_name']);
    $status = e((string) $ticket['status']);
    $department = e((string) ($ticket['department_name'] ?? 'Not specified'));
    $category = e(trim((string) ($ticket['category_name'] ?? '') . ' - ' . (string) ($ticket['subcategory_name'] ?? ''), ' -'));
    $trackUrl = e(absoluteUrl('track.php'));

    return '<!doctype html>
<html>
<body style="margin:0;background:#eff6ff;font-family:Inter,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eff6ff;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #dbeafe;border-radius:14px;overflow:hidden;box-shadow:0 10px 28px rgba(15,47,97,0.12);">
                    <tr>
                        <td style="background:#0f2f61;color:#ffffff;padding:22px 24px;">
                            <div style="font-size:13px;font-weight:800;color:#bfdbfe;">ICT Support</div>
                            <div style="font-size:22px;font-weight:800;line-height:1.25;margin-top:4px;">Ticket Submitted Successfully</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px;">Hello ' . $employeeName . ',</p>
                            <p style="margin:0 0 18px;color:#475569;">Your ICT issue has been received. Keep this tracking code for follow-up.</p>
                            <div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:10px;padding:16px;text-align:center;margin:18px 0;">
                                <div style="font-size:12px;font-weight:800;color:#1e3a8a;text-transform:uppercase;letter-spacing:.04em;">Tracking Code</div>
                                <div style="font-size:26px;font-weight:800;color:#0f2f61;letter-spacing:.04em;margin-top:4px;">' . $trackingCode . '</div>
                            </div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:18px 0;border-collapse:collapse;">
                                <tr><td style="padding:9px 0;color:#64748b;">Status</td><td style="padding:9px 0;font-weight:800;text-align:right;">' . $status . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Department</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $department . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Issue</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $category . '</td></tr>
                            </table>
                            <a href="' . $trackUrl . '" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:800;border-radius:8px;padding:12px 16px;">Track Ticket</a>
                            <p style="margin:20px 0 0;color:#64748b;font-size:13px;">Use the tracking page in the support portal to view updates.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function buildTicketAdminAlertEmail(array $ticket): string
{
    $trackingCode = e((string) $ticket['tracking_code']);
    $employeeName = e((string) $ticket['employee_name']);
    $department = e((string) ($ticket['department_name'] ?? 'Not specified'));
    $category = e(trim((string) ($ticket['category_name'] ?? '') . ' - ' . (string) ($ticket['subcategory_name'] ?? ''), ' -'));
    $priority = e((string) ($ticket['priority'] ?? 'Medium'));
    $status = e((string) ($ticket['status'] ?? STATUS_SUBMITTED));
    $adminUrl = e(absoluteUrl('admin/tickets.php'));

    return '<!doctype html>
<html>
<body style="margin:0;background:#eff6ff;font-family:Inter,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eff6ff;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #dbeafe;border-radius:14px;overflow:hidden;box-shadow:0 10px 28px rgba(15,47,97,0.12);">
                    <tr>
                        <td style="background:#0f2f61;color:#ffffff;padding:22px 24px;">
                            <div style="font-size:13px;font-weight:800;color:#bfdbfe;">ICT Support</div>
                            <div style="font-size:22px;font-weight:800;line-height:1.25;margin-top:4px;">New Ticket Awaiting Assignment</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px;">Hello Admin,</p>
                            <p style="margin:0 0 18px;color:#475569;">A new ICT issue has been submitted and is ready for assignment.</p>
                            <div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:10px;padding:16px;text-align:center;margin:18px 0;">
                                <div style="font-size:12px;font-weight:800;color:#1e3a8a;text-transform:uppercase;letter-spacing:.04em;">Tracking Code</div>
                                <div style="font-size:26px;font-weight:800;color:#0f2f61;letter-spacing:.04em;margin-top:4px;">' . $trackingCode . '</div>
                            </div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:18px 0;border-collapse:collapse;">
                                <tr><td style="padding:9px 0;color:#64748b;">Employee</td><td style="padding:9px 0;font-weight:800;text-align:right;">' . $employeeName . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Department</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $department . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Issue</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $category . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Priority</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $priority . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Status</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $status . '</td></tr>
                            </table>
                            <a href="' . $adminUrl . '" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:800;border-radius:8px;padding:12px 16px;">Assign Ticket</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function buildTicketAssignmentEmail(array $ticket): string
{
    $trackingCode = e((string) $ticket['tracking_code']);
    $employeeName = e((string) $ticket['employee_name']);
    $department = e((string) ($ticket['department_name'] ?? 'Not specified'));
    $category = e(trim((string) ($ticket['category_name'] ?? '') . ' - ' . (string) ($ticket['subcategory_name'] ?? ''), ' -'));
    $status = e((string) ($ticket['status'] ?? STATUS_ASSIGNED));
    $staffName = e((string) ($ticket['assigned_name'] ?? 'ICT Staff'));
    $staffUrl = e(absoluteUrl('staff/my_tickets.php'));

    return '<!doctype html>
<html>
<body style="margin:0;background:#eff6ff;font-family:Inter,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eff6ff;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #dbeafe;border-radius:14px;overflow:hidden;box-shadow:0 10px 28px rgba(15,47,97,0.12);">
                    <tr>
                        <td style="background:#0f2f61;color:#ffffff;padding:22px 24px;">
                            <div style="font-size:13px;font-weight:800;color:#bfdbfe;">ICT Support</div>
                            <div style="font-size:22px;font-weight:800;line-height:1.25;margin-top:4px;">New Ticket Assigned to You</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px;">Hello ' . $staffName . ',</p>
                            <p style="margin:0 0 18px;color:#475569;">A ticket has been assigned to you by the admin team.</p>
                            <div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:10px;padding:16px;text-align:center;margin:18px 0;">
                                <div style="font-size:12px;font-weight:800;color:#1e3a8a;text-transform:uppercase;letter-spacing:.04em;">Tracking Code</div>
                                <div style="font-size:26px;font-weight:800;color:#0f2f61;letter-spacing:.04em;margin-top:4px;">' . $trackingCode . '</div>
                            </div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:18px 0;border-collapse:collapse;">
                                <tr><td style="padding:9px 0;color:#64748b;">Employee</td><td style="padding:9px 0;font-weight:800;text-align:right;">' . $employeeName . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Department</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $department . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Issue</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $category . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Status</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $status . '</td></tr>
                            </table>
                            <a href="' . $staffUrl . '" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:800;border-radius:8px;padding:12px 16px;">Open Assigned Tickets</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function buildTicketResolvedEmail(array $ticket): string
{
    $trackingCode = e((string) $ticket['tracking_code']);
    $employeeName = e((string) $ticket['employee_name']);
    $department = e((string) ($ticket['department_name'] ?? 'Not specified'));
    $category = e(trim((string) ($ticket['category_name'] ?? '') . ' - ' . (string) ($ticket['subcategory_name'] ?? ''), ' -'));
    $status = e((string) ($ticket['status'] ?? STATUS_RESOLVED));
    $resolutionNote = e((string) ($ticket['resolution_note'] ?? ''));
    $trackUrl = e(absoluteUrl('track.php'));

    return '<!doctype html>
<html>
<body style="margin:0;background:#eff6ff;font-family:Inter,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eff6ff;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #dbeafe;border-radius:14px;overflow:hidden;box-shadow:0 10px 28px rgba(15,47,97,0.12);">
                    <tr>
                        <td style="background:#0f2f61;color:#ffffff;padding:22px 24px;">
                            <div style="font-size:13px;font-weight:800;color:#bfdbfe;">ICT Support</div>
                            <div style="font-size:22px;font-weight:800;line-height:1.25;margin-top:4px;">Ticket Marked as Resolved</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px;">Hello ' . $employeeName . ',</p>
                            <p style="margin:0 0 18px;color:#475569;">Your ICT issue has been resolved. You can review the final status and timeline using the tracking code below.</p>
                            <div style="background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:16px;text-align:center;margin:18px 0;">
                                <div style="font-size:12px;font-weight:800;color:#166534;text-transform:uppercase;letter-spacing:.04em;">Tracking Code</div>
                                <div style="font-size:26px;font-weight:800;color:#0f2f61;letter-spacing:.04em;margin-top:4px;">' . $trackingCode . '</div>
                            </div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:18px 0;border-collapse:collapse;">
                                <tr><td style="padding:9px 0;color:#64748b;">Status</td><td style="padding:9px 0;font-weight:800;text-align:right;">' . $status . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Department</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $department . '</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;border-top:1px solid #e2e8f0;">Issue</td><td style="padding:9px 0;font-weight:700;text-align:right;border-top:1px solid #e2e8f0;">' . $category . '</td></tr>
                            </table>
                            <a href="' . $trackUrl . '" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:800;border-radius:8px;padding:12px 16px;">View Ticket</a>
                            <p style="margin:20px 0 0;color:#475569;font-size:13px;">If you still need help, reply to the ICT team with the tracking code.</p>
                            ' . ($resolutionNote !== '' ? '<div style="margin-top:18px;padding:14px 16px;background:#f8fafc;border:1px solid #dbeafe;border-radius:10px;"><div style="font-size:12px;font-weight:800;color:#0f2f61;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Resolution Note</div><div style="color:#334155;line-height:1.55;">' . $resolutionNote . '</div></div>' : '') . '
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function buildOtpEmail(string $fullName, string $otpCode): string
{
    $name = e($fullName);
    $code = e($otpCode);
    $minutes = OTP_EXPIRY_MINUTES;

    return '<!doctype html>
<html>
<body style="margin:0;background:#eff6ff;font-family:Inter,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eff6ff;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #dbeafe;border-radius:14px;overflow:hidden;box-shadow:0 10px 28px rgba(15,47,97,0.12);">
                    <tr>
                        <td style="background:#0f2f61;color:#ffffff;padding:22px 24px;">
                            <div style="font-size:13px;font-weight:800;color:#bfdbfe;">ICT Support</div>
                            <div style="font-size:22px;font-weight:800;line-height:1.25;margin-top:4px;">Login Verification Code</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px;">Hello ' . $name . ',</p>
                            <p style="margin:0 0 18px;color:#475569;">Use the following one-time code to complete your login. This code expires in ' . $minutes . ' minutes.</p>
                            <div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:10px;padding:20px;text-align:center;margin:18px 0;">
                                <div style="font-size:12px;font-weight:800;color:#1e3a8a;text-transform:uppercase;letter-spacing:.08em;">Verification Code</div>
                                <div style="font-size:36px;font-weight:800;color:#0f2f61;letter-spacing:.15em;margin-top:8px;">' . $code . '</div>
                            </div>
                            <p style="margin:18px 0 0;color:#64748b;font-size:13px;">If you did not request this login, please ignore this email or contact ICT support immediately.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function sendNotificationEmail(string $to, string $subject, string $message, ?string $htmlMessage = null): bool
{
    if (defined('SMTP_HOST') && SMTP_HOST !== '') {
        $sent = sendSmtpEmail($to, $subject, $message, $htmlMessage);
        if ($sent) {
            return true;
        }

        // Log fallback attempt and try PHP mail() as a best-effort fallback.
        error_log('[ict-mail] SMTP send failed for: ' . $to . ' subject: ' . $subject);
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= ($htmlMessage !== null ? "Content-type:text/html;charset=UTF-8\r\n" : "Content-type:text/plain;charset=UTF-8\r\n");
    $headers .= 'From: ' . APP_NAME . ' <' . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'no-reply@institution.local') . ">\r\n";

    $result = @mail($to, $subject, $htmlMessage ?? $message, $headers);
    if (!$result) {
        error_log('[ict-mail] PHP mail() failed for: ' . $to . ' subject: ' . $subject);
    }

    return $result;
}

function sendSmtpEmail(string $to, string $subject, string $plainMessage, ?string $htmlMessage = null): bool
{
    $socket = @stream_socket_client('tcp://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 20);
    if (!$socket) {
        error_log('[ict-mail] Could not connect to SMTP server: ' . SMTP_HOST . ':' . SMTP_PORT . ' errno=' . ($errno ?? '') . ' errstr=' . ($errstr ?? ''));
        return false;
    }

    stream_set_timeout($socket, 20);
    $lastResponse = '';
    $read = static function () use ($socket, &$lastResponse): string {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        $lastResponse = $data;
        return $data;
    };
    $send = static function (string $command, array $okCodes) use ($socket, $read, &$lastResponse): bool {
        fwrite($socket, $command . "\r\n");
        $response = $read();
        $lastResponse = $response;
        return in_array(substr($response, 0, 3), $okCodes, true);
    };

    if (substr($read(), 0, 3) !== '220') {
        error_log('[ict-mail] SMTP server did not return 220 on connect. Response: ' . $lastResponse);
        fclose($socket);
        return false;
    }

    $hostName = $_SERVER['SERVER_NAME'] ?? 'localhost';
    if (!$send('EHLO ' . $hostName, ['250'])) {
        error_log('[ict-mail] EHLO failed. Response: ' . $lastResponse);
        fclose($socket);
        return false;
    }

    if (SMTP_ENCRYPTION === 'tls') {
        if (!$send('STARTTLS', ['220']) || !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log('[ict-mail] STARTTLS failed. Response: ' . $lastResponse);
            fclose($socket);
            return false;
        }
        if (!$send('EHLO ' . $hostName, ['250'])) {
            fclose($socket);
            return false;
        }
    }

    if (!$send('AUTH LOGIN', ['334']) || !$send(base64_encode(SMTP_USERNAME), ['334']) || !$send(base64_encode(SMTP_PASSWORD), ['235'])) {
        error_log('[ict-mail] AUTH LOGIN failed. Last response: ' . $lastResponse);
        fclose($socket);
        return false;
    }

    $from = SMTP_USERNAME;
    if (!$send('MAIL FROM:<' . $from . '>', ['250']) || !$send('RCPT TO:<' . $to . '>', ['250', '251']) || !$send('DATA', ['354'])) {
        error_log('[ict-mail] MAIL/RCPT/DATA failed. Last response: ' . $lastResponse);
        fclose($socket);
        return false;
    }

    $boundary = 'ict_' . bin2hex(random_bytes(12));
    $headers = [
        'From: ICT Support <' . $from . '>',
        'To: <' . $to . '>',
        'Subject: ' . (function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($subject, 'UTF-8') : $subject),
        'MIME-Version: 1.0',
    ];

    if ($htmlMessage !== null) {
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$plainMessage}\r\n";
        $body .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$htmlMessage}\r\n--{$boundary}--";
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $body = $plainMessage;
    }

    $payload = implode("\r\n", $headers) . "\r\n\r\n" . preg_replace("/^\./m", '..', $body) . "\r\n.";
    fwrite($socket, $payload . "\r\n");
    $sent = substr($read(), 0, 3) === '250';
    $send('QUIT', ['221']);
    fclose($socket);

    return $sent;
}
