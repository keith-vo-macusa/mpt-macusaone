<div class="py-4 px-2">
    @php
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($getRecord()))
            ->where('subject_id', $getRecord()->id)
            ->latest()
            ->get();
    @endphp

    @if($activities->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <x-filament::icon icon="heroicon-o-information-circle" class="h-12 w-12 mb-2 opacity-20" />
            <p class="italic text-sm">Chưa có hoạt động nào được ghi lại.</p>
        </div>
    @else
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                @foreach($activities as $activity)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                                <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-white/10" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex items-start space-x-4">
                                <div class="relative">
                                    @php
                                        $icon = match($activity->description) {
                                            'created' => 'heroicon-s-plus-circle',
                                            'updated' => 'heroicon-s-pencil-square',
                                            'deleted' => 'heroicon-s-trash',
                                            default => 'heroicon-s-information-circle'
                                        };
                                        $colorClass = match($activity->description) {
                                            'created' => 'bg-green-500 ring-green-100 dark:ring-green-900/30',
                                            'updated' => 'bg-amber-500 ring-amber-100 dark:ring-amber-900/30',
                                            'deleted' => 'bg-red-500 ring-red-100 dark:ring-red-900/30',
                                            default => 'bg-gray-500 ring-gray-100 dark:ring-gray-900/30'
                                        };
                                    @endphp
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $colorClass }} ring-8 text-white">
                                        <x-filament::icon :icon="$icon" class="h-6 w-6" />
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 py-1.5">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-bold text-gray-900 dark:text-white capitalize">
                                            {{ $activity->description === 'created' ? 'Tạo mới' : ($activity->description === 'updated' ? 'Cập nhật' : ($activity->description === 'deleted' ? 'Xoá' : $activity->description)) }}
                                        </span>
                                        @if($activity->causer)
                                            bởi <span class="font-medium text-primary-600 dark:text-primary-400">{{ $activity->causer->name }}</span>
                                        @endif
                                        <span class="whitespace-nowrap ml-2 text-xs opacity-70">
                                            <x-filament::icon icon="heroicon-m-calendar" class="inline h-3 w-3 -mt-0.5" />
                                            {{ $activity->created_at->format('H:i d/m/Y') }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </p>

                                    @if($activity->description === 'updated' && !empty($activity->properties['attributes']))
                                        <div class="mt-3 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 p-3 shadow-sm">
                                            <div class="grid grid-cols-1 gap-2">
                                                @foreach($activity->properties['attributes'] as $key => $value)
                                                    @if(in_array($key, ['password', 'remember_token'])) @continue @endif
                                                    @php
                                                        $oldValue = $activity->properties['old'][$key] ?? 'N/A';
                                                        $newValue = $value;
                                                    @endphp
                                                    <div class="flex flex-col sm:flex-row sm:items-center text-[11px] border-b border-gray-100 dark:border-white/5 last:border-0 pb-1 mb-1 last:pb-0 last:mb-0">
                                                        <span class="font-mono font-bold text-gray-600 dark:text-gray-400 w-24 shrink-0">{{ $key }}:</span>
                                                        <div class="flex items-center space-x-2 flex-1">
                                                            <span class="px-1.5 py-0.5 bg-red-50 text-red-700 rounded line-through dark:bg-red-900/20 dark:text-red-400 truncate max-w-[150px]">
                                                                {{ is_array($oldValue) ? json_encode($oldValue) : $oldValue }}
                                                            </span>
                                                            <x-filament::icon icon="heroicon-m-chevron-double-right" class="h-3 w-3 text-gray-300" />
                                                            <span class="px-1.5 py-0.5 bg-green-50 text-green-700 rounded font-medium dark:bg-green-900/20 dark:text-green-400 truncate max-w-[150px]">
                                                                {{ is_array($newValue) ? json_encode($newValue) : $newValue }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
