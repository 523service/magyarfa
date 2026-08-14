import { liteClient as algoliasearch } from 'algoliasearch/lite';
import instantsearch from 'instantsearch.js';
import { searchBox, hits, pagination, refinementList, stats, configure } from 'instantsearch.js/es/widgets';

const searchClient = algoliasearch(
    import.meta.env.VITE_ALGOLIA_APP_ID,
    import.meta.env.VITE_ALGOLIA_SEARCH_KEY
);

const search = instantsearch({
    indexName: 'shop_products',
    searchClient,
    // Custom stateMapping so the autocomplete's ?q= param is read correctly.
    // Default routing uses ?shop_products[query]=... which conflicts with ?q=.
    routing: {
        stateMapping: {
            stateToRoute(uiState) {
                const s = uiState['shop_products'] || {};
                const route = {};
                if (s.query) route.q = s.query;
                if (s.refinementList?.brand_name?.length) route.brand = s.refinementList.brand_name;
                if (s.refinementList?.category_names?.length) route.category = s.refinementList.category_names;
                if (s.page > 1) route.page = s.page;
                return route;
            },
            routeToState(routeState) {
                return {
                    shop_products: {
                        query: routeState.q || '',
                        refinementList: {
                            brand_name: routeState.brand,
                            category_names: routeState.category,
                        },
                        page: routeState.page,
                    },
                };
            },
        },
    },
});

search.addWidgets([
    configure({ hitsPerPage: 12 }),

    searchBox({ container: '#search-box', placeholder: 'Termék keresése…', autofocus: true }),

    stats({
        container: '#search-stats',
        templates: {
            text: ({ nbHits, query }) =>
                query ? `${nbHits.toLocaleString('hu-HU')} találat: „${query}"` : `${nbHits.toLocaleString('hu-HU')} termék`,
        },
    }),

    hits({
        container: '#hits',
        templates: {
            item: (hit, { html, components }) => html`
                <article class="product-card">
                    <a href="${hit.url}" class="product-image">
                        ${hit.image_url
                            ? html`<img src="${hit.image_url}" alt="${hit.name}" class="w-full h-full object-cover" loading="lazy">`
                            : html`<span class="text-gray-400 text-sm">Termékkép</span>`}
                    </a>
                    <div class="product-title">
                        <a href="${hit.url}">${components.Highlight({ hit, attribute: 'name' })}</a>
                    </div>
                    <div class="product-meta">${hit.brand_name ?? ''}</div>
                    <div class="product-footer">
                        <div class="product-price">${hit.price_formatted} <small>bruttó</small></div>
                        <a href="${hit.url}" class="btn-outline">Részletek</a>
                    </div>
                </article>`,
            empty: ({ query }, { html }) => html`
                <div class="col-span-full text-center py-12 text-gray-500">
                    Nincs találat: „${query}". Próbáljon más keresési kifejezést.
                </div>`,
        },
    }),

    refinementList({ container: '#refinement-brand', attribute: 'brand_name', limit: 10, showMore: true }),
    refinementList({ container: '#refinement-category', attribute: 'category_names', limit: 10, showMore: true }),

    pagination({
        container: '#pagination',
        padding: 2,
        templates: { previous: '‹ Előző', next: 'Következő ›' },
    }),
]);

search.start();
