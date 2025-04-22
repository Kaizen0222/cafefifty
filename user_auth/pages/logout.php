<?php
session_start();
session_destroy();
header("Location: /cafe_fifty/index.php");
exit();
?>
