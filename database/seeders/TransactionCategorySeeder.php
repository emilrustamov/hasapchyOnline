<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransactionCategory;

class TransactionCategorySeeder extends Seeder
{
    public function run()
    {
        TransactionCategory::updateOrCreate(['name' => 'Продажа'], ['type' => 1]);
        TransactionCategory::updateOrCreate(['name' => 'Оплата покупателя за услугу, товар'], ['type' => 1]);
        TransactionCategory::updateOrCreate(['name' => 'Предоплата'], ['type' => 1]);
        // TransactionCategory::updateOrCreate(['name' => 'Возврат денег от поставщика'], ['type' => 1]);
        TransactionCategory::updateOrCreate(['name' => 'Прочий приход денег'], ['type' => 1]);
        TransactionCategory::updateOrCreate(['name' => 'Возврат денег покупателю'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Оплата поставщикам товаров, запчастей'], ['type' => 0]);
        // TransactionCategory::updateOrCreate(['name' => 'Выплата'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Выплата зарплаты'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Выплата налогов'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Оплата аренды'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Оплата ГСМ, транспортных услуг'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Оплата коммунальных расходов'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Оплата рекламы'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Оплата телефона и интернета'], ['type' => 0]);
        TransactionCategory::updateOrCreate(['name' => 'Прочий расход денег'], ['type' => 0]);
    }
}
