<!DOCTYPE html>
<html lang="en">

<head>

   <meta charset="utf-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <meta name="description" content="">
   <meta name="author" content="">

   <title>OpenID Connect: Received Claims</title>

</head>

<body>

         <h3>
            Claims sent back from OpenID Connect via the Apache module
         </h3>
         <br/>


   <!-- OpenAthens attribtues -->
      <?php session_start(); ?>
         <h2>Claims</h2>
         <br/>
         <div class="row">

               <table class="table" style="width:80%;" border="1">
                 <?php foreach ($_SERVER as $key=>$value): ?>
                    <?php if ( preg_match("/OIDC_/i", $key) ): ?>
                       <tr>
                          <td data-toggle="tooltip" title=<?php echo $key; ?>><?php echo $key; ?></td>
                          <td data-toggle="tooltip" title=<?php echo $value; ?>><?php echo $value; ?></td>
                       </tr>
                    <?php endif; ?>
                 <?php endforeach; ?>
               </table>

</body>

</html>