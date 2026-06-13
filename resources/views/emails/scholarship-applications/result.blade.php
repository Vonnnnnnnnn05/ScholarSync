<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Scholarship Renewal :status', ['status' => $application->status->label()]) }}</title>
</head>
<body style="margin:0;background:#f3f7f3;color:#111827;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f7f3;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #d9e7dc;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="border-top:5px solid #047857;padding:28px 28px 12px;">
                            <p style="margin:0 0 8px;color:#047857;font-size:13px;font-weight:700;text-transform:uppercase;">ScholarSync</p>
                            <h1 style="margin:0;color:#111827;font-size:24px;line-height:1.3;">Scholarship Renewal {{ $application->status->label() }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 28px 24px;">
                            <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.7;">Hello {{ $application->student->first_name }},</p>
                            <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.7;">
                                Your continuing scholarship renewal for {{ $application->scholarship_program }} is now marked as {{ $application->status->label() }}.
                            </p>

                            @if ($application->remarks)
                                <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.7;">
                                    Remarks: {{ $application->remarks }}
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
