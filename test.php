<?php
/**
 * Created by IntelliJ IDEA.
 * User: David
 * Date: 17-3-13
 * Time: 16:24
 * File to test features
 */
include_once("io.php");
$IO = new ioOperations();
$in = "f7 a3 32 16 f7 a3 32 16 f7 a3 32 16 f7 a3 32 16 f7 a3 32 16";

$byteArray = magic1($in);
$byteArray2 = $IO->fillPadding($byteArray);
$blaat = magic2($byteArray);
echo(implode(",",$blaat)."\n");
echo("<br />");
echo(implode(",",$byteArray2)."\n");
echo("<br />");
echo(implode(",",$byteArray2)."\n");
echo("<br />");
echo("In count: ".strlen($in));
echo("<br />");
echo("1 count: ".count($byteArray));
echo("<br />");
echo("2 count: ".count($byteArray2));


//echo($byteArray2."\n");

function magic1($in){
	$byteArray = array();
	$in = str_replace(' ', '', $in); //remove all spaces from string
	$in = str_replace('\x', '', $in); // remove all 0x hex format specifiers
	$in = str_replace('0x', '', $in); // remove all 0x hex format specifiers
	$index = 0;
	for ($i=0; $i<strlen($in); $i+=2) {
		$ss = substr($in, $i, 2);
		$byteArray[$index] = hexdec($ss);
		//$byteArray[$index] = $ss;
		$index++;
	}
	return $byteArray;
}

function magic2($byteArray){
	$IO = new ioOperations();
	return $IO->getStates($byteArray);
}



?>