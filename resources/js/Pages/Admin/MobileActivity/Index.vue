<template>
    <div class="min-h-screen bg-gray-50">
        <header
            class="sticky top-0 z-10 border-b border-gray-200 bg-white/70 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <h1 class="text-2xl font-semibold text-gray-900">
                    Mobile App Activity Monitor
                </h1>
                <div class="flex items-center gap-3 text-sm text-gray-600">
                    <span class="inline-flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :class="
                                isLoading ? 'bg-yellow-500' : 'bg-emerald-500'
                            "
                        ></span>
                        <span>{{ isLoading ? 'Updating...' : 'Live' }}</span>
                    </span>
                    <span class="hidden text-gray-300 sm:inline">|</span>
                    <span class="hidden sm:inline"
                        >Active now:
                        <span class="font-medium text-emerald-600">{{
                            stats.active_last_1_min
                        }}</span></span
                    >
                    <span class="hidden sm:inline"
                        >Last 5 min:
                        <span class="font-medium">{{
                            stats.active_last_5_min
                        }}</span></span
                    >
                    <span class="hidden sm:inline"
                        >Total:
                        <span class="font-medium">{{
                            stats.total_active_users
                        }}</span></span
                    >
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-6 px-6 py-6">
            <!-- Stats cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Active Now
                    </div>
                    <div class="mt-1 text-3xl font-bold text-emerald-600">
                        {{ stats.active_last_1_min }}
                    </div>
                </div>
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Last 5 Minutes
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900">
                        {{ stats.active_last_5_min }}
                    </div>
                </div>
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Total Active (1h)
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900">
                        {{ stats.total_active_users }}
                    </div>
                </div>
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Total Requests
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900">
                        {{ stats.total_requests }}
                    </div>
                </div>
            </div>

            <!-- Users table -->
            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Active Users
                        </h2>
                        <span class="text-sm text-gray-500"
                            >({{ users.length }})</span
                        >
                    </div>
                    <button
                        @click="clearAll"
                        :disabled="isClearing"
                        class="rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-sm text-red-700 hover:bg-red-100 disabled:opacity-50"
                    >
                        {{ isClearing ? 'Clearing...' : 'Clear All Data' }}
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    IP Address
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Last Activity
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Requests
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Dart Version
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Last Action
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Recent Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-if="users.length === 0"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    colspan="6"
                                    class="px-6 py-8 text-center text-sm text-gray-500"
                                >
                                    No active users at the moment
                                </td>
                            </tr>
                            <tr
                                v-for="user in users"
                                :key="user.ip"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-3 font-mono text-sm font-medium text-gray-900"
                                >
                                    {{ user.ip }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-2 w-2 rounded-full"
                                            :class="getActivityColor(user)"
                                        ></span>
                                        <span class="text-sm text-gray-600">{{
                                            user.last_activity_human
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20"
                                    >
                                        {{ user.request_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    {{ user.dart_version || '-' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    <div class="max-w-xs truncate">
                                        {{
                                            user.recent_actions[0]
                                                ?.description || '-'
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <button
                                        @click="toggleDetails(user.ip)"
                                        class="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        {{
                                            expandedRows.has(user.ip)
                                                ? 'Hide'
                                                : 'Show'
                                        }}
                                        ({{ user.recent_actions.length }})
                                    </button>
                                </td>
                            </tr>
                            <tr
                                v-for="user in users"
                                v-show="expandedRows.has(user.ip)"
                                :key="`${user.ip}-details`"
                                class="bg-gray-50"
                            >
                                <td colspan="6" class="px-6 py-4">
                                    <div class="space-y-2">
                                        <div
                                            class="text-xs font-semibold uppercase text-gray-500"
                                        >
                                            Recent Actions:
                                        </div>
                                        <div class="space-y-1">
                                            <div
                                                v-for="(
                                                    action, idx
                                                ) in user.recent_actions"
                                                :key="idx"
                                                class="flex items-center gap-3 text-sm"
                                            >
                                                <span
                                                    class="font-mono text-xs text-gray-400"
                                                    >{{
                                                        formatTime(action.time)
                                                    }}</span
                                                >
                                                <span
                                                    class="rounded bg-gray-200 px-1.5 py-0.5 text-xs font-medium text-gray-700"
                                                    >{{ action.method }}</span
                                                >
                                                <span class="text-gray-900">{{
                                                    action.description
                                                }}</span>
                                                <span
                                                    class="font-mono text-xs text-gray-400"
                                                    >{{ action.endpoint }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { onMounted, onUnmounted, reactive, ref } from 'vue';

interface Action {
    endpoint: string;
    method: string;
    time: number;
    description: string;
}

interface User {
    ip: string;
    user_agent: string;
    dart_version: string | null;
    request_count: number;
    last_activity: number;
    last_activity_human: string;
    last_endpoint: string | null;
    last_method: string | null;
    recent_actions: Action[];
}

interface Stats {
    total_active_users: number;
    active_last_5_min: number;
    active_last_1_min: number;
    total_requests: number;
}

const users = reactive<User[]>([]);
const stats = reactive<Stats>({
    total_active_users: 0,
    active_last_5_min: 0,
    active_last_1_min: 0,
    total_requests: 0,
});

const isLoading = ref(false);
const isClearing = ref(false);
const expandedRows = reactive(new Set<string>());
let pollingInterval: number | null = null;

async function fetchData() {
    try {
        isLoading.value = true;
        const response = await axios.get('/admin-api/mobile-activity/data');

        // Update users
        users.splice(0, users.length, ...response.data.users);

        // Update stats
        Object.assign(stats, response.data.stats);
    } catch (error) {
        console.error('Failed to fetch activity data:', error);
    } finally {
        isLoading.value = false;
    }
}

async function clearAll() {
    if (!confirm('Are you sure you want to clear all activity data?')) {
        return;
    }

    try {
        isClearing.value = true;
        await axios.post('/admin-api/mobile-activity/clear');
        await fetchData();
    } catch (error) {
        console.error('Failed to clear data:', error);
        alert('Failed to clear data');
    } finally {
        isClearing.value = false;
    }
}

function toggleDetails(ip: string) {
    if (expandedRows.has(ip)) {
        expandedRows.delete(ip);
    } else {
        expandedRows.add(ip);
    }
}

function getActivityColor(user: User): string {
    const now = Math.floor(Date.now() / 1000);
    const diff = now - user.last_activity;

    if (diff < 60) return 'bg-emerald-500'; // Active now (less than 1 min)
    if (diff < 300) return 'bg-yellow-500'; // Recent (less than 5 min)
    return 'bg-gray-400'; // Inactive
}

function formatTime(timestamp: number): string {
    try {
        const date = new Date(timestamp * 1000);
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch (_) {
        return '';
    }
}

onMounted(() => {
    fetchData();
    // Poll every 3 seconds
    pollingInterval = window.setInterval(fetchData, 3000);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<style scoped></style>
