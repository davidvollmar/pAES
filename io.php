<?php
   class ioOperations {
      /**
       *  This class implements several different operations on input
       *  It has functions to convert form input to a byte array and 
       *  to convert a byte array to a state (as defined in AES)
      **/

      public function getByteArrayFromKeyString()
      {  $correct = false;
         $retv = "";
      
         $in = htmlspecialchars(trim($_POST['key']));

         if ( (!isset($in)) || ($in === NULL) ) {
         $_SESSION['debug'] .= "input not correct";
         }
         else if (empty($in)){
            $_SESSION['debug'] .= "key is empty";
         }
         else {
               //removing spaces and 0x hex format specifiers is not fool-proof but gets normal input to  hex format
               $in = str_replace(' ', '', $in); //remove all spaces from string
               $in = str_replace('\x', '', $in); // remove all 0x hex format specifiers
               $in = str_replace('0x', '', $in); // remove all 0x hex format specifiers
               $index = 0;
               for ($i=0; $i<strlen($in); $i+=2) {
                  $ss = substr($in, $i, 2);
                  $bytearray[$index] = hexdec($ss);
                  //$bytearray[$index] = $ss;
                  $index++;
               }   
               
               for ($i=$index; $i<16; $i++) $bytearray[$i] = 0;
   
               $_SESSION['debug'] .= "The key string converted to a byte-array with the decimal representation of the keybytes:";
               $_SESSION['debug'] .= "\n". implode(",", $bytearray) ."\n";
               $retv = $bytearray;
               $correct = true;
         }
                  
         if ($correct){
            return $retv;
         }
         else{
            return "";
         }
      }
      
      public function getByteArrayFromInput()
      {  $correct = false;
         $retv = "";
      
         $in = htmlspecialchars(trim($_POST['input']));
         $operation = htmlspecialchars(trim($_POST['operation']));

         if ( (!isset($in)) || ($in === NULL) ) {
         $_SESSION['debug'] .= "input not correct";
         }
         else if (empty($in)){
            $_SESSION['debug'] .= "Input is empty";
         }
         else if (!(($_POST['format']=='ascii') || ($_POST['format']=='hex'))){
            $_SESSION['debug'] .= "format incorrect";
         }
         else if (!(($_POST['operation']=='subBytes') || ($_POST['operation']=='shiftRows') || ($_POST['operation']=='mixColumns') || ($_POST['operation']=='addRoundKey') 
                    || ($_POST['operation']=='encrypt') || ($_POST['operation']=='decrypt') )){
            $_SESSION['debug'] .= "operation not supported";
         }
         else {
            if ($_POST['format']=='ascii'){
               if(strlen($in) > 16){
                  $_SESSION['debug'] .= "input to long, maximum length is 16 bytes";
               }
               else
               {
                  //read posted data for state data and for data-format (ascii or hex)

                  // convert to a decimal representation of the ascii-values, C for unsigned char
                  $bytearray = unpack('C*', $in); 

                  // make bytearray start at index 0, because result from unpack starts at index 1
                  $bytearray = array_merge($bytearray); 
                  if (strlen($in)<16){ //pad with null values
                     for ($i=strlen($in); $i<16; $i++) $bytearray[$i] = 0;
                  }
   
                  $_SESSION['debug'] .= "The input converted to a byte-array with the decimal representation of the ascii-values:";
                  $_SESSION['debug'] .= "\n". implode(",", $bytearray) ."\n";
                  $retv = $bytearray;
                  $correct = true;
               }
            }
            else if ($_POST['format']=='hex'){
               //$_SESSION['debug'] .= "\nhex format not supported yet, sorry!";
               //removing spaces and 0x hex format specifiers is not fool-proof but gets normal input to just hex numbers
               $in = str_replace(' ', '', $in); //remove all spaces from string
               $in = str_replace('\x', '', $in); // remove all 0x hex format specifiers
               $in = str_replace('0x', '', $in); // remove all 0x hex format specifiers
               $index = 0;
               for ($i=0; $i<strlen($in); $i+=2) {
                  $ss = substr($in, $i, 2);
                  //$array = unpack("H*data", $ss);
                  //$answer = $array["data"];
                  $bytearray[$index] = hexdec($ss);
                  $index++;
               }   
               
               for ($i=$index; $i<16; $i++) $bytearray[$i] = 0;
   
               $_SESSION['debug'] .= "The input converted to a byte-array with the decimal representation of the ascii-values:";
               $_SESSION['debug'] .= "\n". implode(",", $bytearray) ."\n";
               $retv = $bytearray;
               $correct = true;
            }
         }
                  
         if ($correct){
            return $retv;
         }
         else{
            return "";
         }
      }

   
      public function getState($bytearray)
      {
         // let's convert the input to the state as done with the AES-input, 
         // so first input-byte goes to state[0][0], second input-byte goes to state[1][0], etc 
         $state =  array();  
         $_SESSION['debug'] .= "\nThe input converted to a 4x4 state-array\n";
         for ($i=0; $i<16; $i++)
         {
            //for example the input-byte 5 should go to state[1][1], so state[5%4][floor(5/4)]
            $state[$i%4][floor($i/4)] = $bytearray[$i];  
            //$_SESSION['debug'] .= "state[" . $i%4 . "][" . floor($i/4) . "]=" . $bytearray[$i] . "\n";
         }

         for ($row=0; $row<4; $row++) {
            $_SESSION['debug'] .= "(";
            for ($column=0; $column<4; $column++) {
               $_SESSION['debug'] .= $state[$row][$column];
               if ($column < 3) $_SESSION['debug'] .= ", ";
            }
            $_SESSION['debug'] .= ")\n";
         }
         return $state;
      }

      public function convertStateToByteArray($result)
      {
         for ($i=0; $i<16; $i++)
         {
            //for example state[2][1] should go to output-byte 6, so out[6]=state[6%4][floor(6/4)] 
            $outputarr[$i] = $result[$i%4][floor($i/4)]; 
         }
         $_SESSION['debug'] .= "\nThe result converted back to a linear decimal representation:\n";
         $_SESSION['debug'] .= implode(",", $outputarr);

         for ($i=0; $i<16; $i++)
         {
            $output.= dechex($outputarr[$i])." ";
         }
         return $output;
         }

   }
   
?>
