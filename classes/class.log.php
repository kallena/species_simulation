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

class log
{
  //public $report = null;

  /*
  |--------------------------------------------------------------------------
  | record()
  |--------------------------------------------------------------------------
  |
  |  Update the log
  |
  */

  static function record($report, $tabs = 0, $line_break = "\n", $key = 'log') 
  { 
      $report .= $line_break;

      if(isset($_SESSION[$key]))
      {
        $_SESSION[$key] .= self::add_tabs($report, $tabs);
        
        if(strlen($_SESSION[$key]) > 8388608)
        {
          self::write($key);
        }
      }
      else
      {
        $_SESSION[$key] = self::add_tabs($report, $tabs);
      }
  }

  static function write($key = 'log')
  {
    if(isset($_SESSION[$key]))
    {
      file_put_contents($key . '.txt', $_SESSION[$key], FILE_APPEND);
      unset($_SESSION[$key]);
    }
  }

  static function add_tabs($input, $count)
  {   
      $i = 1;
      
      while ($i <= $count)
      {
        $input = "\t" . $input;
        $i++;
      }
      
      return $input;
  }

  static function clear($key = 'log')
  {
    file_put_contents($key . '.txt', null);
    
    if(isset($_SESSION[$key]))
    {
      unset($_SESSION[$key]);
    }
  }
}