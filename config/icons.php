<?php


/**
 * Render an inline SVG icon.
 */
function icon(string $name, int $size = 20, string $class = 'icon'): string
{
    static $paths = null;
    if ($paths === null) {
        $paths = [
            'calendar'      => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'clock'         => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            'map-pin'       => '<path d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
            'user'          => '<path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/>',
            'users'         => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'search'        => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
            'plus'          => '<path d="M12 5v14M5 12h14"/>',
            'x'             => '<path d="M18 6 6 18M6 6l12 12"/>',
            'check'         => '<path d="M20 6 9 17l-5-5"/>',
            'check-circle'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>',
            'x-circle'      => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
            'alert-circle'  => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
            'menu'          => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>',
            'log-out'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'home'          => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
            'mail'          => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/>',
            'phone'         => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
            'link'          => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
            'copy'          => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            'qr-code'       => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3M17 17h3v3M14 20h3"/>',
            'download'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
            'print'         => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
            'trash'         => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
            'edit'          => '<path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'eye'           => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off'       => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22"/>',
            'layout-dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
            'bar-chart'     => '<path d="M12 20V10M18 20V4M6 20v4"/>',
            'file-text'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
            'award'         => '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>',
            'star'          => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/>',
            'inbox'         => '<path d="M22 12h-6l-2 3H10l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
            'globe'         => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20M2 12h20"/>',
            'save'          => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
            'book'          => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
            'trophy'        => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20 17 22M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
            'palette'       => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>',
            'cpu'           => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2"/>',
            'heart'         => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
            'party'         => '<path d="M5.8 11.3 2 22l10.7-3.79M4 3h.01M22 8h.01M12 2v.01M19 4l-1 1M5 19l-1 1M19 13l1 1M12 22v-1"/><path d="m14.5 9.5 3 3L22 8l-4.5-4.5-3 3L12 5 8.5 8.5l3 3-3 3 3 3 3-3 3 3 3-3-3-3z"/>',
            'wrench'        => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
            'megaphone'     => '<path d="m3 11 18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
            'lock'          => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'zap'           => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
            'ticket'        => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><path d="M13 5v14"/>',
            'database'      => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>',
            'smartphone'    => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
            'sparkles'      => '<path d="m12 3-1.9 5.8H4.4l4.8 3.5-1.9 5.8L12 14.6l4.7 3.5-1.9-5.8 4.8-3.5H13.9z"/>',
            'ban'           => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
            'key'           => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3"/>',
        ];
    }

    $path = $paths[$name] ?? $paths['calendar'];
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';

    return '<svg' . $classAttr . ' width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/** Map category name to icon slug. */
function categoryIconSlug(?string $categoryName): string
{
    $map = [
        'Academic'       => 'book',
        'Sports'         => 'trophy',
        'Arts & Culture' => 'palette',
        'Technology'     => 'cpu',
        'Health'         => 'heart',
        'Social'         => 'party',
        'Workshop'       => 'wrench',
        'Seminar'        => 'megaphone',
    ];
    return $map[$categoryName ?? ''] ?? 'calendar';
}

/** Colored category mark (icon in tinted box). */
function categoryMark(?array $category, int $iconSize = 20, string $extraClass = ''): string
{
    $name  = $category['name'] ?? 'Event';
    $color = $category['color'] ?? '#1a9e5c';
    $slug  = categoryIconSlug($name);
    $class = trim('category-mark ' . $extraClass);

    return '<span class="' . e($class) . '" style="--cat-color:' . e($color) . '" title="' . e($name) . '">'
        . icon($slug, $iconSize, 'category-mark-icon') . '</span>';
}

/** Large category display for event cards without image. */
function categoryHeroMark(?array $category): string
{
    return categoryMark($category, 40, 'category-mark--lg');
}

/** Status indicator dot (no emoji). */
function statusDot(string $status): string
{
    return '<span class="status-dot status-dot--' . e($status) . '" aria-hidden="true"></span>';
}

/** Empty / feedback state icon block. */
function stateIcon(string $name, string $variant = 'default'): string
{
    $size = $variant === 'lg' ? 48 : 40;
    return '<div class="state-icon state-icon--' . e($variant) . '">' . icon($name, $size, 'state-icon-svg') . '</div>';
}

/** Sidebar nav icon wrapper. */
function navIcon(string $name): string
{
    return '<span class="sidebar-link-icon">' . icon($name, 20, 'sidebar-icon') . '</span>';
}

/** Dash stat icon box. */
function dashIcon(string $name, string $tone = 'purple'): string
{
    return '<div class="dash-stat-icon ' . e($tone) . '">' . icon($name, 22, 'dash-stat-svg') . '</div>';
}

/** Button with leading icon. */
function btnIcon(string $label, string $iconName, string $btnClass = 'btn btn-primary'): string
{
    return '<span class="btn-with-icon ' . e($btnClass) . '">'
        . icon($iconName, 18, 'btn-icon-svg') . '<span>' . e($label) . '</span></span>';
}

/** Featured event badge. */
function featuredBadge(): string
{
    return '<span class="badge badge-featured icon-inline">' . icon('star', 12) . ' Featured</span>';
}

/** Attendance status badge. */
function attendanceBadge(string $status): string
{
    if ($status === 'attended') {
        return '<span class="badge badge-ongoing icon-inline">' . icon('check', 12) . ' Attended</span>';
    }
    return '<span class="badge badge-category icon-inline">' . icon('clock', 12) . ' Registered</span>';
}
