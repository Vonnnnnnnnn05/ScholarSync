<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page {
            size: {{ count($report['headings']) >= 7 ? 'landscape' : 'portrait' }};
            margin: 12mm 10mm 15mm 10mm;
        }
        body {
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
        }
        .letterhead {
            text-align: center;
            border-bottom: 2px solid #064e3b;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .republic {
            font-size: 8pt;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #4b5563;
        }
        .university-name {
            font-size: 13pt;
            font-weight: bold;
            color: #111827;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .campus-address {
            font-size: 8pt;
            color: #4b5563;
        }
        .office-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #065f46;
            margin-top: 3px;
            text-transform: uppercase;
        }
        .system-tagline {
            font-size: 7.5pt;
            color: #6b7280;
            font-style: italic;
        }
        .report-header {
            margin-bottom: 12px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 6px;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
            margin: 0 0 4px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            color: #374151;
            margin-top: 4px;
        }
        .meta-table td {
            border: none;
            padding: 2px 0;
            vertical-align: top;
        }
        table.data-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 8px;
            font-size: 8.5pt;
        }
        table.data-table th {
            background-color: #ecfdf5;
            color: #064e3b;
            font-size: 8pt;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            border: 1px solid #9ca3af;
            padding: 5px 6px;
        }
        table.data-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: top;
            color: #1f2937;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .empty-cell {
            text-align: center;
            padding: 16px;
            color: #6b7280;
            font-style: italic;
        }
        .signatures {
            margin-top: 28px;
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .signatures td {
            border: none;
            text-align: center;
            width: 33.33%;
            vertical-align: top;
            padding: 0 8px;
        }
        .sig-line {
            border-bottom: 1px solid #374151;
            margin: 28px 12px 4px 12px;
        }
        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
        }
        .sig-title {
            color: #4b5563;
        }
        .footer-note {
            margin-top: 18px;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
            font-size: 7.5pt;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="letterhead">
        <div class="republic">Republic of the Philippines</div>
        <div class="university-name">Sultan Kudarat State University</div>
        <div class="campus-address">Province of Sultan Kudarat, 9800 Philippines</div>
        <div class="office-name">Office of Student Affairs and Services • Scholarship Unit</div>
        <div class="system-tagline">ScholarSync — Integrated Scholarship Management & Verification System</div>
    </div>

    <div class="report-header">
        <div class="report-title">{{ $report['title'] }}</div>
        <table class="meta-table">
            <tr>
                <td style="text-align: left;">
                    @if ($filters !== [])
                        <strong>Filters:</strong>
                        @foreach ($filters as $key => $value)
                            {{ Str::headline($key) }}: <strong>{{ $value }}</strong>@if (! $loop->last), @endif
                        @endforeach
                    @else
                        <em>All Records (No filters applied)</em>
                    @endif
                </td>
                <td style="text-align: right;">
                    <strong>Generated:</strong> {{ now()->format('M d, Y h:i A') }} |
                    <strong>Total:</strong> {{ count($report['rows']) }} record(s)
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                @foreach ($report['headings'] as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['headings']) }}" class="empty-cell">No records found matching criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div>Prepared by:</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ auth()->check() ? auth()->user()->name : 'Scholarship Administrator' }}</div>
                <div class="sig-title">Scholarship Administration</div>
            </td>
            <td>
                <div>Verified by:</div>
                <div class="sig-line"></div>
                <div class="sig-name">Scholarship Coordinator</div>
                <div class="sig-title">University Scholarship Unit</div>
            </td>
            <td>
                <div>Noted / Approved by:</div>
                <div class="sig-line"></div>
                <div class="sig-name">Director / Campus Head</div>
                <div class="sig-title">Student Affairs and Services</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        ScholarSync • Sultan Kudarat State University • System Generated Official Report • Printed {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>
