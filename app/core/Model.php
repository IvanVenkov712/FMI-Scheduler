<?php
namespace App\Core;

/** Mutable record hydrated from declared properties; public fields drive serialization. */
abstract class Model
{
    public function __construct($data = [])
    {
        $this->fill($data);
    }

    public function fill($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function to_array()
    {
        return get_object_vars($this);
    }
}
