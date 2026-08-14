@if($featuredCategories->isNotEmpty())
<div class="subcategories">
    <div class="subcategories-grid">
        @foreach($featuredCategories as $category)
            <a href="{{ route('category.show', $category->slug) }}" class="subcategory-card">
                <div class="subcategory-icon">
                    @if(!empty($category->meta['icon_path']))
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path d="{{ $category->meta['icon_path'] }}" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    @else
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    @endif
                </div>
                <span class="subcategory-name">{{ $category->meta['featured_label'] ?? $category->name }}</span>
                <span class="subcategory-count">{{ $category->products_count }} termék</span>
            </a>
        @endforeach
    </div>
</div>
@endif