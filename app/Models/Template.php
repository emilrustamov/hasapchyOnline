<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_register_id',
        'name',
        'icon',
        'amount',
        'currency_id',
        'type',
        'category_id',
        'transaction_date',
        'note',
        'client_id',
        'user_id', 
        'project_id',
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientBalance()
    {
        return $this->hasOne(ClientBalance::class);
    }
}