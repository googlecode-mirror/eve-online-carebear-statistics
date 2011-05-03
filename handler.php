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

require_once('configuration.php');

class Handler
{
  /**
   * Configuration class
   * @var Configuration
   */
  private $Config;

  /**
	 * Output HTML code
	 * @var boolean
	 */
	private $outputHtml = true;

  /**
   * DB Connection
   * @var AdoConnection
   */
  private $db;

	/**
	 * @var string
	 */
	private $output;

  /**
   * Translation file
   * @var Lang
   */
  private $lang;

  /**
   * dataTables settings
   * @var array
   */
  private $tableOptions = array(
    'bJQueryUI'       => true,
		'sPaginationType' => 'full_numbers',
		'iDisplayLength'  => 25
  );

  public function __construct()
  {
    $this->Config = new Configuration();
    require_once('lang.'.$this->Config->Language.'.php');
    $this->lang = new Lang();
    $this->tableOptions['oLanguage'] = $this->lang->dataTables;
    
    $this->outputHtml = false;
    if(isset($_REQUEST['auth']))
    {
      $res = $this->checkAuth();
      if($res)
        $this->output = json_encode(array('auth' => 'Success!'));
      else
        $this->output = json_encode(array('auth' => 'Invalid auth data!'));
      return;
    }
    elseif(isset($_REQUEST['act']))
    {
      header('Cache-Control: no-cache, must-revalidate');
      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
      header('Content-type: application/json');
      $this->db_connect();
      $this->output = $this->fetchData($_REQUEST['act']);
    }
    else
    {
      $this->outputHtml = true;
      $this->output = '<div id="page" class="ui-widget ui-widget-content ui-corner-all" style="width: 210px"><form id="loginform" method="post" action=""><table><tr><td><label for="careUsername">'.$this->lang->username.' </label></td><td><input type="text" id="careUsername" class="ui-widget-content" /></td></tr><tr><td><label for="carePassword">'.$this->lang->password.' </label></td><td><input type="password" id="carePassword" class="ui-widget-content" /></td></tr><tr><td></td><td><input type="submit" value="'.$this->lang->log_in.'" id="careSubmit"></td></tr></table></form></div>';
      return;
    }
  }

  /**
	 * Establish connection to the database
	 */
	private function db_connect() {
		$this->db = NewADOConnection($this->Config->Dsn);
		if (!$this->db) {
			die("Connection failed");
		}
		$this->db->SetFetchMode(ADODB_FETCH_ASSOC);
    $this->db->Execute("SET time_zone = '+0:00'");
	}

