<section class="banner">
    <div class="banner-text">
        <strong>Bemutatkozó videó</strong>
        <span>Ismerd meg, hogyan segítünk a megfelelő hőszigetelő rendszer kiválasztásában.</span>
    </div>
    <div class="banner-pill">
        @if(isset($videoUrl))
            <a href="{{ $videoUrl }}" target="_blank" class="text-white hover:underline">
                Videó megtekintése
            </a>
        @else
            Itt lesz a YouTube beágyazás / hero kép
        @endif
    </div>
</section>
