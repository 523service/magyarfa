@if($featuredCategories->isNotEmpty())
<section class="mfa-section">
    <h2 class="mfa-section-title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 3 9l9 5 9-5-9-7Z"/><path d="m3 14 9 5 9-5"/></svg>
        <span>Termékkategóriák</span>
    </h2>

    <div class="mfa-categories">
        @foreach($featuredCategories as $category)
            @php $imageUrl = $category->getFirstMediaUrl(); @endphp
            <a href="{{ route('category.show', $category->slug) }}" class="mfa-category-card">
                <div class="mfa-category-media" @unless($imageUrl) style="background: var(--mfa-placeholder-gradient-{{ $loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3 }})" @endunless>
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $category->name }}" loading="lazy">
                    @endif
                </div>
                <div class="mfa-category-overlay"></div>
                <div class="mfa-category-body">
                    <h3>{{ $category->meta['featured_label'] ?? $category->name }}</h3>
                    <p>{{ $category->description ?? '' }}</p>
                    <span class="mfa-category-cta">Megnézem →</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif
