<aside class="sidebar">
    <!-- Categories Card -->
    @if(isset($categories) && $categories->count() > 0)
    <div class="sidebar-card">
        <h2 class="sidebar-title">Termék kategóriák</h2>

        <div x-data="{ openCategory: {{ isset($category) ? ($category->parent_id ?? $category->id) : ($categories->first()?->id ?? 'null') }} }">
            @foreach($categories as $parentCategory)
                <div
                    class="category-group"
                    :class="{ 'open': openCategory === {{ $parentCategory->id }} }"
                >
                    @if($parentCategory->children->count() > 0)
                        {{-- Parent with children - collapsible --}}
                        <div
                            class="category-group-header"
                            @click="openCategory = openCategory === {{ $parentCategory->id }} ? null : {{ $parentCategory->id }}"
                        >
                            <span class="category-group-header-title">{{ $parentCategory->name }}</span>
                            <span class="category-group-icon">▾</span>
                        </div>
                        <ul class="submenu">
                            @foreach($parentCategory->children as $childCategory)
                                <li>
                                    <a href="{{ route('category.show', $childCategory->slug) }}">
                                        {{ $childCategory->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        {{-- Parent without children - direct link --}}
                        <a href="{{ route('category.show', $parentCategory->slug) }}" class="category-group-header">
                            <span class="category-group-header-title">{{ $parentCategory->name }}</span>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

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
