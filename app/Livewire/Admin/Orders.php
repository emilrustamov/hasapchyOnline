<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderStatus;
use App\Models\OrderCategory;
use App\Services\ClientService;
use App\Models\OrderAf;
use App\Models\OrderAfValue;
use App\Models\FinancialTransaction;
use App\Models\Currency;
use App\Models\TransactionCategory;
use App\Models\CashRegister;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Services\ProductService;

class Orders extends Component
{
    public $orders, $clients, $users, $statuses, $categories, $currencies;
    public $client_id, $user_id, $status_id, $category_id, $note, $date;
    public $order_id;
    public $showForm = false;
    public $showTrForm = false;
    public $showConfirmationModal = false;
    public $clientSearch = ''; // Added for client search
    public $clientResults = []; // Added for client search results
    public $selectedClient; // Added for selected client
    public $afFields;
    public $afValues = [];
    public $transaction_note, $transaction_amount, $transaction_date, $transaction_category_id, $transaction_currency_id, $transaction_cash_register_id;
    public $incomeCategories = [], $transactions, $cashRegisters = [];
    public $isDirty = false;
    public $totalSum = 0;
    public $displayCurrency;
    public $selectedProducts = [];
    public $productId;
    public $productQuantity = 1;
    public $productPrice;
    public $productDiscount = 0;
    public $showPForm = false;
    public $productSearch = '';
    public $productResults = [];
    public $selectedProduct;
    protected $clientService;
    protected $productService;

    protected $listeners = [
        'productSelected' => 'selectProduct',
        'saveProductModal' => 'saveProductModal',
        'closeProductQuantityModal' => 'closePForm',
    ];

    public function boot(ClientService $clientService, ProductService $productService)
    {
        $this->clientService = $clientService;
        $this->productService = $productService;
    }

    public function mount()
    {
        $this->afFields = collect();
        $this->transaction_date = now()->toDateString();
        $this->currencies = Currency::all();
        $this->incomeCategories = TransactionCategory::where('type', 1)->get();
        $this->cashRegisters = CashRegister::whereJsonContains('users', (string) Auth::id())->get();
        $this->displayCurrency = Currency::where('is_report', 1)->first();
    }


    public function render()
    {
        $this->orders = Order::with(['client', 'user', 'status', 'category'])->get();
        $this->clients = $this->clientService->searchClients($this->clientSearch);
        $this->users = User::all();
        $this->statuses = OrderStatus::all();
        $this->categories = OrderCategory::all();
        $this->loadAfFields();

        if ($this->transactions) {
            $this->calculateTotalSum();
        }
        return view('livewire.admin.orders.orders');
    }

    public function loadAfFields()
    {
        if ($this->category_id) {
            $this->afFields = OrderAf::whereJsonContains('category_ids', (string)$this->category_id)->get();

            if ($this->order_id) {
                // Загружаем существующие значения для редактируемого заказа
                $existingValues = OrderAfValue::where('order_id', $this->order_id)
                    ->whereIn('order_af_id', $this->afFields->pluck('id'))
                    ->pluck('value', 'order_af_id')
                    ->toArray();

                $this->afValues = $existingValues;
            } else {
                // Используем значения по умолчанию только для нового заказа
                $this->afValues = $this->afFields->pluck('default', 'id')->toArray();
            }
        } else {
            $this->afFields = collect();
            $this->afValues = [];
        }
    }

    //начало поиск клиент
    public function showAllClients()
    {
        $this->clientResults = $this->clientService->getAllClients();
    }


    public function updatedClientSearch()
    {
        $this->clientResults = $this->clientService->searchClients($this->clientSearch);
    }

    public function selectClient($clientId)
    {
        $this->selectedClient = $this->clientService->getClientById($clientId);
        $this->client_id = $clientId;
        $this->clientResults = [];
    }

    public function deselectClient()
    {
        $this->selectedClient = null;
        $this->client_id = null;
        $this->clientSearch = '';
        $this->clientResults = [];
    }
    //конец поиск клиент

    //начало поиск товар

    //поиск товара начало
    public function showAllProducts()
    {
        $this->productResults = $this->productService->getAllProductsServices();
    }

