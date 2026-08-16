<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $certificateNumber }}</title>
    <style>
        @page { margin: 46.8pt 61.2pt; }
        body { color: #000; font-family: "Times New Roman", DejaVu Serif, serif; font-size: 12pt; line-height: 1.5; }
        .header { line-height: 1.25; text-align: center; }
        .university { font-size: 14pt; font-weight: bold; }
        .campus, .location { font-size: 11pt; }
        .title { font-size: 16pt; font-weight: bold; margin: 34pt 0 22pt; text-align: center; }
        .salutation { font-weight: bold; margin: 0 0 14pt; }
        .body-copy { text-align: justify; }
        .body-copy p { margin: 0 0 12pt; }
        .student-name { font-weight: bold; text-transform: uppercase; }
        .issued { margin-top: 4pt; }
        .metadata { font-size: 11pt; font-weight: bold; margin-top: 64pt; }
        .metadata div { margin-bottom: 3pt; }
    </style>
</head>
<body>
    <header class="header">
        <div class="university">SULTAN KUDARAT STATE UNIVERSITY</div>
        <div class="campus">Isulan Campus</div>
        <div class="location">Isulan, Sultan Kudarat, Philippines</div>
    </header>

    <main>
        <h1 class="title">CERTIFICATE OF NO SCHOLARSHIP</h1>
        <p class="salutation">TO WHOM IT MAY CONCERN:</p>

        <section class="body-copy">
            <p>
                This is to certify that <span class="student-name">{{ str($student->fullName())->upper() }}</span>,
                a {{ $student->year_level ?: 'student' }} student currently enrolled in the
                {{ $student->course ?: 'program/course recorded by the University' }} at Sultan Kudarat State
                University - Isulan Campus for the {{ $semester }} Semester, Academic Year {{ $academicYear }},
                is not currently a recipient of any scholarship grant or financial assistance administered
                through the University, based on the records available in this office.
            </p>

            <p>
                This certification is issued upon the request of the above-named student for
                {{ $certificateRequest->purpose }} and for whatever lawful purpose it may serve.
            </p>

            <p class="issued">
                Issued this {{ $issuedAt->format('jS') }} day of {{ $issuedAt->format('F') }},
                {{ $issuedAt->format('Y') }} at Sultan Kudarat State University - Isulan Campus,
                Isulan, Sultan Kudarat, Philippines.
            </p>
        </section>

        <section class="metadata">
            <div>Certificate No.: {{ $certificateNumber }}</div>
            <div>Date Issued: {{ $issuedAt->format('F d, Y') }}</div>
        </section>
    </main>
</body>
</html>
