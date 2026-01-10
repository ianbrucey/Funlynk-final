@props(['activity'])

{{-- Basic Meta Tags --}}
<meta name="description" content="{{ Str::limit($activity->description, 160) }}">
<meta name="keywords" content="{{ $activity->tags->pluck('name')->implode(', ') }}">

{{-- Open Graph Meta Tags (Facebook, LinkedIn) --}}
<meta property="og:type" content="event">
<meta property="og:title" content="{{ $activity->title }}">
<meta property="og:description" content="{{ Str::limit($activity->description, 200) }}">
<meta property="og:url" content="{{ route('events.public', $activity->slug) }}">
<meta property="og:site_name" content="{{ config('app.name') }}">

@if($activity->cover_image_url)
<meta property="og:image" content="{{ $activity->cover_image_url }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $activity->title }}">
@else
<meta property="og:image" content="{{ asset('images/default-event-cover.jpg') }}">
@endif

{{-- Event-specific Open Graph tags --}}
<meta property="event:start_time" content="{{ $activity->start_time->toIso8601String() }}">
<meta property="event:end_time" content="{{ $activity->end_time?->toIso8601String() }}">
<meta property="event:location:latitude" content="{{ $activity->location_coordinates->latitude }}">
<meta property="event:location:longitude" content="{{ $activity->location_coordinates->longitude }}">

{{-- Twitter Card Meta Tags --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $activity->title }}">
<meta name="twitter:description" content="{{ Str::limit($activity->description, 200) }}">
@if($activity->cover_image_url)
<meta name="twitter:image" content="{{ $activity->cover_image_url }}">
@else
<meta name="twitter:image" content="{{ asset('images/default-event-cover.jpg') }}">
@endif
<meta name="twitter:image:alt" content="{{ $activity->title }}">

{{-- Schema.org Event Markup (JSON-LD) --}}
@php
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $activity->title,
    'description' => $activity->description,
    'startDate' => $activity->start_time->toIso8601String(),
    'endDate' => $activity->end_time?->toIso8601String() ?? $activity->start_time->toIso8601String(),
    'location' => [
        '@type' => 'Place',
        'name' => $activity->location_name,
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $activity->location_coordinates->latitude,
            'longitude' => $activity->location_coordinates->longitude,
        ],
    ],
    'organizer' => [
        '@type' => 'Person',
        'name' => $activity->host->display_name,
        'url' => route('profile.view', $activity->host->username),
    ],
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
];

if ($activity->cover_image_url) {
    $schemaData['image'] = [$activity->cover_image_url];
}

if ($activity->location_address) {
    $schemaData['location']['address'] = $activity->location_address;
}

if ($activity->price > 0) {
    $schemaData['offers'] = [
        '@type' => 'Offer',
        'price' => $activity->price,
        'priceCurrency' => 'USD',
        'availability' => 'https://schema.org/InStock',
        'url' => route('events.public', $activity->slug),
    ];
} else {
    $schemaData['isAccessibleForFree'] = true;
}
@endphp
<script type="application/ld+json">
{!! json_encode($schemaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>

