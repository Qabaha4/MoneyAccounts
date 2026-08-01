<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'action',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        $type = $this->getAttributeFromArray('subject_type');

        if ($type && ! class_exists(static::getActualClassNameForMorph($type))) {
            return new MorphTo(
                $this->newQuery()->setEagerLoads([]),
                $this,
                'subject_id',
                null,
                'subject_type',
                'subject'
            );
        }

        return $this->morphTo();
    }

    public static function log(string $action, ?Model $subject, User $user, string $description, ?array $metadata = null, ?string $subjectType = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'subject_type' => $subjectType ?? ($subject ? get_class($subject) : 'report'),
            'subject_id' => $subject?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
