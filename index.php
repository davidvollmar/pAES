<?php 
   session_start();
   include('includes/htmlhead.php');
 ?>

<body>

   <div id="wrapper">

      <?php include('includes/header.php'); ?>
      
      <?php include('includes/nav.php'); ?>
      
      <div id="content">
         <h2>Welcome</h2>
	      <p>
	         Adapt this website to your own standards and needs.<br>
	         This setup, design and content is only to get you started.<br>
         </p>
	      <h2>Contents</h2>
	      <p>
	         To learn about symmetric encryption, why not implement the current standard, AES!<br>
	         Since, the only common programming language amongst our different students is PHP, that's the language that will be used.
	         Maybe not the best or easiest language to perform all needed operations, but it can surely be done.
	         <br>This website contains a partial implementation of AES, to be completed.
	         <br>

      </div> <!-- end #content -->

      <?php include('includes/sidebar.php'); ?>
      
      <?php include('includes/footer.php'); ?>
      
	</div> <!-- End #wrapper -->

</body>

</html>

