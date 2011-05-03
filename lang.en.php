<?php

/**
 * Copyright (c) 2011 Mikk Kiilaspää (mikk36 at mikk36 eu)

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

defined('IN_APP') || die("Hands off!");

class Lang {
  private $data = array(
      'dataTables' => array(
          'sProcessing'   => 'Processing...',
          'sLengthMenu'   => 'Show _MENU_ entries',
          'sZeroRecords'  => 'No matching records found',
          'sInfo'         => 'Showing _START_ to _END_ of _TOTAL_ entries',
          'sInfoEmpty'    => 'Showing 0 to 0 of 0 entries',
          'sInfoFiltered' => '(filtered from _MAX_ total entries)',
          'sInfoPostFix'  => '',
          'sSearch'       => 'Search:',
          'oPaginate'     => array(
              'sFirst'      => 'First',
              'sPrevious'   => 'Previous',
              'sNext'       => 'Next',
              'sLast'       => 'Last',
          )
      ),
      'dateFormat'        => 'm/d/Y H:i:s',
      'username'          => 'Username',
      'password'          => 'Password',
      'log_in'            => 'Log in',
      'character'         => 'Character',
      'amount'            => 'Amount',
      'sum'               => 'Sum',
      'total_sum'         => 'Total Value',
      'region'            => 'Region',
      'security'          => 'Security',
      'system'            => 'System',
      'agent'             => 'Agent',
      'level'             => 'L',
      'quality'           => 'Q',
      'corporation'       => 'Corp',
      'faction'           => 'Faction',
      'division'          => 'Division',
      'rat'               => 'Rat',
      'rat_type'          => 'Type',
      'bounty'            => 'Bounty',
      'date'              => 'Date',
      'thousandSeparator' => ',',
      'decimalSeparator'  => '.',
  );

  public function __get($name)
  {
    if(array_key_exists($name, $this->data))
    {
      return $this->data[$name];
    }

    $trace = debug_backtrace();
    trigger_error('Undefined property via __get(): '.$name.' in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_NOTICE);
    return null;
  }
}