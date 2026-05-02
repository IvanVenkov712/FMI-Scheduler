<?php
namespace App\core;

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

    public function toArray()
    {
        return get_object_vars($this);
    }
}
