<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <h1>Test PDF Report</h1>
    <p>This is a test to verify PDF generation is working.</p>
    <p>Report Type: {{ $reportTitle ?? 'N/A' }}</p>
</body>
</html>
