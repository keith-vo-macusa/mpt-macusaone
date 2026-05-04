@php
    $record = $getRecord();
    $properties = $record->properties;
    $attributes = $properties['attributes'] ?? [];
    $old = $properties['old'] ?? [];
@endphp

<div class="fi-ta-content overflow-x-auto">
    <table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/5 text-sm">
        <thead>
            <tr class="bg-gray-50 dark:bg-white/5">
                <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Trường</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Giá trị cũ</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Giá trị mới</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
            @foreach($attributes as $key => $value)
                @if($key === 'password' || $key === 'remember_token') @continue @endif
                <tr>
                    <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">{{ $key }}</td>
                    <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                        @if(isset($old[$key]))
                            <span class="px-2 py-1 bg-red-50 text-red-700 rounded dark:bg-red-900/30 dark:text-red-400">
                                {{ is_array($old[$key]) ? json_encode($old[$key]) : $old[$key] }}
                            </span>
                        @else
                            <span class="text-gray-400 italic">Trống</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                        <span class="px-2 py-1 bg-green-50 text-green-700 rounded dark:bg-green-900/30 dark:text-green-400">
                            {{ is_array($value) ? json_encode($value) : $value }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