  /**
   * Get output
   * @return string
   */
  public function getResult()
  {
		$result = NULL;
		if($this->outputHtml === true)
    {
      header('Content-Type: text/html');
      $style = "smoothness/jquery-ui-1.8.10.custom.css";
      $inEVE = 'false';
      if(isset($_SERVER['HTTP_EVE_TRUSTED']))
      {
        $style = "ui-darkness/jquery-ui-1.8.10.custom.css";
        $inEVE = 'true';
      }
			$result = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
  <head>
    <title>{$this->Config->PageTitle}</title>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>
    <link rel=\"stylesheet\" href=\"css/dataTables.css\" type=\"text/css\" media=\"all\" />
    <link rel=\"stylesheet\" href=\"css/{$style}\" type=\"text/css\" media=\"all\" />
    <link rel=\"stylesheet\" href=\"css/style.css\" type=\"text/css\" media=\"all\" />
    <script type=\"text/javascript\" src=\"js/jquery-1.5.1.min.js\"></script>
    <script type=\"text/javascript\" src=\"js/jquery-ui-1.8.10.custom.min.js\"></script>
    <script type=\"text/javascript\" src=\"js/lang.{$this->Config->Language}.js\"></script>
    <script type=\"text/javascript\" src=\"js/jquery.ui.datepicker-{$this->Config->Language}.js\"></script>
    <script type=\"text/javascript\" src=\"js/jquery.dataTables.min.js\"></script>
    <script type=\"text/javascript\" src=\"js/jquery.number_format.js\"></script>
    <script type=\"text/javascript\" src=\"js/jquery.flot.js\"></script>
    <script type=\"text/javascript\" src=\"js/jquery.md5.js\"></script>
    <script type=\"text/javascript\" src=\"js/jquery.jookie.js\"></script>
    <script type=\"text/javascript\" src=\"js/jquery.history.js\"></script>
    <script type=\"text/javascript\" src=\"js/date.format.js\"></script>
    <script type=\"text/javascript\">
      var CBconfig =
      {
        language: '{$this->Config->Language}',
        inEVE: {$inEVE},
        byHour: {$this->Config->ByHour}
      };
    </script>
    <!--[if lte IE 8]><script language=\"javascript\" type=\"text/javascript\" src=\"js/excanvas.min.js\"></script><![endif]-->
    <script type=\"text/javascript\" src=\"js/code.js\"></script>
  </head>
  <body>
    {$this->output}
  </body>
</html>";
		}
    else
    {
      header('Content-Type: application/json');
			$result = $this->output;
		}

    if (strlen($result) > 2 && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
    {
      header('Content-Encoding: gzip');
      $result = gzencode($result, 9);
    }

    header('Content-Length: ' . strlen($result));
    return $result;
	}

  private function checkAuth()
  {
    if(isset($_REQUEST['username']) && isset($_REQUEST['password']))
    {
      if(array_key_exists($_REQUEST['username'], $this->Config->Auth))
      {
        if(md5($this->Config->Auth[$_REQUEST['username']]) == $_REQUEST['password'])
          return true;
      }
    }
    header("HTTP/1.0 403 Forbidden");
    return false;
  }

  /**
   * Grab necessary data
   */
  private function fetchData($what)
  {
    if(!$this->checkAuth())
      return json_encode(array('error' => 'Invalid auth data!'));
    $result = '';
    switch ($what) {
      case 'ByCharacter':
        $result = $this->getByCharacter();
        break;
      case 'ByDay':
        $result = $this->getByDay();
        break;
      case 'BySystem':
        $result = $this->getBySystem();
        break;
      case 'ByRegion':
        $result = $this->getByRegion();
        break;
      case 'ByHisecCharacter':
        $result = $this->getByHisecCharacter();
        break;
      case 'ByAgent':
        $result = $this->getByAgent();
        break;
      case 'ByRat':
        $result = $this->getByRat();
        break;
      case 'ByFancyRat':
        $result = $this->getByFancyRat();
        break;
      default:
        $result = $this->getNotImplemented();
        break;
    }
    $result['act'] = $what;
    return json_encode($result);
  }

  private function getNotImplemented()
  {
    header("HTTP/1.0 501 Not Implemented");
    return array('error' => 'Not implemented!');
  }

  private function getByCharacter()
  {
    $response = array();

    $response['aaData'] = array();
    $sql = '';

    $agent = false;
    if(isset($_REQUEST['agent']))
      $agent = true;

    $rat = false;
    if(isset($_REQUEST['rat']))
      $rat = true;

    if(isset($_REQUEST['system']) || isset($_REQUEST['region']))
    {
      $sql = "SELECT
j3.ownerName2,
j3.ownerID2,
j3.corpTax,
Sum(j3.amount) AS amount
FROM
(
	SELECT
	j1.ownerName2 AS ownerName2,
	j1.ownerID2,
	j1.corpTax,
	Sum(j1.amount) AS amount
	FROM
	journal AS j1
  ".(isset($_REQUEST['region'])?"INNER JOIN mapsolarsystems AS systems1 ON j1.argID1 = systems1.solarSystemID":"")."
	WHERE
	j1.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j1.date < {$this->db->qstr($_REQUEST['to'])} AND
	j1.refTypeID = 85
  ".(isset($_REQUEST['system'])?"AND j1.argID1 = {$this->db->qstr($_REQUEST['system'])}":"")."
  ".(isset($_REQUEST['region'])?"AND systems1.regionID = {$this->db->qstr($_REQUEST['region'])}":"")."
	GROUP BY
	j1.ownerID2
	UNION	ALL
	SELECT
	j2.ownerName2 AS ownerName2,
	j2.ownerID2,
	j2.corpTax,
	Sum(j2.amount) AS amount
	FROM
	journal AS j2
	INNER JOIN agtagents AS agents ON j2.argID1 = agents.agentID
	INNER JOIN mapdenormalize AS denormalize ON agents.locationID = denormalize.itemID
	WHERE
	j2.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j2.date < {$this->db->qstr($_REQUEST['to'])} AND
	j2.refTypeID IN (33, 34)
  ".(isset($_REQUEST['system'])?"AND 	denormalize.solarSystemID = {$this->db->qstr($_REQUEST['system'])}":"")."
  ".(isset($_REQUEST['region'])?"AND 	denormalize.regionID = {$this->db->qstr($_REQUEST['region'])}":"")."
	GROUP BY
	j2.ownerID2
) AS j3
GROUP BY
j3.ownerID2
ORDER BY
amount DESC";
    }
    else
    {
      $sql = "SELECT
j.ownerName2,
j.ownerID2,
j.corpTax,
".($rat?"SUM(rats.amount)":"SUM(j.amount)")." AS amount
FROM
journal AS j
".($rat?"INNER JOIN rats ON j.id = rats.journal_id":"")."
WHERE
j.date >= {$this->db->qstr($_REQUEST['from'])} AND
j.date < {$this->db->qstr($_REQUEST['to'])}
".($agent?" AND j.refTypeID IN (33, 34) AND j.ownerID1 = {$this->db->qstr($_REQUEST['agent'])}":"")."
".($rat?" AND rats.rat = {$this->db->qstr($_REQUEST['rat'])}":"")."
GROUP BY
j.ownerID2
ORDER BY
amount DESC";
    }
    
    $rs = $this->db->Execute($sql);
    if(!$rs)
      die($this->db->ErrorMsg());
    $num = 0;
    $response['totalSum'] = 0;
    while($row = $rs->FetchRow())
    {
      $num++;
      if($rat)
        $response['aaData'][] = array(
          $num,
          htmlentities($row['ownerName2'], ENT_QUOTES, 'UTF-8'),
          $row['ownerID2'],
          number_format($row['amount'], 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator),
          $row['amount']
        );
      else
        $response['aaData'][] = array(
          $num,
          htmlentities($row['ownerName2'], ENT_QUOTES, 'UTF-8'),
          $row['ownerID2'],
          number_format(($row['amount']/$row['corpTax']), 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator).' ISK',
          $row['amount']/$row['corpTax']
        );
      $response['totalSum'] += $row['amount']/$row['corpTax'];
    }

    $response['aaSorting'] = array();
    $response['aaSorting'][] = array(3, 'desc');

    $response['aoColumns'] = array();
    $response['aoColumns'][] = array('sTitle' => '#');
    $response['aoColumns'][] = array('sTitle' => $this->lang->character);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => ($rat?$this->lang->amount:$this->lang->sum), 'sClass' => 'right nonbreaking', 'iDataSort' => 4);
    $response['aoColumns'][] = array('bVisible' => false);
    
    return $response + $this->tableOptions;
  }

