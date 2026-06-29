<header class="absolute w-full z-30 dark">
    <div class="max-w-6xl mx-auto px-5 sm:px-6">
        <div class="flex items-center justify-between h-16 md:h-20">

            <!-- Site branding -->
            <div class="shrink-0 mr-4">
                <!-- Logo -->
                <a class="block text-blue-600 transition duration-150 ease-in-out" href="index.html" aria-label="Cruip">
                    <svg class="w-8 h-8 fill-current" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M15.25 4.116c.039.899-.51.468-.79.629-.894.524-1.491 2.04-2.612 2.125-.423.87-1.121.898-1.874 1.503-.327.256-.463.839-.756 1.097l-3.694 3.27a2.395 2.395 0 0 1-.44.314c-.378.219-.656.717-1.096.81-.475.106-1.601-.775-2.633-2.012-.825-1.587-.025-1.924.518-2.525.916-1.022 2.48-2.064 3.585-2.753.843-.537 1.677-1.13 2.59-1.482.935-.357 1.779-.842 2.738-1.041.7-.141 1.522.063 2.206 0a9.596 9.596 0 0 1 2.258.065M30.676 3.646c-.06 1.046-1.038.955-1.593 1.363C27.32 6.31 25.8 8.656 23.63 9.583c-1.07 1.412-2.411 1.952-4.056 3.254-.711.56-1.21 1.417-1.877 1.965-2.887 2.381-5.668 4.524-8.64 6.823-.303.236-.646.468-1.008.697-.834.528-1.675 1.394-2.543 1.77-.95.413-2.376-.279-3.375-1.544-.43-1.849 1.112-2.657 2.38-3.737 2.13-1.82 5.33-4.012 7.558-5.536 1.707-1.17 3.395-2.426 5.138-3.43 1.789-1.03 3.439-2.179 5.224-3.063 1.303-.643 2.758-.96 4.038-1.53a40.762 40.762 0 0 1 4.207-1.606M30.386 14.346c.229.918-.566.8-.896 1.136-1.048 1.058-1.721 3.056-3.254 3.7-.563 1.17-1.522 1.532-2.603 2.507-.464.426-.731 1.128-1.177 1.542-1.94 1.77-3.91 3.263-6.082 4.772a6.36 6.36 0 0 1-.748.43c-.618.313-1.24.914-1.901 1.065-.731.161-1.856-.807-2.662-2.199-.401-1.784.715-2.129 1.6-2.818 1.49-1.15 3.693-2.49 5.192-3.486 1.154-.755 2.247-1.633 3.417-2.292 1.2-.68 2.244-1.52 3.44-2.128.868-.448 1.927-.61 2.79-1.029.92-.447 1.873-.85 2.884-1.2"/>
                    </svg>
                </a>
            </div>

            <!-- Desktop navigation -->
            <nav class="hidden md:flex md:grow">

                <!-- Desktop menu links -->
                <ul class="flex grow justify-start flex-wrap items-center">
                    <li>
                        <a class="font-medium text-slate-800 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-600 px-3 lg:px-5 py-2 flex items-center transition duration-150 ease-in-out"
                           href="pricing.html">Pricing</a>
                    </li>
                    <li>
                        <a class="font-medium text-slate-800 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-600 px-3 lg:px-5 py-2 flex items-center transition duration-150 ease-in-out"
                           href="about.html">About</a>
                    </li>
                    <li>
                        <a class="font-medium text-slate-800 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-600 px-3 lg:px-5 py-2 flex items-center transition duration-150 ease-in-out"
                           href="blog.html">Blog</a>
                    </li>
                    <li>
                        <a class="font-medium text-slate-800 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-600 px-3 lg:px-5 py-2 flex items-center transition duration-150 ease-in-out"
                           href="wall-of-love.html">Wall of Love</a>
                    </li>
                    <!-- 1st level: hover -->
                    <li
                        class="relative"
                        x-data="{ open: false }"
                        @mouseenter="open = true"
                        @mouseleave="open = false"
                    >
                        <a
                            class="font-medium text-slate-800 dark:text-slate-400 px-3 lg:px-5 py-2 flex items-center transition duration-150 ease-in-out"
                            href="#0"
                            aria-haspopup="true"
                            :aria-expanded="open"
                            @focus="open = true"
                            @focusout="open = false"
                            @click.prevent
                        >
                            Resources
                            <svg
                                class="w-3 h-3 fill-current text-slate-400 dark:text-slate-500 cursor-pointer ml-1 shrink-0"
                                viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M10.28 4.305L5.989 8.598 1.695 4.305A1 1 0 00.28 5.72l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z"/>
                            </svg>
                        </a>
                        <!-- 2nd level: hover -->
                        <ul
                            class="origin-top-right absolute top-full right-0 w-40 bg-white py-2 ml-4 rounded-sm shadow-lg"
                            x-show="open"
                            x-transition:enter="transition ease-out duration-200 transform"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-out duration-200"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            x-cloak
                        >
                            <li>
                                <a class="font-medium text-sm text-slate-600 hover:text-blue-600 flex py-2 px-5 leading-tight"
                                   href="404.html" @focus="open = true" @focusout="open = false">404</a>
                            </li>
                            <li>
                                <a class="font-medium text-sm text-slate-600 hover:text-blue-600 flex py-2 px-5 leading-tight"
                                   href="support.html" @focus="open = true" @focusout="open = false">Support</a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <!-- Desktop sign in links -->
                <ul class="flex grow justify-end flex-wrap items-center">
                    <li>
                        <a class="font-medium text-slate-800 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-600 px-3 lg:px-5 py-2 flex items-center transition duration-150 ease-in-out"
                           href="signin.html">Sign in</a>
                    </li>
                    <li>
                        <a class="font-medium text-blue-600 dark:text-slate-300 dark:hover:text-white px-3 lg:px-5 py-2 flex items-center transition duration-150 ease-in-out group"
                           href="request-demo.html">Request Demo <span
                                class="tracking-normal text-blue-600 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span></a>
                    </li>
                </ul>

            </nav>

            <!-- Mobile menu -->
            <div class="flex md:hidden" x-data="{ expanded: false }">

                <!-- Hamburger button -->
                <button
                    class="group inline-flex w-8 h-8 text-slate-300 hover:text-white text-center items-center justify-center transition"
                    aria-controls="mobile-nav" :aria-expanded="expanded" @click.stop="expanded = !expanded">
                    <span class="sr-only">Menu</span>
                    <svg class="w-4 h-4 fill-current pointer-events-none" viewBox="0 0 16 16"
                         xmlns="http://www.w3.org/2000/svg">
                        <rect
                            class="origin-center transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.1)] -translate-y-[5px] group-aria-expanded:rotate-[315deg] group-aria-expanded:translate-y-0"
                            y="7" width="16" height="2" rx="1"></rect>
                        <rect
                            class="origin-center group-aria-expanded:rotate-45 transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.8)]"
                            y="7" width="16" height="2" rx="1"></rect>
                        <rect
                            class="origin-center transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.1)] translate-y-[5px] group-aria-expanded:rotate-[135deg] group-aria-expanded:translate-y-0"
                            y="7" width="16" height="2" rx="1"></rect>
                    </svg>
                </button>

                <!-- Mobile navigation -->
                <nav
                    id="mobile-nav"
                    class="absolute top-full h-screen pb-16 z-20 left-0 w-full overflow-scroll no-scrollbar bg-white"
                    @click.away="expanded = false"
                    @keydown.escape.window="expanded = false"
                    x-show="expanded"
                    x-transition:enter="transition ease-out duration-200 transform"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-out duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    x-cloak
                >
                    <ul class="px-5 py-2">
                        <li>
                            <a class="flex font-medium text-slate-800 hover:text-blue-600 py-2" href="pricing.html">Pricing</a>
                        </li>
                        <li>
                            <a class="flex font-medium text-slate-800 hover:text-blue-600 py-2"
                               href="about.html">About</a>
                        </li>
                        <li>
                            <a class="flex font-medium text-slate-800 hover:text-blue-600 py-2"
                               href="blog.html">Blog</a>
                        </li>
                        <li>
                            <a class="flex font-medium text-slate-800 hover:text-blue-600 py-2"
                               href="wall-of-love.html">Wall of Love</a>
                        </li>
                        <li class="py-2 my-2 border-t border-b border-slate-200">
                            <span class="flex font-medium text-slate-800 hover:text-blue-600 py-2">Resources</span>
                            <ul class="pl-4">
                                <li>
                                    <a class="text-sm flex font-medium text-slate-600 hover:text-blue-600 py-2"
                                       href="404.html">404</a>
                                </li>
                                <li>
                                    <a class="text-sm flex font-medium text-slate-600 hover:text-blue-600 py-2"
                                       href="support.html">Support</a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a class="flex font-medium w-full text-slate-800 hover:text-blue-600 py-2"
                               href="signin.html">Sign in</a>
                        </li>
                        <li>
                            <a class="flex font-medium text-blue-600 py-2 group" href="request-demo.html">Request Demo
                                <span
                                    class="tracking-normal text-blue-600 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span></a>
                        </li>
                    </ul>
                </nav>

            </div>

        </div>
    </div>
</header>
