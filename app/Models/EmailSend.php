<?php

namespace App\Models;

use App\Models\Shop\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailSend extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'shop_order_id',
        'recipient_email',
        'subject',
        'tracking_token',
        'sent_at',
        'opened_at',
        'open_count',
        'click_count',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
    ];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'shop_order_id');
    }

    /** @return HasMany<EmailLinkClick, $this> */
    public function clicks(): HasMany
    {
        return $this->hasMany(EmailLinkClick::class);
    }
}
