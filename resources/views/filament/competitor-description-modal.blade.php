<div class="space-y-4">
    @if($link->scraped_price || $link->scraped_sale_price)
        <div class="flex gap-4 text-sm">
            @if($link->scraped_price)
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Ár:</span>
                    <span class="ml-1 font-semibold">{{ number_format((float) $link->scraped_price, 0, ',', ' ') }} Ft</span>
                </div>
            @endif
            @if($link->scraped_sale_price)
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Akciós ár:</span>
                    <span class="ml-1 font-semibold text-danger-600">{{ number_format((float) $link->scraped_sale_price, 0, ',', ' ') }} Ft</span>
                </div>
            @endif
        </div>
    @endif

    @if($link->scraped_image_url)
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Kép URL:</p>
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    readonly
                    value="{{ $link->scraped_image_url }}"
                    class="flex-1 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded px-2 py-1 font-mono"
                    x-data
                    x-ref="imageUrl"
                />
                <button
                    type="button"
                    class="text-sm px-3 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded border border-gray-300 dark:border-gray-600"
                    x-data
                    @click="navigator.clipboard.writeText('{{ $link->scraped_image_url }}').then(() => $el.textContent = 'Másolva!').then(() => setTimeout(() => $el.textContent = 'Másolás', 1500))"
                >
                    Másolás
                </button>
            </div>
        </div>
    @endif

    <div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Leírás:</p>
            <button
                type="button"
                class="text-sm px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white rounded"
                x-data="{ copied: false }"
                @click="navigator.clipboard.writeText($refs.descText.innerText).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                x-text="copied ? 'Másolva!' : 'Leírás másolása'"
            ></button>
        </div>

        @if($link->scraped_description)
            <div
                x-ref="descText"
                class="max-h-96 overflow-y-auto bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-3 text-sm whitespace-pre-wrap leading-relaxed select-all"
            >{{ $link->scraped_description }}</div>
        @else
            <p class="text-sm text-gray-400 italic">Nincs scrape-elt leírás. Először futtasd a frissítést.</p>
        @endif
    </div>

    @if($link->scrape_error)
        <div class="text-sm text-danger-600 bg-danger-50 dark:bg-danger-900/20 rounded p-2">
            <span class="font-medium">Hiba:</span> {{ $link->scrape_error }}
        </div>
    @endif
</div>
