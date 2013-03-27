<?php
   session_start();
   include_once("io.php"); //io operations for reading input to bytearrays and visa versa

   $_SESSION['debug'] = "";
   $_SESSION['input'] = htmlspecialchars(trim($_POST['input']));
   $_SESSION['key'] = htmlspecialchars(trim($_POST['key']));
   $_SESSION['format'] = htmlspecialchars(trim($_POST['format']));
   $_SESSION['source'] = $_SERVER['SCRIPT_FILENAME'];
   $operation = htmlspecialchars(trim($_POST['operation']));
   $encmode = htmlspecialchars(trim($_POST['encmode']));

   $iop = new ioOperations();
   //create byte array from string key
   $key = $iop->getByteArrayFromKeyString();
   //create byte array from user's input
   $bytearray = $iop->getByteArrayFromInput();
   if (empty($bytearray)) $_SESSION['debug'] .= "\ninput not valid";
   else
   {
      //create AES state (4x4 bytes) of byte array
      $state = $iop->getState($bytearray);
      $input = $iop->getStates($bytearray);
	  
	  $_SESSION['debug'] .= "input heeft ".count($input)." state blokken\n";  
	  $_SESSION['debug'] .= "inhoud bytearray: ".implode(",",$bytearray)."\n"; 	  
	  
	  //put key in 4x4 byte array too
      //$key = $iop->getState($keyarray);
   
      // perform a subBytes operation
      $aesops = new Aes();
      $result = array();
	 // $IV = $aesops->makeIV(); // we gebruiken zelfde IV voor alle encrypties
	 // $IV=explode(",","a0,d4,90,97,e4,eb,ba,1,c9,c7,20,9c,e8,1d,1c,de");
	 //$IV = array(0x12, 0x13, 0xfa, 0x1a, 0x2b, 0x3c,0x00, 0x01, 0x3c, 0x4a, 0x4c, 0x37,0x20, 0x21, 0x42, 0x11);
	// IV voor tests volgens http://tools.ietf.org/html/draft-ietf-ipsec-ciph-aes-cbc-04
	// 1 blok CBC encrypten IV :
	// $IV = array(0x3d,0xaf,0xba,0x42,0x9d,0x9e,0xb4,0x30,0xb4,0x22,0xda,0x80,0x2c,0x9f,0xac,0x41);
	// 2 blokken CBC encrypten IV :
	//$IV=array(0x56,0x2e,0x17,0x99,0x6d,0x09,0x3d,0x28,0xdd,0xb3,0xba,0x69,0x5a,0x2e,0x6f,0x58);
	// 3 blokken CBC encrypten IV :
	$IV=array(0xc7,0x82,0xdc,0x4c,0x09,0x8c,0x66,0xcb,0xd9,0xcd,0x27,0xd8,0x25,0x68,0x2c,0x81);
	
      $_SESSION['debug'] .= "\nThe ". $operation . " operation:\n";
      switch ($operation){
         case "subBytes":
            $result=$aesops->subBytes($state);
            break;
         case "shiftRows":
            $result=$aesops->shiftRows($state);
            break;
         case "mixColumns":
            $result=$aesops->mixColumns($state);
            break;
         case "addRoundKey":
            $w = $aesops->keyExpansion($key); //generate roundkeys in key expansion
            $result=$aesops->addRoundKey($state, $w, 0); //add roundkey 0 for this example
            break;
         case "encrypt":				
			switch($encmode){
				case "SBM":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->sbm_encrypt($input,$key);
					break;
				case "ECB":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->ecb_encrypt($input,$key);
					break;
				case "CBC":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->cbc_encrypt($input,$key,$IV);
					break;
				case "CFB":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->cfb_encrypt($input,$key,$IV);
					break;
				case "CTR":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->ctr_encrypt($input,$key,$IV);
					break;
				default:
					$_SESSION['debug'] .= "\n Error, enc operation not valid";
					break;
				}
			break;
         case "decrypt":       
			switch($encmode){
				case "SBM":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->sbm_decrypt($input, $key);
					break;
				case "ECB":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->ecb_decrypt($input,$key);
					break;
				case "CBC":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->cbc_decrypt($input,$key,$IV);
					break;
				case "CFB":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->cfb_decrypt($input,$key,$IV);
					break;
				case "CTR":
					$_SESSION['debug'] .= "Mode = ".$encmode."\n";
					$result=$aesops->ctr_decrypt($input,$key,$IV);
					break;
				default:
					$_SESSION['debug'] .= "\n Error, dec operation not valid";
					break;
				}
				break;
		default:
            $_SESSION['debug'] .= "\n Error, no such operation.";
            break;

      }           

      // now convert back the final state to output
      $_SESSION['debug'] .= "\nThis should only apear once.\n";
      $output = $iop->convertStatesToByteString($result);
      $_SESSION['debug'] .= "\n\nThe hexadecimal result of the ". $operation ." operation:\n$output\n";
      $_SESSION['output'] .= $output;	 
   }
   header('Location:operations.php'); //reload the operations.php page with the session values
?>


