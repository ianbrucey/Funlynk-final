<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Became an Event</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            padding: 40px 20px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .event-card {
            background-color: #f9fafb;
            border-left: 4px solid #ec4899;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .event-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin: 0 0 10px 0;
        }
        .event-detail {
            display: flex;
            align-items: center;
            margin: 8px 0;
            color: #6b7280;
            font-size: 14px;
        }
        .event-detail strong {
            color: #1f2937;
            margin-right: 8px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
            color: white;
            padding: 12px 32px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            opacity: 0.9;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>🎉 Post Became an Event!</h1>
        </div>

        {{-- Content --}}
        <div class="content">
            <div class="greeting">
                Hi {{ $recipient->display_name ?? $recipient->username }},
            </div>

            <p>
                <strong>{{ $host->display_name ?? $host->username }}</strong> just created an event based on the post you were interested in!
            </p>

            {{-- Event Card --}}
            <div class="event-card">
                <div class="event-title">{{ $activity->title }}</div>
                
                @if($activity->description)
                    <p style="color: #6b7280; margin: 10px 0; font-size: 14px;">
                        {{ Str::limit($activity->description, 150) }}
                    </p>
                @endif

                <div class="divider"></div>

                <div class="event-detail">
                    <strong>📍 Location:</strong>
                    {{ $activity->location_name }}
                </div>

                <div class="event-detail">
                    <strong>🕐 Start Time:</strong>
                    {{ $activity->start_time->format('M d, Y \a\t g:i A') }}
                </div>

                <div class="event-detail">
                    <strong>⏱️ Duration:</strong>
                    {{ $activity->start_time->diffInHours($activity->end_time) }} hours
                </div>

                @if($activity->max_attendees)
                    <div class="event-detail">
                        <strong>👥 Max Attendees:</strong>
                        {{ $activity->max_attendees }}
                    </div>
                @endif

                <div class="event-detail">
                    <strong>💰 Price:</strong>
                    @if($activity->is_paid)
                        ${{ number_format($activity->price_cents / 100, 2) }}
                    @else
                        Free
                    @endif
                </div>
            </div>

            <p>
                Interested? Click the button below to view the event and RSVP!
            </p>

            <div style="text-align: center;">
                <a href="{{ $rsvpUrl }}" class="cta-button">View Event & RSVP</a>
            </div>

            <p style="color: #6b7280; font-size: 14px;">
                You received this email because you reacted to the original post. You can manage your notification preferences in your account settings.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>© {{ date('Y') }} FunLynk. All rights reserved.</p>
            <p>
                <a href="{{ route('home') }}" style="color: #9ca3af; text-decoration: none;">Visit FunLynk</a> ·
                <a href="{{ route('settings.notifications') }}" style="color: #9ca3af; text-decoration: none;">Notification Settings</a>
            </p>
            <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                You're receiving this email because you reacted to a post that was converted to an event.
            </p>
        </div>
    </div>
</body>
</html>

