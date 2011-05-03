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

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-type: text/plain');

define('IN_APP', true);

require_once('configuration.php');

function refIDSort($a, $b)
{
  return (floatval($a['refID']) > floatval($b['refID'])) ? -1 : 1;
}

class Cron
{
  /**
   * DB Connection
   * @var AdoConnection
   */
  private $db;

  /**
   * Configuration class
   * @var Configuration
   */
  public $conf;

  public $output;

  public $start;

  private function dbConnect() {
    $this->db = NewADOConnection($this->conf->Dsn);
    if (!$this->db) {
      $this->conf->DebugCron && file_put_contents('cron.log', " Connection failed\n", FILE_APPEND);
      die("Connection failed");
    }
    $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
  }

  private function getCorpTax()
  {
    $url = "https://api.eveonline.com/corp/CorporationSheet.xml.aspx?userID={$this->conf->UserID}&apiKey={$this->conf->ApiKey}&characterID={$this->conf->CharacterID}";

    $contents = false;
    if($this->conf->UseCURL)
      $contents = Cron::get($url);
    else
      $contents = file_get_contents ($url);

    if($contents === false)
    {
      $this->conf->DebugCron && file_put_contents('cron.log', " Could not fetch data\n", FILE_APPEND);
      die('Could not fetch data');
    }

    //file_put_contents('corporationsheet.xml', $contents);

    $xml = new SimpleXMLElement($contents);
    if(isset($xml->error))
    {
      $this->conf->DebugCron && file_put_contents('cron.log', " Error {$xml->error['code']}: {$xml->error}\n", FILE_APPEND);
      die("Error {$xml->error['code']}: {$xml->error}");
    }
    return $xml->result->taxRate/100;
  }

  private function getApiResponse($fromID = null)
  {
    $url = "{$this->conf->ApiUrl}?userID={$this->conf->UserID}&apiKey={$this->conf->ApiKey}&characterID={$this->conf->CharacterID}&rowCount={$this->conf->RowCount}";
    if($fromID !== null)
      $url .= "&fromID=$fromID";

    $contents = false;
    if($this->conf->UseCURL)
      $contents = Cron::get($url);
    else
      $contents = file_get_contents ($url);
    
    if($contents === false)
    {
      $this->conf->DebugCron && file_put_contents('cron.log', " Could not fetch data\n", FILE_APPEND);
      die('Could not fetch data');
    }

    //$this->conf->DebugCron && file_put_contents('debug'.$fromID.'.xml', $contents);
    $xml = new SimpleXMLElement($contents);

    if(isset($xml->error))
    {
      $this->conf->DebugCron && file_put_contents('cron.log', " Error {$xml->error['code']}: {$xml->error}\n", FILE_APPEND);
      die("Error {$xml->error['code']}: {$xml->error}");
    }

    $journalItems = array();
    foreach($xml->result->rowset->row as $row)
    {
      $refTypeIDList = array(33, 34, 85);
      if(!in_array(intval($row['refTypeID']), $refTypeIDList))
        continue;

      $data = array();
      foreach($row->attributes() as $key => $element)
      {
        $data[$key] = (string)$element;
      }
      $journalItems[] = $data;
    }
    usort($journalItems, "refIDSort");
    return $journalItems;
  }

  private function checkCronTimer()
  {
    $rs = $this->db->Execute("SELECT timestamp from croncheck WHERE id = 0");
    if($rs->RecordCount() == 0)
    {
      $this->db->Execute("INSERT INTO `croncheck` (`id`, `timestamp`) VALUES ('0', '".time()."')");
      return true;
    }
    $row = $rs->FetchRow();
    if(intval($row['timestamp']) < time() - 27*60)
    {
      $this->db->Execute("UPDATE `croncheck` SET `timestamp`='".time()."' WHERE (`id`='0')");
      return true;
    }

    return false;
  }

  public function __construct()
  {
    set_time_limit(30);
    $this->start = time();
    $this->conf = new Configuration();
    $this->conf->DebugCron && file_put_contents('cron.log', strftime('%Y-%m-%d %H:%M:%S', $this->start), FILE_APPEND);
    $this->dbConnect();

    if(!$this->checkCronTimer())
    {
      $this->conf->DebugCron && file_put_contents('cron.log', " Too soon\n", FILE_APPEND);
      die('Too soon');
    }

    $corpTax = $this->getCorpTax();

    $latestRefID = 0;
    $rs = $this->db->Execute("SELECT refID FROM {$this->conf->JournalTable} ORDER BY refID DESC LIMIT 1");
    if($rs->RecordCount() != 0)
    {
      $latestRefID = $rs->Fields('refID');
    }
    $xml = $this->getApiResponse();
    $currentRefID = floatval($xml[0]['refID']);
    if($currentRefID == $latestRefID)
    {
      $this->output .= "No new entries\n";
    }
    while($currentRefID > $latestRefID)
    {
      set_time_limit(30);
      //$this->output .= "Count: {$xml->result->rowset->row->count()}\n";

      foreach($xml as $row)
      {
        $currentRefID = floatval($row['refID']);
        if($currentRefID <= $latestRefID)
          break;

        $this->output .= "$currentRefID\n";

        $refTypeIDList = array(33, 34, 85);
        if(!in_array(intval($row['refTypeID']), $refTypeIDList))
          continue;

        $data = array();
        foreach($row as $key => $element)
        {
          $data[$key] = $this->db->qstr($element);
        }

        $sql = "INSERT INTO `{$this->conf->JournalTable}`
        (
          `date`, `refID`, `refTypeID`, `ownerName1`, `ownerID1`, `ownerName2`, `ownerID2`, `argName1`, `argID1`, `amount`, `balance`, `reason`, `corpTax`
        ) VALUES (
          {$data['date']}, {$data['refID']}, {$data['refTypeID']}, {$data['ownerName1']}, {$data['ownerID1']}, {$data['ownerName2']}, {$data['ownerID2']}, {$data['argName1']}, {$data['argID1']}, {$data['amount']}, {$data['balance']}, {$data['reason']}, {$corpTax})";
        $rs = $this->db->Execute($sql);

        if(!empty($row['reason']))
        {
          $insert_id = $this->db->Insert_ID();
          $reason_s = trim($row['reason'], '.');
          $reason_s = trim($reason_s, ',');
          $reasons = explode(',', $reason_s);
          foreach($reasons as $reason)
          {
            list($rat_id, $rat_amount) = explode(':', $reason);
            $sql2 = "INSERT INTO `{$this->conf->RatsTable}` (`journal_id`, `rat`, `amount`) VALUES ({$this->db->qstr($insert_id)}, {$this->db->qstr($rat_id)}, {$this->db->qstr($rat_amount)})";
            $this->db->Execute($sql2);
          }
        }
      }

      if(count($xml) < $this->conf->RowCount)
        break;

      if($currentRefID <= $latestRefID)
        break;

      $xml = $this->getApiResponse($currentRefID);
    }
  }

  static function get($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $data = curl_exec($ch);
    curl_close($ch);

    if($data === false)
    {
      //file_put_contents('cron.log', " Could not fetch data\n", FILE_APPEND);
      die('Could not fetch data');
    }

    return $data;
  }
}

$cron = new Cron();

$cron->conf->DebugCron && file_put_contents('cron.log', "\n".$cron->output, FILE_APPEND);

unset($cron);
