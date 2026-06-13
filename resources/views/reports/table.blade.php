<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body { color: #111827; font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; }
        h1 { color: #065f46; font-size: 20px; margin: 0 0 6px; }
        p { color: #4b5563; margin: 0 0 16px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #ecfdf5; color: #064e3b; font-size: 10px; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
    </style>
</head>
<body>
    <h1>{{ $report['title'] }}</h1>
    <p>Generated {{ now()->format('M d, Y h:i A') }}</p>

    @if ($filters !== [])
        <p>Filters:
            @foreach ($filters as $key => $value)
                {{ Str::headline($key) }}={{ $value }}@if (! $loop->last), @endif
            @endforeach
        </p>
    @endif

    <table>
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
                    <td colspan="{{ count($report['headings']) }}">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
