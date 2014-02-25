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

class creature extends species
{
  public $current_age               = 0; // in months
  public $gender                    = FEMALE; // FEMALE / MALE see defines.php
  public $dead                      = ALIVE; // ALIVE, HEAT, COLD, THIRST, STARVATION', OLD_AGE
  
  private $pregnant                 = 0; // gestation count, Note: a value of 0 also indicate not pregnant
  private $months_without_food      = 0; // tracks the number of months in a member of the species has been without food

  /*
  |--------------------------------------------------------------------------
  | give_birth()
  |--------------------------------------------------------------------------
  |
  |  This method simulates an individual member of the species giving birth
  |  and returns $baby, a separate species object.
  |
  */

  function give_birth() // Note: when a species is seeded into a habitat, it's gender is passed in explicitly. Otherwise it is determined by chance below.
  { 
      $this->pregnant = 0; // Reset the pregnancy / gestation counter, since the species is giving birth.

      return $this->spawn(mt_rand(FEMALE,MALE)); // Spohn a new creature with a 50-50 chance of it being MALE or FEMALE
  }

  /*
  |--------------------------------------------------------------------------
  | get_age_in_years()
  |--------------------------------------------------------------------------
  |  
  |  Converts the age of the species from months into years.
  |
  */

  function get_age_in_years()
  {
    return round($this->current_age / 12, 2);
  }

  /*
  |--------------------------------------------------------------------------
  | live()
  |--------------------------------------------------------------------------
  |  
  |  This method accepts the current habitat and handles all of the actions
  |  taken by, and influences exerted upon species within a single month.
  |
  */

  function live(&$habitat) // Handles all of the monthly activities required for the species to live it's life.
  {
      //Give Birth
      if($this->pregnant == $this->gestation_period)  // If the species is pregnant and it's gestation is now complete...
      {
        $habitat->creatures[] = $this->give_birth();  // ... then give birth, and assign the new instance to $baby
        $habitat->current_creature_count++;           // ... and increment the habitat's current species count by 1.
        $habitat->total_births++;
      }

      // Drink
      $this->drink($habitat);
      
      // Eat
      $this->eat($habitat);
      
      // Extreme Cold Temperature Survival Check 
      if($habitat->current_temperature < $this->minimum_temperature) // Check to make sure that the habitat's temperature is not below the minimum temperature in which the species can survive.
      {
        $this->death(COLD); // If it is too cold then this species dies of extreme cold
      }

      // Extreme Heat Temperature Survival Check 
      if($habitat->current_temperature > $this->maximum_temperature) // Check to make sure that the habitat's temperature is not above the maximum temperature in which the species can survive.
      {
        $this->death(HEAT); // If it is too hot then this species dies of extreme heat
      }

      // Breed
      $this->breed($habitat->stressed);

      // Age
      $this->age();
  }

  /*
  |--------------------------------------------------------------------------
  | drink()
  |--------------------------------------------------------------------------
  |  
  |  This method accepts a habitat object and handles an attempt to consume
  |  water from the habitat.
  |
  */

  function drink(&$habitat)
  {      
      if($habitat->current_water < $this->monthly_water_consumption) // check to see if there is enough water in the habitat
      {
        $this->death(THIRST); // if there is not enough water then the species dies of thirst (species must have enough water every single month)
      }
      else // in the event that there is enough water...
      {
        $habitat->current_water = $habitat->current_water - $this->monthly_water_consumption; // decrement the habitat's available water supply in accordance with the species' monthly water consumption requirements.
      }
  }

  /*
  |--------------------------------------------------------------------------
  | eat()
  |--------------------------------------------------------------------------
  |  
  |  This method accepts a habitat object and handles an attempt to consume
  |  food from the habitat.
  |
  */

  function eat(&$habitat)
  {
      if($habitat->current_food < $this->monthly_food_consumption) // check to see if there is enough food in the habitat to feed the species
      {
        if($this->months_without_food > 3) // If there is not enough food and the species has not eaten in three months ...
        {
          $this->death(STARVATION); // ... then it dies of starvation
        }
        else
        {
          $this->months_without_food++; // ... otherwise, simply increment the months_without_food counter.
        }
      }
      else // In the event that there is enough food to eat...
      {
        $habitat->current_food = $habitat->current_food - $this->monthly_food_consumption; // ... decrement the habitat's available food supply in accordance with the species' monthly food consumption requirements.
        $this->months_without_food = 0; // reset the months_without_food counter.
      }
  }

  /*
  |--------------------------------------------------------------------------
  | breed()
  |--------------------------------------------------------------------------
  |  
  |  This method handles both becoming pregnant and gestation period
  |
  */

  function breed($resources_stressed = false)
  {
    
    //Females within their predefined breeding age range can become pregnant
    //Also, allowing the last condition ($this->pregnant > 0) permits females that became pregnant before reaching their maximum breeding age to carry their child to term.
    
    if($this->gender == FEMALE AND $this->get_age_in_years() >= $this->minimum_breeding_age AND ($this->get_age_in_years() <= $this->maximum_breeding_age OR $this->pregnant > 0))
    {
      
      if($this->pregnant == 0 AND $resources_stressed) // if the habitat's resources are stressed then there is only a .5% chance that any given nonpregnant female can get pregnant.
      {
        if(mt_rand(1,1000) > 5)
        {
          return false;
        }
      }

      $this->pregnant++;

      return true;
    }
  }

  /*
  |--------------------------------------------------------------------------
  | age()
  |--------------------------------------------------------------------------
  |  
  |  This method handles the aging process and death via old age.
  |
  */

  function age()
  {
    if($this->get_age_in_years() <= $this->life_span) // If the species hasn't reached its the end of its lifespan yet...
    {
      $this->current_age++; // ... then increment its age by one month.
    }
    else
    {
      $this->death(OLD_AGE); // ... otherwise, the species has as been fortunate enough to escape all of the other perils of life, but now, sadly, it must still die of old age.
    }
  }

  /*
  |--------------------------------------------------------------------------
  | death()
  |--------------------------------------------------------------------------
  |  
  |  Called when an individual member of the species dies.
  |
  */

  function death($reason)
  {
    $this->dead = $reason; // Toggle dead to true so that the species can be removed from the habitat's species array
    
    // log the death
    $cause = array(HEAT => 'HEAT', COLD => 'COLD', THIRST => 'THIRST', STARVATION => 'STARVATION', OLD_AGE => 'OLD_AGE');
    log::record('1 ' . $this->name . ' died of ' . $cause[$reason] . ' at ' . $this->get_age_in_years() . ' years of age.', 3);
  }
}