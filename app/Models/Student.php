<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
        ->generateSlugsFrom('username')
        ->saveSlugsTo('slug');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function generateFullName($first_name, $last_name, $middle_name = null)
    {
        return $first_name
        . ' '
        .($middle_name?"$middle_name ":'')
        .$last_name;
    }

    public function getFullNameAttribute()
    {
        return $this->first_name
        . ' '
        .($this->middle_name?"$this->middle_name ":'')
        .$this->last_name;
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'student_id');
    }

    public function shortlist()
    {
        return $this->hasOne(Shortlist::class, 'student_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'student_id');
    }

    public function isShortlisted()
    {
        return (null !== $this->shortlist)?true:false;
    }

    public function currentApplication()
    {
        return $this->applications()->where('academic_year_id', AcademicYear::current()->id)->first();
    }

    public function currentInvoice()
    {
        return $this->invoices()->where('academic_year_id', AcademicYear::current()->id)->first();
    }

    public function studentDeadline()
    {
        if ($this->is_fresher) {
            $student_type = 'fresher';
        } else { $student_type = 'continuous'; }

        return Deadline::where([['academic_year_id', AcademicYear::current()->id], ['student_type', $student_type]])->first();
    }

    public function scopeHasApplication($query)
    {
        $query->whereHas('applications', function (Builder $query, $academic_year_id = null)
        {
            $query
            ->where('academic_year_id', $academic_year_id??AcademicYear::current()->id);
        });
    }

    public function scopeNotRedFlagged($query)
    {
        $query->whereHas('applications', function (Builder $query)
        {
            $query
            ->where('red_flagged', 0);
        });
    }

}
