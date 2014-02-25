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

class world
{
	public $years;
	public $iterations;

	public $current_year 		= 1;
	public $current_month 		= 1;
	private $habitats		 	= array();
	private $species			= array();
	private $current_species 	= null;

	private $population			= array();
	
	/*
	|--------------------------------------------------------------------------
	| __construct()
	|--------------------------------------------------------------------------
	|
	| Called when the world is created, this method accepts the location
	| of the config file, then reads and parses the file to generate the world.
	|
	*/

	function __construct($config_location)
	{
		$config = Spyc::YAMLLoad($config_location);

		$this->years		= $config['years'];
		$this->iterations	= $config['iterations'];

		foreach ($config['species'] as $species_config)
		{
			$this->add_species($species_config);
		}

		foreach ($config['habitats'] as $habitat_config)
		{
			$this->add_habitat($habitat_config);
		}
	}

	/*
	|--------------------------------------------------------------------------
	| add_habitat()
	|--------------------------------------------------------------------------
	|
	| This method accepts an array of habitats, parses them and 
	| then loads of them as objects into the world's habitat's array.
	|
	*/

	function add_habitat($config)
	{	
		
		$new_habitat = new habitat();

		foreach($config as $key => $value)
		{
			$new_habitat->$key = $value;
		}

		$this->habitats[] = $new_habitat;

		unset($new_habitat);
	}

	/*
	|--------------------------------------------------------------------------
	| add_species()
	|--------------------------------------------------------------------------
	|
	| This method accepts an array of species, parses them and 
	| then loads of them as objects into the world's species's array.
	|
	*/

	function add_species($config)
	{	
		$new_species = new species();

		$new_species->name = $config['name'];

		foreach($config['attributes'] as $key => $value)
		{
			$new_species->$key = $value;
		}

		$this->species[] = $new_species;

		unset($new_species);
	}

	/*
	|--------------------------------------------------------------------------
	| get_season()
	|--------------------------------------------------------------------------
	|
	| This method determines the world's season based on the current month
	|
	*/

	function get_season()
	{	
		switch($this->current_month)
		{
			case 12:
			case 1:
			case 2:
				return 'winter';
			case 3:
			case 4:
			case 5:
				return 'spring';
			case 6:
			case 7:
			case 8:
				return 'summer';
			case 9:
			case 10:
			case 11:
				return 'fall';
		}
	}

	/*
	|--------------------------------------------------------------------------
	| seed()
	|--------------------------------------------------------------------------
	|
	| This method iterates through the world's habitats
	| and seeds them with their initial species
	|
	*/

	function seed()
	{
		foreach($this->habitats as $habitat) // Loop through each habitat in the world and seed it.
		{
			$habitat->seed($this->current_species);
			$habitat->refresh_resources();
		}
	}

	/*
	|--------------------------------------------------------------------------
	| simulate_month()
	|--------------------------------------------------------------------------
	|
	| This method handles the passage of one month in the world
	|
	*/

	function simulate_month()
	{

		log::record('Year: ' . $this->current_year . ' Month: ' . $this->current_month . ' - ' . $this->get_season(), 1);

		foreach($this->habitats as $habitat) // Loop through each habitat in the world.
		{
			// Establish the habitat's temperature for the current month
			$temperature = $habitat->set_tempature($this->get_season());
			
			log::record('Habitat: ' . $habitat->name . ' - Temperature: ' . $temperature, 2);
			
			// Simulate the habitat for the current month
			$habitat->simulate();
		}
	}

	/*
	|--------------------------------------------------------------------------
	| simulate_year()
	|--------------------------------------------------------------------------
	|
	| This method handles the passage of one year in the world
	|
	*/

	function simulate_year()
	{
		$this->current_month = 1; // Set the current month counter
		
		while ($this->current_month <= 12)
		{
			$this->simulate_month();

			$this->current_month++; // increment month counter
		}
	}

	/*
	|--------------------------------------------------------------------------
	| simulate_iteration()
	|--------------------------------------------------------------------------
	|
	| This method handles a the passage of a single iteration (collection of years)
	| in the world. The lenght of an iteration is defined by $this->years.
	|
	*/

	function simulate_iteration()
	{		
		
		$this->current_year = 1; // Set the current year counter

		$this->seed();

		while ($this->current_year <= $this->years)
		{
			$this->simulate_year();
			
			$this->current_year++; // increment year counter
		}
	}

	/*
	|--------------------------------------------------------------------------
	| simulate()
	|--------------------------------------------------------------------------
	|
	| This method is called to simulate the entire world once it has been configured.
	|
	*/

	function simulate()
	{	
		log::clear();
		log::clear('output');

		foreach($this->species as $species)
		{
			$this->current_species = $species;

			$i = 1;
			while ($i <= $this->iterations)
			{
				log::record($species->name . ' - iteration: ' . $i);
				$this->simulate_iteration();
				$i++; // increment iteration counter
			}

			$this->generate_report($species);
		}

		echo('The simulation is complete. It ran for ' . $this->iterations . ' iteration(s) at ' . $this->years . ' year(s) per iteration.<br />Please <a href="output.txt?random='. time() . '" target="_blank">click here</a> to view the output file.<br />Please <a href="log.txt?random='. time() . '" target="_blank">click here</a> to view a complete log file.<br />');
		
		// Summarize and then write the output.txt file
		log::write('output');

		// Write the log.txt file.
		log::write();
	}

	/*
	|--------------------------------------------------------------------------
	| generate_report()
	|--------------------------------------------------------------------------
	|
	| 
	|
	*/

	function generate_report($species)
	{
		log::record($species->name . ':', 0, "\n", 'output');

		$cause = array(HEAT => 'hot_weather', COLD => 'cold_weather', THIRST => 'thirst', STARVATION => 'starvation', OLD_AGE => 'age');

		foreach($this->habitats as $habitat) // Loop through each habitat in the world.
		{
			$total_births		= $habitat->total_births;
			$total_deaths		= array_sum($habitat->deaths);
			$total_population	= $habitat->population;
			$average_population = round($total_population / ($this->iterations * $this->years * 12));

			$mortality_rate 	= round($total_deaths / $total_births * 100, 2);

			log::record($habitat->name . ':', 1, "\n", 'output');
			log::record('Average Population: ' . $average_population, 2, "\n", 'output');
			log::record('Max Population: ' . $habitat->max_creature_count, 2, "\n", 'output');
			log::record('Mortality Rate: ' . $mortality_rate . '%', 2, "\n", 'output');
			log::record('Cause of Death:', 2, "\n", 'output');

			foreach ($habitat->deaths as $key => $value)
			{
				$percent = round($value / $total_deaths * 100, 4);
				log::record($percent . '% ' . $cause[$key], 3, "\n", 'output');
			}

			$habitat->reset();
		}
	}
}