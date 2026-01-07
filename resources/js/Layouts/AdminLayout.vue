<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Off-canvas menu for mobile -->
        <TransitionRoot as="template" :show="sidebarOpen">
            <Dialog
                as="div"
                class="relative z-50 lg:hidden"
                @close="sidebarOpen = false"
            >
                <TransitionChild
                    as="template"
                    enter="transition-opacity ease-linear duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="transition-opacity ease-linear duration-300"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/80" />
                </TransitionChild>

                <div class="fixed inset-0 flex">
                    <TransitionChild
                        as="template"
                        enter="transition ease-in-out duration-300 transform"
                        enter-from="-translate-x-full"
                        enter-to="translate-x-0"
                        leave="transition ease-in-out duration-300 transform"
                        leave-from="translate-x-0"
                        leave-to="-translate-x-full"
                    >
                        <DialogPanel
                            class="relative mr-16 flex w-full max-w-xs flex-1"
                        >
                            <div
                                class="absolute left-full top-0 flex w-16 justify-center pt-5"
                            >
                                <button
                                    type="button"
                                    class="-m-2.5 p-2.5"
                                    @click="sidebarOpen = false"
                                >
                                    <span class="sr-only">Close sidebar</span>
                                    <i class="fa fa-times text-white"></i>
                                </button>
                            </div>

                            <!-- Sidebar component for mobile -->
                            <div
                                class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4"
                            >
                                <div class="flex h-16 shrink-0 items-center">
                                    <img
                                        class="h-8 w-auto"
                                        src="/logo.svg"
                                        alt="Carbeat"
                                    />
                                </div>
                                <nav class="flex flex-1 flex-col">
                                    <ul
                                        role="list"
                                        class="flex flex-1 flex-col gap-y-7"
                                    >
                                        <li>
                                            <ul
                                                role="list"
                                                class="-mx-2 space-y-1"
                                            >
                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.masters.index',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.masters.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-users text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.masters.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        Masters
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.app_config.index',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.app_config.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-sliders text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.app_config.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        App Config
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.services.index',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.services.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-wrench text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.services.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        Services
                                                    </Link>
                                                </li>
                                                <!-- Subscriptions (mobile) -->
                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.subscriptions.index',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.subscriptions.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-credit-card text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.subscriptions.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        Subscriptions
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.payments.index',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.payments.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-cog text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.payments.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        Payments
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.import.index',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.import.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-download text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.import.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        Import
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.api-docs.index',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.api-docs.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-book text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.api-docs.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        API Документація
                                                    </Link>
                                                </li>

                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.maintenance.index',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.maintenance.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-tools text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.maintenance.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        Maintenance
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'admin.realtime.availability',
                                                            )
                                                        "
                                                        :class="[
                                                            route().current(
                                                                'admin.realtime.*',
                                                            )
                                                                ? 'bg-gray-50 text-blue-600'
                                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                        ]"
                                                    >
                                                        <i
                                                            class="fa fa-bolt text-lg"
                                                            :class="[
                                                                route().current(
                                                                    'admin.realtime.*',
                                                                )
                                                                    ? 'text-blue-600'
                                                                    : 'text-gray-400 group-hover:text-blue-600',
                                                            ]"
                                                        ></i>
                                                        Realtime
                                                    </Link>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="mt-auto">
                                            <Link
                                                :href="route('logout')"
                                                method="post"
                                                as="button"
                                                class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-700 hover:bg-gray-50 hover:text-blue-600"
                                            >
                                                <i
                                                    class="fa fa-sign-out text-lg text-gray-400 group-hover:text-blue-600"
                                                ></i>
                                                Logout
                                            </Link>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Static sidebar for desktop -->
        <div
            class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col"
        >
            <div
                class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4"
            >
                <div class="flex h-16 shrink-0 items-center">
                    <img class="h-8 w-auto" src="/logo.svg" alt="Carbeat" />
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <Link
                                        :href="route('admin.app_config.index')"
                                        :class="[
                                            route().current(
                                                'admin.app_config.*',
                                            )
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-sliders text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.app_config.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        App Config
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('admin.masters.index')"
                                        :class="[
                                            route().current('admin.masters.*')
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-users text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.masters.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        Masters
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('admin.services.index')"
                                        :class="[
                                            route().current('admin.services.*')
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-wrench text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.services.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        Services
                                    </Link>
                                </li>
                                <!-- Subscriptions (desktop) -->
                                <li>
                                    <Link
                                        :href="
                                            route('admin.subscriptions.index')
                                        "
                                        :class="[
                                            route().current(
                                                'admin.subscriptions.*',
                                            )
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-credit-card text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.subscriptions.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        Subscriptions
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('admin.payments.index')"
                                        :class="[
                                            route().current('admin.payments.*')
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-cog text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.payments.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        Payments
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('admin.api-docs.index')"
                                        :class="[
                                            route().current('admin.api-docs.*')
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-book text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.api-docs.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        API Документація
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('admin.import.index')"
                                        :class="[
                                            route().current('admin.import.*')
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-download text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.import.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        Import
                                    </Link>
                                </li>

                                <li>
                                    <Link
                                        :href="route('admin.maintenance.index')"
                                        :class="[
                                            route().current(
                                                'admin.maintenance.*',
                                            )
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-tools text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.maintenance.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        Maintenance
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="
                                            route('admin.realtime.availability')
                                        "
                                        :class="[
                                            route().current('admin.realtime.*')
                                                ? 'bg-gray-50 text-blue-600'
                                                : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600',
                                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                        ]"
                                    >
                                        <i
                                            class="fa fa-bolt text-lg"
                                            :class="[
                                                route().current(
                                                    'admin.realtime.*',
                                                )
                                                    ? 'text-blue-600'
                                                    : 'text-gray-400 group-hover:text-blue-600',
                                            ]"
                                        ></i>
                                        Realtime
                                    </Link>
                                </li>
                            </ul>
                        </li>
                        <li class="mt-auto">
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-700 hover:bg-gray-50 hover:text-blue-600"
                            >
                                <i
                                    class="fa fa-sign-out text-lg text-gray-400 group-hover:text-blue-600"
                                ></i>
                                Logout
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="lg:pl-72">
            <div
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8"
            >
                <button
                    type="button"
                    class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                    @click="sidebarOpen = true"
                >
                    <span class="sr-only">Open sidebar</span>
                    <i class="fa fa-bars text-xl"></i>
                </button>

                <!-- Separator -->
                <div
                    class="h-6 w-px bg-gray-200 lg:hidden"
                    aria-hidden="true"
                />

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex flex-1"></div>
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Global project/brand switcher -->
                        <div class="relative">
                            <label for="brandSwitcher" class="sr-only"
                                >Project</label
                            >
                            <select
                                id="brandSwitcher"
                                class="block w-44 rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm sm:leading-6"
                                v-model="selectedBrand"
                                @change="onBrandChange"
                            >
                                <option
                                    v-for="b in brands"
                                    :key="b.value"
                                    :value="b.value"
                                >
                                    {{ b.label }}
                                </option>
                            </select>
                        </div>
                        <!-- Profile dropdown -->
                        <div class="relative">
                            <button
                                type="button"
                                class="-m-1.5 flex items-center p-1.5"
                                id="user-menu-button"
                                aria-expanded="false"
                                aria-haspopup="true"
                            >
                                <span class="sr-only">Open user menu</span>
                                <i
                                    class="fa fa-user-circle text-2xl text-gray-400"
                                ></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <slot></slot>
        </div>
    </div>
</template>

<script setup>
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { Inertia } from '@inertiajs/inertia';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const sidebarOpen = ref(false);

const page = usePage();
// Keep brands reactive
const brands = computed(() => page.props.brands || []);

// Local selected value to bind with v-model to avoid flicker when Inertia updates
const selectedBrand = ref(page.props.adminBrand || '');
// Keep local selectedBrand in sync when Inertia replaces page props
watch(
    () => page.props.adminBrand,
    (v) => {
        selectedBrand.value = v || '';
    },
);

function onBrandChange(e) {
    const value = e.target ? e.target.value : selectedBrand.value;
    // Immediately update select value locally to avoid flashing
    selectedBrand.value = value;
    // Use Inertia to navigate with the brand query param so shared props update
    const url = new URL(window.location.href);
    const pathname = url.pathname;
    Inertia.get(
        pathname,
        { ...Object.fromEntries(url.searchParams.entries()), brand: value },
        { preserveState: false, preserveScroll: true },
    );
}
</script>
