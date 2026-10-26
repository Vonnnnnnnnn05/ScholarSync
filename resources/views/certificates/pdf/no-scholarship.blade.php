<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $certificateNumber }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 0;
        }

        @font-face {
            font-family: 'Monotype Corsiva';
            src: url("{{ public_path('fonts/mtcorsva.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, 'DejaVu Sans', sans-serif;
            font-size: 10.5pt;
            color: #000000;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
        }

        .page-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
            z-index: -1000;
        }

        .page-bg img {
            width: 210mm;
            height: 297mm;
            display: block;
        }

        /* Header layout aligning with stationery */
        .header-container {
            margin-top: 24pt;
            margin-left: 50pt;
            width: 380pt;
        }

        .header-table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0;
        }

        .logo-box {
            width: 48pt;
            text-align: center;
        }

        .logo-box img {
            width: 44pt;
            height: 44pt;
            display: inline-block;
        }

        .header-text {
            text-align: center;
            padding-left: 6pt;
            line-height: 1.25;
        }

        .header-country {
            font-family: 'Times New Roman', Times, 'DejaVu Serif', serif;
            font-size: 8.5pt;
            color: #222222;
        }

        .header-university {
            font-family: 'Times New Roman', Times, 'DejaVu Serif', serif;
            font-size: 11.5pt;
            font-weight: bold;
            color: #00703c;
            letter-spacing: 0.3px;
            margin-top: 1pt;
        }

        .header-address {
            font-family: 'Times New Roman', Times, 'DejaVu Serif', serif;
            font-size: 8pt;
            font-style: italic;
            color: #333333;
            margin-top: 1pt;
            line-height: 1.2;
        }

        /* Office Heading */
        .office-title {
            text-align: center;
            margin-top: 28pt;
            margin-bottom: 22pt;
            font-family: 'Monotype Corsiva', 'Brush Script MT', 'Lucida Calligraphy', cursive, serif;
            font-size: 19pt;
            font-style: italic;
            color: #000000;
            letter-spacing: 0.2px;
        }

        /* Document Title */
        .doc-title {
            text-align: center;
            font-family: Arial, Helvetica, 'DejaVu Sans', sans-serif;
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 8px;
            margin-bottom: 28pt;
            color: #000000;
        }

        /* Main Content */
        .content-body {
            margin-left: 65pt;
            margin-right: 50pt;
            font-family: Arial, Helvetica, 'DejaVu Sans', sans-serif;
            font-size: 10.5pt;
            line-height: 1.65;
            color: #000000;
        }

        .content-body p {
            margin: 0 0 16pt 0;
            text-indent: 32pt;
            text-align: left;
        }

        .student-name {
            font-weight: bold;
            text-decoration: underline;
            color: #000000;
        }

        sup {
            font-size: 7.5pt;
            vertical-align: baseline;
            position: relative;
            top: -0.4em;
        }

        /* Signatory and Stamp */
        .bottom-section {
            margin-left: 65pt;
            margin-right: 50pt;
            margin-top: 36pt;
        }

        .signatory-container {
            float: right;
            width: 250pt;
            text-align: center;
        }

        .signatory-name {
            font-family: Arial, Helvetica, 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            font-weight: bold;
            color: #000000;
        }

        .signatory-title {
            font-family: Arial, Helvetica, 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            color: #111827;
            margin-top: 2pt;
        }

        .dst-stamp-block {
            clear: both;
            padding-top: 55pt;
            padding-left: 10pt;
            font-family: Arial, Helvetica, 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            color: #111827;
            line-height: 1.35;
        }

        /* Footer Policy */
        .footer-policy {
            position: fixed;
            bottom: 24pt;
            left: 55pt;
            right: 40pt;
            font-family: Arial, Helvetica, 'DejaVu Sans', sans-serif;
            font-size: 5.4pt;
            color: #4b5563;
            text-align: center;
            line-height: 1.35;
            border-top: 1px solid #10b981;
            padding-top: 4pt;
        }

        .policy-heading {
            font-weight: bold;
            color: #047857;
        }
    </style>
