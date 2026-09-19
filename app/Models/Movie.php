<?php

namespace App\Models;

/**
 * Backward compatibility alias for Event model
 */
class Movie extends Event
{
    protected $table = 'events';
}
