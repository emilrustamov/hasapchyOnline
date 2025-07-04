@section('page-title', 'Перемещение товаров')
<div class="mx-auto p-4">
    @include('components.alert')

    <div class="flex items-center space-x-4 mb-4">

        <button wire:click="openForm" class="bg-[#5CB85C] text-white px-4 py-2 rounded">
            <i class="fas fa-plus"></i>
        </button>

        @include('components.warehouse-accordion')
        @livewire('admin.date-filter')
    </div>

    <table class="min-w-full bg-white shadow-md rounded mb-6">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-1 border border-gray-200">ID</th>
                <th class="p-1 border border-gray-200">Дата</th>
                <th class="p-1 border border-gray-200">Склад-отправитель</th>
                <th class="p-1 border border-gray-200">Склад-получатель</th>
                <th class="p-1 border border-gray-200">Товары</th>
                <th class="p-1 border border-gray-200">Примечание</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stockMovements as $movement)
                <tr wire:click="edit({{ $movement->id }})" class="cursor-pointer">

                    <td class="p-1 border border-gray-200">{{ $movement->id }}</td>
                    <td class="p-1 border border-gray-200">{{ $movement->created_at->format('d.m.Y') }}</td>
                    <td class="p-1 border border-gray-200">{{ $movement->warehouseFrom->name }}</td>
                    <td class="p-1 border border-gray-200">{{ $movement->warehouseTo->name }}</td>
                    <td class="p-1 border border-gray-200">
                        @foreach ($movement->products as $movementProduct)
                            {{ $movementProduct->product->name }}: {{ $movementProduct->quantity }} шт.<br>
                        @endforeach
                    </td>
                    <td class="p-1 border border-gray-200">{{ $movement->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div id="modalBackground"
        class="fixed overflow-y-auto inset-0 bg-gray-900 bg-opacity-50 z-40 transition-opacity duration-500 {{ $showForm ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeForm">
        <div id="form"
            class="fixed top-0 right-0 w-1/3 h-full bg-white shadow-lg transform transition-transform duration-500 ease-in-out z-50 mx-auto p-4"
            style="transform: {{ $showForm ? 'translateX(0)' : 'translateX(100%)' }};" wire:click.stop>
            <button wire:click="closeForm" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl"
                style="right: 1rem;">&times;</button>
            <h2 class="text-xl font-bold mb-4">{{ $movementId ? 'Редактировать перемещение' : 'Новое перемещение' }}
            </h2>

            <div class="mb-4">
                <label>Дата перемещения</label>
                <input type="datetime-local" wire:model="date" class="w-full border rounded">
            </div>

            <div class="mb-4">
                <label>Склад-отправитель</label>
                <select wire:model.change="whFrom" class="w-full border rounded"
                    @if ($selectedProducts || $movementId) disabled @endif>
                    <option value="">Выберите склад</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @if ($warehouse->id == $whTo) disabled @endif>
                            {{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label>Склад-получатель</label>
                <select wire:model.change="whTo" class="w-full border rounded">
                    <option value="">Выберите склад</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @if ($warehouse->id == $whFrom) disabled @endif>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                @include('components.product-search', ['disabled' => count($selectedProducts) > 0])
            </div>

            <div class="mb-4">
                <label>Примечание</label>
                <textarea wire:model="note" class="w-full border rounded"></textarea>
            </div>

            <h3 class="text-lg font-bold mb-4">Выбранные товары</h3>
            <table class="w-full border-collapse border border-gray-200 shadow-md rounded">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-1 border border-gray-200">Товар</th>
                        <th class="p-1 border border-gray-200">Количество</th>
                        <th class="p-1 border border-gray-200">Действия</th>
                    </tr>
                </thead>
                @if ($selectedProducts)
                    <tbody>
                        @foreach ($selectedProducts as $productId => $details)
                            <tr>
                                <td class="p-1 border border-gray-200">
                                    <div class="flex items-center">
                                        @if (!$details['image'])
                                            <img src="{{ asset('no-photo.jpeg') }}" class="w-16 h-16 object-cover">
                                        @else
                                            <img src="{{ Storage::url($details['image']) }}"
                                                class="w-16 h-16 object-cover">
                                        @endif
                                        <span class="ml-2">{{ $details['name'] }}</span>
                                    </div>
                                </td>
                                <td class="p-1 border border-gray-200">{{ $details['quantity'] }}</td>
                                <td class="p-1 border border-gray-200">
                                    <button wire:click="openPForm({{ $productId }})" class="text-yellow-500 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="removeProduct({{ $productId }})" class="text-red-500">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100">
                        @php
                            $totalQuantity = 0;
                            foreach ($selectedProducts as $details) {
                                $totalQuantity += $details['quantity'];
                            }
                        @endphp
                        <tr>
                            <td class="p-1 border border-gray-200 font-bold" colspan="1">Итого:</td>
                            <td class="p-1 border border-gray-200 font-bold">{{ $totalQuantity }}</td>
                            <td class="p-1 border border-gray-200"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <div class="flex justify-start mt-4">
                <button wire:click="save" class="bg-[#5CB85C] text-white px-4 py-2 rounded mr-2">
                    <i class="fas fa-save"></i>
                </button>

                @if ($movementId)
                    <button wire:click="delete" class="bg-red-500 text-white px-4 py-2 rounded">
                        <i class="fas fa-trash"></i>
                    </button>
                @endif
            </div>
            @include('components.confirmation-modal')
        </div>
    </div>
    @include('components.product-quantity-modal')
</div>
