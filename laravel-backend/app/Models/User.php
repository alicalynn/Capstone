<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'verified',
        'username',
        'phone_number',
        'address',
        'application_status',
        'business_permit',
        'photo_url',
        'age',
        'height',
        'weight',
        'activity_level',
        'fitness_goal',
        'allergies',
        'dietary_restrictions',
        'cuisine_preferences',
        'preferred_meal_times',
        'location',
        'preferences'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'verified' => 'boolean',
            'allergies' => 'array',
            'dietary_restrictions' => 'array',
            'cuisine_preferences' => 'array',
            'preferred_meal_times' => 'array',
            'location' => 'array',
            'preferences' => 'array',
        ];
    }

    /**
     * Get the user's allergens
     */
    public function allergens()
    {
        return $this->hasMany(Allergen::class);
    }

    /**
     * Get the user's meal plans
     */
    public function mealPlans()
    {
        return $this->hasMany(MealPlan::class);
    }

    /**
     * Get the user's karenderia (if they're an owner)
     */
    public function karenderia()
    {
        return $this->hasOne(Karenderia::class, 'owner_id');
    }

    /**
     * Get the user's orders (if they're a customer)
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
