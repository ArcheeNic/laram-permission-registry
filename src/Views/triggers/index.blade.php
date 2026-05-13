<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                ⚡ Реестр триггеров
            </h2>
            <a href="{{ route('permission-registry::triggers.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700">
                Создать триггер
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg">
                @if(session('success'))
                    <div class="p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-t-lg">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Название
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Класс
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Тип
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Статус
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Действия
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            @forelse($services as $service)
                                @php
                                    $configured = $configuredByService[$service] ?? collect();
                                    $unconfigured = $unconfiguredByService[$service] ?? collect();
                                @endphp
                                <tr class="bg-gray-50 dark:bg-neutral-900/40">
                                    <td colspan="5" class="px-6 py-2 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ $service }}
                                        <span class="ml-2 text-gray-400 dark:text-gray-500 normal-case">({{ $configured->count() }})</span>
                                    </td>
                                </tr>
                                @foreach($configured as $trigger)
                                    @php
                                        $missing = $missingFieldsByTrigger[$trigger->id] ?? ['class_missing' => false, 'fields' => []];
                                        $hasMissingFields = ! empty($missing['fields']);
                                        $classMissing = $missing['class_missing'] ?? false;
                                    @endphp
                                    <tr @class(['bg-amber-50 dark:bg-amber-900/20' => $hasMissingFields || $classMissing])>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                <span>{{ $trigger->name }}</span>
                                                @if($classMissing)
                                                    <span title="Класс триггера не найден в коде"
                                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">
                                                        Класс отсутствует
                                                    </span>
                                                @endif
                                            </div>
                                            @if($hasMissingFields)
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                                        ⚠ Поля без маппинга: {{ count($missing['fields']) }}
                                                    </span>
                                                </div>
                                                <ul class="text-xs text-amber-700 dark:text-amber-300 mt-1 font-mono list-disc list-inside">
                                                    @foreach($missing['fields'] as $field)
                                                        <li>{{ $field }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500 dark:text-gray-400 font-mono break-all">
                                                {{ $trigger->class_name }}
                                            </div>
                                            @if($trigger->description)
                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                    {{ $trigger->description }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if($trigger->type === 'grant') bg-green-100 text-green-800
                                                @elseif($trigger->type === 'revoke') bg-red-100 text-red-800
                                                @else bg-blue-100 text-blue-800
                                                @endif">
                                                {{ $trigger->type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($trigger->is_active)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Активен
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Неактивен
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('permission-registry::triggers.edit', $trigger) }}"
                                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">
                                                Редактировать
                                            </a>
                                            <form action="{{ route('permission-registry::triggers.destroy', $trigger) }}"
                                                  method="POST"
                                                  class="inline"
                                                  onsubmit="return confirm('Удалить триггер?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                                    Удалить
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach($unconfigured as $available)
                                    <tr class="bg-amber-50/40 dark:bg-amber-900/10">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 italic">
                                                {{ $available['name'] }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500 dark:text-gray-400 font-mono break-all">
                                                {{ $available['class_name'] }}
                                            </div>
                                            @if(!empty($available['description']))
                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                    {{ $available['description'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-gray-300">
                                                —
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                                Не настроен
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('permission-registry::triggers.create', ['class_name' => $available['class_name']]) }}"
                                               class="text-purple-600 hover:text-purple-900 dark:text-purple-400">
                                                Настроить
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Триггеры не найдены. Создайте первый триггер.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
