<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLinkClick extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'email_send_id',
        'url',
        'ip_address',
        'user_agent',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    /** @return BelongsTo<EmailSend, $this> */
    public function emailSend(): BelongsTo
    {
        return $this->belongsTo(EmailSend::class);
    }
}
