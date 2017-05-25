<?php
/**
 * Created by PhpStorm.
 * User: Szekeres
 * Date: 2016. 09. 17.
 * Time: 23:02
 */

function JobStartAsync($server, $url, $port=80,$conn_timeout=30, $rw_timeout=86400) {
  $errno = '';
  $errstr = '';

  set_time_limit(0);

  $fp = fsockopen($server, $port, $errno, $errstr, $conn_timeout);
  if (!$fp) {
    echo "$errstr ($errno)<br />\n";
    return false;
  }
  $out = "GET $url HTTP/1.1\r\n";
  $out .= "Host: $server\r\n";
  $out .= "Connection: Close\r\n\r\n";

  stream_set_blocking($fp, false);
  stream_set_timeout($fp, $rw_timeout);
  fwrite($fp, $out);

  return $fp;
}

function JobPollAsync(&$fp)
{
  if ($fp === false) return false;

  if (feof($fp)) {
    fclose($fp);
    $fp = false;
    return false;
  }

  return fread($fp, 10000);
}

###########################################################################################

$path    = 'C:\wamp\www\sql';
$dir = scandir($path);
$files=array();
foreach ($dir as $file) {
    if(strpos($file,".sql")) {
        $files[]=$file;
    }
}
$i=1;
foreach ($files as $file) {
    $content = '<?php
set_time_limit(0);
$cmd = "C:\wamp\bin\mysql\mysql5.7.14\bin\mysql.exe -u root test < c:\wamp\www\sql\\' . $file . '";
shell_exec($cmd);
';
    $content = str_replace('"',"'",$content);
    file_put_contents("jobs".$i.".php", $content);
    $i++;
}

$procedSql = 35;
for ($i = 1; $i <= $procedSql; $i++) {
    $fp[$i] = JobStartAsync('localhost', '/multi_sql/jobs'.$i.'.php');
}

$already_echo=array();
  while (true) {
//Check job is run ?
    sleep(1);
      for ($i = 1; $i <= $procedSql; $i++) {
          $r[$i] = JobPollAsync($fp[$i]);
          if ($r[$i]) {
              echo "<b>Job ".$i." working<br>";
          }
      }
      flush(); @ob_flush();
      $stop=0;
      foreach ($r as $key => $runner) {
          if ($runner === false) {
              if (!isset($already_echo[$key])) {
              echo "<h3>Runner ". $key ." Stop working.</h3>";
              flush(); @ob_flush();
              $already_echo[$key] = false;
              }
        $stop++;
        if ($stop == $procedSql) {
            flush(); @ob_flush();
            echo "<h3>Jobs Complete</h3>";
            die;
        }
          }
      }
  }