  private function getByHisecCharacter()
  {
    $response = array();

    $response['aaData'] = array();

    // {$this->db->qstr($_REQUEST['from'])}
    // {$this->db->qstr($_REQUEST['to'])}
    $sql = "SELECT
j3.characterName,
j3.characterID,
j3.corpTax,
Sum(j3.amount) AS amount
FROM
(
	SELECT
	j1.ownerName2 AS characterName,
	j1.ownerID2 AS characterID,
	j1.corpTax,
	Sum(j1.amount) AS amount
	FROM
	journal AS j1
	INNER JOIN mapsolarsystems AS systems1 ON j1.argID1 = systems1.solarSystemID
	WHERE
	j1.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j1.date < {$this->db->qstr($_REQUEST['to'])} AND
	j1.refTypeID = 85 AND
	ROUND(systems1.`security`, 1) >= 0.5
	GROUP BY
	characterID
	UNION	ALL
	SELECT
	j2.ownerName2 AS characterName,
	j2.ownerID2 AS characterID,
	j2.corpTax,
	Sum(j2.amount) AS amount
	FROM
	journal AS j2
	INNER JOIN agtagents AS agents ON j2.argID1 = agents.agentID
	INNER JOIN mapdenormalize AS denormalize ON agents.locationID = denormalize.itemID
	INNER JOIN mapsolarsystems AS systems2 ON denormalize.solarSystemID = systems2.solarSystemID
	WHERE
	j2.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j2.date < {$this->db->qstr($_REQUEST['to'])} AND
	j2.refTypeID IN (33, 34) AND
	ROUND(systems2.`security`, 1) >= 0.5
	GROUP BY
	characterID
) AS j3
GROUP BY
j3.characterID
ORDER BY
amount DESC";
    $rs = $this->db->Execute($sql);
    $num = 0;
    $response['totalSum'] = 0;
    while($row = $rs->FetchRow())
    {
      $num++;
      $response['aaData'][] = array(
        $num,
        htmlentities($row['characterName'], ENT_QUOTES, 'UTF-8'),
        $row['characterID'],
        number_format(($row['amount']/$row['corpTax']), 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator).' ISK',
        $row['amount']/$row['corpTax']
      );
      $response['totalSum'] += $row['amount']/$row['corpTax'];
    }

    $response['aaSorting'] = array();
    $response['aaSorting'][] = array(3, 'desc');

    $response['aoColumns'] = array();
    $response['aoColumns'][] = array('sTitle' => '#');
    $response['aoColumns'][] = array('sTitle' => $this->lang->character);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->sum, 'sClass' => 'right nonbreaking', 'iDataSort' => 4);
    $response['aoColumns'][] = array('bVisible' => false);
    