    public function updatedProductSearch()
    {
        $this->productResults = $this->productService->searchProductsByWarehouse($this->productSearch, $this->warehouseId);
    }

    public function selectProduct($productId)
    {
        $this->selectedProduct = $this->productService->getProductById($productId);
        $this->productSearch = ''; // Очищаем поле поиска
        $this->productResults = []; // Очищаем результаты поиска
        $this->openPForm($productId); // Добавляем товар в выбранные
    }

    public function deselectProduct()
    {
        $this->selectedProduct = null;
    }
    //поиск товара конец


    public function updatedCategoryId($value)
    {
        $this->afFields = OrderAf::whereJsonContains('category_ids', $value)->get();
        if ($this->order_id) {

            $existingValues = OrderAfValue::where('order_id', $this->order_id)
                ->whereIn('order_af_id', $this->afFields->pluck('id'))
                ->pluck('value', 'order_af_id')
                ->toArray();
            $this->afValues = $existingValues;
        } else {

            $this->afValues = [];
            foreach ($this->afFields as $field) {
                $this->afValues[$field->id] = $field->default;
            }
        }
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function store()
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'category_id' => 'required|exists:order_categories,id',
            'date' => 'required|date',
        ]);

        $order = Order::updateOrCreate(['id' => $this->order_id], [
            // 'name' => $this->name,
            'client_id' => $this->client_id,
            'user_id' => Auth::id(),
            'status_id' => $this->status_id ?? 1,
            'category_id' => $this->category_id,
            'note' => $this->note,
            'date' => $this->date,
        ]);

        $validAfIds = $this->afFields->pluck('id')->toArray();

        foreach ($this->afValues as $afId => $value) {

            if (in_array($afId, $validAfIds)) {
                OrderAfValue::updateOrCreate(
                    ['order_id' => $order->id, 'order_af_id' => $afId],
                    ['value' => $value ?? '']
                );
            }
        }

        OrderAfValue::where('order_id', $order->id)
            ->whereNotIn('order_af_id', $validAfIds)
            ->delete();

        session()->flash(
            'message',
            $this->order_id ? 'Заказ успешно обновлен.' : 'Заказ успешно создан.'
        );
        $this->resetForm();
        $this->showForm = false;
        $this->isDirty = false;
    }

    public function edit($id)
    {
        $order = Order::findOrFail($id);
        $this->order_id = $id;
        $this->client_id = $order->client_id;
        $this->status_id = $order->status_id;
        $this->category_id = $order->category_id;
        $this->note = $order->note;
        $this->date = $order->date;
        $this->selectedClient = $this->clientService->getClientById($order->client_id);
        $this->loadAfFields();
        $this->transactions = FinancialTransaction::whereIn('id', json_decode($order->transaction_ids) ?? [])->get();
        $this->calculateTotalSum();
        $this->showForm = true;

        // Загрузка товаров заказа
        $this->selectedProducts = $order->orderProducts->mapWithKeys(function ($item) {
            return [
                $item->product_id => [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                ]
            ];
        })->toArray();
    }

    private function calculateTotalSum()
    {
        if (!$this->displayCurrency) {
            $this->totalSum = $this->transactions->sum('amount');
            return;
        }

        $this->totalSum = $this->transactions->sum(function ($transaction) {
            if ($transaction->currency_id == $this->displayCurrency->id) {
                return $transaction->amount;
            } else {
                // Предполагается, что у вас есть методы для получения курса обмена
                $transactionCurrency = $transaction->currency;
                $cashRegisterCurrency = $this->displayCurrency;

                $transactionExchangeRate = $transactionCurrency->currentExchangeRate()->exchange_rate;
                $cashRegisterExchangeRate = $cashRegisterCurrency->currentExchangeRate()->exchange_rate;

                if ($transactionExchangeRate && $cashRegisterExchangeRate) {
                    return ($transaction->amount / $transactionExchangeRate) * $cashRegisterExchangeRate;
                }
                return 0;
            }
        });
    }
    public function deleteOrderForm()
    {

        $order = Order::find($this->order_id);
        if ($order && $order->transaction_ids) {
            $transactionIds = json_decode($order->transaction_ids, true);
            FinancialTransaction::whereIn('id', $transactionIds)->delete();
        }

        Order::find($this->order_id)->delete();
        session()->flash('message', 'Order Deleted Successfully.');
        $this->showForm = false;
        $this->resetForm();
    }


    public function deleteTransaction($transactionId)
    {
        $order = Order::find($this->order_id);
        if ($order && $order->transaction_ids) {
            $transactionIds = json_decode($order->transaction_ids, true);
            $updatedTransactionIds = array_filter($transactionIds, function ($id) use ($transactionId) {
                return $id != $transactionId;
            });
            $order->transaction_ids = json_encode(array_values($updatedTransactionIds));
            $order->save();
        }

        $transaction = FinancialTransaction::find($transactionId);
        if ($transaction) {
            $cashRegister = $transaction->cashRegister;
            if ($cashRegister) {
                $cashRegister->balance -= $transaction->amount;
                $cashRegister->save();
            }
            $transaction->delete();
            session()->flash('message', 'Транзакция успешно удалена.');
        }
    }

    public function updateStatus($orderId, $newStatusId)
    {
        $order = Order::find($orderId);
        if ($order) {
            $order->status_id = $newStatusId;
            $order->save();

            session()->flash('message', 'Статус заказа обновлен.');
        }
    }

    public function openConfirmationModal()
    {
        $this->showConfirmationModal = true;
    }

    public function closeConfirmationModal()
    {
        $this->showConfirmationModal = false;
    }

    public function confirmClose($confirm = false)
    {
        if ($confirm) {
            $this->resetForm();
            $this->showForm = false;
            $this->isDirty = false;
        }
        $this->showConfirmationModal = false;
    }


    public function closeForm()
    {
        if ($this->isDirty) {
            $this->showConfirmationModal = true;
        } else {
            $this->resetForm();
            $this->showForm = false;
        }
    }

    public function openForm()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openTrForm()
    {
        $this->resetTrForm();
        $this->showTrForm = true;
    }

    public function closeTrForm()
    {
        if ($this->isDirty) {
            $this->showConfirmationModal = true;
        } else {
            $this->resetTrForm();
            $this->showTrForm = false;
        }
    }

    public function createTransaction()
    {
        $this->validate([
            'transaction_note' => 'nullable|string|max:255',
            'transaction_amount' => 'required|numeric',
            'transaction_date' => 'required|date',
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'transaction_currency_id' => 'required|exists:currencies,id',
            'transaction_cash_register_id' => 'required|exists:cash_registers,id',
        ]);

        $transaction = FinancialTransaction::create([
            'type' => 1,
            'amount' => $this->transaction_amount,
            'note' => 'Заказ номер ' . $this->order_id . ': ' . $this->transaction_note,
            'transaction_date' => $this->transaction_date,
            'category_id' => $this->transaction_category_id,
            'currency_id' => $this->transaction_currency_id,
            'client_id' => $this->client_id,
            'user_id' => Auth::id(),
            'cash_register_id' => $this->transaction_cash_register_id,
        ]);

        $cashRegister = CashRegister::find($this->transaction_cash_register_id);
        if ($cashRegister) {
            if ($this->transaction_currency_id && $this->transaction_currency_id != $cashRegister->currency_id) {
                $transactionCurrency = Currency::find($this->transaction_currency_id);
                $cashRegisterCurrency = Currency::find($cashRegister->currency_id);
                $transactionExchangeRate = $transactionCurrency->currentExchangeRate()->exchange_rate;
                $cashRegisterExchangeRate = $cashRegisterCurrency->currentExchangeRate()->exchange_rate;
                $amountInDefaultCurrency = $this->transaction_amount / $transactionExchangeRate;
                $convertedAmount = $amountInDefaultCurrency * $cashRegisterExchangeRate;

                $transaction->note .= " // Original Amount: {$this->transaction_amount} {$transactionCurrency->symbol}";

                $transaction->update([
                    'amount' => $convertedAmount,
                    'currency_id' => $cashRegister->currency_id,
                ]);
            } else {
                $convertedAmount = $this->transaction_amount;
            }
            $cashRegister->balance += $convertedAmount;
            $cashRegister->save();
        }

        $order = Order::find($this->order_id);
        $transactionIds = json_decode($order->transaction_ids, true) ?? [];
        $transactionIds[] = $transaction->id;
        $order->update(['transaction_ids' => json_encode($transactionIds)]);

        session()->flash('message', 'Транзакция успешно создана.');
        $this->resetTrForm();
        $this->showTrForm = false;
    }


    public function editTransaction($transactionId)
    {
        $transaction = FinancialTransaction::find($transactionId);
        if ($transaction) {

            $this->transaction_note = $transaction->note;
            $this->transaction_amount = $transaction->amount;
            $this->transaction_date = $transaction->transaction_date;
            $this->transaction_category_id = $transaction->category_id;
            $this->transaction_currency_id = $transaction->currency_id;
            $this->transaction_cash_register_id = $transaction->cash_register_id;
            $this->order_id = $transaction->order_id ?? $this->order_id;
            $this->showTrForm = true;
        }
    }

    private function resetTrForm()
    {
        $this->transaction_note = '';
        $this->transaction_amount = '';
        $this->transaction_date = now()->toDateString();
        $this->transaction_category_id = '';
        $this->transaction_currency_id = '';
        $this->transaction_cash_register_id = '';
    }

    public function resetForm()
    {
        $this->reset([
            'client_id',
            'user_id',
            'status_id',
            'category_id',
            'note',
            'date',
            'order_id',
            'clientSearch',
            'clientResults',
            'selectedClient',
            'afFields',
            'afValues',
            'transaction_note',
            'transaction_amount',
            'transaction_date',
            'transaction_category_id',
            'transaction_currency_id',
            'transaction_cash_register_id',
            'incomeCategories',
            'transactions',
            'cashRegisters',
        ]);
    }

    public function addProduct()
    {

        $this->selectedProducts[] = [
            'name' => '',
            'quantity' => 1,
            'price' => 0,
            'discount' => 0,
        ];
    }

    public function saveProductModal()
    {
        $this->validate([
            'productQuantity' => 'required|integer|min:1',
            'productPrice' => 'required|numeric|min:0.01', // Использование 'productPrice'
            'productDiscount' => 'nullable|numeric|min:0',
        ]);

        if ($this->productQuantity <= 0) {
            session()->flash('error', 'Количество товара должно быть больше нуля.');
            return;
        }

        $product = Product::findOrFail($this->productId);

        $this->selectedProducts[$this->productId] = [
            'name' => $product->name,
            'quantity' => $this->productQuantity,
            'price' => $this->productPrice, // Использование 'productPrice'
            'discount' => $this->productDiscount,
        ];

        $this->closePForm();
    }

    public function saveOrder()
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'category_id' => 'required|exists:order_categories,id',
            'date' => 'required|date',
            'selectedProducts' => 'required|array|min:1',
            // ...other validations...
        ]);

        $order = Order::updateOrCreate(
            ['id' => $this->order_id],
            [
                'client_id' => $this->client_id,
                'user_id' => Auth::id(),
                'status_id' => $this->status_id ?? 1,
                'category_id' => $this->category_id,
                'note' => $this->note,
                'date' => $this->date,
            ]
        );

        // Обработка товаров в заказе
        $order->orderProducts()->delete(); // Удаляем старые позиции

        foreach ($this->selectedProducts as $productId => $details) {
            $order->orderProducts()->create([
                'product_id' => $productId,
                'quantity' => $details['quantity'],
                'price' => $details['price'],
                'discount' => $details['discount'],
            ]);
        }

        session()->flash(
            'message',
            $this->order_id ? 'Заказ успешно обновлен.' : 'Заказ успешно создан.'
        );
        $this->resetForm();
        $this->showForm = false;
        $this->isDirty = false;
    }

    public function removeProduct($productId)
    {
        unset($this->selectedProducts[$productId]);
    }

  
    public function openPForm($productId)
    {
        $this->productId = $productId;
        $this->showPForm = true;

        // Инициализация productPrice при открытии модального окна
        $product = Product::find($productId);
        $this->productPrice = $product->price; // Предполагается, что у модели Product есть поле 'price'
    }

  
    public function closePForm()
    {
        $this->showPForm = false;
        $this->productId = null;
        $this->productQuantity = 1;
        $this->productPrice = null; // Сброс 'productPrice'
        $this->productDiscount = 0;
    }
}
