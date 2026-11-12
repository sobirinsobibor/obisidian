<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800">
        <!-- Top Navigation -->
        <nav class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-xs">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <div class="flex items-center space-x-8">
                        <!-- Logo & Brand -->
                        <Link :href="route('dashboard')" class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                                ⌘
                            </div>
                            <span class="font-bold text-slate-900 tracking-tight text-base sm:text-lg">
                                KnowledgeBase <span class="text-xs px-1.5 py-0.5 rounded font-medium bg-purple-100 text-purple-700">Obsidian</span>
                            </span>
                        </Link>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-4 sm:-my-px sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>
                            <NavLink :href="route('notes.index')" :active="route().current('notes.*') && !route().current('notes.create')">
                                Catatan
                            </NavLink>
                            <NavLink :href="route('spaces.index')" :active="route().current('spaces.*')">
                                Spaces
                            </NavLink>
                            <NavLink
                                v-if="$page.props.auth.can_review"
                                :href="route('reviews.index')"
                                :active="route().current('reviews.*')"
                            >
                                Antrean Review
                            </NavLink>
                        </div>
                    </div>

                    <!-- Right Header Section -->
                    <div class="hidden sm:flex sm:items-center sm:space-x-4">
                        <!-- Quick Add Note Button -->
                        <Link
                            :href="route('notes.create')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tulis Catatan
                        </Link>

                        <!-- Settings Dropdown -->
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                                    >
                                        <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 font-semibold flex items-center justify-center text-xs mr-2">
                                            {{ $page.props.auth.user.name.charAt(0) }}
                                        </div>
                                        {{ $page.props.auth.user.name }}
                                        <svg class="-me-0.5 ms-2 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="px-4 py-2 text-xs text-slate-500 border-b border-slate-100">
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                    <DropdownLink :href="route('profile.edit')">
                                        Pengaturan Profil
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Keluar (Log Out)
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Mobile Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-500 transition"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Mobile Navigation -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden border-t border-slate-200">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('notes.index')" :active="route().current('notes.*')">
                        Catatan
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('spaces.index')" :active="route().current('spaces.*')">
                        Spaces
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        v-if="$page.props.auth.can_review"
                        :href="route('reviews.index')"
                        :active="route().current('reviews.*')"
                    >
                        Antrean Review
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('notes.create')">
                        + Tulis Catatan Baru
                    </ResponsiveNavLink>
                </div>
                <div class="border-t border-slate-200 pb-1 pt-4 px-4">
                    <div class="text-base font-medium text-slate-800">{{ $page.props.auth.user.name }}</div>
                    <div class="text-sm font-medium text-slate-500">{{ $page.props.auth.user.email }}</div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profil</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Keluar</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Notifications -->
        <div v-if="$page.props.flash?.success" class="bg-emerald-50 border-b border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            <div class="max-w-7xl mx-auto flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $page.props.flash.success }}</span>
            </div>
        </div>

        <div v-if="$page.props.flash?.error" class="bg-rose-50 border-b border-rose-200 text-rose-800 px-4 py-3 text-sm">
            <div class="max-w-7xl mx-auto flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $page.props.flash.error }}</span>
            </div>
        </div>

        <!-- Optional Header -->
        <header v-if="$slots.header" class="bg-white border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Main Content -->
        <main class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>
