<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;
    /* expense category relation */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ExpenseCategory::class, 'expense_category_id', 'id');
    }

    /* user relation */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id')->withTrashed();
    }
}
