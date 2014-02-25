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

class habitat
{
	public $name						= 'New York City';
	public $monthly_food				= null;
    public $monthly_water 				= null;
    public $average_temperature			= array('summer' => null, 'spring' => null, 'fall' => null, 'winter' => null);

    public $creatures 					= array(); // an array where all of the current species living the habitat are stored.
	public $creature_count				= 0;
	public $current_males				= 0;

	public $current_food 				= null;
	public $current_water 				= null;
	public $current_temperature			= null;

	private $food_stress_coefficient	= null;
	private $water_stress_coefficient	= null;
	public  $stressed					= false;

	//stats
	public $current_creature_count		= 0;
	public $max_creature_count			= 0;
	public $population					= 0;
	public $total_births				= 0;
	public $total_deaths				= 0;
	public $deaths						= array(HEAT => 0, COLD => 0, STARVATION => 0, THIRST => 0, OLD_AGE => 0);

	/*
	|--------------------------------------------------------------------------
	| resources_stressed()
	|--------------------------------------------------------------------------
	|  
	| This method determines whether or not the current species population will
	| exceed the habitat's resources. Sets $resources_stressed to either true or false
	|
	*/

	function resource_stress_test() 
	{
		// set demand couters
		$food_demand  = $this->food_stress_coefficient * $this->current_creature_count;
		$water_demand = $this->water_stress_coefficient * $this->current_creature_count;

		// Set $this->resources_stressed
		$this->stressed = ($water_demand > $this->monthly_water OR $food_demand > $this->monthly_food);

		if($this->stressed)
		{
			log::record('Food and/or water resources are currently stressed!', 3);
		}
	}

	/*
	|--------------------------------------------------------------------------
	| seed()
	|--------------------------------------------------------------------------
	|  
	| This method is called when a species starts in new habitat.
	|
	*/

	function seed($species)
	{	
		// This method is called when a species starts in new habitat.
		// One female and one male are assigned to the habitat's species array.

		log::record('Seeding ' . $this->name .  ' with 1 male & 1 female ' . $species->name, 1);

		unset($this->creatures); 

		$this->creatures[] = $species->spawn(FEMALE);
		$this->creatures[] = $species->spawn(MALE);

		$this->current_creature_count = 2;
		$this->total_births += 2;
		$this->current_males = 1;

		$this->food_stress_coefficient  = $species->monthly_food_consumption;
		$this->water_stress_coefficient = $species->monthly_water_consumption;
	}

	/*
	|--------------------------------------------------------------------------
	| refresh_resources()
	|--------------------------------------------------------------------------
	|  
	| This method is called to reset the habitat's food and water resources
	| to the pre-determined defaults.
	|
	*/

	function refresh_resources()
	{
		$this->current_food  = $this->monthly_food;
		$this->current_water = $this->monthly_water;
	}

	/*
	|--------------------------------------------------------------------------
	| reset()
	|--------------------------------------------------------------------------
	|  
	| Used to reset the habitat's counters.
	|
	*/

	function reset()
	{		
		$this->total_births = 0;
		$this->total_deaths = 0;
		$this->deaths 		= array(HEAT => 0, COLD => 0, STARVATION => 0, THIRST => 0, OLD_AGE => 0);
	}

	/*
	|--------------------------------------------------------------------------
	| set_tempature()
	|--------------------------------------------------------------------------
	|  
	| Determines and sets the current temperature for the habitat according to
	| the season, which is pass into the method.
	|
	*/

	function set_tempature($season)
	{	
		// Determine by how many degrees the habitat's temperature will fluctuate from it's average this month.
		if (mt_rand(1, 1000) > 5) // There is 99.5% chance of normal tempature fluctuation
		{
			// The tempature is allowed to fluctuate above/below the habitat's average tempature by up to 5 degrees.
			$tempature_modifier = mt_rand(-5, 5); 
		}
		else // There is a 0.5% chance of extreme tempature fluctuation 
		{
			// The tempature is permitted fluctuate by up to 15 degrees above/below the habitat's average tempature.
			$tempature_modifier = mt_rand(-15, 15); 
		}

		return $this->current_temperature = $this->average_temperature[$season] + $tempature_modifier; // Apply the fluctuation determined above to the habitat's average temperature taking into account the current season. Set and return the results.
	}

	/*
	|--------------------------------------------------------------------------
	| simulate()
	|--------------------------------------------------------------------------
	|  
	| Simulates one month in the habitat.
	|
	*/

	function simulate()
	{
		// run the habitats resource stress test to determine whether or not the habitat can sustain the current demand.
		$this->resource_stress_test();

		// Uncomment the following line of code to intoduce an added element of randomness into species resource competition
		shuffle($this->creatures); // shuffle the habitat's creatures array so that no one is given preferential access to resources.
		
		foreach ($this->creatures as $key => $creature) // iterate through each individual species currently living in the habitat.
		{
			// let each individual live it's life in the current habitat.
			$creature->live($this);

			// Note: if a mother dies in the same month that it gives bith, it is assumed that the child was born before the death of the mother.

			if($creature->dead > 0) // if the creature died during the simulation...
			{
				$this->current_creature_count--;	// ... reduce the current species count by 1
				$this->deaths[$creature->dead]++;	// ... record the cause death
				$this->total_deaths++;				// ... increment the total_deaths count by 1

				if($creature->gender == MALE)		// ... if the creature that died was a male
		        {
		          $this->current_males--;		// ... reduce the habitat's current_male count.
		        }

				unset($this->creatures[$key]);		// ... remove the species from the habitat's species array.
			}
		}

		// update the habitat's max species count if the current species count exceeds the previous max count.
		if($this->current_creature_count > $this->max_creature_count)
		{
			$this->max_creature_count = $this->current_creature_count;
		}

		$this->population += $this->current_creature_count;

		log::record('END OF MONTH STATS >> Current Species Count: ' .  $this->current_creature_count . ' - Current Males: ' . $this->current_males . ' - Max Count: ' . $this->max_creature_count . ' - Total Births: ' . $this->total_births . ' - Total Deaths: ' . $this->total_deaths, 3);

		// It's the end of the month, and time for the habitat to refresh its resources.
		$this->refresh_resources();
	}
}