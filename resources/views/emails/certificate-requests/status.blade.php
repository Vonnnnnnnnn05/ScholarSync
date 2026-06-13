<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;background:#f3f7f3;color:#111827;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f7f3;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #d9e7dc;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="border-top:5px solid #047857;padding:28px 28px 12px;">
                            <p style="margin:0 0 8px;color:#047857;font-size:13px;font-weight:700;text-transform:uppercase;">ScholarSync</p>
                            <h1 style="margin:0;color:#111827;font-size:24px;line-height:1.3;">{{ $title }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 28px 24px;">
                            <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.7;">Hello {{ $certificateRequest->student->first_name }},</p>
                            <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.7;">{{ $bodyMessage }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:20px 0;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #e5e7eb;">Request ID</td>
                                    <td style="padding:10px 0;color:#111827;font-size:13px;text-align:right;border-top:1px solid #e5e7eb;">#{{ $certificateRequest->id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #e5e7eb;">Status</td>
                                    <td style="padding:10px 0;color:#111827;font-size:13px;text-align:right;border-top:1px solid #e5e7eb;">{{ $certificateRequest->status->label() }}</td>
                                </tr>
                            </table>

                            @if ($actionUrl && $actionText)
                                <p style="margin:24px 0;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#065f46;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 18px;border-radius:6px;">
                                        {{ $actionText }}
                                    </a>
                                </p>
                            @endif

                            <p style="margin:24px 0 0;color:#6b7280;font-size:13px;line-height:1.6;">
                                This is an automated message from ScholarSync.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
