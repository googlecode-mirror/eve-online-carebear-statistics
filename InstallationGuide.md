# How-to #

## Import data ##

Import data from the following files into your SQL server database:
  * carebear.sql
  * carebear\_static\_1.sql
  * carebear\_static\_2.sql

## Configure ##

Configure settings in configuration.php:
```
public $Auth = array(
  'username' => 'password'
);
```
This is an array in the form of username and password.
You can specify multiple entries here, put them on seperate lines and add a comma to every line except the last one.

```
public $Dsn = 'mysql://username:password@host/database';
```
Replace username, password, host and database with appropriate entries with what your server host has provided you with.

```
public $Language = 'en';
```
This can be used to change the language file being used, I have provided the package with two languages, english and estonian. It should not be too hard to add your own based on english.

```
public $UserID = '123';
```
This is the keyID

```
public $ApiKey = 'asd';
```
This is the vCode

```
public $CharacterID = '123';
```
This is your Character ID, you can find it from EVE Gate if you look at your character's avatar image name.

```
public $PageTitle = 'Carebearing Statistics';
```
Use it to edit the page title of Carebearing Statistics.

```
public $ByHour = 3;
```
If 3 or less days are selected by the user, it shows the graph by hour instead of default days.

```
public $ApiUrl = 'https://api.eve-online.com/corp/WalletJournal.xml.aspx';
```
Change /corp/ to /char/ to make it ask only for personal data, not necessary unless you put it up to only track yourself.

```
public $RowCount = 100;
```
Changes the rowcount that is asked at a time with cron from API.
If there are more entries, the cron script asks them from API until all have been received.

```
public $UseCURL = true;
```
Set this to "false", if your PHP configuration does not allow cURL.

```
public $JournalTable = 'journal';
```
Can be used to change the table used to store the wallet journal. Don't change unless you have modified the database tables.

```
public $RatsTable = 'rats';
```
Can be used to change the table used to store the rat kills. Don't change unless you have modified the database tables.

```
public $DebugCron = false;
```
Make cron script output debug data to cron.log. If you enable this, make cron.log file writable.

## Set up cron script ##

Set up cron script to pull data at most every 30 minutes `(*/30 * * * *)`