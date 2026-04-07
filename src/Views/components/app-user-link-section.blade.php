@php
    $linkTitle = __('permission-registry::Link to app user');
    $linkDesc = __('permission-registry::Link to app user description');
    $appUserLabel = __('permission-registry::App user');
    $notLinked = __('permission-registry::— Not linked —');
    if ($linkTitle === 'permission-registry::Link to app user') { $linkTitle = 'Привязать к пользователю приложения'; }
    if ($linkDesc === 'permission-registry::Link to app user description') { $linkDesc = 'Связь с учётной записью в системе. Нужна для подтверждения заявок и отображения в реестре.'; }
    if ($appUserLabel === 'permission-registry::App user') { $appUserLabel = 'Пользователь приложения'; }
    if ($notLinked === 'permission-registry::— Not linked —') { $notLinked = '— Не привязан —'; }
@endphp
@if(count($this->appUsersForLink) > 0)
<div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-5">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
        {{ $linkTitle }}
    </h4>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {{ $linkDesc }}
    </p>
    <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-[200px] flex-1">
            <label for="linked_app_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ $appUserLabel }}
                <x-perm::field-hint
                    :title="__('permission-registry::hints.app_user_link_linked_user_title')"
                    :description="__('permission-registry::hints.app_user_link_linked_user_desc')"
                />
            </label>
            <select id="linked_app_user_id"
                    wire:model="linkedAppUserId"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">{{ $notLinked }}</option>
                @foreach($this->appUsersForLink as $u)
                    <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="saveLinkedAppUser"
                type="button"
                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            {{ __('permission-registry::Save') }}
        </button>
    </div>
</div>
@endif
