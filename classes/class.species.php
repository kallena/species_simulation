<?php

/*
Copyright (c) 2014, Arron Kallenberg

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

class species
{

  public $name;
  public $monthly_food_consumption 	= 3;
  public $monthly_water_consumption = 4;
  public $life_span 				        = 200;
  public $minimum_breeding_age 		  = 5;
  public $maximum_breeding_age 		  = 10;
  public $gestation_period 		      = 9;
  public $minimum_temperature 		  = 0;
  public $maximum_temperature 		  = 95;

  function spawn($gender = null)
  {
      $creature = new creature();

      // The new creature is assinged the properties of the species from which it spawned.
      foreach($this as $var => $value)
      {
          $creature->$var = $value;
      }

      $creature->gender         = $gender;
      $creature->current_age    = 0;

      log::record('A new ' . (($creature->gender) ? 'male' : 'female') . ' ' . $creature->name . ' was born.', 3);

      return $creature;
  }

}