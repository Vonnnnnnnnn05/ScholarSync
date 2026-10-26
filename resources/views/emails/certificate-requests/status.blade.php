<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>

<body
    style="margin:0; padding:0; background-color:#edf2ee; font-family:Arial, Helvetica, sans-serif; color:#1f2937; -webkit-font-smoothing:antialiased;">
    @php
        $bagongLogo = (isset($message) && method_exists($message, 'embed') && file_exists(public_path('images/bagong-pilipinas.png')))
            ? $message->embed(public_path('images/bagong-pilipinas.png'))
            : asset('images/bagong-pilipinas.png');

        $sksuLogoPath = file_exists(public_path('images/sksu-seal-cert.png'))
            ? public_path('images/sksu-seal-cert.png')
            : (file_exists(public_path('images/sksu-seal.png')) ? public_path('images/sksu-seal.png') : null);

        $sksuLogo = ($sksuLogoPath && isset($message) && method_exists($message, 'embed'))
            ? $message->embed($sksuLogoPath)
            : ($sksuLogoPath ? asset('images/' . basename($sksuLogoPath)) : '');

        $student = $certificateRequest->student;
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color:#edf2ee; padding:28px 12px;">
        <tr>
            <td align="center">
                <!-- Main Email Card -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                    style="max-width:640px; background-color:#ffffff; border:1px solid #d1ded4; border-radius:8px; overflow:hidden; box-shadow:0 4px 14px rgba(0,0,0,0.06);">

                    <!-- Top University Banner Colors -->
                    <tr>
                        <td style="background-color:#00703c; height:6px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="background-color:#f59e0b; height:2px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>

                    <!-- Official University Letterhead -->
                    <tr>
                        <td style="padding:24px 28px 16px; background-color:#ffffff; border-bottom:2px solid #00703c;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <!-- Logos -->
                                    <td
                                        style="width:115px; vertical-align:middle; text-align:left; white-space:nowrap;">
                                        @if($bagongLogo)
                                            <img src="{{ $bagongLogo }}" alt="Bagong Pilipinas" width="50" height="50"
                                                style="display:inline-block; vertical-align:middle; margin-right:6px; border:0;">
                                        @endif
                                        @if($sksuLogo)
                                            <img src="{{ $sksuLogo }}" alt="SKSU Seal" width="50" height="50"
                                                style="display:inline-block; vertical-align:middle; border:0;">
                                        @endif
                                    </td>
                                    <!-- University Header Info -->
                                    <td style="vertical-align:middle; text-align:center; padding-left:8px;">
                                        <div
                                            style="font-family:'Times New Roman', Georgia, serif; font-size:11px; color:#374151; letter-spacing:0.3px;">
                                            Republic of the Philippines
                                        </div>
                                        <div
                                            style="font-family:'Times New Roman', Georgia, serif; font-size:15px; font-weight:bold; color:#00703c; letter-spacing:0.4px; margin:2px 0;">
                                            SULTAN KUDARAT STATE UNIVERSITY
                                        </div>
                                        <div
                                            style="font-family:'Times New Roman', Georgia, serif; font-size:10.5px; font-style:italic; color:#4b5563; line-height:1.2;">
                                            EJC Montilla, City of Tacurong, 9800, Province of Sultan Kudarat
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Contact Strip -->
                            <div
                                style="margin-top:12px; padding-top:8px; border-top:1px dashed #d1e2d6; text-align:center; font-family:Arial, sans-serif; font-size:10px; color:#4b5563;">
                                <span style="display:inline-block; margin:0 6px;">🌐 <a href="https://www.sksu.edu.ph"
                                        target="_blank"
                                        style="color:#00703c; text-decoration:none;">www.sksu.edu.ph</a></span>
                                <span style="display:inline-block; margin:0 6px;">✉️
                                    officialdocumentmail@sksu.edu.ph</span>
                                <span style="display:inline-block; margin:0 6px;">📞 (064) 200-7336</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Office of the Student Affairs and Services Heading -->
                    <tr>
                        <td
                            style="background-color:#fafcf9; padding:16px 28px 12px; text-align:center; border-bottom:1px solid #e7efe9;">
                            <div
                                style="font-family:'Georgia', 'Times New Roman', serif; font-style:italic; font-size:18px; color:#111827; font-weight:bold; letter-spacing:0.3px;">
                                Office of the Student Affairs and Services
                            </div>
                            <div style="margin-top:6px;">
                                <span
                                    style="display:inline-block; background-color:#e8f5ec; color:#00703c; font-family:Arial, sans-serif; font-size:11px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; padding:4px 12px; border-radius:3px; border:1px solid #c2e7cc;">
                                    OFFICIAL NOTIFICATION
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Main Letter Content -->
                    <tr>
                        <td style="padding:24px 32px 28px;">
                            <!-- Reference and Date Header -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="margin-bottom:18px; font-family:Arial, sans-serif; font-size:12px; color:#4b5563;">
                                <tr>
                                    <td style="vertical-align:top;">
                                        <strong>Date:</strong> {{ now()->format('F d, Y') }}
                                    </td>
                                    <td style="vertical-align:top; text-align:right;">
                                        <strong>Request Ref:</strong>
                                        #{{ str_pad((string) $certificateRequest->id, 5, '0', STR_PAD_LEFT) }}
                                    </td>
                                </tr>
                            </table>

                            <!-- Addressee Formal Box -->
                            <div
                                style="background-color:#f8faf8; border-left:4px solid #00703c; padding:12px 16px; margin-bottom:20px; font-family:Arial, sans-serif; font-size:12.5px; line-height:1.5;">
                                <div
                                    style="font-weight:bold; color:#111827; font-size:13.5px; text-transform:uppercase;">
                                    {{ $student->fullName() }}
                                </div>
                                <div style="color:#4b5563; margin-top:2px;">
                                    Student ID: <strong>{{ $student->student_id_number ?: 'N/A' }}</strong> &bull;
                                    {{ $student->campus ?: 'ACCESS Campus' }}
                                </div>
                                <div style="color:#4b5563;">
                                    Program: <strong>{{ $student->course ?: 'Bachelor of Science' }}</strong>
                                    ({{ $student->year_level ?: '3rd Year' }})
                                </div>
                            </div>

                            <!-- Salutation -->
                            <p style="margin:0 0 14px; font-family:Arial, sans-serif; font-size:14px; color:#111827;">
                                Dear <strong>{{ $student->first_name }}</strong>,
                            </p>

                            <!-- Primary Message -->
                            <p
                                style="margin:0 0 16px; font-family:Arial, sans-serif; font-size:14px; line-height:1.7; color:#374151;">
                                {{ $bodyMessage }}
                            </p>

                            <!-- Formal Status & Details Table -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="margin:20px 0; border:1px solid #e5e7eb; border-radius:6px; overflow:hidden; font-family:Arial, sans-serif; font-size:13px;">
                                <tr style="background-color:#f9fafb;">
                                    <td colspan="2"
                                        style="padding:10px 14px; font-weight:bold; color:#00703c; border-bottom:1px solid #e5e7eb; text-transform:uppercase; font-size:11.5px; letter-spacing:0.5px;">
                                        Certificate Request Details
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        style="padding:10px 14px; color:#6b7280; border-bottom:1px solid #f3f4f6; width:40%;">
                                        Document Requested</td>
                                    <td
                                        style="padding:10px 14px; color:#111827; font-weight:bold; border-bottom:1px solid #f3f4f6;">
                                        Certificate of Non-Grantee (No Scholarship)</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 14px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
                                        Stated Purpose</td>
                                    <td style="padding:10px 14px; color:#111827; border-bottom:1px solid #f3f4f6;">
                                        {{ $certificateRequest->purpose ?: 'General Purpose / Scholarship Requirement' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 14px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
                                        Request Status</td>
                                    <td style="padding:10px 14px; border-bottom:1px solid #f3f4f6;">
                                        @if($certificateRequest->status->value === 'approved')
                                            <span
                                                style="display:inline-block; background-color:#dcfce7; color:#15803d; font-weight:bold; font-size:12px; padding:3px 10px; border-radius:4px;">
                                                ✓ Approved &amp; Generated
                                            </span>
                                        @elseif($certificateRequest->status->value === 'rejected')
                                            <span
                                                style="display:inline-block; background-color:#fee2e2; color:#b91c1c; font-weight:bold; font-size:12px; padding:3px 10px; border-radius:4px;">
                                                ✕ Rejected
                                            </span>
                                        @else
                                            <span
                                                style="display:inline-block; background-color:#fef3c7; color:#b45309; font-weight:bold; font-size:12px; padding:3px 10px; border-radius:4px;">
                                                {{ $certificateRequest->status->label() }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @if($certificateRequest->certificate)
                                    <tr>
                                        <td style="padding:10px 14px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
                                            Certificate Number</td>
                                        <td
                                            style="padding:10px 14px; color:#00703c; font-weight:bold; border-bottom:1px solid #f3f4f6;">
                                            {{ $certificateRequest->certificate->certificate_number }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 14px; color:#6b7280; border-bottom:1px solid #f3f4f6;">Date
                                            Issued</td>
                                        <td style="padding:10px 14px; color:#111827; border-bottom:1px solid #f3f4f6;">
                                            {{ $certificateRequest->certificate->generated_at->format('F d, Y') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 14px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
                                            Signatory</td>
                                        <td style="padding:10px 14px; color:#111827; border-bottom:1px solid #f3f4f6;">
                                            ADONIS S. BESA, PhD &bull; Director, OSAS
                                        </td>
                                    </tr>
                                @endif
                                @if($certificateRequest->remarks)
                                    <tr>
                                        <td style="padding:10px 14px; color:#6b7280;">Admin Remarks</td>
                                        <td style="padding:10px 14px; color:#991b1b; font-style:italic;">
                                            {{ $certificateRequest->remarks }}
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <!-- Rejection Notice Callout (if rejected) -->
                            @if ($certificateRequest->status->value === 'rejected')
                                <div
                                    style="background-color:#fff5f5; border:1px solid #fecaca; border-radius:6px; padding:14px 18px; margin:20px 0; font-family:Arial, sans-serif; font-size:13px; line-height:1.6; color:#991b1b;">
                                    <strong>Need to submit a replacement receipt?</strong><br>
                                    Please ensure your Official Receipt clearly shows the payment date, OR number, and
                                    student details. You can submit a new request at any time through your student portal.
                                </div>
                            @endif

                            <!-- Action Buttons / Links -->
                            @if ($actionUrl && $actionText)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    style="margin:26px 0 20px;">
                                    <tr>
                                        <td align="center">
                                            <table role="presentation" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td
                                                        style="background-color:#00703c; border-radius:4px; text-align:center;">
                                                        <a href="{{ $actionUrl }}"
                                                            style="display:inline-block; background-color:#00703c; color:#ffffff; text-decoration:none; font-family:Arial, sans-serif; font-weight:bold; font-size:14px; padding:13px 26px; border-radius:4px; letter-spacing:0.4px;">
                                                            {{ $actionText }} &rarr;
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @elseif ($certificateRequest->certificate)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    style="margin:26px 0 20px;">
                                    <tr>
                                        <td align="center">
                                            <table role="presentation" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td
                                                        style="background-color:#00703c; border-radius:4px; text-align:center; padding-right:8px;">
                                                        <a href="{{ route('student.certificate-requests.certificate.download', $certificateRequest) }}"
                                                            style="display:inline-block; background-color:#00703c; color:#ffffff; text-decoration:none; font-family:Arial, sans-serif; font-weight:bold; font-size:14px; padding:13px 24px; border-radius:4px; letter-spacing:0.4px;">
                                                            Download Certificate (PDF) &rarr;
                                                        </a>
                                                    </td>
                                                    <td
                                                        style="background-color:#ffffff; border:1.5px solid #00703c; border-radius:4px; text-align:center;">
                                                        <a href="{{ route('student.certificate-requests.certificate.view', $certificateRequest) }}"
                                                            target="_blank"
                                                            style="display:inline-block; background-color:#ffffff; color:#00703c; text-decoration:none; font-family:Arial, sans-serif; font-weight:bold; font-size:14px; padding:12px 22px; border-radius:4px; letter-spacing:0.4px;">
                                                            View Online
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @elseif ($certificateRequest->status->value === 'rejected')
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    style="margin:24px 0 16px;">
                                    <tr>
                                        <td align="center">
                                            <table role="presentation" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td
                                                        style="background-color:#00703c; border-radius:4px; text-align:center;">
                                                        <a href="{{ route('student.certificate-requests.create') }}"
                                                            style="display:inline-block; background-color:#00703c; color:#ffffff; text-decoration:none; font-family:Arial, sans-serif; font-weight:bold; font-size:14px; padding:13px 26px; border-radius:4px; letter-spacing:0.4px;">
                                                            Submit New Certificate Request &rarr;
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <!-- Signatory Sign-off Block -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="margin-top:28px; border-top:1px solid #e5e7eb; padding-top:18px;">
                                <tr>
                                    <td
                                        style="font-family:Arial, sans-serif; font-size:13px; color:#4b5563; line-height:1.6;">
                                        Very truly yours,<br><br>
                                        <strong style="color:#111827; font-size:14px;">OFFICE OF THE STUDENT AFFAIRS AND
                                            SERVICES</strong><br>
                                        <span style="font-size:12px; color:#4b5563;">Sultan Kudarat State University -
                                            ACCESS Campus</span><br>
                                        <span style="font-size:11px; color:#6b7280;">EJC Montilla, Tacurong City, 9800
                                            Sultan Kudarat</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- University Policy Footer -->
                    <tr>
                        <td
                            style="background-color:#fafcf9; border-top:2px solid #00703c; padding:16px 24px; font-family:Arial, sans-serif; font-size:10px; color:#555555; text-align:center; line-height:1.5;">
                            <p style="margin:0 0 6px;">
                                <strong style="color:#00703c;">VISION:</strong> A leading University in advancing
                                scholarly innovation, multi-cultural convergence, and responsive public service in a
                                borderless Region. &nbsp;|&nbsp;
                                <strong style="color:#00703c;">MISSION:</strong> The University shall primarily provide
                                advanced instruction and professional training in science and technology, agriculture,
                                fisheries, education and other relevant fields of study.
                            </p>
                            <p style="margin:0 0 8px;">
                                <strong style="color:#00703c;">MAXIM:</strong> Generator of Solutions. &nbsp;|&nbsp;
                                <strong style="color:#00703c;">CORE VALUES:</strong> Patriotism, Respect, Integrity,
                                Zeal, Excellence in Public Service.
                            </p>
                            <div
                                style="border-top:1px solid #e2e8f0; margin-top:8px; padding-top:8px; font-size:9.5px; color:#9ca3af;">
                                This is an official automated notification from ScholarSync &bull; Sultan Kudarat State
                                University. If you have any inquiries, please contact the OSAS office.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>