export default function vacancyLiveSearch({ filtersOpen = false } = {}) {
    return {
        filtersOpen,
        loading: false,
        announcement: '',
        abortController: null,
        requestSequence: 0,
        pendingUrl: null,
        lastSuccessfulUrl: window.location.href,
        popstateHandler: null,
        form: null,

        init() {
            this.form = this.$root;
            this.popstateHandler = () => {
                const url = new URL(window.location.href);

                this.syncFormFromUrl(url);
                this.$nextTick(() => this.updateResults(url, { updateHistory: false }));
            };

            window.addEventListener('popstate', this.popstateHandler);
        },

        destroy() {
            this.abortController?.abort();
            window.removeEventListener('popstate', this.popstateHandler);
        },

        queueSearch() {
            this.loading = true;
            this.announcement = '';
        },

        submitSearch() {
            return this.updateResults(this.formUrl());
        },

        clearSearch(target) {
            const url = new URL(target, window.location.origin);

            this.syncFormFromUrl(url);
            this.$nextTick(() => this.updateResults(url));
        },

        formUrl() {
            const url = new URL(this.form.action, window.location.origin);

            for (const [name, value] of new FormData(this.form).entries()) {
                if (typeof value === 'string' && value !== '') {
                    url.searchParams.append(name, value);
                }
            }

            return url;
        },

        syncFormFromUrl(url) {
            const additionalFilters = [
                'bedrijf',
                'dienstverband',
                'ervaring',
                'functiegebied',
                'opleiding',
                'sector',
                'vergoeding',
                'bedrag_van',
                'bedrag_tot',
                'werklocatie',
            ];

            for (const control of this.form.elements) {
                if (! control.name || control.name === 'meer_filters' || control.type === 'submit') {
                    continue;
                }

                control.value = url.searchParams.get(control.name) ?? (control.name === 'sort' ? 'nieuwste' : '');
            }

            this.filtersOpen = url.searchParams.has('meer_filters')
                || additionalFilters.some((name) => url.searchParams.has(name))
                || (url.searchParams.has('sort') && url.searchParams.get('sort') !== 'nieuwste');
        },

        async updateResults(url, { updateHistory = true } = {}) {
            const targetUrl = new URL(url, window.location.origin);

            if (targetUrl.href === this.pendingUrl || targetUrl.href === this.lastSuccessfulUrl) {
                this.loading = false;

                return;
            }

            const results = document.querySelector('[data-vacancy-results]');

            if (! results) {
                window.location.assign(targetUrl.href);

                return;
            }

            this.abortController?.abort();
            this.abortController = new AbortController();
            const sequence = ++this.requestSequence;
            const focusedElement = this.form.contains(document.activeElement) ? document.activeElement : null;
            const selection = focusedElement instanceof HTMLInputElement
                ? [focusedElement.selectionStart, focusedElement.selectionEnd]
                : null;

            this.pendingUrl = targetUrl.href;
            this.loading = true;
            this.announcement = '';
            results.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(targetUrl, {
                    headers: {
                        Accept: 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SMV-Search-Results': '1',
                    },
                    signal: this.abortController.signal,
                });

                if (! response.ok) {
                    throw new Error(`Search request failed with status ${response.status}`);
                }

                const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
                const replacement = responseDocument.querySelector('[data-vacancy-results]');

                if (! replacement) {
                    throw new Error('Search response did not contain a results region');
                }

                if (sequence !== this.requestSequence) {
                    return;
                }

                const scrollPosition = { left: window.scrollX, top: window.scrollY };
                results.replaceWith(document.importNode(replacement, true));
                this.lastSuccessfulUrl = targetUrl.href;

                if (updateHistory && targetUrl.href !== window.location.href) {
                    window.history.pushState({ vacancySearch: true }, '', targetUrl);
                }

                const count = Number.parseInt(replacement.dataset.resultCount ?? '0', 10);
                this.announcement = count === 1
                    ? '1 vacature gevonden.'
                    : `${count} vacatures gevonden.`;

                this.$nextTick(() => {
                    window.scrollTo({ ...scrollPosition, behavior: 'auto' });

                    if (focusedElement instanceof HTMLElement
                        && document.contains(focusedElement)
                        && document.activeElement !== focusedElement) {
                        focusedElement.focus({ preventScroll: true });

                        if (selection && focusedElement instanceof HTMLInputElement) {
                            focusedElement.setSelectionRange(selection[0], selection[1]);
                        }
                    }
                });
            } catch (error) {
                if (error.name !== 'AbortError' && sequence === this.requestSequence) {
                    results.removeAttribute('aria-busy');
                    this.announcement = 'De vacatures konden niet worden bijgewerkt. Probeer het opnieuw.';
                }
            } finally {
                if (sequence === this.requestSequence) {
                    this.pendingUrl = null;
                    this.loading = false;
                }
            }
        },
    };
}
