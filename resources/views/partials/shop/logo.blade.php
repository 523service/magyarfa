{{-- Magyar Fa logo — inline SVG pine icon + wordmark. Pass ['compact' => true] to omit the subtitle line. --}}
@php $compact = $compact ?? false; @endphp
<a href="{{ route('home') }}" class="logo-group" aria-label="Magyar Fa — kezdőlap">
    <span class="logo-icon" aria-hidden="true">
        <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 2 L28 14 H24 L30 22 H25 L31 30 H22 V38 H18 V30 H9 L15 22 H10 L16 14 H12 L20 2 Z" fill="currentColor"/>
        </svg>
    </span>
    <span class="logo-brand-name">
        <span class="logo-title">MAGYAR FA</span>
        @unless($compact)
            <span class="logo-subtitle">Faanyag kereskedés</span>
        @endunless
    </span>
</a>
