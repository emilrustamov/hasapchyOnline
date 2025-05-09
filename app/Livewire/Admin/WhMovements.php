<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WhMovement;
use App\Models\WhMovementProduct;
use App\Services\ProductService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WhMovements extends Component
{

    public $whFrom, $whTo, $note, $movementId;
    public $selectedProducts = [];
    public $warehouses, $stockMovements = [];
    public $showForm = false, $showPForm = false, $showConfirmationModal = false;
    public $productId, $productQuantity = 1, $selectedProduct = null;
    public $productSearch = '', $productResults = [];
    public $startDate, $endDate, $date;
    public $isDirty = false;
    protected $productService;
    protected $listeners = [
        'dateFilterUpdated' => 'updateDateFilter',
        'confirmClose'
    ];

    public function boot(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function mount()
    {
        $this->date = now()->format('Y-m-d\TH:i');
        $this->warehouses = Warehouse::whereJsonContains('users', (string) Auth::id())->get();
        $this->load();
    }

    public function render()
    {
        $this->load();
        return view('livewire.admin.warehouses.movement');
    }

    public function openForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->isDirty = false;
    }

    public function closeForm()
    {
        if ($this->showPForm) return;
        if ($this->isDirty) {
            $this->showConfirmationModal = true;
        } else {
            $this->resetForm();
            $this->showForm = false;
        }
    }

    public function confirmClose($confirm = false)
    {
        if ($confirm) {
            $this->resetForm();
            $this->isDirty = false;
            $this->showForm = false;
        }
        $this->showConfirmationModal = false;
    }

    public function openPForm($productId)
    {
        $this->productId = $productId;
        $this->productQuantity = $this->selectedProducts[$productId]['quantity'] ?? 1;
        $this->showPForm = true;
    }

    public function closePForm()
    {
        $this->reset(['productId', 'productQuantity']);
        $this->showPForm = false;
    }

    public function updated($propertyName)
    {
        $this->isDirty = true;
    }

    public function resetForm()
    {
        $this->reset([
            'whFrom',
            'whTo',
            'note',
            'selectedProducts',
            'productId',
            'productQuantity',
            'selectedProduct',
            'productSearch',
            'movementId'
        ]);
    }

    public function addProduct($productId)
    {
        $stock = WarehouseStock::with('product')
            ->where('warehouse_id', $this->warehouseId)
            ->where('product_id', $productId)
            ->first();

        if ($stock) {
            $this->selectedProducts[$productId] = [
                'name'     => $stock->product->name,
                'quantity' => 1,
                'image'    => $stock->product->image ?? null,
            ];
            $this->openPForm($productId);
        }
    }

    public function removeProduct($productId)
    {
        unset($this->selectedProducts[$productId]);
    }


    public function saveProductModal()
    {
        $this->validate([
            'productQuantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($this->productId);
        if (!$product) {
            session()->flash('error', "Товар не найден.");
            return;
        }
        $this->selectedProducts[$this->productId] = [
            'name'     => $product->name,
            'quantity' => $this->productQuantity,
            'image'    => $product->image ?? null,
        ];
        $this->showPForm = false;
    }

    public function save()
    {
        $this->validate([
            'whFrom'           => 'required|exists:warehouses,id',
            'whTo'             => 'required|exists:warehouses,id|different:whFrom',
            'selectedProducts' => 'required|array|min:1',
            'date'             => 'required|date',
        ]);

        // Если идет редактирование, сначала отменяем предыдущее перемещение
        if ($this->movementId) {
            $this->reverseMovement();
            $movement = WhMovement::findOrFail($this->movementId);
            $movement->update([
                'wh_from' => $this->whFrom,
                'wh_to'   => $this->whTo,
                'note'           => $this->note,
                'date'    => $this->date,
            ]);
        } else {
            // Создаем новое перемещение
            $movement = WhMovement::create([
                'wh_from' => $this->whFrom,
                'wh_to'   => $this->whTo,
                'note'           => $this->note,
                'date'    => $this->date,
            ]);
        }

        // Обновляем остатки и создаем записи перемещения по каждому товару
        foreach ($this->selectedProducts as $productId => $details) {
            // Проверка наличия товара и достаточного остатка на складе отправления
            $stockFrom = WarehouseStock::where('warehouse_id', $this->whFrom)
                ->where('product_id', $productId)
                ->first();
            if (!$stockFrom || $stockFrom->quantity < $details['quantity']) {
                session()->flash('error', "Недостаточно товара на складе для перемещения: {$details['name']}");
                return;
            }

            // Обновляем остаток на складе отправления
            $stockFrom->decrement('quantity', $details['quantity']);

            // Обновляем остаток на складе получения
            $stockTo = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $this->whTo, 'product_id' => $productId],
                ['quantity' => 0]
            );
            $stockTo->increment('quantity', $details['quantity']);

            // Записываем перемещение для данного товара
            WhMovementProduct::create([
                'movement_id' => $movement->id,
                'product_id'  => $productId,
                'quantity'    => $details['quantity'],
            ]);
        }

        session()->flash('success', $this->movementId ? 'Перемещение успешно обновлено.' : 'Перемещение успешно выполнено.');
        $this->closeForm();
    }

    protected function reverseMovement()
    {
        $movement = WhMovement::with('products')->findOrFail($this->movementId);
        foreach ($movement->products as $movementProduct) {
            $productId = $movementProduct->product_id;
            $quantity = $movementProduct->quantity;

            // Восстанавливаем остаток на складе отправления
            WarehouseStock::updateOrCreate(
                ['warehouse_id' => $movement->wh_from, 'product_id' => $productId],
                ['quantity' => DB::raw("quantity + {$quantity}")]
            );

            // Снимаем остаток со склада получения
            $stockTo = WarehouseStock::where('warehouse_id', $movement->wh_to)
                ->where('product_id', $productId)
                ->first();
            if ($stockTo && $stockTo->quantity >= $quantity) {
                $stockTo->decrement('quantity', $quantity);
            }
            // Удаляем запись по товару перемещения
            $movementProduct->delete();
        }
    }

    public function edit($id)
    {
        $movement = WhMovement::with('products')->findOrFail($id);
        $this->movementId = $movement->id;
        $this->whFrom = $movement->wh_from;
        $this->whTo = $movement->wh_to;
        $this->note = $movement->note;
        $this->selectedProducts = [];

        foreach ($movement->products as $movementProduct) {
            $product = Product::find($movementProduct->product_id);
            if ($product) {
                $this->selectedProducts[$product->id] = [
                    'name'     => $product->name,
                    'quantity' => $movementProduct->quantity,
                    'image'    => $product->image ?? null,
                ];
            } else {
                session()->flash('error', "Товар с ID {$movementProduct->product_id} не найден.");
            }
        }

        $this->showForm = true;
    }

    public function delete()
    {
        if (!$this->movementId) {
            session()->flash('error', 'Перемещение не найдено.');
            return;
        }

        $movement = WhMovement::with('products')->findOrFail($this->movementId);
        foreach ($movement->products as $movementProduct) {
            $productId = $movementProduct->product_id;
            // Восстанавливаем остатки
            WarehouseStock::updateOrCreate(
                ['warehouse_id' => $movement->wh_from, 'product_id' => $productId],
                ['quantity' => DB::raw("quantity + {$movementProduct->quantity}")]
            );

            $stockTo = WarehouseStock::where('warehouse_id', $movement->wh_to)
                ->where('product_id', $productId)
                ->first();
            if ($stockTo && $stockTo->quantity >= $movementProduct->quantity) {
                $stockTo->decrement('quantity', $movementProduct->quantity);
            }
        }
        $movement->delete();
        session()->flash('success', 'Перемещение успешно удалено.');
        $this->resetForm();
    }

    //поиск товара начало
    public function showAllProducts()
    {
        $this->productResults = $this->productService->searchProductsByWarehouse('', $this->whFrom);
    }

    public function updatedProductSearch()
    {
        $this->productResults = $this->productService->searchProductsByWarehouse($this->productSearch, $this->whFrom);
    }

    public function selectProduct($productId)
    {
        $this->selectedProduct = $this->productService->getProductById($productId);
        $this->productSearch = '';
        $this->productResults = [];
        $this->openPForm($productId);
    }

    public function deselectProduct()
    {
        $this->selectedProduct = null;
    }
    //поиск товара конец

    public function updateDateFilter($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->load();
    }

    public function load()
    {
        $query = WhMovement::with(['warehouseFrom', 'warehouseTo', 'products.product']);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }
        $this->stockMovements = $query->latest()->get();
    }
}
