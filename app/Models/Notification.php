<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'recipient_user_id',
        'type',
        'related_restock_id',
        'is_anomaly',
        'message',
        'is_read',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_anomaly' => 'boolean',
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Scope: unread notifications only.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Get the user receiving this notification.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * Get the related restock (if type = restock).
     */
    public function restock(): BelongsTo
    {
        return $this->belongsTo(Restock::class, 'related_restock_id');
    }
}
