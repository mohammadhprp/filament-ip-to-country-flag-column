document.addEventListener('alpine:init', () => {
    // Must stay in sync with
    // Mohammadhprp\IPToCountryFlagColumn\Livewire\IPToCountryFlagColumnHook::METHOD.
    // The PHP constant cannot be read from plain JS with no build step, so this
    // string is the one place that name is unavoidably duplicated.
    const RESOLVE_METHOD = 'resolveIpToCountryFlagColumns'

    window.Alpine.store('ipCountryFlagColumn', {
        // Map<componentId, Map<token, el[]>> - grouped by Livewire component (its
        // wire:id) rather than one page-wide bag. The server only resolves columns
        // against the CALLED component's own table, so a page with several tables
        // must route each cell to its own table.
        pending: new Map(),

        // Flat FIFO of { componentId, token, elements } waiting to be resolved.
        // Cells are requested one at a time (see drain()) so each one paints as
        // soon as its own value arrives.
        queue: [],

        // True while drain() is stepping through the queue. Prevents two drains
        // from overlapping, which is also what keeps Livewire from bundling the
        // per-cell requests back into a single response.
        draining: false,

        // Map<componentId, Map<token, html>> - likewise keyed by component. A token
        // only encodes columnName + recordKey, so two tables using the same column
        // name against a record with the same id produce identical tokens. An
        // unscoped cache would paint one table's HTML into another table's cell for
        // a different underlying record.
        cache: new Map(),

        // Elements currently enqueued or in-flight. Needed alongside the settled
        // dataset flag: this covers the window where a cell has been sent to the
        // server but no response has come back yet.
        tracked: new WeakSet(),

        // WeakMap<el, string> - the cell's server-rendered loading markup, captured
        // before we ever overwrite it, so retry can put it back.
        loading: new WeakMap(),

        scheduled: false,
        observer: null,

        register(el) {
            const token = el.dataset.ipCountryFlagToken

            if (! token) {
                return
            }

            // Already reached a terminal state and nothing about the DOM has
            // changed since. Livewire's morph strips this attribute back off
            // whenever it re-sends the server's fresh (unresolved) placeholder.
            if (el.dataset.ipCountryFlagSettled === '1') {
                return
            }

            if (! this.loading.has(el)) {
                this.loading.set(el, el.innerHTML)
            }

            const componentId = this.componentIdFor(el)

            // Repaint instantly from the client-side cache: after a sort or a
            // paginate, rows we already resolved should not hit the server again.
            if (componentId && this.cache.get(componentId)?.has(token)) {
                el.innerHTML = this.cache.get(componentId).get(token)
                el.dataset.ipCountryFlagSettled = '1'

                return
            }

            if (this.tracked.has(el)) {
                return
            }

            if (el.dataset.ipCountryFlagWhenVisible === '1') {
                this.tracked.add(el)
                this.observe(el)

                return
            }

            this.enqueue(el)
        },

        observe(el) {
            if (! this.observer) {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (! entry.isIntersecting) {
                            return
                        }

                        this.observer.unobserve(entry.target)
                        this.enqueue(entry.target)
                    })
                })
            }

            this.observer.observe(el)
        },

        enqueue(el) {
            const token = el.dataset.ipCountryFlagToken
            const componentId = this.componentIdFor(el)

            if (! componentId) {
                return
            }

            this.tracked.add(el)

            if (! this.pending.has(componentId)) {
                this.pending.set(componentId, new Map())
            }

            const cells = this.pending.get(componentId)

            if (! cells.has(token)) {
                cells.set(token, [])
            }

            cells.get(token).push(el)
            this.schedule()
        },

        // One animation frame of debounce coalesces everything pending into as many
        // requests as there are distinct Livewire components with pending cells -
        // still exactly one request per component, no matter how many rows.
        schedule() {
            if (this.scheduled) {
                return
            }

            this.scheduled = true

            requestAnimationFrame(() => {
                this.scheduled = false
                this.flush()
            })
        },

        flush() {
            if (this.pending.size === 0) {
                return
            }

            const batches = this.pending
            this.pending = new Map()

            batches.forEach((cells, componentId) => {
                cells.forEach((elements, token) => {
                    this.queue.push({ componentId, token, elements })
                })
            })

            this.drain()
        },

        // Resolves one cell per request, in order, painting each cell the moment
        // its own value comes back. Awaiting each request before sending the next
        // is deliberate: it is what stops Livewire from bundling the calls into a
        // single response, which would make every cell appear together again.
        async drain() {
            if (this.draining) {
                return
            }

            this.draining = true

            try {
                while (this.queue.length > 0) {
                    const { componentId, token, elements } = this.queue.shift()
                    const batch = new Map([[token, elements]])

                    // Already resolved or failed - a retry can race a request that
                    // is still in flight.
                    if (elements.every((el) => el.dataset.ipCountryFlagSettled === '1')) {
                        continue
                    }

                    let component = null

                    // The component may have been removed from the page between
                    // enqueue and now, or Livewire.find() may throw on an id it no
                    // longer knows about. Either way this must not stop the rest of
                    // the queue.
                    try {
                        component = window.Livewire.find(componentId)
                    } catch (error) {
                        component = null
                    }

                    if (! component) {
                        this.markFailed(batch)

                        continue
                    }

                    try {
                        const result = await component.call(RESOLVE_METHOD, [token])
                        this.apply(componentId, batch, result)
                    } catch (error) {
                        this.markFailed(batch)
                    }
                }
            } finally {
                this.draining = false
            }

            // Cells may have been queued while the last request was in flight, back
            // when draining was still true and this method would have declined.
            if (this.queue.length > 0) {
                this.drain()
            }
        },

        apply(componentId, batch, result) {
            const cells = (result && result.cells) || {}

            // Any token the server declined to return - max_batch_size truncation,
            // a record deleted between paint and resolve, a column toggled off
            // mid-flight, ... - must not be left shimmering forever. Everything
            // requested but absent degrades to a failed, retryable cell.
            const missing = new Map()

            batch.forEach((elements, token) => {
                const cell = cells[token]

                if (! cell) {
                    missing.set(token, elements)

                    return
                }

                if (! cell.error) {
                    if (! this.cache.has(componentId)) {
                        this.cache.set(componentId, new Map())
                    }

                    this.cache.get(componentId).set(token, cell.html)
                }

                elements.forEach((el) => {
                    el.innerHTML = cell.html
                    el.dataset.ipCountryFlagError = cell.error ? '1' : '0'
                    el.dataset.ipCountryFlagSettled = '1'
                    this.tracked.delete(el)

                    if (cell.error) {
                        this.appendRetryAffordance(el)
                    }
                })
            })

            if (missing.size > 0) {
                this.markFailed(missing)
            }
        },

        markFailed(batch) {
            batch.forEach((elements) => {
                elements.forEach((el) => this.renderFailed(el))
            })
        },

        renderFailed(el) {
            el.dataset.ipCountryFlagError = '1'
            el.dataset.ipCountryFlagSettled = '1'
            this.tracked.delete(el)

            el.innerHTML = ''

            const errorLabel = el.dataset.ipCountryFlagErrorLabel

            if (errorLabel) {
                const message = document.createElement('span')
                message.textContent = errorLabel
                el.appendChild(message)
            }

            this.appendRetryAffordance(el)
        },

        appendRetryAffordance(el) {
            if (el.dataset.ipCountryFlagRetryable !== '1') {
                return
            }

            const label = el.dataset.ipCountryFlagRetryLabel

            if (! label) {
                return
            }

            el.appendChild(document.createTextNode(' '))

            const retry = document.createElement('span')
            retry.className = 'fi-ip-country-flag-retry'
            retry.textContent = label
            el.appendChild(retry)
        },

        retry(el) {
            delete el.dataset.ipCountryFlagError
            delete el.dataset.ipCountryFlagSettled

            // Put the loading markup back, so the click has visible effect straight
            // away instead of reading as a dead button until the response lands.
            if (this.loading.has(el)) {
                el.innerHTML = this.loading.get(el)
            }

            const token = el.dataset.ipCountryFlagToken
            const componentId = this.componentIdFor(el)

            if (componentId) {
                this.cache.get(componentId)?.delete(token)
            }

            this.enqueue(el)
        },

        componentIdFor(el) {
            const root = el.closest('[wire\\:id]')

            return root ? root.getAttribute('wire:id') : null
        },
    })

    // Defensive safety net for Livewire's DOM morph: Alpine's morph integration
    // only re-runs x-init on nodes the morph ADDS, never on ones it merely UPDATES.
    // A placeholder that survives such a morph has its children patched back to the
    // server's fresh skeleton without x-init re-running, so without this it would
    // sit there forever instead of repainting from the client-side cache.
    //
    // register() is deferred with queueMicrotask(): morph.updated fires for an
    // element BEFORE morphdom patches that same element's children, so a
    // synchronous cache repaint here would be immediately clobbered. register() is
    // idempotent, so this is safe even when x-init already re-ran for an element.
    if (typeof window.Livewire !== 'undefined') {
        window.Livewire.hook('morph.updated', ({ el }) => {
            if (! el?.dataset?.ipCountryFlagToken) {
                return
            }

            queueMicrotask(() => {
                window.Alpine.store('ipCountryFlagColumn')?.register(el)
            })
        })
    }
})
