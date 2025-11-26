<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Tests\Fixtures\Factories\UserFactory;
use Outerweb\ImageLibrary\Traits\HasImages;

#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasFactory;
    use HasImages;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function profilePicture(): MorphOne
    {
        return $this->morphOne(Image::class, 'model');
    }

    public function gallery(): MorphMany
    {
        return $this->morphMany(Image::class, 'model');
    }

    public function friends(): HasMany
    {
        return $this->hasMany(self::class, 'user_id');
    }

    /**
     * @return array{
     *   email_verified_at: 'datetime',
     *   password: 'hashed',
     * }
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
