<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $certificateNumber }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 36pt 45pt 36pt 45pt;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, DejaVu Sans, sans-serif;
            font-size: 11pt;
            color: #111827;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .page-bg {
            position: fixed;
            top: -36pt;
            left: -45pt;
            width: 210mm;
            height: 297mm;
            z-index: -1000;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10pt;
            margin-bottom: 5pt;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-col {
            width: 72px;
            text-align: center;
        }

        .logo-img {
            width: 66px;
            height: 66px;
        }

        .header-center {
            text-align: center;
            line-height: 1.25;
        }

        .header-country {
            font-size: 9pt;
            color: #262626;
            letter-spacing: 0.2px;
        }

        .header-university {
            font-size: 13pt;
            font-weight: bold;
            color: #008a45;
            letter-spacing: 0.5px;
            margin-top: 2pt;
        }

        .header-address {
            font-size: 8.5pt;
            color: #333333;
            margin-top: 1pt;
        }

        .office-title {
            text-align: center;
            margin-top: 16pt;
            font-size: 12pt;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.3px;
        }

        .doc-title {
            text-align: center;
            font-size: 20pt;
            font-weight: bold;
            letter-spacing: 5px;
            margin-top: 28pt;
            margin-bottom: 26pt;
            color: #111827;
        }

        .content-body {
            margin-top: 10pt;
            text-align: justify;
        }

        .content-body p {
            margin: 0 0 16pt 0;
            line-height: 1.8;
            text-indent: 32pt;
            font-size: 11pt;
        }

        .student-name {
            font-weight: bold;
            color: #000000;
        }

        .signatory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 36pt;
        }

        .dst-stamp-box {
            display: inline-block;
            border: 1.5px solid #666666;
            width: 110pt;
            height: 72pt;
            text-align: center;
            padding-top: 16pt;
            color: #555555;
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.35;
        }

        .dst-stamp-sub {
            font-size: 6.5pt;
            font-weight: normal;
            display: block;
            margin-top: 2pt;
            letter-spacing: 0.3px;
        }

        .cert-audit-info {
            margin-top: 12pt;
            font-size: 8pt;
            color: #64748b;
            line-height: 1.4;
        }

        .cert-audit-info strong {
            color: #1e293b;
        }

        .signatory-name {
            font-size: 12pt;
            font-weight: bold;
            color: #000000;
            text-decoration: underline;
        }

        .signatory-title {
            font-size: 10pt;
            color: #333333;
            margin-top: 2pt;
        }

        .footer-policy {
            position: fixed;
            bottom: 22pt;
            left: 0;
            right: 0;
            font-size: 6.2pt;
            color: #4b5563;
            text-align: justify;
            line-height: 1.3;
            border-top: 1px solid #d1fae5;
            padding-top: 5pt;
        }

        .footer-policy strong {
            color: #065f46;
        }
    </style>
</head>
<body>
    @if(file_exists(public_path('images/sksu-cert-stationery.jpg')))
        <div class="page-bg">
            <img src="{{ public_path('images/sksu-cert-stationery.jpg') }}" style="width: 100%; height: 100%;" alt="">
        </div>
    @endif

    <!-- Official Header -->
    <table class="header-table">
        <tr>
            <td class="logo-col">
                @if(file_exists(public_path('images/sksu-seal-cert.png')))
                    <img class="logo-img" src="{{ public_path('images/sksu-seal-cert.png') }}" alt="SKSU Seal">
                @elseif(file_exists(public_path('images/sksu-seal.png')))
                    <img class="logo-img" src="{{ public_path('images/sksu-seal.png') }}" alt="SKSU Seal">
                @endif
            </td>
            <td class="header-center">
                <div class="header-country">Republic of the Philippines</div>
                <div class="header-university">SULTAN KUDARAT STATE UNIVERSITY</div>
                <div class="header-address">EJC Montilla, City of Tacurong, 9800<br>Province of Sultan Kudarat</div>
            </td>
            <td class="logo-col">
                @if(file_exists(public_path('images/bagong-pilipinas.png')))
                    <img class="logo-img" src="{{ public_path('images/bagong-pilipinas.png') }}" alt="Bagong Pilipinas">
                @endif
            </td>
        </tr>
    </table>

    <!-- Office -->
    <div class="office-title">
        Office of the Student Affairs and Services
    </div>

    <!-- Title -->
    <div class="doc-title">
        C E R T I F I C A T I O N
    </div>

    <!-- Certification Body -->
    <div class="content-body">
        <p>
            This is to certify that <span class="student-name">{{ str($student->fullName())->upper() }}</span> is a <strong>{{ $student->year_level ?: '3rd year' }}</strong> <strong>{{ $student->course ?: 'Bachelor of Science' }}</strong> student for S.Y <strong>{{ $academicYear }}</strong>.
        </p>

        <p>
            This is to certify further, that the above-named student is a <strong>non-grantee of any government scholarships</strong>.
        </p>

        <p>
            This certification is being issued upon his/her request for whatever legal purpose it may serve him/her best. Issued this <strong>{{ $issuedAt->format('jS') }} day of {{ $issuedAt->format('F Y') }}</strong> at <strong>Sultan Kudarat State University-{{ $student->campus ?: 'ACCESS Campus' }}, EJC Montilla, Tacurong City, Sultan Kudarat</strong>.
        </p>
    </div>

    <!-- Signatory and Stamp Section -->
    <table class="signatory-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="dst-stamp-box">
                    DST STAMP
                    <span class="dst-stamp-sub">DOCUMENTARY STAMP TAX PAID</span>
                </div>
                <div class="cert-audit-info">
                    <strong>Certificate No.:</strong> {{ $certificateNumber }}<br>
                    <strong>Date Issued:</strong> {{ $issuedAt->format('F d, Y') }}<br>
                    <span style="color: #008a45;">✓ Verified via SKSUScholarSync</span>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: center;">
                <div style="margin-top: 42pt;">
                    <div class="signatory-name">ADONIS S. BESA, PhD</div>
                    <div class="signatory-title">Director, Student Affairs and Services</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Official University Statements Footer -->
    <div class="footer-policy">
        <strong>VISION:</strong> A leading University in advancing scholarly innovation, multi-cultural convergence, and responsive public service in a borderless Region. &nbsp;|&nbsp;
        <strong>MISSION:</strong> The University shall primarily provide advanced instruction and professional training in science and technology, agriculture, fisheries, education and other relevant fields of study. It shall also undertake research and extension services, and provide progressive leadership in its areas of specialization. &nbsp;|&nbsp;
        <strong>MAXIM:</strong> Generator of Solutions. &nbsp;|&nbsp;
        <strong>CORE VALUES:</strong> Patriotism, Respect, Integrity, Zeal, Excellence in Public Service.
    </div>
</body>
</html>
