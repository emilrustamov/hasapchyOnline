@section('page-title', 'Управление проектами')
@section('showSearch', true)
<div class="mx-auto p-4">
    @include('components.alert')


    <div class="flex items-center space-x-4 mb-4">

        <button wire:click="openForm" class="bg-[#5CB85C] text-white px-4 py-2 rounded">
            <i class="fas fa-plus"></i>
        </button>

        @livewire('admin.date-filter')
    </div>

    <table class="min-w-full bg-white shadow-md rounded mb-6">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-1 border border-gray-200">Название</th>
                <th class="p-1 border border-gray-200">Клиент</th>
                <th class="p-1 border border-gray-200">Бюджет</th>
            
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $project)
                <tr wire:click="edit({{ $project->id }})" class="cursor-pointer mb-2 p-2 border rounded">
                    <td class="p-1 border border-gray-200">{{ $project->name }}</td>
                    <td class="p-1 border border-gray-200">{{ $project->client->first_name ?? 'N/A' }}</td>
                    <td class="p-1 border border-gray-200">{{ $project->budget }}</td>
                   
                </tr>
            @endforeach
        </tbody>
    </table>

    <div id="modalBackground"
        class="fixed overflow-y-auto inset-0 bg-gray-900 bg-opacity-50 z-40 transition-opacity duration-500 {{ $showForm ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeForm">
        <div id="form" x-data="{ activeTab: 1 }"
            class="fixed top-0 right-0 w-1/3 h-full bg-white shadow-lg transform transition-transform duration-500 ease-in-out z-50 mx-auto p-4"
            style="transform: {{ $showForm ? 'translateX(0)' : 'translateX(100%)' }};" wire:click.stop>
            <button wire:click="closeForm" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl"
                style="right: 1rem;">
                &times;
            </button>
            <h2 class="text-xl font-bold mb-4">Проект</h2>
            @include('components.confirmation-modal')

            <div x-data="{ activeTab: 1 }">
                <ul class="flex border-b mb-4">
                    <li class="-mb-px mr-1">
                        <a :class="{ 'border-l border-t border-r rounded-t-lg text-blue-700': activeTab === 1 }"
                            @click.prevent="activeTab = 1"
                            class="bg-white inline-block py-2 px-4 text-blue-500 hover:text-blue-800 font-semibold"
                            href="#">Общие</a>
                    </li>
                    @if ($projectId)
                        <li class="-mb-px mr-1">
                            <a :class="{ 'border-l border-t border-r rounded-t-lg text-blue-700': activeTab === 2 }"
                                @click.prevent="activeTab = 2"
                                class="bg-white inline-block py-2 px-4 text-blue-500 hover:text-blue-800 font-semibold"
                                href="#">Баланс</a>
                        </li>
                        <li class="-mb-px mr-1">
                            <a :class="{ 'border-l border-t border-r rounded-t-lg text-blue-700': activeTab === 3 }"
                                @click.prevent="activeTab = 3"
                                class="bg-white inline-block py-2 px-4 text-blue-500 hover:text-blue-800 font-semibold"
                                href="#">Файлы</a>
                        </li>
                    @endif
                </ul>
                <div x-show="activeTab === 1">
                    <div class="mb-4">
                        <label class="block mb-1">Название</label>
                        <input type="text" wire:model="name" placeholder="Название"
                            class="w-full p-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block mb-1">Бюджет</label>
                        <input type="text" wire:model="budget" placeholder="Бюджет"
                            class="w-full p-2 border rounded">
                    </div>
                    <div class="mb-4">
                        @include('components.client-search')
                    </div>


                    <div class="mb-4">
                        <label class="block mb-1">Пользователи</label>
                        @foreach ($allUsers as $user)
                            <div class="flex items-center mb-2">
                                <input type="checkbox" wire:model="users" value="{{ $user->id }}" class="mr-2">
                                <label>{{ $user->name }}</label>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex space-x-2">
                        <button wire:click="save" class="bg-[#5CB85C] text-white px-4 py-2 rounded">
                            <i class="fas fa-save"></i>
                        </button>

                        @if ($projectId)
                            <button wire:click="delete({{ $project->id }})"
                                class="bg-red-500 text-white px-4 py-2 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div x-show="activeTab === 2">
                    <h3 class="text-lg font-bold mb-4">Транзакции</h3>
                    <div class="mb-4">
                        <strong>Итоговая сумма: </strong>
                        <span
                            class="{{ $totalAmount >= 0 ? 'text-green-500' : 'text-red-500' }}">{{ $totalAmount }}</span>
                    </div>
                    <table class="min-w-full bg-white shadow-md rounded mb-6">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-1 border border-gray-200">Тип</th>
                                <th class="p-1 border border-gray-200">Дата</th>
                                <th class="p-1 border border-gray-200">Сумма</th>
                                <th class="p-1 border border-gray-200">Примечание</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($projectTransactions && count($projectTransactions) !== 0)
                                @foreach ($projectTransactions as $transaction)
                                    <tr>
                                        <td
                                            class="p-1 border border-gray-200 {{ $transaction->type == 1 ? 'bg-green-200' : 'bg-red-200' }}">
                                            {{ $transaction->type == 1 ? 'Приход' : 'Расход' }}
                                        </td>
                                        <td class="p-1 border border-gray-200">{{ $transaction->date }}
                                        </td>
                                        <td
                                            class="p-1 border border-gray-200 {{ $transaction->type == 1 ? 'text-green-500' : 'text-red-500' }}">
                                            {{ $transaction->amount }}
                                        </td>
                                        <td class="p-1 border border-gray-200">{{ $transaction->note }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                <div x-show="activeTab === 3">
                    <div class="mb-4">
                        <label class="block mb-1">Загрузить файлы</label>
                        <input type="file" wire:model="fileAttachments" multiple class="w-full p-2 border rounded">
                        @error('fileAttachments.*')
                            <span class="text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                    @if (count($attachments ?? []) > 0)
                        <div class="mb-4">
                            <h4 class="font-semibold">Загруженные файлы:</h4>
                            <ul class="list-disc pl-5">
                                @foreach ($attachments as $index => $file)
                                    <li class="flex items-center">
                                        <a href="{{ asset('storage/' . $file['file_path']) }}" target="_blank" class="text-blue-500 hover:underline">
                                            {{ $file['file_name'] }}
                                        </a>
                                        <button wire:click="removeFile({{ $index }})" class="ml-2 text-red-500" title="Удалить файл">
                                            &times;
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <button wire:click="save" class="bg-[#5CB85C] text-white px-4 py-2 rounded">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
