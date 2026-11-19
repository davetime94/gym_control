<?php
include("conn.php");
include("components/security.php");




?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style1.css">
</head>
<style>
        .a_6{
        background-color: var(--hover);
        color: black;
    }
</style>
<body>

<?php include("components/navbar.php"); ?>

<main>
    <section>
        <?php include("components/sidebar.php"); ?>
    </section>


    <section>
        <div class="content_information">
            <div class="card" style="padding:30px;"> <?php include("components/table_logs.php"); ?></div>
        </div>
    </section>

</main>
    
</body>
<script src="script.js" > 
    
</script>
</html>