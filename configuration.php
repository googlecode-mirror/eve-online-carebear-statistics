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

date_default_timezone_set('UTC');

require_once('adodb5/adodb.inc.php');

class Configuration
{
  /**
   * Valid logins for auth
   * @var array
   */
  public $Auth = array(
      'username' => 'password'
  );
  /**
   * Database connection setting
   * @var string
   */
  public $Dsn = 'mysql://username:password@host/database';

  /**
   * Translation selector
   * @var string
   */
  public $Language = 'en';

  /**
   * userID
   * @var string
   */
  public $UserID = '123';

  /**
   * apiKey
   * @var string
   */
  public $ApiKey = 'asd';

  /**
   * characterID
   * @var string
   */
  public $CharacterID = '123';

  /**
   * Page title
   * @var string
   */
  public $PageTitle = 'Carebearing Statistics';

  /**
   * Graph days to show by hour
   * @var int
   */
  public $ByHour = 3;

  /**
   * API URL
   * @var string
   */
  public $ApiUrl = 'https://api.eve-online.com/corp/WalletJournal.xml.aspx';

  /**
   * How many rows to pull from API at a time (if there are more new rows than this, it pulls multiple times)
   * @var int
   */
  public $RowCount = 100;

  /**
   * Use cURL instead of get_file_contents(), set false to disable
   * @var boolean
   */
  public $UseCURL = true;

  /**
   * Journal table name
   * @var string
   */
  public $JournalTable = 'journal';

  /**
   * Rats table name
   * @var string
   */
  public $RatsTable = 'rats';

  /**
   * Pring out debug log into cron.log
   * @var boolean
   */
  public $DebugCron = false;
}