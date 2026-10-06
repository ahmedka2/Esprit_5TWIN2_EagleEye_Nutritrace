@props(['name'])

<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('truck')
            <path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>
            @break
        @case('route')
            <circle cx="6" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><path d="M8 18h2a4 4 0 0 0 4-4v-4a4 4 0 0 1 4-4"/>
            @break
        @case('pin')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
            @break
        @case('snowflake')
            <path d="M12 2v20M4.9 6l14.2 12M4.9 18 19.1 6M2 12h20"/>
            @break
        @case('thermometer')
            <path d="M14 14.8V5a2 2 0 0 0-4 0v9.8a4 4 0 1 0 4 0Z"/><path d="M12 10v7"/>
            @break
        @case('ruler')
            <path d="m4 20 16-16 2 2L6 22z"/><path d="m14 6 4 4M10 10l4 4M6 14l4 4"/>
            @break
        @case('package')
            <path d="m3 7 9 5 9-5-9-5z"/><path d="M3 7v10l9 5 9-5V7M12 12v10"/>
            @break
        @case('eye')
            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/>
            @break
        @case('pencil')
            <path d="m4 20 4.2-1 10.9-10.9a2.1 2.1 0 0 0-3-3L5.2 16z"/><path d="m14.5 6.7 3 3"/>
            @break
        @case('trash')
            <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v5M14 11v5"/>
            @break
    @endswitch
</svg>
