<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('permission-registry::Права доступа пользователя') }} - {{ $user->name }}
            </h2>
            <a href="{{ route('permission-registry::users.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                {{ __('permission-registry::Назад к пользователям') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                <div class="mb-6">
                    <h3 class="text-lg font-semibold">{{ __('permission-registry::Права пользователя') }}</h3>
                    <p class="text-gray-600">{{ $user->name }} ({{ $user->email }})</p>
                </div>

                <!-- Выданные доступы -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold mb-3">{{ __('permission-registry::Выданные права') }}</h4>

                    @if($grantedPermissions->isEmpty())
                        <p class="text-gray-500">{{ __('permission-registry::Права еще не выданы') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('permission-registry::Сервис') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('permission-registry::Название') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('permission-registry::Выдано') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('permission-registry::Истекает') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('permission-registry::Поля') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('permission-registry::Действия') }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($grantedPermissions as $grantedPermission)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $grantedPermission->permission->service }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $grantedPermission->permission->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $grantedPermission->granted_at->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $grantedPermission->expires_at ? $grantedPermission->expires_at->format('d.m.Y H:i') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            @if($grantedPermission->fieldValues->isNotEmpty())
                                                <div class="flex flex-col gap-1">
                                                    @foreach($grantedPermission->fieldValues as $fieldValue)
                                                        <span class="text-xs">
                                                            {{ $fieldValue->field->name }}: <span class="font-medium">{{ $fieldValue->value }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form action="{{ route('permission-registry::users.permissions.revoke', [$user->id, $grantedPermission->id]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('{{ __('permission-registry::Вы уверены, что хотите отозвать это право?') }}')">
                                                    {{ __('permission-registry::Отозвать') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Форма выдачи нового права -->
                <div class="mt-10">
                    <h4 class="text-md font-semibold mb-3">{{ __('permission-registry::Выдать новое право') }}</h4>

                    <form action="{{ route('permission-registry::users.permissions.grant', $user->id) }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="permission_id" class="block text-sm font-medium text-gray-700">
                                    {{ __('permission-registry::Право доступа') }}*
                                </label>
                                <select id="permission_id" name="permission_id" required
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <option value="">{{ __('permission-registry::Выберите право доступа') }}</option>
                                    @foreach($availablePermissions as $permission)
                                        <option value="{{ $permission->id }}">
                                            {{ $permission->service }} - {{ $permission->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="expires_at" class="block text-sm font-medium text-gray-700">
                                    {{ __('permission-registry::Срок действия') }}
                                </label>
                                <input type="datetime-local" id="expires_at" name="expires_at"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div id="fields-container" class="hidden space-y-4 mt-4">
                            <h5 class="text-sm font-semibold">{{ __('permission-registry::Поля права доступа') }}</h5>
                            <div id="permission-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Сюда будут динамически добавляться поля -->
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ __('permission-registry::Выдать право') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const permissionSelect = document.getElementById('permission_id');
            const fieldsContainer = document.getElementById('fields-container');
            const permissionFields = document.getElementById('permission-fields');

            // Данные о полях прав доступа
            const permissions = @json($permissionsWithFields);

            permissionSelect.addEventListener('change', function() {
                const permissionId = this.value;
                permissionFields.innerHTML = '';

                if (permissionId) {
                    const permission = permissions.find(p => p.id == permissionId);

                    if (permission && permission.fields.length > 0) {
                        fieldsContainer.classList.remove('hidden');

                        permission.fields.forEach(field => {
                            const fieldHtml = `
                                <div>
                                    <label for="field_${field.id}" class="block text-sm font-medium text-gray-700">
                                        ${field.name}
                                    </label>
                                    <input type="text" id="field_${field.id}" name="fields[${field.id}]"
                                           value="${field.default_value || ''}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            `;

                            permissionFields.insertAdjacentHTML('beforeend', fieldHtml);
                        });
                    } else {
                        fieldsContainer.classList.add('hidden');
                    }
                } else {
                    fieldsContainer.classList.add('hidden');
                }
            });
        });
    </script>
</x-app-layout>
