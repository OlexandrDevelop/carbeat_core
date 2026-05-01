<template>
    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">SEO Content</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Generated SEO texts are shown here. You can override any title,
                    description, intro, sections or FAQ without code changes.
                </p>
            </div>
            <a
                v-if="selected?.route"
                :href="selected.route"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Open page
            </a>
        </div>

        <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
            <section class="rounded-xl border bg-white p-4">
                <div class="grid gap-3">
                    <input
                        v-model="search"
                        type="text"
                        class="w-full rounded-lg border px-3 py-2"
                        placeholder="Search city, service or STO"
                    />
                    <select
                        v-model="type"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                        <option value="all">All types</option>
                        <option value="master">STO</option>
                        <option value="city">City</option>
                        <option value="city_service">City + service</option>
                    </select>
                </div>

                <div class="mt-4 max-h-[70vh] space-y-2 overflow-y-auto pr-1">
                    <button
                        v-for="entry in entries"
                        :key="entry.key"
                        type="button"
                        class="w-full rounded-xl border p-3 text-left transition"
                        :class="
                            selected?.key === entry.key
                                ? 'border-blue-300 bg-blue-50'
                                : 'border-gray-200 bg-white hover:border-gray-300'
                        "
                        @click="selectEntry(entry)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ entry.label }}
                                </div>
                                <div class="mt-1 text-xs uppercase tracking-wide text-gray-500">
                                    {{ entry.type }}
                                </div>
                            </div>
                            <span
                                v-if="entry.override && Object.keys(entry.override).length"
                                class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                            >
                                override
                            </span>
                        </div>
                        <div class="mt-2 line-clamp-2 text-xs text-gray-600">
                            {{ entry.final.description }}
                        </div>
                    </button>
                </div>
            </section>

            <section v-if="selected" class="rounded-xl border bg-white p-5">
                <div class="mb-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">
                        {{ selected.type }}
                    </div>
                    <div class="mt-1 text-xl font-semibold text-gray-900">
                        {{ selected.label }}
                    </div>
                    <div class="mt-1 text-sm text-gray-500">
                        {{ selected.route }}
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="space-y-5">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Title override</label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full rounded-lg border px-3 py-2"
                                :placeholder="selected.default.title"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Meta description override</label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full rounded-lg border px-3 py-2"
                                :placeholder="selected.default.description"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Intro override</label>
                            <textarea
                                v-model="form.intro"
                                rows="5"
                                class="w-full rounded-lg border px-3 py-2"
                                :placeholder="selected.default.intro || ''"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Sections JSON override</label>
                            <textarea
                                v-model="form.sectionsJson"
                                rows="10"
                                class="w-full rounded-lg border px-3 py-2 font-mono text-xs"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">FAQ JSON override</label>
                            <textarea
                                v-model="form.faqJson"
                                rows="10"
                                class="w-full rounded-lg border px-3 py-2 font-mono text-xs"
                            />
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                                :disabled="saving"
                                @click="save"
                            >
                                {{ saving ? 'Saving...' : 'Save override' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-700"
                                :disabled="saving"
                                @click="resetToDefault"
                            >
                                Reset fields
                            </button>
                            <span
                                v-if="message"
                                class="text-sm"
                                :class="messageType === 'error' ? 'text-red-700' : 'text-green-700'"
                            >
                                {{ message }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-xl border bg-gray-50 p-4">
                            <h2 class="text-sm font-semibold text-gray-900">Current final preview</h2>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500">Title</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ finalPreview.title }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500">Description</div>
                                    <div class="mt-1 text-sm text-gray-700">
                                        {{ finalPreview.description }}
                                    </div>
                                </div>
                                <div v-if="finalPreview.intro">
                                    <div class="text-xs uppercase tracking-wide text-gray-500">Intro</div>
                                    <div class="mt-1 text-sm text-gray-700">
                                        {{ finalPreview.intro }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border bg-gray-50 p-4">
                            <h2 class="text-sm font-semibold text-gray-900">Default sections</h2>
                            <pre class="mt-3 overflow-x-auto whitespace-pre-wrap text-xs text-gray-700">{{ pretty(selected.default.sections) }}</pre>
                        </div>

                        <div class="rounded-xl border bg-gray-50 p-4">
                            <h2 class="text-sm font-semibold text-gray-900">Default FAQ</h2>
                            <pre class="mt-3 overflow-x-auto whitespace-pre-wrap text-xs text-gray-700">{{ pretty(selected.default.faq) }}</pre>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

type Entry = {
    key: string;
    type: string;
    label: string;
    route: string;
    default: {
        title: string;
        description: string;
        intro?: string;
        sections?: Array<{ heading: string; body: string }>;
        faq?: Array<{ q: string; a: string }>;
    };
    override: Record<string, unknown>;
    final: {
        title: string;
        description: string;
        intro?: string;
    };
};

const entries = ref<Entry[]>([]);
const selected = ref<Entry | null>(null);
const type = ref('all');
const search = ref('');
const saving = ref(false);
const message = ref('');
const messageType = ref<'success' | 'error'>('success');

const form = ref({
    title: '',
    description: '',
    intro: '',
    sectionsJson: '[]',
    faqJson: '[]',
});

const finalPreview = computed(() => {
    if (!selected.value) {
        return { title: '', description: '', intro: '' };
    }

    return {
        title: form.value.title.trim() || selected.value.default.title,
        description:
            form.value.description.trim() || selected.value.default.description,
        intro: form.value.intro.trim() || selected.value.default.intro || '',
    };
});

function pretty(value: unknown): string {
    return JSON.stringify(value ?? [], null, 2);
}

function fillForm(entry: Entry): void {
    const override = entry.override || {};
    form.value = {
        title: String(override.title || ''),
        description: String(override.description || ''),
        intro: String(override.intro || ''),
        sectionsJson: pretty(override.sections || []),
        faqJson: pretty(override.faq || []),
    };
}

function selectEntry(entry: Entry): void {
    selected.value = entry;
    fillForm(entry);
}

async function load(): Promise<void> {
    const { data } = await axios.get('/admin-api/seo-content', {
        params: {
            type: type.value,
            search: search.value || undefined,
        },
    });

    entries.value = data.entries || [];

    if (!selected.value && entries.value.length) {
        selectEntry(entries.value[0]);
        return;
    }

    if (selected.value) {
        const refreshed = entries.value.find((entry) => entry.key === selected.value?.key);
        if (refreshed) {
            selectEntry(refreshed);
        }
    }
}

function parseJsonField(label: string, value: string): unknown[] {
    if (!value.trim()) {
        return [];
    }

    const parsed = JSON.parse(value);
    if (!Array.isArray(parsed)) {
        throw new Error(`${label} must be a JSON array`);
    }

    return parsed;
}

async function save(): Promise<void> {
    if (!selected.value || saving.value) return;

    saving.value = true;
    message.value = '';

    try {
        const payload = {
            key: selected.value.key,
            title: form.value.title.trim() || null,
            description: form.value.description.trim() || null,
            intro: form.value.intro.trim() || null,
            sections: parseJsonField('Sections', form.value.sectionsJson),
            faq: parseJsonField('FAQ', form.value.faqJson),
        };

        await axios.put('/admin-api/seo-content', payload);
        messageType.value = 'success';
        message.value = 'SEO override saved';
        await load();
    } catch (error: any) {
        messageType.value = 'error';
        message.value =
            error?.message ||
            error?.response?.data?.message ||
            'Failed to save SEO override';
    } finally {
        saving.value = false;
    }
}

function resetToDefault(): void {
    if (!selected.value) return;
    form.value = {
        title: '',
        description: '',
        intro: '',
        sectionsJson: '[]',
        faqJson: '[]',
    };
}

let debounceTimer: number | null = null;
watch([type, search], () => {
    if (debounceTimer !== null) {
        window.clearTimeout(debounceTimer);
    }
    debounceTimer = window.setTimeout(() => {
        void load();
    }, 250);
});

onMounted(load);
</script>
