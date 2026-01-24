<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    // 承認ステータス定数
    public const STATUS_NORMAL = 0;      // 通常
    public const STATUS_PENDING = 1;     // 承認待ち
    public const STATUS_APPROVED = 2;    // 承認済み

    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'break_times',
        'remarks',
        'approval_status',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_times' => 'array',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 承認待ちかどうかを判定
     */
    public function isPending(): bool
    {
        return $this->approval_status === self::STATUS_PENDING;
    }

    /**
     * 承認済みかどうかを判定
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }
}
