<?php

$MAX=30;
$SLEEP_TIME=1000000;

$Number_Of_Connections = "";
$cpuUsageStr = "";
$Outputed = "";
$Number_Of_Sources = 0;

clearApacheLogs();

for (; ; ){
      updateInfo();
      printInfo();
      checkNetworkConnection();
      updateInfo();
      usleep($SLEEP_TIME);
      printInfo();
      usleep($SLEEP_TIME);
      checkNetworkConnection();
}

function clearApacheLogs(){
      shell_exec("echo > /var/log/apache2/access.log");
}

function updateInfo(){
      global $MAX;
      global $Number_Of_Connections;
      global $cpuUsageStr;
      global $Outputed;
      global $Number_Of_Sources;
      $Sources = array();

      $Number_Of_Connections = intval(shell_exec("netstat --tcp -4 -plan | grep :80 | wc -l"));
      $cpuUsage = shell_exec("top -bn 1 | awk '{print $9}' | tail -n +8 | awk '{s+=$1} END {print s}'");
      $color = ($cpuUsage > 50) ? (($cpuUsage > 75)?"\033[31m":"\033[33m") : "\033[32m";
      $cpuUsageStr = "";
      $cpuUsageStr = "$color \t\tLoad: <";
      for ($i=0; $i < $cpuUsage/2; $i++){
        $cpuUsageStr = $cpuUsageStr."=";
      }
      $cpuUsageStr = $cpuUsageStr.">\n\n";
      $Outputed = "";
      $color = "\033[32m";
      for ($i=1, $j=$MAX; $j>0 ; $i++, $j--) {
        $Connections = shell_exec("tail -n$j /var/log/apache2/access.log | head -n 1");
	if ($Connections == "\n"){
	    $i--;
	    continue;
	}
	$StrArray = explode(" ", $Connections);
        $time = intval($StrArray[4]);
        $color = ($time > 3000) ? (($time > 5000)? "\033[31m" : "\033[33m" ) : "\033[32m";
        $ipAddr = intval($StrArray[1]);
        array_push($Sources, $ipAddr);

	$I = str_pad($i, 2, "0", STR_PAD_LEFT);
        $Outputed = $Outputed."$color$I: $Connections";
        $Outputed = str_replace(" ","\t", $Outputed);
      }
      $Sources = array_unique($Sources);
      $Number_Of_Sources = count($Sources);
}

function printInfo(){
      global $Number_Of_Connections;
      global $cpuUsageStr;
      global $Outputed;
      global $Number_Of_Sources;

      echo "\033[2J\033[;H";
      echo "\033[32m$Number_Of_Connections Connections from $Number_Of_Sources sources\n\n";
      echo $cpuUsageStr;
      echo $Outputed;
}

function checkNetworkConnection(){
      $tmp=shell_exec("route -n | wc -l");
      if ($tmp < 3){
            echo chr(7);
	    echo "\033[31mIt seems like, that the network is down";
      }
}

?>
