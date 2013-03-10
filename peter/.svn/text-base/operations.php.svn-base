<?php 
   session_start();
   include('includes/htmlhead.php'); 
?>

<body>

   <div id="wrapper">

      <?php include('includes/header.php'); ?>
      
      <?php include('includes/nav.php'); ?>
      
      <div id="content">
         <h2>AES operations</h2>
	      <p>Symmetric encryption in general uses transposition and substitution, so moving around e.g. bytes and replacing e.g. bytes.<br>
	      AES does these types of operations on blocks (also called 'State', see paragraph 3.4 in the <a href="http://csrc.nist.gov/publications/fips/fips197/fips-197.pdf">FIPS-197 standard</a>) of 128 bits, so 16 bytes of data.<br>
	      This happens in the different steps of the algorithm, SubBytes, ShiftRows, MixColumns<cr>
	      This page gives examples of the essential operations in these steps.<cr>
	      Because the PHP source code is listed, you can also see how these operations can be performed with PHP.<br><br>
	      </p>
         <?php
         if ( (empty($_SESSION['input'])) || (!isset($_SESSION['input'])) ) {
            $_SESSION['input'] = "12345689abcdefgh";
         }   
         if ( (empty($_SESSION['key'])) || (!isset($_SESSION['key'])) ) {
            $_SESSION['key'] = "11 22 33 44 55 66 77 88 99 00 AA BB CC DD EE FF";
         }         
      
         ?>

         <form method="post" action="aes.php">
            <label for="key">Key:</label>
            <input type="text" id="key" name="key" size="60" value="<?php echo $_SESSION['key']; ?>">
            <br>
            <label for="input">Input:</label>
            <input type="text" id="input" name="input" size="60" value="<?php echo $_SESSION['input']; ?>">
            <input checked id="ascii" name="format" type=radio value="ascii">
            <label for="ascii">Ascii</label>
            <input id="hex" name="format" type=radio value="hex">
            <label for="hex">Hex</label>
            <br><br>
            <input type="submit" name="operation" value="subBytes" />
            <input type="submit" name="operation" value="shiftRows" />
            <input type="submit" name="operation" value="mixColumns" />
            <input type="submit" name="operation" value="addRoundKey" />
            <br><br>
            <input type="submit" name="operation" value="encrypt" />
            <input type="submit" name="operation" value="decrypt" />

          </form>	      

          <br>
          <label for="output">Output:</label>
          <input type="text" id="output" name="output" size="60" value="<?php echo $_SESSION['output']; ?>" >
          
          <br><br>Debug info:<br>
          <textarea name="details" id="details" rows="20" cols="80"><?php echo $_SESSION['debug']; ?></textarea>

          <br><br>De source code:<br>
          <div id="source">
             <?php 
               show_source("aes.php");
               show_source("io.php");
             ?>
          </div> 

         <h3>SubBytes Transformation</h3>
	      <p> The SubBytes transformation is a substitution using a substitution table, 
	      the so-called <a href="http://en.wikipedia.org/wiki/Rijndael_S-box">S-box</a>.<br>
	      This S-box is the result of some specialized <a href="http://en.wikipedia.org/wiki/Finite_field_arithmetic#Rijndael.27s_finite_field">
	      <i>finite field</i> arithmetic</a>, but, for implementation a simple lookup in the S-box table will do the trick.<br>
         For a hexadecimal representation of a byte xy, the substition then results in the S-box value in row x, column y.
         </p>

         <h3>ShiftRow Transformation</h3>
         <p>
         In the Shiftrow operation, every row of the state is shifted in a cyclic way, so rotated a number of places:
         <ul>
         <li> The first row is not shifted, 
         <li> the second row is shifted one position to the left, </li>
         <li> the third row is shifted two positions to the left, </li>
         <li> the fourth row is shifted three positions to the left. </li>
         </ul>
         </p>

         <h3>MixColumns Transformation</h3>
         In the MixColumns operation each column in the state (treated as a four-term polynomial in Rijndael's Galois Field GF(2^8)) is multiplied with a fixed polynomial a(x).<br>
         Here, a(x)= 3x^3+x^2+x+2.  <br>
         For more information on this, see the AES standard <a href="http://csrc.nist.gov/publications/fips/fips197/fips-197.pdf">FIPS-197</a>:<br>
         <ul>
            <li>polynomial representation of bytes in paragraph 3.2</li> 
            <li>basic calculations like addition and multiplication in GF(2^8), in chapter 4</li>
            <li>the MixColumns transformation in paragraph 5.1.3</li>
         </ul>
         
         <p>
         </p>


          
      </div> <!-- end #content -->

      <?php include('includes/sidebar.php'); ?>
      
      <?php include('includes/footer.php'); ?>
      
	</div> <!-- End #wrapper -->

</body>

</html>