    return $response + $this->tableOptions;
  }

  private function getBySystem()
  {
    $response = array();

    $response['aaData'] = array();

    $character = false;
    if(isset($_REQUEST['character']))
      $character = true;

    $rat = false;
    if(isset($_REQUEST['rat']))
      $rat = true;

    if($rat)
    {
      $sql = "SELECT
systems.solarSystemName AS systemName,
systems.solarSystemID AS systemID,
systems.security AS security,
regions.regionName AS regionName,
systems.regionID AS regionID,
j.corpTax,
SUM(rats.amount) AS amount
FROM
journal AS j
INNER JOIN mapsolarsystems AS systems ON j.argID1 = systems.solarSystemID
INNER JOIN mapregions AS regions ON systems.regionID = regions.regionID
INNER JOIN rats ON j.id = rats.journal_id
WHERE
j.date >= {$this->db->qstr($_REQUEST['from'])} AND
j.date < {$this->db->qstr($_REQUEST['to'])} AND
j.refTypeID = 85 AND
rats.rat = {$this->db->qstr($_REQUEST['rat'])}
GROUP BY
systemID
ORDER BY
amount DESC";
    }
    else
      $sql = "SELECT
j3.systemName,
j3.systemID,
j3.security,
j3.regionName,
j3.regionID,
j3.corpTax,
Sum(j3.amount) AS amount
FROM
(
	SELECT
	systems1.solarSystemName AS systemName,
	systems1.solarSystemID AS systemID,
	systems1.security AS security,
	regions1.regionName AS regionName,
  systems1.regionID AS regionID,
	j1.corpTax,
	Sum(j1.amount) AS amount
	FROM
	journal AS j1
	INNER JOIN mapsolarsystems AS systems1 ON j1.argID1 = systems1.solarSystemID
	INNER JOIN mapregions AS regions1 ON systems1.regionID = regions1.regionID
	WHERE
	j1.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j1.date < {$this->db->qstr($_REQUEST['to'])} AND
	j1.refTypeID = 85
  ".($character?" AND j1.ownerID2 = {$this->db->qstr($_REQUEST['character'])}":"")."
  ".(isset($_REQUEST['region'])?"AND systems1.regionID = {$this->db->qstr($_REQUEST['region'])}":"")."
	GROUP BY
	j1.argID1
	UNION	ALL
	SELECT
	systems2.solarSystemName AS systemName,
	systems2.solarSystemID AS systemID,
	systems2.security AS security,
	regions2.regionName AS regionName,
  systems2.regionID AS regionID,
	j2.corpTax,
	Sum(j2.amount) AS amount
	FROM
	journal AS j2
	INNER JOIN agtagents AS agents ON j2.argID1 = agents.agentID
	INNER JOIN mapdenormalize AS denormalize ON agents.locationID = denormalize.itemID
	INNER JOIN mapsolarsystems AS systems2 ON denormalize.solarSystemID = systems2.solarSystemID
	INNER JOIN mapregions AS regions2 ON systems2.regionID = regions2.regionID
	WHERE
	j2.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j2.date < {$this->db->qstr($_REQUEST['to'])} AND
	j2.refTypeID IN (33, 34)
  ".($character?" AND j2.ownerID2 = {$this->db->qstr($_REQUEST['character'])}":"")."
  ".(isset($_REQUEST['region'])?"AND systems2.regionID = {$this->db->qstr($_REQUEST['region'])}":"")."
	GROUP BY
	systems2.solarSystemID
) AS j3
GROUP BY
j3.systemID
ORDER BY
amount DESC";

    $rs = $this->db->Execute($sql);
    $num = 0;
    $response['totalSum'] = 0;
    while($row = $rs->FetchRow())
    {
      $num++;
      $securityState = 'lowsec';
      if(number_format(round($row['security'], 1) >= 0.5)) $securityState = 'highsec';
      elseif(number_format(round($row['security'], 1) < 0.1)) $securityState = 'nullsec';
      if($rat)
        $response['aaData'][] = array(
          $num,
          htmlentities($row['systemName'], ENT_QUOTES, 'UTF-8'),
          $row['systemID'],
          number_format(round($row['security'], 1), 1),
          htmlentities($row['regionName'], ENT_QUOTES, 'UTF-8'),
          $row['regionID'],
          number_format($row['amount'], 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator),
          $row['amount'],
          $securityState
        );
      else
        $response['aaData'][] = array(
          $num,
          htmlentities($row['systemName'], ENT_QUOTES, 'UTF-8'),
          $row['systemID'],
          number_format(round($row['security'], 1), 1),
          htmlentities($row['regionName'], ENT_QUOTES, 'UTF-8'),
          $row['regionID'],
          number_format(($row['amount']/$row['corpTax']), 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator).' ISK',
          $row['amount']/$row['corpTax'],
          $securityState
        );
      $response['totalSum'] += $row['amount']/$row['corpTax'];
    }

    $response['aaSorting'] = array();
    $response['aaSorting'][] = array(6, 'desc');

    $response['aoColumns'] = array();
    $response['aoColumns'][] = array('sTitle' => '#');
    $response['aoColumns'][] = array('sTitle' => $this->lang->system);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->security, 'sClass' => 'right');
    $regionColumn = array('sTitle' => $this->lang->region);
    if(isset($_REQUEST['region'])) $regionColumn['bVisible'] = false;
    $response['aoColumns'][] = $regionColumn;
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => ($rat?$this->lang->amount:$this->lang->sum), 'sClass' => 'right nonbreaking', 'iDataSort' => 7);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('bVisible' => false);

    return $response + $this->tableOptions;
  }

  private function getByRegion()
  {
    $response = array();

    $response['aaData'] = array();

    // {$this->db->qstr($_REQUEST['from'])}
    // {$this->db->qstr($_REQUEST['to'])}
    $sql = "SELECT
j3.regionName,
j3.regionID,
j3.corpTax,
Sum(j3.amount) AS amount
FROM
(
	SELECT
	systems1.regionID AS regionID,
	regions1.regionName AS regionName,
	journal1.corpTax,
	Sum(journal1.amount) AS amount
	FROM
	journal AS journal1
	INNER JOIN mapsolarsystems AS systems1 ON journal1.argID1 = systems1.solarSystemID
	INNER JOIN mapregions AS regions1 ON systems1.regionID = regions1.regionID
	WHERE
	journal1.date >= {$this->db->qstr($_REQUEST['from'])} AND
	journal1.date < {$this->db->qstr($_REQUEST['to'])} AND
	journal1.refTypeID = 85
	GROUP BY
	systems1.regionID
	UNION	ALL
	SELECT
	systems2.regionID AS regionID,
	regions2.regionName AS regionName,
	journal2.corpTax,
	Sum(journal2.amount) AS amount
	FROM
	journal AS journal2
	INNER JOIN agtagents AS agents2 ON journal2.argID1 = agents2.agentID
	INNER JOIN mapdenormalize AS denormalize2 ON agents2.locationID = denormalize2.itemID
	INNER JOIN mapsolarsystems AS systems2 ON denormalize2.solarSystemID = systems2.solarSystemID
	INNER JOIN mapregions AS regions2 ON systems2.regionID = regions2.regionID
	WHERE
	journal2.date >= {$this->db->qstr($_REQUEST['from'])} AND
	journal2.date < {$this->db->qstr($_REQUEST['to'])} AND
	journal2.refTypeID IN (33, 34)
	GROUP BY
	systems2.regionID
) AS j3
GROUP BY
j3.regionID
ORDER BY
amount DESC";

    $rs = $this->db->Execute($sql);
    $num = 0;
    $response['totalSum'] = 0;
    while($row = $rs->FetchRow())
    {
      $num++;
      $response['aaData'][] = array(
        $num,
        htmlentities($row['regionName'], ENT_QUOTES, 'UTF-8'),
        $row['regionID'],
        number_format(($row['amount']/$row['corpTax']), 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator).' ISK',
        $row['amount']/$row['corpTax']
      );
      $response['totalSum'] += $row['amount']/$row['corpTax'];
    }

    $response['aaSorting'] = array();
    $response['aaSorting'][] = array(3, 'desc');

    $response['aoColumns'] = array();
    $response['aoColumns'][] = array('sTitle' => '#');
    $response['aoColumns'][] = array('sTitle' => $this->lang->region);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->sum, 'sClass' => 'right nonbreaking', 'iDataSort' => 4);
    $response['aoColumns'][] = array('bVisible' => false);

    return $response + $this->tableOptions;
  }

  private function getByAgent()
  {
    $response = array();

    $response['aaData'] = array();

    // {$this->db->qstr($_REQUEST['from'])}
    // {$this->db->qstr($_REQUEST['to'])}
    $systemJoin = '';
    $systemWhere = '';
    if(isset($_REQUEST['system']))
    {
      $systemJoin = "INNER JOIN mapdenormalize AS denormalize ON agents.locationID = denormalize.itemID";
      $systemWhere = "AND denormalize.solarSystemID = {$this->db->qstr($_REQUEST['system'])}";
    }
    $regionJoin = '';
    $regionWhere = '';
    if(isset($_REQUEST['region']))
    {
      $regionJoin = "INNER JOIN mapdenormalize AS denormalize ON agents.locationID = denormalize.itemID";
      $regionWhere = "AND denormalize.regionID = {$this->db->qstr($_REQUEST['region'])}";
    }

    $character = '';
    if(isset($_REQUEST['character']))
      $character = " AND j.ownerID2 = {$this->db->qstr($_REQUEST['character'])}";
    
    $sql = "SELECT
t.agentName,
t.agentID,
t.`level`,
t.quality,
t.corpName,
t.corpID,
t.factionName,
t.factionID,
t.divisionName,
t.divisionID,
t.corpTax,
SUM(t.missionCount) AS missionCount,
SUM(t.amount) AS amount
FROM
(
	SELECT
	j.argName1 AS agentName,
	j.argID1 AS agentID,
	agents.`level`,
	agents.quality,
	corpNames.itemName AS corpName,
	agents.corporationID AS corpID,
	factionNames.itemName AS factionName,
	corporations.factionID AS factionID,
	divisions.divisionName,
	agents.divisionID AS divisionID,
	j.corpTax,
	1 AS missionCount,
	SUM(j.amount) AS amount
	FROM journal AS j
	INNER JOIN agtagents AS agents ON j.argID1 = agents.agentID
	INNER JOIN crpnpccorporations AS corporations ON agents.corporationID = corporations.corporationID
	INNER JOIN evenames AS corpNames ON agents.corporationID = corpNames.itemID
	INNER JOIN evenames AS factionNames ON corporations.factionID = factionNames.itemID
	INNER JOIN crpnpcdivisions AS divisions ON agents.divisionID = divisions.divisionID
  {$systemJoin}
  {$regionJoin}
  WHERE
	j.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j.date < {$this->db->qstr($_REQUEST['to'])} AND
	j.refTypeID IN (33, 34)
  {$character}
  {$systemWhere}
  {$regionWhere}
  GROUP BY
	agentID,
	j.date
) AS t
GROUP BY
agentID
ORDER BY
amount DESC";

    $rs = $this->db->Execute($sql);
    $num = 0;
    $response['totalSum'] = 0;
    while($row = $rs->FetchRow())
    {
      $num++;
      $response['aaData'][] = array(
        $num,
        htmlentities($row['agentName'], ENT_QUOTES, 'UTF-8'),
        $row['agentID'],
        $row['level'],
        $row['quality'],
        htmlentities($row['corpName'], ENT_QUOTES, 'UTF-8'),
        $row['corpID'],
        htmlentities($row['factionName'], ENT_QUOTES, 'UTF-8'),
        $row['factionID'],
        $row['divisionName'],
        $row['divisionID'],
        $row['missionCount'],
        number_format(($row['amount']/$row['corpTax']), 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator).' ISK',
        $row['amount']/$row['corpTax'],
        'L'.$row['level'],
        'Q'.$row['quality']
      );
      $response['totalSum'] += $row['amount']/$row['corpTax'];
    }

    $response['aaSorting'] = array();
    $response['aaSorting'][] = array(12, 'desc');

    $response['aoColumns'] = array();
    $response['aoColumns'][] = array('sTitle' => '#');
    $response['aoColumns'][] = array('sTitle' => $this->lang->agent);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->level);
    $response['aoColumns'][] = array('sTitle' => $this->lang->quality);
    $response['aoColumns'][] = array('sTitle' => $this->lang->corporation);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->faction);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->division);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->amount);
    $response['aoColumns'][] = array('sTitle' => $this->lang->sum, 'sClass' => 'right nonbreaking', 'iDataSort' => 13);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('bVisible' => false);

    return $response + $this->tableOptions;
  }

  private function getByRat()
  {
    $response = array();

    $response['aaData'] = array();
    
    $system = '';
    if(isset($_REQUEST['system']))
    {
      $system = "AND journal.argID1 = {$this->db->qstr($_REQUEST['system'])}";
    }
    $regionJoin = '';
    $regionWhere = '';
    if(isset($_REQUEST['region']))
    {
      $regionJoin = "INNER JOIN mapsolarsystems AS systems ON journal.argID1 = systems.solarSystemID";
      $regionWhere = "AND systems.regionID = {$this->db->qstr($_REQUEST['region'])}";
    }

    $character = '';
    if(isset($_REQUEST['character']))
      $character = " AND journal.ownerID2 = {$this->db->qstr($_REQUEST['character'])}";
    
    // {$this->db->qstr($_REQUEST['from'])}
    // {$this->db->qstr($_REQUEST['to'])}
    $sql = "SELECT
types.typeName AS ratName,
rats.rat AS ratID,
metaGroups.metaGroupName AS ratGroup,
typeAttributes.`value` AS bounty,
Sum(rats.amount) AS amount,
Sum(rats.amount) * typeAttributes.`value` AS totalSum
FROM
rats
INNER JOIN journal ON rats.journal_id = journal.id
INNER JOIN invtypes AS types ON rats.rat = types.typeID
INNER JOIN dgmtypeattributes AS typeAttributes ON rats.rat = typeAttributes.typeID AND typeAttributes.attributeID = 481
{$regionJoin}
LEFT OUTER JOIN factiongroups AS fgroups ON types.groupID = fgroups.groupID
LEFT OUTER JOIN invmetagroups AS metaGroups ON fgroups.metaGroupID = metaGroups.metaGroupID
WHERE
journal.date >= {$this->db->qstr($_REQUEST['from'])} AND
journal.date < {$this->db->qstr($_REQUEST['to'])} AND
journal.refTypeID = 85
{$system}
{$character}
{$regionWhere}
GROUP BY
rats.rat
ORDER BY
totalSum DESC";

    $rs = $this->db->Execute($sql);
    if(!$rs)
      die($this->db->ErrorMsg());
    $num = 0;
    $sum = 0;
    while($row = $rs->FetchRow())
    {
      $num++;
      $response['aaData'][] = array(
        htmlentities($row['ratName'], ENT_QUOTES, 'UTF-8'),
        $row['ratID'],
        $row['ratGroup'],
        number_format($row['bounty'], 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator).' ISK',
        $row['bounty'],
        $row['amount'],
        number_format($row['totalSum'], 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator).' ISK',
        $row['totalSum']
      );
      $sum += $row['totalSum'];
    }
    $response['totalSum'] = $sum;

    $response['aaSorting'] = array();
    $response['aaSorting'][] = array(6, 'desc');

    $response['aoColumns'] = array();
    $response['aoColumns'][] = array('sTitle' => $this->lang->rat);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->rat_type);
    $response['aoColumns'][] = array('sTitle' => $this->lang->bounty, 'sClass' => 'right nonbreaking', 'iDataSort' => 4);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->amount, 'sClass' => 'right');
    $response['aoColumns'][] = array('sTitle' => $this->lang->total_sum, 'sClass' => 'right nonbreaking', 'iDataSort' => 7);
    $response['aoColumns'][] = array('bVisible' => false);

    return $response + $this->tableOptions;
  }

  private function getByFancyRat()
  {
    $response = array();

    $response['aaData'] = array();

    // {$this->db->qstr($_REQUEST['from'])}
    // {$this->db->qstr($_REQUEST['to'])}
    $sql = "SELECT
rats.rat AS ratID,
types.typeName AS ratName,
metaGroups.metaGroupName AS ratGroup,
typeAttributes.`value` AS bounty,
journal.ownerName2 AS characterName,
journal.ownerID2 AS characterID,
journal.argName1 AS systemName,
journal.argID1 AS systemID,
regions.regionName,
regions.regionID,
UNIX_TIMESTAMP(journal.date) AS date
FROM
rats
INNER JOIN journal ON rats.journal_id = journal.id
INNER JOIN invtypes AS types ON rats.rat = types.typeID
INNER JOIN dgmtypeattributes AS typeAttributes ON rats.rat = typeAttributes.typeID AND typeAttributes.attributeID = 481
INNER JOIN factiongroups AS fgroups ON types.groupID = fgroups.groupID
INNER JOIN invmetagroups AS metaGroups ON fgroups.metaGroupID = metaGroups.metaGroupID
INNER JOIN mapsolarsystems AS systems ON journal.argID1 = systems.solarSystemID
INNER JOIN mapregions AS regions ON systems.regionID = regions.regionID
WHERE
journal.date >= {$this->db->qstr($_REQUEST['from'])} AND
journal.date < {$this->db->qstr($_REQUEST['to'])}
ORDER BY
journal.date DESC";

    $rs = $this->db->Execute($sql);
    while($row = $rs->FetchRow())
    {
      $response['aaData'][] = array(
        htmlentities($row['characterName'], ENT_QUOTES, 'UTF-8'),
        $row['characterID'],
        htmlentities($row['ratName'], ENT_QUOTES, 'UTF-8'),
        $row['ratID'],
        $row['ratGroup'],
        htmlentities($row['systemName'], ENT_QUOTES, 'UTF-8'),
        $row['systemID'],
        htmlentities($row['regionName'], ENT_QUOTES, 'UTF-8'),
        $row['regionID'],
        number_format($row['bounty'], 0, $this->lang->decimalSeparator, $this->lang->thousandSeparator).' ISK', $row['bounty'],
        date($this->lang->dateFormat, $row['date']),
        $row['date']
      );
    }

    $response['aaSorting'] = array();
    $response['aaSorting'][] = array(11, 'desc');

    $response['aoColumns'] = array();
    $response['aoColumns'][] = array('sTitle' => $this->lang->character);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->rat);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->rat_type);
    $response['aoColumns'][] = array('sTitle' => $this->lang->system);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->region);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->bounty, 'sClass' => 'right nonbreaking', 'iDataSort' => 10);
    $response['aoColumns'][] = array('bVisible' => false);
    $response['aoColumns'][] = array('sTitle' => $this->lang->date, 'sClass' => 'nonbreaking', 'iDataSort' => 12);
    $response['aoColumns'][] = array('bVisible' => false);

    return $response + $this->tableOptions;
  }

  private function getByDay()
  {
    $response = array();
    $start = strtotime($_REQUEST['from']);
    $end = strtotime($_REQUEST['to']);
    $sql = '';

    $character = false;
    if(isset($_REQUEST['character']))
      $character = true;

    $agent = false;
    if(isset($_REQUEST['agent']))
      $agent = true;

    $rat = false;
    if(isset($_REQUEST['rat']))
      $rat = true;

    $byHour = false;
    if($end - $start <= $this->Config->ByHour*86400)
      $byHour = true;

    if(isset($_REQUEST['system']) || isset($_REQUEST['region']))
    {
      $sql = "SELECT
j3.date,
j3.corpTax,
Sum(j3.amount) AS amount
FROM
(
	SELECT
	UNIX_TIMESTAMP".($byHour?"(DATE(j1.date))+HOUR(j1.date)*3600":"(DATE(j1.date))")." AS date,
  j1.corpTax,
	Sum(j1.amount) AS amount
	FROM
	journal AS j1
  ".(isset($_REQUEST['region'])?"INNER JOIN mapsolarsystems AS systems1 ON j1.argID1 = systems1.solarSystemID":"")."
	WHERE
	j1.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j1.date < {$this->db->qstr($_REQUEST['to'])} AND
	j1.refTypeID = 85
  ".(isset($_REQUEST['system'])?"AND j1.argID1 = {$this->db->qstr($_REQUEST['system'])}":"")."
  ".(isset($_REQUEST['region'])?"AND systems1.regionID = {$this->db->qstr($_REQUEST['region'])}":"")."
  ".($character?" AND j1.ownerID2 = {$this->db->qstr($_REQUEST['character'])}":"")."
	GROUP BY
	".($byHour?"DATE(j1.date), HOUR(j1.date)":"DATE(j1.date)")."
	UNION	ALL
	SELECT
	UNIX_TIMESTAMP".($byHour?"(DATE(j2.date))+HOUR(j2.date)*3600":"(DATE(j2.date))")." AS date,
  j2.corpTax,
	Sum(j2.amount) AS amount
	FROM
	journal AS j2
	INNER JOIN agtagents AS agents ON j2.argID1 = agents.agentID
	INNER JOIN mapdenormalize AS denormalize ON agents.locationID = denormalize.itemID
	WHERE
	j2.date >= {$this->db->qstr($_REQUEST['from'])} AND
	j2.date < {$this->db->qstr($_REQUEST['to'])} AND
	j2.refTypeID IN (33, 34)
  ".(isset($_REQUEST['system'])?"AND 	denormalize.solarSystemID = {$this->db->qstr($_REQUEST['system'])}":"")."
  ".(isset($_REQUEST['region'])?"AND 	denormalize.regionID = {$this->db->qstr($_REQUEST['region'])}":"")."
  ".($character?" AND j2.ownerID2 = {$this->db->qstr($_REQUEST['character'])}":"")."
	GROUP BY
	".($byHour?"DATE(j2.date), HOUR(j2.date)":"DATE(j2.date)")."
) AS j3
GROUP BY
j3.date
ORDER BY
amount DESC";
    }
    else // normal, character
    {
      $sql = "SELECT
UNIX_TIMESTAMP".($byHour?"(DATE(j.date))+HOUR(j.date)*3600":"(DATE(j.date))")." AS date,
j.corpTax,
".($rat?"SUM(rats.amount)":"SUM(j.amount)")." AS amount
FROM
journal AS j
".($rat?"INNER JOIN rats ON j.id = rats.journal_id":"")."
WHERE
j.date >= {$this->db->qstr($_REQUEST['from'])} AND
j.date < {$this->db->qstr($_REQUEST['to'])}
".($character?" AND j.ownerID2 = {$this->db->qstr($_REQUEST['character'])}":"")."
".($agent?" AND j.refTypeID IN (33, 34) AND j.ownerID1 = {$this->db->qstr($_REQUEST['agent'])}":"")."
".($rat?" AND rats.rat = {$this->db->qstr($_REQUEST['rat'])}":"")."
GROUP BY
".($byHour?"DATE(j.date), HOUR(j.date)":"DATE(j.date)")."
ORDER BY
date ASC";
    }

    $rs = $this->db->Execute($sql);
    $temp = array();
    $response['totalSum'] = 0;
    while($row = $rs->FetchRow())
    {
      if($rat)
      {
        $temp[$row['date']] = $row['amount'];
        $response['totalSum'] += $row['amount'];
      }
      else
      {
        $temp[$row['date']] = $row['amount']/$row['corpTax'];
        $response['totalSum'] += $row['amount']/$row['corpTax'];
      }
    }

    $temp2 = array();
    while($start < $end)
    {
      $temp2[$start] = 0;
      if(!$byHour)
        $start += 60*60*24;
      else
      {
        $timecheck = $start + 60 * 60;
        if(date('i') < 30)
          $timecheck += 60 * 60;
        if($timecheck >= time())
          break;
        $start += 60 * 60;
      }
    }
    foreach($temp as $key => $val)
    {
      $temp2[$key] = $val;
    }
    foreach($temp2 as $key => $val)
    {
      $response['graph'][] = array($key*1000, $val);
    }

    return $response;
  }

  private function getSampleData()
  {
    $response = array();

    $response['aaSorting'] = array();
    $response['aaSorting'][] = array(1, 'asc');

    $response['aaData'] = array();
    $response['aaData'][] = array('Trident', 'Internet Explorer 4.0', 'Win 95+', 4, 'X');
    $response['aaData'][] = array('Trident', 'Internet Explorer 5.0', 'Win 95+', 5, 'C');
    $response['aaData'][] = array('Trident', 'Internet Explorer 5.5', 'Win 95+', 5.5, 'A');
    $response['aaData'][] = array('Trident', 'Internet Explorer 6.0', 'Win 98+', 6, 'A');
    $response['aaData'][] = array('Trident', 'Internet Explorer 7.0', 'Win XP SP2+', 7, 'A');
    $response['aaData'][] = array('Gecko', 'Firefox 1.5', 'Win 98+ / OSX.2+', 1.8, 'A');
    $response['aaData'][] = array('Gecko', 'Firefox 2', 'Win 98+ / OSX.2+', 1.8, 'A');
    $response['aaData'][] = array('Gecko', 'Firefox 3', 'Win 2k+ / OSX.3+', 1.9, 'A');
    $response['aaData'][] = array('Webkit', 'Safari 1.2', 'OSX.3', 125.5, 'A');
    $response['aaData'][] = array('Webkit', 'Safari 1.3', 'OSX.3', 312.8, 'A');
    $response['aaData'][] = array('Webkit', 'Safari 2.0', 'OSX.4+', 419.3, 'A');
    $response['aaData'][] = array('Webkit', 'Safari 3.0', 'OSX.4+', 522.1, 'A');

    $response['aoColumns'] = array();
    $response['aoColumns'][] = array('sTitle' => 'Engine');
    $response['aoColumns'][] = array('sTitle' => 'Browser');
    $response['aoColumns'][] = array('sTitle' => 'Platform');
    $response['aoColumns'][] = array('sTitle' => 'Version', 'sClass' => 'center');
    $response['aoColumns'][] = array('sTitle' => 'Grade', 'sClass' => 'center');
    
    return $response + $this->tableOptions;
  }
}
