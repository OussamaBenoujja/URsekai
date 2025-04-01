<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'transaction_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_id',
        'user_id',
        'transaction_type',
        'amount',
        'currency',
        'description',
        'status',
        'payment_method',
        'payment_details',
        'platform_fee',
        'developer_cut',
        'tax_amount',
        'ip_address',
        'user_agent',
        'created_at',
        'updated_at',
        'completed_at',
        'reference_id',
        'game_id',
        'developer_id',
        'item_id',
        'subscription_id',
        'is_test'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'platform_fee' => 'float',
        'developer_cut' => 'float',
        'tax_amount' => 'float',
        'payment_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_test' => 'boolean',
    ];

    /**
     * Get the user who made the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the game associated with the transaction.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Get the developer associated with the transaction.
     */
    public function developer()
    {
        return $this->belongsTo(Developer::class, 'developer_id', 'developer_id');
    }

    /**
     * Get the item purchased in this transaction.
     */
    public function item()
    {
        return $this->belongsTo(VirtualItem::class, 'item_id', 'item_id');
    }

    /**
     * Get the subscription purchased in this transaction.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'subscription_id');
    }

    /**
     * Get the user subscription associated with this transaction.
     */
    public function userSubscription()
    {
        return $this->hasOne(UserSubscription::class, 'transaction_id', 'transaction_id');
    }

    /**
     * Get the user item associated with this transaction.
     */
    public function userItem()
    {
        return $this->hasOne(UserItem::class, 'transaction_id', 'transaction_id');
    }

    /**
     * Scope a query to only include successful transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter by transaction type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate UUID when creating a new transaction
        static::creating(function ($model) {
            if (!$model->transaction_id) {
                $model->transaction_id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}