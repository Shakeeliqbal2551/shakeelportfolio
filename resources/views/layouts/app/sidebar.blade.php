<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Overview')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="globe-alt" href="{{ url('/') }}" target="_blank">
                        {{ __('View public site') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Site')" class="grid">
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.site-settings')" :current="request()->routeIs('admin.site-settings')" wire:navigate>
                        {{ __('Site Settings') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="photo" :href="route('admin.profile-photos')" :current="request()->routeIs('admin.profile-photos')" wire:navigate>
                        {{ __('Profile Photos') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Portfolio')" class="grid">
                    <flux:sidebar.item icon="briefcase" :href="route('admin.projects.index')" :current="request()->routeIs('admin.projects.*')" wire:navigate>
                        {{ __('Projects') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('admin.projects.categories')" :current="request()->routeIs('admin.projects.categories')" wire:navigate>
                        {{ __('Project Categories') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="academic-cap" :href="route('admin.skills.index')" :current="request()->routeIs('admin.skills.*')" wire:navigate>
                        {{ __('Skills') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('admin.experiences')" :current="request()->routeIs('admin.experiences')" wire:navigate>
                        {{ __('Experience') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open" :href="route('admin.educations')" :current="request()->routeIs('admin.educations')" wire:navigate>
                        {{ __('Education') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="wrench-screwdriver" :href="route('admin.services')" :current="request()->routeIs('admin.services')" wire:navigate>
                        {{ __('Services') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="star" :href="route('admin.testimonials')" :current="request()->routeIs('admin.testimonials')" wire:navigate>
                        {{ __('Testimonials') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="sparkles" :href="route('admin.why-points')" :current="request()->routeIs('admin.why-points')" wire:navigate>
                        {{ __('Why Hire Me') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Blog')" class="grid">
                    <flux:sidebar.item icon="document-text" :href="route('admin.blog.posts')" :current="request()->routeIs('admin.blog.posts*')" wire:navigate>
                        {{ __('Posts') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder" :href="route('admin.blog.categories')" :current="request()->routeIs('admin.blog.categories')" wire:navigate>
                        {{ __('Categories') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="hashtag" :href="route('admin.blog.tags')" :current="request()->routeIs('admin.blog.tags')" wire:navigate>
                        {{ __('Tags') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Inbox')" class="grid">
                    <flux:sidebar.item icon="envelope" :href="route('admin.contact-messages')" :current="request()->routeIs('admin.contact-messages')" wire:navigate>
                        {{ __('Contact Messages') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('admin.visitor-logs')" :current="request()->routeIs('admin.visitor-logs')" wire:navigate>
                        {{ __('Visitor Logs') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog" :href="route('profile.edit')" wire:navigate>
                    {{ __('Account Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>


        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
