<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Portfolio Contact Message</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { padding: 20px; background-color: #f5f5f5; }
        .section { background-color: #fff; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #f00a77; }
        .section h3 { color: #f00a77; margin-top: 0; }
        .field { margin: 10px 0; }
        .field strong { color: #f00a77; }
        a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="section">
            <h3>Contact Information</h3>
            <div class="field"><strong>Name:</strong> {{ $validated['name'] }}</div>
            <div class="field"><strong>Email:</strong> {{ $validated['email'] }}</div>
            <div class="field"><strong>Phone:</strong> {{ $validated['phone'] ?: 'Not provided' }}</div>
            <div class="field"><strong>Message:</strong><br>{!! nl2br(e($validated['message'])) !!}</div>
        </div>

        <div class="section">
            <h3>Visitor Information</h3>
            <div class="field"><strong>IP Address:</strong> {{ $visitor['ip_address'] }}</div>
            <div class="field"><strong>Country:</strong> {{ $visitor['country'] }}</div>
            <div class="field"><strong>City:</strong> {{ $visitor['city'] }}</div>
            <div class="field"><strong>Region:</strong> {{ $visitor['region'] }}</div>
            <div class="field">
                <strong>Coordinates:</strong> {{ $visitor['latitude'] }}, {{ $visitor['longitude'] }}
                @if ($mapLink)
                    - <a href="{{ $mapLink }}">View on Google Maps</a>
                @endif
            </div>
            <div class="field"><strong>Browser:</strong> {{ $visitor['browser'] }}</div>
            <div class="field"><strong>Operating System:</strong> {{ $visitor['os'] }}</div>
            <div class="field"><strong>Device Type:</strong> {{ $visitor['device_type'] }}</div>
            <div class="field"><strong>User Agent:</strong> {{ $visitor['user_agent'] }}</div>
            <div class="field"><strong>Submission Time:</strong> {{ $submissionTime }}</div>
        </div>
    </div>
</body>
</html>

