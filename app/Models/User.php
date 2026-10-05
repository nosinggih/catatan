<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'terms_accepted_at' => 'datetime',
        ];
    }

    /**
     * Whether the user has accepted the current version of the terms.
     */
    public function hasAcceptedCurrentTerms(): bool
    {
        return $this->terms_accepted_at !== null
            && $this->terms_version === config('catatan.terms_version');
    }

    public function acceptTerms(): void
    {
        $this->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => config('catatan.terms_version'),
        ])->save();
    }

    public function households(): BelongsToMany
    {
        return $this->belongsToMany(Household::class, 'household_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * The household the user is working in. Every user gets a personal
     * household the first time it is needed.
     */
    public function currentHousehold(): Household
    {
        if ($household = $this->households()->oldest('household_members.id')->first()) {
            return $household;
        }

        $household = Household::create(['name' => 'Rumah '.$this->name]);
        $household->members()->attach($this->id, ['role' => 'owner']);

        return $household;
    }
}
