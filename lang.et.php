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
          'sProcessing'   => 'Palun oodake, koostan kuvamiseks nimekirja!',
          'sLengthMenu'   => 'Näita kirjeid _MENU_ kaupa',
          'sZeroRecords'  => 'Otsitavat vastet ei leitud.',
          'sInfo'         => 'Kuvatud: _TOTAL_ kirjet (_START_-_END_)',
          'sInfoEmpty'    => 'Otsinguvasteid ei leitud',
          'sInfoFiltered' => ' - filtreeritud _MAX_ kirje seast.',
          'sInfoPostFix'  => '',
          'sSearch'       => 'Otsi kõikide tulemuste seast:',
          'oPaginate'     => array(
              'sFirst'      => 'Algus',
              'sPrevious'   => 'Eelmine',
              'sNext'       => 'Järgmine',
              'sLast'       => 'Viimane',
          )
      ),
      'dateFormat'        => 'd.m.Y H:i:s',
      'username'          => 'Kasutaja',
      'password'          => 'Parool',
      'log_in'            => 'Logi sisse',
      'character'         => 'Mängija',
      'amount'            => 'Kogus',
      'sum'               => 'Summa',
      'total_sum'         => 'Koguväärtus',
      'region'            => 'Regioon',
      'security'          => 'Turvatase',
      'system'            => 'Süsteem',
      'agent'             => 'Agent',
      'level'             => 'L',
      'quality'           => 'Q',
      'corporation'       => 'Korp',
      'faction'           => 'Fraktsioon',
      'division'          => 'Osakond',
      'rat'               => 'Rott',
      'rat_type'          => 'Tüüp',
      'bounty'            => 'Pearaha',
      'date'              => 'Aeg',
      'thousandSeparator' => ' ',
      'decimalSeparator'  => ',',
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