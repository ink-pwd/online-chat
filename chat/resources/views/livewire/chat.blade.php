<div class="relative mb-6 w-full">
    <flux:heading size="xl" level="1">{{ __('Online Chat') }}</flux:heading>


    <div
        x-data="{
            pane: 'list',
            conversations: [],
            activeId: null,
            draft: '',
            emojiOpen: false,
            lastMessageId: 0,
            emojis: ['\u{1F600}', '\u{1F603}', '\u{1F609}', '\u{1F60A}', '\u{1F60D}', '\u{1F618}', '\u{1F61C}', '\u{1F914}', '\u{1F644}', '\u{1F62C}', '\u{1F614}', '\u{1F62D}', '\u{1F621}', '\u{1F44D}', '\u{1F44E}', '\u{1F44C}', '\u{1F44F}', '\u{1F64F}', '\u{1F4AA}', '\u{2764}\u{FE0F}', '\u{1F525}', '\u{2728}', '\u{1F389}', '\u{1F680}'],

            get filtered() {
                return this.conversations
            },

            get active() {
                if (this.activeId === null) {
                    return null
                }

                return this.conversations.find(c =&gt; c.id === this.activeId) ?? null
            },

            open(id) {
                this.activeId = id
                this.pane = 'thread'
                this.emojiOpen = false

                const conversation = this.conversations.find(c =&gt; c.id === id)

                if (conversation) {
                    conversation.unread = 0
                }

                this.scrollDown()
            },

            startChat(user) {
                let conversation = this.conversations.find(c =&gt; c.id === user.id)

                if (! conversation) {
                    conversation = {
                        id: user.id,
                        name: user.name,
                        initials: user.initials,
                        color: user.color,
                        online: user.online,
                        typing: false,
                        unread: 0,
                        last_seen: @js(__('offline')),
                        messages: [],
                    }

                    this.conversations.unshift(conversation)
                }

                this.open(conversation.id)
            },

            send() {
                const body = this.draft.trim()

                if (body === '' || ! this.active) {
                    return
                }

                const stamp = this.stamp()

                this.active.messages.push({
                    id: ++this.lastMessageId,
                    body: body,
                    mine: true,
                    read: false,
                    time: stamp.time,
                    day: stamp.day,
                })

                this.draft = ''
                this.emojiOpen = false
                this.$nextTick(() =&gt; {
                    if (this.$refs.composer) {
                        this.grow(this.$refs.composer)
                    }
                })
                this.scrollDown()
            },

            insertEmoji(emoji) {
                this.draft += emoji
                this.emojiOpen = false
                this.$nextTick(() =&gt; {
                    if (this.$refs.composer) {
                        this.$refs.composer.focus()
                        this.grow(this.$refs.composer)
                    }
                })
            },

            grow(el) {
                el.style.height = 'auto'
                el.style.height = Math.min(el.scrollHeight, 160) + 'px'
            },

            scrollDown() {
                this.$nextTick(() =&gt; {
                    if (this.$refs.thread) {
                        this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight
                    }
                })
            },

            preview(conversation) {
                const last = conversation.messages[conversation.messages.length - 1]

                return last ? last.body : ''
            },

            previewTime(conversation) {
                const last = conversation.messages[conversation.messages.length - 1]

                return last ? last.time : ''
            },

            showDay(conversation, index) {
                if (index === 0) {
                    return true
                }

                return conversation.messages[index - 1].day !== conversation.messages[index].day
            },

            stamp() {
                const now = new Date()

                return {
                    time: now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    day: now.toLocaleDateString([], { day: 'numeric', month: 'long' }),
                }
            },
        }"
        x-init="scrollDown()"
        class="mt-6 flex h-[calc(100dvh-12rem)] min-h-[28rem] w-full overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm md:h-[calc(100dvh-9.5rem)] dark:border-zinc-700 dark:bg-zinc-900"
    >
        {{-- Список диалогов --}}
        <aside
            :class="pane === 'list' ? 'flex' : 'hidden md:flex'"
            class="w-full shrink-0 flex-col border-zinc-200 bg-zinc-50/60 md:w-72 md:border-e lg:w-80 dark:border-zinc-700 dark:bg-zinc-900/60"
        >
            <div class="px-4 pt-4">
                <flux:heading size="lg">{{ __('Messages') }}</flux:heading>
            </div>

            {{-- Поиск пользователей --}}
            <div
                class="px-4 pt-3 pb-3"
                x-data="{
                    endpoint: @js(route('users.search')),
                    query: '',
                    results: [],
                    open: false,
                    searching: false,
                    cursor: 0,
                    request: null,
                    palette: [
                        'from-rose-500 to-orange-500',
                        'from-sky-500 to-indigo-500',
                        'from-emerald-500 to-teal-500',
                        'from-violet-500 to-fuchsia-500',
                        'from-amber-500 to-pink-500',
                        'from-cyan-500 to-blue-600',
                    ],

                    get hasResults() {
                        return this.results.length &gt; 0
                    },

                    get tooShort() {
                        const term = this.query.trim()

                        return term.length &gt; 0 &amp;&amp; term.length &lt; 2
                    },

                    reset() {
                        if (this.request) {
                            this.request.abort()
                            this.request = null
                        }

                        this.query = ''
                        this.results = []
                        this.cursor = 0
                        this.searching = false
                        this.open = false
                    },

                    async run() {
                        const term = this.query.trim()

                        if (this.request) {
                            this.request.abort()
                            this.request = null
                        }

                        if (term.length &lt; 2) {
                            this.results = []
                            this.searching = false
                            this.open = false

                            return
                        }

                        const controller = new AbortController()

                        this.request = controller
                        this.cursor = 0
                        this.searching = true
                        this.open = true

                        try {
                            const response = await fetch(this.endpoint + '?query=' + encodeURIComponent(term), {
                                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                                signal: controller.signal,
                            })

                            if (! response.ok) {
                                throw new Error('Search failed: ' + response.status)
                            }

                            const payload = await response.json()

                            this.results = (payload.data ?? []).map(this.normalize, this)
                        } catch (error) {
                            if (error.name === 'AbortError') {
                                return
                            }

                            this.results = []
                        } finally {
                            if (this.request === controller) {
                                this.searching = false
                                this.request = null
                            }
                        }
                    },

                    normalize(user, index) {
                        const email = user.email ?? ''
                        const name = user.name ?? ''

                        return {
                            id: user.id ?? email ?? index,
                            name: name,
                            email: email,
                            initials: this.initials(name),
                            color: this.color(name + email),
                            online: user.online ?? false,
                        }
                    },

                    initials(name) {
                        const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2)

                        if (parts.length === 0) {
                            return '?'
                        }

                        return parts.map(part =&gt; part[0].toUpperCase()).join('')
                    },

                    color(seed) {
                        let hash = 0

                        for (let i = 0; i &lt; seed.length; i++) {
                            hash = (hash * 31 + seed.charCodeAt(i)) % 997
                        }

                        return this.palette[hash % this.palette.length]
                    },

                    move(step) {
                        if (! this.hasResults) {
                            return
                        }

                        this.open = true
                        this.cursor = (this.cursor + step + this.results.length) % this.results.length

                        this.$nextTick(() =&gt; {
                            const option = this.$refs.list?.querySelectorAll('[role=option]')[this.cursor]

                            if (option) {
                                option.scrollIntoView({ block: 'nearest' })
                            }
                        })
                    },

                    pick(user) {
                        if (! user) {
                            return
                        }

                        this.startChat(user)
                        this.reset()
                    },

                    escape(value) {
                        const node = document.createElement('div')

                        node.textContent = String(value ?? '')

                        return node.innerHTML
                    },

                    mark(value) {
                        const text = this.escape(value)
                        const term = this.escape(this.query.trim())

                        if (term.length &lt; 2) {
                            return text
                        }

                        const pattern = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&amp;') + ')', 'gi')

                        return text.replace(pattern, '&lt;mark class=\'rounded bg-amber-200/70 text-inherit dark:bg-amber-400/30\'&gt;$1&lt;/mark&gt;')
                    },
                }"
                x-on:keydown.escape.window="reset()"
            >
                <div class="relative" x-on:click.outside="open = false">
                    <flux:input
                        x-model="query"
                        x-on:input.debounce.300ms="run()"
                        x-on:focus="if (results.length) { open = true }"
                        x-on:keydown.arrow-down.prevent="move(1)"
                        x-on:keydown.arrow-up.prevent="move(-1)"
                        x-on:keydown.enter.prevent="pick(results[cursor])"
                        type="search"
                        size="sm"
                        icon="magnifying-glass"
                        autocomplete="off"
                        role="combobox"
                        aria-autocomplete="list"
                        x-bind:aria-expanded="open"
                        :placeholder="__('Search people')"
                        class="w-full"
                    />

                    {{-- Выпадающий список пользователей --}}
                    <div
                        x-show="open"
                        x-transition.origin.top
                        style="display: none"
                        role="listbox"
                        class="absolute inset-x-0 top-full z-30 mt-2 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 dark:border-zinc-700">
                            <span class="text-[11px] font-semibold tracking-wide text-zinc-400 uppercase">{{ __('People') }}</span>
                            <span
                                x-show="! searching &amp;&amp; hasResults"
                                x-text="results.length"
                                class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-zinc-100 px-1 text-[10px] font-semibold text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300"
                            ></span>
                        </div>

                        {{-- Скелетон загрузки --}}
                        <div x-show="searching" class="space-y-2.5 p-3">
                            <template x-for="i in 3" :key="i">
                                <div class="flex animate-pulse items-center gap-3">
                                    <div class="size-9 shrink-0 rounded-full bg-zinc-200 dark:bg-zinc-700"></div>
                                    <div class="min-w-0 flex-1 space-y-1.5">
                                        <div class="h-2.5 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                        <div class="h-2 w-1/3 rounded bg-zinc-100 dark:bg-zinc-700/60"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Найденные пользователи --}}
                        <div x-ref="list" x-show="! searching &amp;&amp; hasResults" class="max-h-72 overflow-y-auto p-1.5">
                            <template x-for="(user, i) in results" :key="user.id">
                                <button
                                    type="button"
                                    role="option"
                                    x-on:click="pick(user)"
                                    x-on:mouseenter="cursor = i"
                                    x-bind:aria-selected="cursor === i"
                                    :class="cursor === i ? 'bg-zinc-100 dark:bg-zinc-700/70' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/40'"
                                    class="flex w-full cursor-pointer items-center gap-3 rounded-lg p-2 text-start transition"
                                >
                                    <div class="relative shrink-0">
                                        <div
                                            :class="user.color"
                                            class="flex size-9 items-center justify-center rounded-full bg-gradient-to-br text-xs font-semibold text-white"
                                            x-text="user.initials"
                                        ></div>
                                        <span
                                            x-show="user.online"
                                            class="absolute -end-0.5 -bottom-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"
                                        ></span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-zinc-900 dark:text-white" x-html="mark(user.name)"></div>
                                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400" x-html="mark(user.email)"></div>
                                    </div>

                                    <flux:icon.plus class="size-4 shrink-0 text-zinc-400" />
                                </button>
                            </template>
                        </div>

                        {{-- Ничего не найдено --}}
                        <div x-show="! searching &amp;&amp; ! hasResults" class="px-4 py-8 text-center">
                            <flux:icon.users class="mx-auto mb-2 size-6 text-zinc-300 dark:text-zinc-600" />
                            <flux:text size="sm">{{ __('No users found') }}</flux:text>
                            <p class="mt-0.5 text-xs text-zinc-400">{{ __('Try another name') }}</p>
                        </div>

                        <div class="hidden border-t border-zinc-100 px-3 py-1.5 text-[10px] text-zinc-400 md:block dark:border-zinc-700">
                            {{ __('↑ ↓ — navigate, Enter — select, Esc — close') }}
                        </div>
                    </div>
                </div>

                <p x-show="tooShort" style="display: none" class="mt-1.5 text-[11px] text-zinc-400">
                    {{ __('Type at least 2 characters') }}
                </p>
            </div>

            <div class="min-h-0 flex-1 space-y-1 overflow-y-auto px-2 pb-3">
                <template x-for="c in filtered" :key="c.id">
                    <button
                        type="button"
                        x-on:click="open(c.id)"
                        :class="c.id === activeId
                            ? 'bg-white shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700'
                            : 'hover:bg-white/70 dark:hover:bg-zinc-800/60'"
                        class="flex w-full cursor-pointer items-center gap-3 rounded-xl p-2.5 text-start transition"
                    >
                        <div class="relative shrink-0">
                            <div
                                :class="c.color"
                                class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br text-sm font-semibold text-white"
                                x-text="c.initials"
                            ></div>
                            <span
                                x-show="c.online"
                                class="absolute -end-0.5 -bottom-0.5 size-3 rounded-full border-2 border-zinc-50 bg-emerald-500 dark:border-zinc-900"
                            ></span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-sm font-medium text-zinc-900 dark:text-white" x-text="c.name"></span>
                                <span class="shrink-0 text-xs text-zinc-400 dark:text-zinc-500" x-text="previewTime(c)"></span>
                            </div>

                            <div class="flex items-center justify-between gap-2">
                                <span
                                    x-show="! c.typing"
                                    class="truncate text-xs text-zinc-500 dark:text-zinc-400"
                                    x-text="preview(c)"
                                ></span>
                                <span x-show="c.typing" class="truncate text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                    {{ __('typing…') }}
                                </span>
                                <span
                                    x-show="c.unread &gt; 0"
                                    x-text="c.unread"
                                    class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-zinc-900 px-1.5 text-[11px] font-semibold text-white dark:bg-white dark:text-zinc-900"
                                ></span>
                            </div>
                        </div>
                    </button>
                </template>

                <div x-show="filtered.length === 0" class="px-4 py-10 text-center">
                    <flux:icon.magnifying-glass class="mx-auto mb-2 size-6 text-zinc-400" />
                    <flux:text size="sm">{{ __('Nothing found') }}</flux:text>
                </div>
            </div>
        </aside>

        {{-- Диалог --}}
        <section :class="pane === 'thread' ? 'flex' : 'hidden md:flex'" class="min-w-0 flex-1 flex-col">
            <template x-if="active">
                <div class="flex min-h-0 flex-1 flex-col">
                    {{-- Шапка диалога --}}
                    <header class="flex items-center gap-3 border-b border-zinc-200 px-3 py-3 md:px-5 dark:border-zinc-700">
                        <flux:button
                            icon="arrow-left"
                            variant="subtle"
                            size="sm"
                            class="md:hidden"
                            x-on:click="pane = 'list'"
                            :aria-label="__('Back')"
                        />

                        <div class="relative shrink-0">
                            <div
                                :class="active.color"
                                class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br text-sm font-semibold text-white"
                                x-text="active.initials"
                            ></div>
                            <span
                                x-show="active.online"
                                class="absolute -end-0.5 -bottom-0.5 size-3 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-900"
                            ></span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white" x-text="active.name"></div>
                            <div
                                class="truncate text-xs"
                                :class="active.online ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400'"
                                x-text="active.typing ? '{{ __('typing…') }}' : active.last_seen"
                            ></div>
                        </div>
                    </header>

                    {{-- Лента сообщений --}}
                    <div
                        x-ref="thread"
                        class="min-h-0 flex-1 space-y-1 overflow-y-auto bg-zinc-50/70 px-3 py-4 md:px-6 dark:bg-zinc-950/30"
                    >
                        <template x-for="(m, i) in active.messages" :key="m.id">
                            <div>
                                <div x-show="showDay(active, i)" class="my-4 flex justify-center">
                                    <span
                                        class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-zinc-500 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700"
                                        x-text="m.day"
                                    ></span>
                                </div>

                                <div class="flex" :class="m.mine ? 'justify-end' : 'justify-start'">
                                    <div
                                        :class="m.mine
                                            ? 'bg-zinc-100 text-zinc-900 rounded-br-md ring-1 ring-zinc-300/70 dark:bg-zinc-700/70 dark:text-zinc-50 dark:ring-zinc-600/70'
                                            : 'bg-white text-zinc-800 rounded-bl-md ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:ring-zinc-700'"
                                        class="max-w-[85%] rounded-2xl px-3.5 py-2 shadow-sm sm:max-w-[70%]"
                                    >
                                        <p class="text-sm leading-relaxed whitespace-pre-wrap break-words" x-text="m.body"></p>

                                        <div
                                            class="mt-1 flex items-center justify-end gap-1 text-[11px]"
                                            :class="m.mine ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-400'"
                                        >
                                            <span x-text="m.time"></span>
                                            <template x-if="m.mine">
                                                <svg viewBox="0 0 20 20" fill="none" class="size-3.5" :class="m.read ? 'text-zinc-600 dark:text-zinc-200' : 'text-zinc-400 dark:text-zinc-500'">
                                                    <path d="M1.5 10.5 5 14l6.5-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path x-show="m.read" d="M7.5 10.5 11 14l6.5-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Индикатор набора текста --}}
                        <div x-show="active.typing" class="flex justify-start pt-1">
                            <div class="flex items-center gap-1 rounded-2xl rounded-bl-md bg-white px-4 py-3 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                                <span class="size-1.5 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.3s]"></span>
                                <span class="size-1.5 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.15s]"></span>
                                <span class="size-1.5 animate-bounce rounded-full bg-zinc-400"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Поле ввода --}}
                    <footer class="border-t border-zinc-200 bg-white p-3 md:px-5 md:py-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <form x-on:submit.prevent="send()" class="flex items-end gap-2">
                            <div class="flex min-w-0 flex-1 items-end gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-2 focus-within:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:focus-within:border-zinc-500">
                                <textarea
                                    x-ref="composer"
                                    x-model="draft"
                                    x-on:input="grow($el)"
                                    x-on:keydown.enter.prevent="if (! $event.shiftKey) send(); else { draft += String.fromCharCode(10); $nextTick(() =&gt; grow($el)) }"
                                    rows="1"
                                    placeholder="{{ __('Write a message…') }}"
                                    class="max-h-40 w-full resize-none border-0 bg-transparent p-0 text-sm text-zinc-900 placeholder-zinc-400 focus:ring-0 focus:outline-none dark:text-white"
                                ></textarea>

                                <div class="relative shrink-0">
                                    <flux:button
                                        icon="face-smile"
                                        variant="ghost"
                                        size="xs"
                                        x-on:click="emojiOpen = ! emojiOpen"
                                        x-bind:aria-expanded="emojiOpen"
                                        :aria-label="__('Emoji')"
                                    />

                                    <div
                                        x-show="emojiOpen"
                                        x-on:click.outside="emojiOpen = false"
                                        x-on:keydown.escape.window="emojiOpen = false"
                                        x-transition.origin.bottom.right
                                        style="display: none"
                                        class="absolute end-0 bottom-full z-20 mb-2 w-64 rounded-xl border border-zinc-200 bg-white p-2 shadow-lg sm:w-72 dark:border-zinc-700 dark:bg-zinc-800"
                                    >
                                        <div class="grid max-h-48 grid-cols-8 gap-0.5 overflow-y-auto">
                                            <template x-for="emoji in emojis" :key="emoji">
                                                <button
                                                    type="button"
                                                    x-on:click="insertEmoji(emoji)"
                                                    x-text="emoji"
                                                    class="flex size-7 cursor-pointer items-center justify-center rounded-md text-lg leading-none transition hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <flux:button
                                type="submit"
                                icon="paper-airplane"
                                variant="primary"
                                class="shrink-0 rounded-full!"
                                x-bind:disabled="draft.trim() === ''"
                                :aria-label="__('Send')"
                            />
                        </form>

                        <p class="mt-2 hidden text-[11px] text-zinc-400 md:block">
                            {{ __('Enter — send, Shift + Enter — new line') }}
                        </p>
                    </footer>
                </div>
            </template>

            {{-- Пустое состояние --}}
            <div x-show="! active" class="flex flex-1 flex-col items-center justify-center gap-2 p-8 text-center">
                <flux:icon.chat-bubble-oval-left-ellipsis class="size-10 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg">{{ __('Select a conversation') }}</flux:heading>
                <flux:text>{{ __('Choose a chat on the left to start messaging.') }}</flux:text>
            </div>
        </section>
    </div>
</div>
