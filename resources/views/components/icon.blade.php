@props(['name', 'size' => 20])
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('home')<path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/>@break
@case('search')<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>@break
@case('compass')<circle cx="12" cy="12" r="9"/><path d="m16 8-3 5-5 3 3-5Z"/>@break
@case('bookmark')<path d="M6 3h12v18l-6-4-6 4Z"/>@break
@case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>@break
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('edit')<path d="m15 4 5 5M4 20l5-1L21 7a2 2 0 0 0-5-5L4 15Z"/>@break
@case('user')<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>@break
@case('settings')<path d="m9 3-1 3-3 1-2 4 2 3v4l4 2 3-1 3 1 4-2v-4l2-3-2-4-3-1-1-3Z"/><circle cx="12" cy="12" r="3"/>@break
@case('heart')<path d="M20 5c-3-3-7-1-8 2C11 4 7 2 4 5c-5 5 2 10 8 15 6-5 13-10 8-15Z"/>@break
@case('comment')<path d="M21 11a9 9 0 0 1-9 9H3l2-5a9 9 0 1 1 16-4Z"/>@break
@case('arrow')<path d="M4 12h16m-6-6 6 6-6 6"/>@break
@case('shield')<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m8 12 3 3 5-6"/>@break
@case('chart')<path d="M4 3v18h17M8 16v-5m5 5V6m5 10v-7"/>@break
@case('code')<path d="m7 7-5 5 5 5m10-10 5 5-5 5M14 3l-4 18"/>@break
@case('menu')<path d="M3 6h18M3 12h18M3 18h18"/>@break
@case('sun')<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l2 2m10 10 2 2M5 19l2-2M17 7l2-2"/>@break
@default <path d="M12 5v14M5 12h14"/>
@endswitch
</svg>
