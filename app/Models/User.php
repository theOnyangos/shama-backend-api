<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'first_name',
        'last_name',
        'user_type',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function addressDetails(): HasOne
    {
        return $this->hasOne(UserAddress::class);
    }

    public function medicalDetails(): HasOne
    {
        return $this->hasOne(MedicalDetail::class);
    }

    public function educationDetails(): HasOne
    {
        return $this->hasOne(EducationDetail::class);
    }

    public function otherDetails(): HasOne
    {
        return $this->hasOne(UserOtherDetail::class);
    }

    /**
     * Define a relationship with the "teams" table for users who are coaches.
     */
    public function coachedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'coach_id');
    }

    /**
     * Define a relationship with the "teams" table for users who are players.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'id');
    }

    /**
     * Define a many-to-many relationship with the "team_locations" table for coaches.
     */
    public function coachedTeamLocations(): BelongsToMany
    {
        return $this->belongsToMany(TeamLocation::class, 'team_location_user', 'member_id', 'team_location_id')
            ->where('role', 'coach');
    }

    /**
     * Define a many-to-many relationship with the "team_locations" table for players.
     */
    public function teamLocations(): BelongsToMany
    {
        return $this->belongsToMany(TeamLocation::class, 'player_team_location', 'member_id', 'team_location_id');
    }

    // Define a relationship with TeamLocationUser
    public function teamLocationUsers(): HasMany
    {
        return $this->hasMany(TeamLocationUser::class, 'user_id');
    }
}
