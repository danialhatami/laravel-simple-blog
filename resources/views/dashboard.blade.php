<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 lg:p-8">
        <div class="mt-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                @if(auth()->user()->hasPermissionTo(App\Enums\PermissionEnum::ACCESS_PANEL))
                <a href="{{url('/admin')}}">
                    <div class="scale-100 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Admin Panel</h2>
                        </div>
                    </div>
                </a>
                @endif
                <a href="{{route('index')}}">
                    <div class="scale-100 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Home Page</h2>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

</x-app-layout>
