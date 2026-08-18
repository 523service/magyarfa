<aside class="sidebar">
    @if(isset($categories) && $categories->count() > 0)
    <div class="mfa-sidebar-card">
        <h2 class="mfa-sidebar-title">Kategóriák</h2>

        <div
            class="mfa-accordion"
            x-data="{ openCategory: {{ isset($category) ? ($category->parent_id ?? $category->id) : 'null' }} }"
        >
            @foreach($categories as $parentCategory)
                <div
                    class="mfa-accordion-item"
                    :class="{ 'is-open': openCategory === {{ $parentCategory->id }} }"
                >
                    @if($parentCategory->children->count() > 0)
                        <button
                            type="button"
                            class="mfa-accordion-header {{ isset($category) && ($category->id === $parentCategory->id || $category->parent_id === $parentCategory->id) ? 'is-current' : '' }}"
                            @click="openCategory = openCategory === {{ $parentCategory->id }} ? null : {{ $parentCategory->id }}"
                        >
                            <span class="mfa-accordion-icon">
                                @if(!empty($parentCategory->meta['icon_path']))
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="{{ $parentCategory->meta['icon_path'] }}" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @else
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @endif
                            </span>
                            <span class="mfa-accordion-title">{{ $parentCategory->name }}</span>
                            <svg class="mfa-accordion-chevron" width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <ul class="mfa-accordion-submenu">
                            @foreach($parentCategory->children as $childCategory)
                                <li>
                                    <a href="{{ route('category.show', $childCategory->slug) }}" class="{{ isset($category) && $category->id === $childCategory->id ? 'is-current' : '' }}">
                                        {{ $childCategory->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a
                            href="{{ route('category.show', $parentCategory->slug) }}"
                            class="mfa-accordion-header {{ isset($category) && $category->id === $parentCategory->id ? 'is-current' : '' }}"
                        >
                            <span class="mfa-accordion-icon">
                                @if(!empty($parentCategory->meta['icon_path']))
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="{{ $parentCategory->meta['icon_path'] }}" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @else
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @endif
                            </span>
                            <span class="mfa-accordion-title">{{ $parentCategory->name }}</span>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mfa-sidebar-cta">
        <h3>Nem találta, amit keres?</h3>
        <p>Kérjen ajánlatot egyedi méretre vagy speciális igényre!</p>
        <a href="{{ route('cart.index') }}" class="btn-cta-primary">
            <span>Ajánlatkérés</span> →
        </a>
    </div>

    {{--
    <!-- Brands Card -->
    @if(isset($brands) && $brands->count() > 0)
    <div class="sidebar-card">
        <h2 class="sidebar-title">Gyártók</h2>
        <ul class="simple-list">
            @foreach($brands as $brand)
                <li>
                    <a href="{{ route('home') }}">
                        <span>{{ $brand->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    --}}

    {{--
    <!-- Glossary Card -->
    @php
        $defaultGlossaryTerms = [
            ['name' => 'Hungarocell', 'slug' => 'hungarocell'],
            ['name' => 'Polisztirol', 'slug' => 'polisztirol'],
            ['name' => 'Homlokzati hőszigetelés', 'slug' => 'homlokzati-hoszigeteles'],
            ['name' => 'Grafitos hőszigetelés', 'slug' => 'grafitos-hoszigeteles'],
            ['name' => 'Lépésálló szigetelés', 'slug' => 'lepesallo-szigeteles'],
            ['name' => 'Lábazati hőszigetelés', 'slug' => 'labazati-hoszigeteles'],
            ['name' => 'Ásványgyapot', 'slug' => 'asvanygyapot'],
            ['name' => 'Kőzetgyapot', 'slug' => 'kozetgyapot'],
            ['name' => 'Üveggyapot', 'slug' => 'uveggyapot'],
        ];
        $displayTerms = $glossaryTerms ?? $defaultGlossaryTerms;
    @endphp

    @if(count($displayTerms) > 0)
    <div class="sidebar-card">
        <h2 class="sidebar-title">Fogalmak</h2>
        <ul class="simple-list">
            @foreach($displayTerms as $term)
                <li>
                    <a href="{{ route('home') }}">
                        <span>{{ $term['name'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    --}}
</aside>