<?php
/*
* This is the class section of this php file.
* It defines two classes AesSubbytes and ioOperations
*/
   class Aes {

      /**
* This class implements onse part of the AES algortihm, the subBytes operation.
* It is meant for demonstration purpose only.
**/
       
      // sBox is the pre-computed multiplicative inverse in GF(2^8) used in subBytes() and also in the keyExpansion.
      private static $sBox = array(
      0x63,0x7c,0x77,0x7b,0xf2,0x6b,0x6f,0xc5,0x30,0x01,0x67,0x2b,0xfe,0xd7,0xab,0x76,
      0xca,0x82,0xc9,0x7d,0xfa,0x59,0x47,0xf0,0xad,0xd4,0xa2,0xaf,0x9c,0xa4,0x72,0xc0,
      0xb7,0xfd,0x93,0x26,0x36,0x3f,0xf7,0xcc,0x34,0xa5,0xe5,0xf1,0x71,0xd8,0x31,0x15,
      0x04,0xc7,0x23,0xc3,0x18,0x96,0x05,0x9a,0x07,0x12,0x80,0xe2,0xeb,0x27,0xb2,0x75,
      0x09,0x83,0x2c,0x1a,0x1b,0x6e,0x5a,0xa0,0x52,0x3b,0xd6,0xb3,0x29,0xe3,0x2f,0x84,
      0x53,0xd1,0x00,0xed,0x20,0xfc,0xb1,0x5b,0x6a,0xcb,0xbe,0x39,0x4a,0x4c,0x58,0xcf,
      0xd0,0xef,0xaa,0xfb,0x43,0x4d,0x33,0x85,0x45,0xf9,0x02,0x7f,0x50,0x3c,0x9f,0xa8,
      0x51,0xa3,0x40,0x8f,0x92,0x9d,0x38,0xf5,0xbc,0xb6,0xda,0x21,0x10,0xff,0xf3,0xd2,
      0xcd,0x0c,0x13,0xec,0x5f,0x97,0x44,0x17,0xc4,0xa7,0x7e,0x3d,0x64,0x5d,0x19,0x73,
      0x60,0x81,0x4f,0xdc,0x22,0x2a,0x90,0x88,0x46,0xee,0xb8,0x14,0xde,0x5e,0x0b,0xdb,
      0xe0,0x32,0x3a,0x0a,0x49,0x06,0x24,0x5c,0xc2,0xd3,0xac,0x62,0x91,0x95,0xe4,0x79,
      0xe7,0xc8,0x37,0x6d,0x8d,0xd5,0x4e,0xa9,0x6c,0x56,0xf4,0xea,0x65,0x7a,0xae,0x08,
      0xba,0x78,0x25,0x2e,0x1c,0xa6,0xb4,0xc6,0xe8,0xdd,0x74,0x1f,0x4b,0xbd,0x8b,0x8a,
      0x70,0x3e,0xb5,0x66,0x48,0x03,0xf6,0x0e,0x61,0x35,0x57,0xb9,0x86,0xc1,0x1d,0x9e,
      0xe1,0xf8,0x98,0x11,0x69,0xd9,0x8e,0x94,0x9b,0x1e,0x87,0xe9,0xce,0x55,0x28,0xdf,
      0x8c,0xa1,0x89,0x0d,0xbf,0xe6,0x42,0x68,0x41,0x99,0x2d,0x0f,0xb0,0x54,0xbb,0x16);

private static $InvS_Box = array(	
0x52, 0x09, 0x6A, 0xD5, 0x30, 0x36, 0xA5, 0x38, 0xBF, 0x40, 0xA3, 0x9E, 0x81, 0xF3, 0xD7, 0xFB,
0x7C, 0xE3, 0x39, 0x82, 0x9B, 0x2F, 0xFF, 0x87, 0x34, 0x8E, 0x43, 0x44, 0xC4, 0xDE, 0xE9, 0xCB,
0x54, 0x7B, 0x94, 0x32, 0xA6, 0xC2, 0x23, 0x3D, 0xEE, 0x4C, 0x95, 0x0B, 0x42, 0xFA, 0xC3, 0x4E,
0x08, 0x2E, 0xA1, 0x66, 0x28, 0xD9, 0x24, 0xB2, 0x76, 0x5B, 0xA2, 0x49, 0x6D, 0x8B, 0xD1, 0x25,
0x72, 0xF8, 0xF6, 0x64, 0x86, 0x68, 0x98, 0x16, 0xD4, 0xA4, 0x5C, 0xCC, 0x5D, 0x65, 0xB6, 0x92,
0x6C, 0x70, 0x48, 0x50, 0xFD, 0xED, 0xB9, 0xDA, 0x5E, 0x15, 0x46, 0x57, 0xA7, 0x8D, 0x9D, 0x84,
0x90, 0xD8, 0xAB, 0x00, 0x8C, 0xBC, 0xD3, 0x0A, 0xF7, 0xE4, 0x58, 0x05, 0xB8, 0xB3, 0x45, 0x06,
0xD0, 0x2C, 0x1E, 0x8F, 0xCA, 0x3F, 0x0F, 0x02, 0xC1, 0xAF, 0xBD, 0x03, 0x01, 0x13, 0x8A, 0x6B,
0x3A, 0x91, 0x11, 0x41, 0x4F, 0x67, 0xDC, 0xEA, 0x97, 0xF2, 0xCF, 0xCE, 0xF0, 0xB4, 0xE6, 0x73,
0x96, 0xAC, 0x74, 0x22, 0xE7, 0xAD, 0x35, 0x85, 0xE2, 0xF9, 0x37, 0xE8, 0x1C, 0x75, 0xDF, 0x6E,
0x47, 0xF1, 0x1A, 0x71, 0x1D, 0x29, 0xC5, 0x89, 0x6F, 0xB7, 0x62, 0x0E, 0xAA, 0x18, 0xBE, 0x1B,
0xFC, 0x56, 0x3E, 0x4B, 0xC6, 0xD2, 0x79, 0x20, 0x9A, 0xDB, 0xC0, 0xFE, 0x78, 0xCD, 0x5A, 0xF4,
0x1F, 0xDD, 0xA8, 0x33, 0x88, 0x07, 0xC7, 0x31, 0xB1, 0x12, 0x10, 0x59, 0x27, 0x80, 0xEC, 0x5F,
0x60, 0x51, 0x7F, 0xA9, 0x19, 0xB5, 0x4A, 0x0D, 0x2D, 0xE5, 0x7A, 0x9F, 0x93, 0xC9, 0x9C, 0xEF,
0xA0, 0xE0, 0x3B, 0x4D, 0xAE, 0x2A, 0xF5, 0xB0, 0xC8, 0xEB, 0xBB, 0x3C, 0x83, 0x53, 0x99, 0x61,
0x17, 0x2B, 0x04, 0x7E, 0xBA, 0x77, 0xD6, 0x26, 0xE1, 0x69, 0x14, 0x63, 0x55, 0x21, 0x0C, 0x7D
);

      // rCon is Round Constant used for the Key Expansion, first column is 2^(r-1) in GF(2^8)
      private static $rCon = array(
      array(0x00, 0x00, 0x00, 0x00),
      array(0x01, 0x00, 0x00, 0x00),
      array(0x02, 0x00, 0x00, 0x00),
      array(0x04, 0x00, 0x00, 0x00),
      array(0x08, 0x00, 0x00, 0x00),
      array(0x10, 0x00, 0x00, 0x00),
      array(0x20, 0x00, 0x00, 0x00),
      array(0x40, 0x00, 0x00, 0x00),
      array(0x80, 0x00, 0x00, 0x00),
      array(0x1b, 0x00, 0x00, 0x00),
      array(0x36, 0x00, 0x00, 0x00) );

		
				
		public function makeIV()
		{

			$IO = new ioOperations();


		// Maken IV :
			// Voor CBC mode moet er een Initialisatie Vector gemaakt worden. Een (bijna-) random block van 128 bits :
			// De IV bloklengte moet gelijk zijn aan de blokgrootte van de boodschap. (en die is bij ons altijd 128 bit.)
			
			for($i=0 ; $i<16 ; $i++)
			{	
				$random = dechex(rand(0,256));
				$IV[$i] = $random;
			}
			$_SESSION['debug'] .= "De IV als bytearray : ". implode(",", $IV) ."\n"; 
			$IV = $IO->getState($IV); // maak een State blok van de IV
			
			return $IV;
		}
		
      public function encrypt($input, $key) // $input is de state.
     {	 
	 // Bepaal aantal rondes  :
	 
	 $aantalRondes = 0;
			$keylengte = count($key)*8;			
			if($keylengte == 128 || $keylengte == 192 || $keylengte == 256)
				{
					$_SESSION['debug'] .= "Sleutel is " .$keylengte. " bits.\n";
					if($keylengte == 128) {$aantalRondes = 10;} else
					if($keylengte == 192) {$aantalRondes = 12;} else
					if($keylengte == 256) {$aantalRondes = 14;}															
					$_SESSION['debug'] .= "Aantal rondes : ".$aantalRondes."\n";
				} else
				$_SESSION['debug'] .= "Controleer sleutel lengte. Moet 128/192/256 bits zijn.\n" ;	 
	 
			// Stap 1 : Pak de State Array en pak de oorspronkelijke sleutel. Voer hiermee AddRoundKey() uit.
			// Ook wel iteratie 0, initieel genoemd.
			$w = self::keyExpansion($key); //generate roundkeys in key expansion
            $result=self::addRoundKey($input, $w, 0); //voeg roundkey 0 toe voor de eerste stap.

			// Stap 2 Loopen sub,shift,mix,addRoundKey, aantalrondes - 1 keer
			//for($i=1;$i<$aantalRondes-1;$i++)
			for($i=1;$i<$aantalRondes;$i++)
				{
					$substitutedBytes=self::subBytes($result);
					$shiftedRows=self::shiftRows($substitutedBytes);	
					$mixedColumns=self::mixColumns($shiftedRows);
					$result = self::addRoundKey($mixedColumns, $w, $i); //add roundkey
				}

// Stap 3 laatste stap : Sub, shift, addRoundKey :

			$substitutedBytes=self::subBytes($result);
			$shiftedRows=self::shiftRows($substitutedBytes);	
			$EncryptedResult = self::addRoundKey($shiftedRows, $w, $aantalRondes); //add roundkey

			$_SESSION['debug'] .= "Encrypted result :\n";
			for ($row=0; $row<4; $row++) {
			$_SESSION['debug'] .= "(";
			for ($column=0; $column<4; $column++) {
			$_SESSION['debug'] .= $EncryptedResult[$row][$column];
			if ($column < 3) $_SESSION['debug'] .= ", ";
			}
			$_SESSION['debug'] .= ")\n";
					 }	
					 return($EncryptedResult);
	} //end function encrypt

      public function decrypt($input, $key)
      {
			$aantalRondes = 0;
			$keylengte = count($key)*8;			
			if($keylengte == 128 || $keylengte == 192 || $keylengte == 256)
				{
					$_SESSION['debug'] .= "Sleutel is " .$keylengte. " bits.\n";
					if($keylengte == 128) {$aantalRondes = 10;} else
					if($keylengte == 192) {$aantalRondes = 12;} else
					if($keylengte == 256) {$aantalRondes = 14;}															
					$_SESSION['debug'] .= "Aantal rondes : ".$aantalRondes."\n";
				} else
				$_SESSION['debug'] .= "Controleer sleutel lengte. Moet 128/192/256 bits zijn.\n" ;	
// Stap 1 : Key expansion draaien en met sleutel nr 10 beginnen ipv sleutel 0.
			$w = self::keyExpansion($key);
			$result=self::addRoundKey($input, $w, $aantalRondes);	

// Stap 2 : loop starten, 9 keer doorlopen:
			for($i=$aantalRondes-1;$i>=1;$i--)
			{
				$result = self::invShiftRows($result);
				$result = self::invSubBytes($result);
				$result = self::addRoundKey($result,$w,$i);	
				$result = self::invMixColumns($result);	
			}
			// Stap 3 : laatste decryptie stap :
			$result = self::invShiftRows($result);	
			$result = self::invSubBytes($result);
			$result = self::addRoundKey($result,$w,0);
			$_SESSION['debug'] .= "Finished decrypting!\n";
			
			return($result);
      } //end function decrypt


	/**
	 * Encrypts an array of states with Sbm
	 * @param $input an array of states (only the first state will be processed)
	 * @return array the encrypt state in an array of states
	 */
	public function sbm_encrypt($input,$key){
		if(!$input || !is_array($input)){
			die("Invalid input at smb_encrypt");
		}else
			$return = array();
			$_SESSION['debug'] .= "PETERS DESPERATE BUG CHECK ON STATE:".implode(",",$input)."\n";
			$return[0] = self::encrypt($input[0],$key);
			return $return;
	}



	/**
	 * decrypts an array of states with Sbm
	 * @param $input an array of states (only the first state will be processed)
	 * @return array the decrypt state in an array of states
	 */
	public function sbm_decrypt($input,$key){
		if(!$input || !is_array($input)){
			die("Invalid input at smb_decrypt");
		}else
			$return = array();
			$return[0] = self::decrypt($input[0],$key);
			return $return;
	}


	   /**
	    * Encrypten via ECB
	    *
	    * todo: testen. Waarschijnlijk gaat er iets mis met (het gebrek aan) padding.
	    *
	    * @param $input array De volledige input, dus meerdere blocks
	    * @param $key array De key om mee te encrypten
	    * @return array Encrypted data.
	    */
	   public function ecb_encrypt($input,$key)
	   {
		   $result = array();
		   $max = count($input);
		   $_SESSION['debug'] .= "ECB: Size of input =".$max."\n";
		   for($i = 0  ; $i < $max; $i++){
			   $result[$i] = self::encrypt($input[$i],$key);

		   }
		   return $result;
	   }

	   /**
	    * Decryptie via ECB
	    *
	    * todo: testen. Waarschijnlijk gaat er iets mis met (het gebrek aan) padding.
	    *
	    * @param $input array De volledige binaire input
	    * @param $key array De key om mee te decrypten
	    * @return array De decrypted waarde
	    */
	   public function ecb_decrypt($input,$key)
	   {
		   $result = array();
		   $max = sizeof($input);
		   for($i = 0  ; $i < $max; $i++){
			   $result[$i] = self::decrypt($input[$i],$key);
		   }
		   return $result;
	   }
	  
	  public function cbc_encrypt($input, $key, $IV)
	  {
			// Er gaat iets helemaal mis met de IV vermoedelijk
			// debuggen IV:			
			$_SESSION['debug'] .= "imploded IV: \n".implode(",",$IV)."\n";
			// De IV lijkt gewoon in orde te zijn nu
            $IO = new ioOperations();
			// CBC Mode encryptie :
			// $input is in dit geval een array van blokken van 128 bits groot (states).
			// Hoeveel blokken moeten we encrypten ?
			$aantalBlokken = 0;
			$aantalBlokken = count($input); // $input is een array van state blokken		
			$endResult = array();
			$result=array();
			$XORresult=array();
			$eersteBlok = array();
			// Encryptie :					
			// Stap 1 : Eerste blok klare tekst XORen met de IV.				
			$eersteBlok = $input[0]; // Haal eerste state blok uit de array van blokken
            $IV_stated  = $IO->getState($IV); // maak state van de IV voor XOR operatie
			$_SESSION['debug'] .= "De IV stated: ".implode(",",$IV_stated)."\n"; // to be sure
			$result = self::xorState($eersteBlok,$IV_stated);
			$_SESSION['debug'] .= "Resultaat XOR met IV als bytearray: ".implode(",",$result)."\n";
			// Encrypt het resultaat van de XOR tussen M1 en IV :
			$result = self::encrypt($result,$key);
			$endResult[0]=$result; // Eerste blok C1 opslaan in array	  	
			
			// Stap 2 : Loop starten. Gebruik output van ieder blok om te XORen met volgende blok.
			for ($p = 1 ; $p < $aantalBlokken;$p++) 
			{
				$XORresult = self::xorState($result, $input[$p]);
				$result = self::encrypt($XORresult,$key);				
				$endResult[$p] = $result;			
			/*
				PeterK: $input is al een array van states newb. nie mee rommelen dus.
				$XORresult = self::xorState($result, $IO->getState($input[$p]));
				$result = self::encrypt($XORresult,$key);				
				$endResult[$p] = $result;
			*/
			}	
			$_SESSION['debug'] .= "cbc encryptie eindresultaat:".implode(",",$endResult)."\n";	
			return $endResult; // array van encrypted blokken
			
	  }
	  	  
	  public function cbc_decrypt($input,$key,$IV)
	  {
            $IO = new ioOperations();
			// CBC Mode decryptie :
			// $input is in dit geval een array van blokken van 128 bits groot (states).
			$result = array();
			$endResult = array();
			$blokStore = array();
			$aantalBlokken = count($input); 
			// Stap 1
			// Decryptie eerste blok uit encryptie methode :
			// Onthoud dit blok want het moet gexorred worden met de output van de volgende encryptie stap
			$aantalBlokken = count($input);
			$eersteBlokDecr = self::decrypt($input[0],$key);
			// XOR met IV na decryptie eerste blok (laatste blok van encryptie)
			$result = self::xorState($eersteBlokDecr,$IO->getState($IV));
			//$endResult[$aantalBlokken] = $result;
			$endResult[0] = $result;

			// Stap 2
			// Loop waarin de encrypted blokken geXORred worden met de klare tekst na de volgende decrypt operatie
			// onthoud blok in $eersteBlok
			//$eersteBlok = $input[0]; // pak eerste blok uit encrypted array
			// output eerste stap met decryptie en IV-XOR zit in $result nu
			// start loop			
			for($i=1;$i<($aantalBlokken);$i++)
			{					
				$result = self::decrypt($input[$i],$key);
				$result = self::xorState($result,$input[($i-1)]);
				
				$endResult[$i] = $result;
			}
			return $endResult;				
	  }
	  
		public function cfb_encrypt($input, $key, $IV)
		{			
			// CFB Mode encryptie :
			// $input is een array van state blokken.						
			// Hoeveel blokken moeten we encrypten ?
			$aantalBlokken = 0;
			$aantalBlokken = count($input); // $input is een array van state blokken		
			$_SESSION['debug'] .= "Aantal blokken input: ".$aantalBlokken."\n";
			$endResult = array();
			$result=array();
			$eersteBlok = array();
			// Encryptie :			
			// Stap 1: De IV moet vercijferd worden met de key :
			$result = self::encrypt($IV,$key);
			// Stap 2 : De output van de IV-vercijfering moet geXORed worden met Blok 1 Klare tekst			
			$result = self::xorState($result,$input[0]);
			$_SESSION['debug'] .= "Resultaat XOR van IV met M1: ".$result."\n";
			$endResult[0] = $result; 						  				
			// Stap 3 : Loop starten. Gebruik output van ieder blok om te XORen met volgende blok.
			for ($p = 1 ; $p < $aantalBlokken;$p++) 
			{		
				$result = self::encrypt($endResult[($p-1)],$key);
				$result = self::xorState($result, $input[$p]);					
				$endResult[$p] = $result;
			}				
			return $endResult; // array van CFB encrypted blokken		
		}

		public function cfb_decrypt($input,$key,$IV)
		{			
			// CFB Mode decryptie :
			// $input is in dit geval een array van blokken van 128 bits groot (states).
			$result = array();
			$endResult = array();
			$blokStore = array();
			$aantalBlokken = count($input); 
			// Stap 1 CFB decryptie :
			// ENCRYPTIE(!) van de IV :
			$result = self::encrypt($IV,$key);
			// Voer XOR uit met C1 en de encrypted IV :
			$result = self::xorState($result,$input[0]);
			// $result bevat nu het eerste blok klare tekst M1.
			// aan array toevoegen van eindresultaat :
			$endResult[0] = $result;			
			// loop waarin C2 wordt encrypted en daarna geXORed met C3 :	
			for($i=1;$i<($aantalBlokken-1);$i++)
			{					
				$result = encrypt($input[$i]);
				$result = xorState($result,$input[($i+1)]);
				$endResult[$i] = $result;
			}
			return $endResult;			
		}
	  
	  public function ctr_encrypt($input,$key,$IV)
	  {
			$IO = new ioOperations();
	  // Counter mode :
		// Met iedere IV een int meegeven.
		// XORen met de IV array lijkt een goed idee op het moment.
			$endResult = array(); // Hier komt eindresultaat in van encrypted state blokken
			$result=array();
		// CTR Mode encryptie :
			// $input is een array van state blokken.
			// maken teller :
			// Hoeveel blokken moeten we encrypten ?
			$IVX = array();
			$counterMax = count($input); // $input is een array van state blokken
			// start loop:
			for($i=0;$i<$counterMax;$i++)
			{
				// XOR IV met $counter :
					$byteArrayFromCounter = $IO->getState(dechex($i));
				//	$_SESSION['debug'] .= "Resultaat maken ByteArray van counter: ".$byteArrayFromCounter."\n";
					$IVX = self::xorState($IO->getState($IV),$byteArrayFromCounter);
				//	$_SESSION['debug'] .= "Resultaat XOR ByteArray met counter: ".implode(",",$IVX)."\n";
				// encrypt de geXORde counter met IV met de key:
					$result = self::encrypt($IVX,$key);
				// XOR bewerking klare tekst blok en encrypted IV(incl counter dus):
					$result = self::xorState($result,$input[$i]);
					$endResult[$i] = $result;
			}
			return $endResult; // array van CFB encrypted blokken
	  }

	  public function ctr_decrypt($input,$key,$IV)
	  {
			// CTR Mode decryptie : (is gelijk aan encryptie eigenlijk)
			// XORen met de IV array lijkt een goed idee op het moment.
			$endResult = array(); // Hier komt eindresultaat in van encrypted state blokken
			$result=array();
		// CTR Mode encryptie :
			// $input is een array van state blokken.
			$IVX = array();
			// maken teller :
			// Hoeveel blokken moeten we encrypten ?

			$counterMax = count($input); // $input is een array van state blokken

			$IO = new ioOperations();
			// start loop:
			for($i=0;$i<$counterMax;$i++)
			{
				// XOR IV met $counter :
					$byteArrayFromCounter = $IO->getState(dechex($i));
			//		$_SESSION['debug'] .= "Resultaat maken ByteArray van counter: ".$byteArrayFromCounter."\n";
					$IVX = self::xorState($IO->getState($IV),$byteArrayFromCounter);
			//		$_SESSION['debug'] .= "Resultaat XOR IV met counter: ".implode(",",$IVX)."\n";
				// encrypt de geXORde counter met IV met de key:
					$result = self::encrypt($IVX,$key);
				// XOR bewerking klare tekst blok en encrypted IV(incl counter dus):
					$result = self::xorState($result,$input[$i]);
					$endResult[$i] = $result;
			}
			return $endResult; // array van CFB encrypted blokken
		}
	  	  
	  public function subBytes($state)
      {
         for ($row=0; $row<4; $row++){ // for all 16 bytes in the (4x4-byte) State
            for ($column=0; $column<4; $column++){ // for all 16 bytes in the (4x4-byte) State
               $_SESSION['debug'] .= "subBytes : state[$row][$column]=" . $state[$row][$column] .
                 "-->" . self::$sBox[$state[$row][$column]]."\n";
               $state[$row][$column] = self::$sBox[$state[$row][$column]];
            }
         }
         return $state;
      } // end function subBytes

public function invSubBytes($state)
{
         for ($row=0; $row<4; $row++){ // for all 16 bytes in the (4x4-byte) State
            for ($column=0; $column<4; $column++){ // for all 16 bytes in the (4x4-byte) State
               $_SESSION['debug'] .= "state na invSubBytes : state[$row][$column]=" . $state[$row][$column] .
                  "-->" . self::$InvS_Box[$state[$row][$column]]."\n";
// here we actually do stuff:
               $state[$row][$column] = self::$InvS_Box[$state[$row][$column]];
            }
         }
         return $state;
      } // end function invSubBytes

      public function shiftRows($state)
      {
         $temp = array(); //create temporary array for shifting
         for ($row=0; $row<4; $row++){
            for ($column=0; $column<4; $column++){
               //shiftleft the rows n positions for row n, so 0 for row 0, 1 position for row 1, etc.
               $temp[$row][$column] = $state[$row][($column+$row)%4];
            }
         }
         //now, copy back the result from temp to state
         for ($row=0; $row<4; $row++){
            for ($column=0; $column<4; $column++){
               $state[$row][$column] = $temp[$row][$column];
              $_SESSION['debug'] .= "ShiftRows : state[$row][$column]=".$state[$row][$column]."\n";
            }
         }
         return $state;
      } // end function shiftRows

public function invShiftRows($state)
      {	
         $temp = array(); //create temporary array for shifting
         for ($row=0; $row<4; $row++){
            for ($column=0; $column<4; $column++){
               //shiftleft and move factor to left side of equasion.
               $temp[$row][($column+$row)%4] = $state[$row][$column];
            }
         }

         //now, copy back the result from temp to state
         for ($row=0; $row<4; $row++){
            for ($column=0; $column<4; $column++){
               $state[$row][$column] = $temp[$row][$column];
               $_SESSION['debug'] .= "state after invShiftRows[$row][$column]=".$state[$row][$column]." in HEX :".dechex($state[$row][$column])."\n";
            }
         }
         return $state;
      } // end function invShiftRows

      public static function mixColumns($state)
      {
         //multiplication tables taken from http://en.wikipedia.org/wiki/Rijndael_mix_columns
         static $mul2 = array(
            0x00,0x02,0x04,0x06,0x08,0x0a,0x0c,0x0e,0x10,0x12,0x14,0x16,0x18,0x1a,0x1c,0x1e,
            0x20,0x22,0x24,0x26,0x28,0x2a,0x2c,0x2e,0x30,0x32,0x34,0x36,0x38,0x3a,0x3c,0x3e,
            0x40,0x42,0x44,0x46,0x48,0x4a,0x4c,0x4e,0x50,0x52,0x54,0x56,0x58,0x5a,0x5c,0x5e,
            0x60,0x62,0x64,0x66,0x68,0x6a,0x6c,0x6e,0x70,0x72,0x74,0x76,0x78,0x7a,0x7c,0x7e,
            0x80,0x82,0x84,0x86,0x88,0x8a,0x8c,0x8e,0x90,0x92,0x94,0x96,0x98,0x9a,0x9c,0x9e,
            0xa0,0xa2,0xa4,0xa6,0xa8,0xaa,0xac,0xae,0xb0,0xb2,0xb4,0xb6,0xb8,0xba,0xbc,0xbe,
            0xc0,0xc2,0xc4,0xc6,0xc8,0xca,0xcc,0xce,0xd0,0xd2,0xd4,0xd6,0xd8,0xda,0xdc,0xde,
            0xe0,0xe2,0xe4,0xe6,0xe8,0xea,0xec,0xee,0xf0,0xf2,0xf4,0xf6,0xf8,0xfa,0xfc,0xfe,
            0x1b,0x19,0x1f,0x1d,0x13,0x11,0x17,0x15,0x0b,0x09,0x0f,0x0d,0x03,0x01,0x07,0x05,
            0x3b,0x39,0x3f,0x3d,0x33,0x31,0x37,0x35,0x2b,0x29,0x2f,0x2d,0x23,0x21,0x27,0x25,
            0x5b,0x59,0x5f,0x5d,0x53,0x51,0x57,0x55,0x4b,0x49,0x4f,0x4d,0x43,0x41,0x47,0x45,
            0x7b,0x79,0x7f,0x7d,0x73,0x71,0x77,0x75,0x6b,0x69,0x6f,0x6d,0x63,0x61,0x67,0x65,
            0x9b,0x99,0x9f,0x9d,0x93,0x91,0x97,0x95,0x8b,0x89,0x8f,0x8d,0x83,0x81,0x87,0x85,
            0xbb,0xb9,0xbf,0xbd,0xb3,0xb1,0xb7,0xb5,0xab,0xa9,0xaf,0xad,0xa3,0xa1,0xa7,0xa5,
            0xdb,0xd9,0xdf,0xdd,0xd3,0xd1,0xd7,0xd5,0xcb,0xc9,0xcf,0xcd,0xc3,0xc1,0xc7,0xc5,
            0xfb,0xf9,0xff,0xfd,0xf3,0xf1,0xf7,0xf5,0xeb,0xe9,0xef,0xed,0xe3,0xe1,0xe7,0xe5);

         static $mul3 = array(
            0x00,0x03,0x06,0x05,0x0c,0x0f,0x0a,0x09,0x18,0x1b,0x1e,0x1d,0x14,0x17,0x12,0x11,
            0x30,0x33,0x36,0x35,0x3c,0x3f,0x3a,0x39,0x28,0x2b,0x2e,0x2d,0x24,0x27,0x22,0x21,
            0x60,0x63,0x66,0x65,0x6c,0x6f,0x6a,0x69,0x78,0x7b,0x7e,0x7d,0x74,0x77,0x72,0x71,
            0x50,0x53,0x56,0x55,0x5c,0x5f,0x5a,0x59,0x48,0x4b,0x4e,0x4d,0x44,0x47,0x42,0x41,
            0xc0,0xc3,0xc6,0xc5,0xcc,0xcf,0xca,0xc9,0xd8,0xdb,0xde,0xdd,0xd4,0xd7,0xd2,0xd1,
            0xf0,0xf3,0xf6,0xf5,0xfc,0xff,0xfa,0xf9,0xe8,0xeb,0xee,0xed,0xe4,0xe7,0xe2,0xe1,
            0xa0,0xa3,0xa6,0xa5,0xac,0xaf,0xaa,0xa9,0xb8,0xbb,0xbe,0xbd,0xb4,0xb7,0xb2,0xb1,
            0x90,0x93,0x96,0x95,0x9c,0x9f,0x9a,0x99,0x88,0x8b,0x8e,0x8d,0x84,0x87,0x82,0x81,
            0x9b,0x98,0x9d,0x9e,0x97,0x94,0x91,0x92,0x83,0x80,0x85,0x86,0x8f,0x8c,0x89,0x8a,
            0xab,0xa8,0xad,0xae,0xa7,0xa4,0xa1,0xa2,0xb3,0xb0,0xb5,0xb6,0xbf,0xbc,0xb9,0xba,
            0xfb,0xf8,0xfd,0xfe,0xf7,0xf4,0xf1,0xf2,0xe3,0xe0,0xe5,0xe6,0xef,0xec,0xe9,0xea,
            0xcb,0xc8,0xcd,0xce,0xc7,0xc4,0xc1,0xc2,0xd3,0xd0,0xd5,0xd6,0xdf,0xdc,0xd9,0xda,
            0x5b,0x58,0x5d,0x5e,0x57,0x54,0x51,0x52,0x43,0x40,0x45,0x46,0x4f,0x4c,0x49,0x4a,
            0x6b,0x68,0x6d,0x6e,0x67,0x64,0x61,0x62,0x73,0x70,0x75,0x76,0x7f,0x7c,0x79,0x7a,
            0x3b,0x38,0x3d,0x3e,0x37,0x34,0x31,0x32,0x23,0x20,0x25,0x26,0x2f,0x2c,0x29,0x2a,
            0x0b,0x08,0x0d,0x0e,0x07,0x04,0x01,0x02,0x13,0x10,0x15,0x16,0x1f,0x1c,0x19,0x1a);


         for ($c=0; $c<4; $c++) {
            $a = array(4); // 'a' is a copy of the current column from 's'
            for ($i=0; $i<4; $i++) $a[$i] = $state[$i][$c];
            
            $_SESSION['debug'] .= "\nMixColumns : a is copy column from state: " . implode(",", $a);
            $state[0][$c] = $mul2[$a[0]] ^ $mul3[$a[1]] ^ $a[2] ^ $a[3]; // 2*a0 + 3*a1 + a2 + a3
            $state[1][$c] = $a[0] ^ $mul2[$a[1]] ^ $mul3[$a[2]] ^ $a[3]; // a0 * 2*a1 + 3*a2 + a3
            $state[2][$c] = $a[0] ^ $a[1] ^ $mul2[$a[2]] ^ $mul3[$a[3]]; // a0 + a1 + 2*a2 + 3*a3
            $state[3][$c] = $mul3[$a[0]] ^ $a[1] ^ $a[2] ^ $mul2[$a[3]]; // 3*a0 + a1 + a2 + 2*a3

           $_SESSION['debug'] .= "\nresulting column: ".$state[0][$c].",".$state[1][$c].",".$state[2][$c].",".$state[3][$c];
         }
         return $state;
      } // end function mixColumns

public static function invMixColumns($state)
      {
         //multiplication tables taken from http://en.wikipedia.org/wiki/Rijndael_mix_columns
         static $mul9 = array(
0x00,0x09,0x12,0x1b,0x24,0x2d,0x36,0x3f,0x48,0x41,0x5a,0x53,0x6c,0x65,0x7e,0x77,
0x90,0x99,0x82,0x8b,0xb4,0xbd,0xa6,0xaf,0xd8,0xd1,0xca,0xc3,0xfc,0xf5,0xee,0xe7,
0x3b,0x32,0x29,0x20,0x1f,0x16,0x0d,0x04,0x73,0x7a,0x61,0x68,0x57,0x5e,0x45,0x4c,
0xab,0xa2,0xb9,0xb0,0x8f,0x86,0x9d,0x94,0xe3,0xea,0xf1,0xf8,0xc7,0xce,0xd5,0xdc,
0x76,0x7f,0x64,0x6d,0x52,0x5b,0x40,0x49,0x3e,0x37,0x2c,0x25,0x1a,0x13,0x08,0x01,
0xe6,0xef,0xf4,0xfd,0xc2,0xcb,0xd0,0xd9,0xae,0xa7,0xbc,0xb5,0x8a,0x83,0x98,0x91,
0x4d,0x44,0x5f,0x56,0x69,0x60,0x7b,0x72,0x05,0x0c,0x17,0x1e,0x21,0x28,0x33,0x3a,
0xdd,0xd4,0xcf,0xc6,0xf9,0xf0,0xeb,0xe2,0x95,0x9c,0x87,0x8e,0xb1,0xb8,0xa3,0xaa,
0xec,0xe5,0xfe,0xf7,0xc8,0xc1,0xda,0xd3,0xa4,0xad,0xb6,0xbf,0x80,0x89,0x92,0x9b,
0x7c,0x75,0x6e,0x67,0x58,0x51,0x4a,0x43,0x34,0x3d,0x26,0x2f,0x10,0x19,0x02,0x0b,
0xd7,0xde,0xc5,0xcc,0xf3,0xfa,0xe1,0xe8,0x9f,0x96,0x8d,0x84,0xbb,0xb2,0xa9,0xa0,
0x47,0x4e,0x55,0x5c,0x63,0x6a,0x71,0x78,0x0f,0x06,0x1d,0x14,0x2b,0x22,0x39,0x30,
0x9a,0x93,0x88,0x81,0xbe,0xb7,0xac,0xa5,0xd2,0xdb,0xc0,0xc9,0xf6,0xff,0xe4,0xed,
0x0a,0x03,0x18,0x11,0x2e,0x27,0x3c,0x35,0x42,0x4b,0x50,0x59,0x66,0x6f,0x74,0x7d,
0xa1,0xa8,0xb3,0xba,0x85,0x8c,0x97,0x9e,0xe9,0xe0,0xfb,0xf2,0xcd,0xc4,0xdf,0xd6,
0x31,0x38,0x23,0x2a,0x15,0x1c,0x07,0x0e,0x79,0x70,0x6b,0x62,0x5d,0x54,0x4f,0x46
            );

         static $mul11 = array(
0x00,0x0b,0x16,0x1d,0x2c,0x27,0x3a,0x31,0x58,0x53,0x4e,0x45,0x74,0x7f,0x62,0x69,
0xb0,0xbb,0xa6,0xad,0x9c,0x97,0x8a,0x81,0xe8,0xe3,0xfe,0xf5,0xc4,0xcf,0xd2,0xd9,
0x7b,0x70,0x6d,0x66,0x57,0x5c,0x41,0x4a,0x23,0x28,0x35,0x3e,0x0f,0x04,0x19,0x12,
0xcb,0xc0,0xdd,0xd6,0xe7,0xec,0xf1,0xfa,0x93,0x98,0x85,0x8e,0xbf,0xb4,0xa9,0xa2,
0xf6,0xfd,0xe0,0xeb,0xda,0xd1,0xcc,0xc7,0xae,0xa5,0xb8,0xb3,0x82,0x89,0x94,0x9f,
0x46,0x4d,0x50,0x5b,0x6a,0x61,0x7c,0x77,0x1e,0x15,0x08,0x03,0x32,0x39,0x24,0x2f,
0x8d,0x86,0x9b,0x90,0xa1,0xaa,0xb7,0xbc,0xd5,0xde,0xc3,0xc8,0xf9,0xf2,0xef,0xe4,
0x3d,0x36,0x2b,0x20,0x11,0x1a,0x07,0x0c,0x65,0x6e,0x73,0x78,0x49,0x42,0x5f,0x54,
0xf7,0xfc,0xe1,0xea,0xdb,0xd0,0xcd,0xc6,0xaf,0xa4,0xb9,0xb2,0x83,0x88,0x95,0x9e,
0x47,0x4c,0x51,0x5a,0x6b,0x60,0x7d,0x76,0x1f,0x14,0x09,0x02,0x33,0x38,0x25,0x2e,
0x8c,0x87,0x9a,0x91,0xa0,0xab,0xb6,0xbd,0xd4,0xdf,0xc2,0xc9,0xf8,0xf3,0xee,0xe5,
0x3c,0x37,0x2a,0x21,0x10,0x1b,0x06,0x0d,0x64,0x6f,0x72,0x79,0x48,0x43,0x5e,0x55,
0x01,0x0a,0x17,0x1c,0x2d,0x26,0x3b,0x30,0x59,0x52,0x4f,0x44,0x75,0x7e,0x63,0x68,
0xb1,0xba,0xa7,0xac,0x9d,0x96,0x8b,0x80,0xe9,0xe2,0xff,0xf4,0xc5,0xce,0xd3,0xd8,
0x7a,0x71,0x6c,0x67,0x56,0x5d,0x40,0x4b,0x22,0x29,0x34,0x3f,0x0e,0x05,0x18,0x13,
0xca,0xc1,0xdc,0xd7,0xe6,0xed,0xf0,0xfb,0x92,0x99,0x84,0x8f,0xbe,0xb5,0xa8,0xa3
            );

static $mul13 = array(
0x00,0x0d,0x1a,0x17,0x34,0x39,0x2e,0x23,0x68,0x65,0x72,0x7f,0x5c,0x51,0x46,0x4b,
0xd0,0xdd,0xca,0xc7,0xe4,0xe9,0xfe,0xf3,0xb8,0xb5,0xa2,0xaf,0x8c,0x81,0x96,0x9b,
0xbb,0xb6,0xa1,0xac,0x8f,0x82,0x95,0x98,0xd3,0xde,0xc9,0xc4,0xe7,0xea,0xfd,0xf0,
0x6b,0x66,0x71,0x7c,0x5f,0x52,0x45,0x48,0x03,0x0e,0x19,0x14,0x37,0x3a,0x2d,0x20,
0x6d,0x60,0x77,0x7a,0x59,0x54,0x43,0x4e,0x05,0x08,0x1f,0x12,0x31,0x3c,0x2b,0x26,
0xbd,0xb0,0xa7,0xaa,0x89,0x84,0x93,0x9e,0xd5,0xd8,0xcf,0xc2,0xe1,0xec,0xfb,0xf6,
0xd6,0xdb,0xcc,0xc1,0xe2,0xef,0xf8,0xf5,0xbe,0xb3,0xa4,0xa9,0x8a,0x87,0x90,0x9d,
0x06,0x0b,0x1c,0x11,0x32,0x3f,0x28,0x25,0x6e,0x63,0x74,0x79,0x5a,0x57,0x40,0x4d,
0xda,0xd7,0xc0,0xcd,0xee,0xe3,0xf4,0xf9,0xb2,0xbf,0xa8,0xa5,0x86,0x8b,0x9c,0x91,
0x0a,0x07,0x10,0x1d,0x3e,0x33,0x24,0x29,0x62,0x6f,0x78,0x75,0x56,0x5b,0x4c,0x41,
0x61,0x6c,0x7b,0x76,0x55,0x58,0x4f,0x42,0x09,0x04,0x13,0x1e,0x3d,0x30,0x27,0x2a,
0xb1,0xbc,0xab,0xa6,0x85,0x88,0x9f,0x92,0xd9,0xd4,0xc3,0xce,0xed,0xe0,0xf7,0xfa,
0xb7,0xba,0xad,0xa0,0x83,0x8e,0x99,0x94,0xdf,0xd2,0xc5,0xc8,0xeb,0xe6,0xf1,0xfc,
0x67,0x6a,0x7d,0x70,0x53,0x5e,0x49,0x44,0x0f,0x02,0x15,0x18,0x3b,0x36,0x21,0x2c,
0x0c,0x01,0x16,0x1b,0x38,0x35,0x22,0x2f,0x64,0x69,0x7e,0x73,0x50,0x5d,0x4a,0x47,
0xdc,0xd1,0xc6,0xcb,0xe8,0xe5,0xf2,0xff,0xb4,0xb9,0xae,0xa3,0x80,0x8d,0x9a,0x97
);

static $mul14 = array(
0x00,0x0e,0x1c,0x12,0x38,0x36,0x24,0x2a,0x70,0x7e,0x6c,0x62,0x48,0x46,0x54,0x5a,
0xe0,0xee,0xfc,0xf2,0xd8,0xd6,0xc4,0xca,0x90,0x9e,0x8c,0x82,0xa8,0xa6,0xb4,0xba,
0xdb,0xd5,0xc7,0xc9,0xe3,0xed,0xff,0xf1,0xab,0xa5,0xb7,0xb9,0x93,0x9d,0x8f,0x81,
0x3b,0x35,0x27,0x29,0x03,0x0d,0x1f,0x11,0x4b,0x45,0x57,0x59,0x73,0x7d,0x6f,0x61,
0xad,0xa3,0xb1,0xbf,0x95,0x9b,0x89,0x87,0xdd,0xd3,0xc1,0xcf,0xe5,0xeb,0xf9,0xf7,
0x4d,0x43,0x51,0x5f,0x75,0x7b,0x69,0x67,0x3d,0x33,0x21,0x2f,0x05,0x0b,0x19,0x17,
0x76,0x78,0x6a,0x64,0x4e,0x40,0x52,0x5c,0x06,0x08,0x1a,0x14,0x3e,0x30,0x22,0x2c,
0x96,0x98,0x8a,0x84,0xae,0xa0,0xb2,0xbc,0xe6,0xe8,0xfa,0xf4,0xde,0xd0,0xc2,0xcc,
0x41,0x4f,0x5d,0x53,0x79,0x77,0x65,0x6b,0x31,0x3f,0x2d,0x23,0x09,0x07,0x15,0x1b,
0xa1,0xaf,0xbd,0xb3,0x99,0x97,0x85,0x8b,0xd1,0xdf,0xcd,0xc3,0xe9,0xe7,0xf5,0xfb,
0x9a,0x94,0x86,0x88,0xa2,0xac,0xbe,0xb0,0xea,0xe4,0xf6,0xf8,0xd2,0xdc,0xce,0xc0,
0x7a,0x74,0x66,0x68,0x42,0x4c,0x5e,0x50,0x0a,0x04,0x16,0x18,0x32,0x3c,0x2e,0x20,
0xec,0xe2,0xf0,0xfe,0xd4,0xda,0xc8,0xc6,0x9c,0x92,0x80,0x8e,0xa4,0xaa,0xb8,0xb6,
0x0c,0x02,0x10,0x1e,0x34,0x3a,0x28,0x26,0x7c,0x72,0x60,0x6e,0x44,0x4a,0x58,0x56,
0x37,0x39,0x2b,0x25,0x0f,0x01,0x13,0x1d,0x47,0x49,0x5b,0x55,0x7f,0x71,0x63,0x6d,
0xd7,0xd9,0xcb,0xc5,0xef,0xe1,0xf3,0xfd,0xa7,0xa9,0xbb,0xb5,0x9f,0x91,0x83,0x8d
);

         for ($c=0; $c<4; $c++) {
            $a = array(4); // 'a' is a copy of the current column from 's'
            for ($i=0; $i<4; $i++) $a[$i] = $state[$i][$c];
            
            $_SESSION['debug'] .= "\n invMixColumns : a is copy column from state: " . implode(",", $a);
            $state[0][$c] = $mul14[$a[0]] ^ $mul11[$a[1]] ^ $mul13[$a[2]] ^ $mul9[$a[3]]; // 14*a0 + 11*a1 + 13*a2 + 9*a3
            $state[1][$c] = $mul9[$a[0]] ^ $mul14[$a[1]] ^ $mul11[$a[2]] ^ $mul13[$a[3]]; // 9*a0 * 14*a1 + 11*a2 + 13*a3
            $state[2][$c] = $mul13[$a[0]] ^ $mul9[$a[1]] ^ $mul14[$a[2]] ^ $mul11[$a[3]]; // 13*a0 + 9*a1 + 14*a2 + 11*a3
            $state[3][$c] = $mul11[$a[0]] ^ $mul13[$a[1]] ^ $mul9[$a[2]] ^ $mul14[$a[3]]; // 11*a0 + 13*a1 + 9*a2 + 14*a3

            $_SESSION['debug'] .= "\nresulting column: ".$state[0][$c].",".$state[1][$c].",".$state[2][$c].",".$state[3][$c];
			$_SESSION['debug'] .= "\ncolumn in Hexade: ".dechex($state[0][$c]).",".dechex($state[1][$c]).",".dechex($state[2][$c]).",".dechex($state[3][$c]."\n");
         }
         return $state;
      } // end function mixColumns

      public function addRoundKey($state, $w, $rnd) // xor Round Key into state S [§5.1.4]
      {
         $_SESSION['debug'] .= "\naddRoundKey:\n";
         for ($r=0; $r<4; $r++) {
            for ($c=0; $c<4; $c++){
               $_SESSION['debug'] .= "state[".$r."][".$c."]=".$state[$r][$c]." XOR ".$w[$rnd*4+$c][$r]."=";
               $state[$r][$c] ^= $w[$rnd*4+$c][$r];
              $_SESSION['debug'] .= $state[$r][$c]."  In HEX: ".dechex($state[$r][$c])."\n";
            }
         }
         return $state;
      } // end function addRoundKey

      public static function keyExpansion($key) // generate Key Schedule from Key
      {
         $_SESSION['debug'] .= "keyExpansion:\n";
         $Nk = count($key)/4; // key length (in words): 4/6/8 for 128/192/256-bit keys
         $Nr = $Nk + 6; // no of rounds: 10/12/14 for 128/192/256-bit keys
  
         $w = array();
         $temp = array();
         $_SESSION['debug'] .= "key[0]=";
         for ($i=0; $i<$Nk; $i++) {
            $r = array($key[4*$i], $key[4*$i+1], $key[4*$i+2], $key[4*$i+3]);
            $w[$i] = $r;
            $_SESSION['debug'] .= "w[".$i."]=";
            for ($n=0; $n<4; $n++) $_SESSION['debug'] .= dechex($w[$i][$n])." ";
         }
         $_SESSION['debug'] .= "\nkey[1]=";
         for ($i=$Nk; $i<(4*($Nr+1)); $i++) {
            $w[$i] = array();
            for ($t=0; $t<4; $t++) $temp[$t] = $w[$i-1][$t];
            if ($i % $Nk == 0) {
              $temp = self::subWord(self::rotWord($temp));
            for ($t=0; $t<4; $t++) $temp[$t] ^= self::$rCon[$i/$Nk][$t];
            } else if ($Nk > 6 && $i%$Nk == 4) {
            $temp = self::subWord($temp);
            }
            $_SESSION['debug'] .= "w[".$i."]=";
            for ($t=0; $t<4; $t++) {
               $w[$i][$t] = $w[$i-$Nk][$t] ^ $temp[$t];
               $_SESSION['debug'] .= dechex($w[$i][$t])." ";
            }
            if (((($i+1)%4)==0)&&($i<4*$Nr)) $_SESSION['debug'] .= "\nkey[".(($i+1)/4)."]=";
         }
         return $w;
      }// end function keyExpansion

      private static function subWord($w) // apply SBox to 4-byte word w
      {
         for ($i=0; $i<4; $i++) $w[$i] = self::$sBox[$w[$i]];
         return $w;
      }
  
      private static function rotWord($w) // rotate 4-byte word w left by one byte
      {
         $tmp = $w[0];
         for ($i=0; $i<3; $i++) $w[$i] = $w[$i+1];
         $w[3] = $tmp;
         return $w;
      }
	  
	  public function xorState($state1, $state2)
	  {
		for($i = 0 ;$i<4;$i++)
			{
				for($k=0;$k<4;$k++)
				{
					$result[$i][$k] = $state1[$i][$k] ^ $state2[$i][$k]; // result = matrix1[1][1] XOR matrix2[1][1]
				}
			}
		return $result;	  
	  }
	  

	  
   } //end class AesSubBytes

?>