</head>
<body>
    @if(!empty($isPreview))
        <div style="position: absolute; top: 12pt; right: 28pt; padding: 3pt 8pt; border: 1.5px dashed #047857; background: #ecfdf5; color: #047857; font-size: 8pt; font-weight: bold; font-family: Arial, sans-serif; letter-spacing: 1px; border-radius: 4px; text-transform: uppercase;">
            Preview / Draft Only
        </div>
    @endif

    @if(file_exists(public_path('images/sksu-cert-stationery.jpg')))
        <div class="page-bg">
            <img src="{{ public_path('images/sksu-cert-stationery.jpg') }}" alt="">
        </div>
    @endif


    <!-- Official Header -->
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="logo-box">
                    @if(file_exists(public_path('images/bagong-pilipinas.png')))
                        <img src="{{ public_path('images/bagong-pilipinas.png') }}" alt="Bagong Pilipinas">
                    @endif
                </td>
                <td class="logo-box">
                    @if(file_exists(public_path('images/sksu-seal-cert.png')))
                        <img src="{{ public_path('images/sksu-seal-cert.png') }}" alt="SKSU Seal">
                    @elseif(file_exists(public_path('images/sksu-seal.png')))
                        <img src="{{ public_path('images/sksu-seal.png') }}" alt="SKSU Seal">
                    @endif
                </td>
                <td class="header-text">
                    <div class="header-country">Republic of the Philippines</div>
                    <div class="header-university">SULTAN KUDARAT STATE UNIVERSITY</div>
                    <div class="header-address">EJC Montilla, City of Tacurong, 9800<br>Province of Sultan Kudarat</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Office Title -->
    <div class="office-title">
        Office of the Student Affairs and Services
    </div>

    <!-- Document Title -->
    <div class="doc-title">
        C E R T I F I C A T I O N
    </div>

    @php
        $rawYear = $student->year_level ?: '3rd Year';
        if (preg_match('/^(\d+)(st|nd|rd|th)(\s*year)?/i', $rawYear, $matches)) {
            $formattedYear = $matches[1] . '<sup>' . strtolower($matches[2]) . '</sup> year';
        } else {
            $formattedYear = e(strtolower($rawYear));
        }
    @endphp

    <!-- Certification Body -->
    <div class="content-body">
        <p>
            This is to certify that <span class="student-name">{{ str($student->fullName())->upper() }}</span> is a {!! $formattedYear !!} <strong>{{ $student->course ?: 'Bachelor of Science' }}</strong> student for S.Y {{ $academicYear }}.
        </p>

        <p>
            This is to certify further, that the above-named student is a non-grantee of any government scholarships.
        </p>

        <p>
            This certification is being issued upon his/her request for whatever legal purpose it may serve him/her best.  Issued this {{ $issuedAt->format('j') }}<sup>{{ strtolower($issuedAt->format('S')) }}</sup> day of {{ $issuedAt->format('F Y') }} at Sultan Kudarat State University-ACCESS Campus, EJC Montilla, Tacurong City, Sultan Kudarat.
        </p>
    </div>

    <!-- Signatory and DST Stamp -->
    <div class="bottom-section">
        <div class="signatory-container">
            @if(!empty($signatureImage))
                <div class="signature-image-container" style="height: 48pt; margin-bottom: -14pt; text-align: center;">
                    <img src="{{ $signatureImage }}" alt="Authorized Signature" style="height: 48pt; max-width: 170pt; object-fit: contain;">
                </div>
            @else
                <div style="height: 20pt;"></div>
            @endif
            <div class="signatory-name">ADONIS S. BESA, PhD</div>
            <div class="signatory-title">Director, Student Affairs and Services</div>
        </div>
        <div class="dst-stamp-block">
            DST<br>Stamp
        </div>
    </div>

    <!-- Official University Statements Footer -->
    <div class="footer-policy">
        <span class="policy-heading">VISION:</span> A leading University in advancing scholarly innovation, multi-cultural convergence, and responsive public service in a borderless Region. &nbsp;|&nbsp;
        <span class="policy-heading">MISSION:</span> The University shall primarily provide advanced instruction and professional training in science and technology, agriculture, fisheries, education and other relevant fields of study. It shall also undertake research and extension services, and provide progressive leadership in its areas of specialization. &nbsp;|&nbsp;
        <span class="policy-heading">MAXIM:</span> Generator of Solutions. &nbsp;|&nbsp;
        <span class="policy-heading">CORE VALUES:</span> Patriotism, Respect, Integrity, Zeal, Excellence in Public Service.
    </div>

    <!-- Certificate Tracking Metadata: Certificate No.: {{ $certificateNumber }} | Date Issued: {{ $issuedAt->format('F d, Y') }} -->
</body>
</html>
