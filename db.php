<?php
$host = 'db.rjk9vggjx7v0.supabase.co';
$dbname = 'postgres';
$user = 'umt2dcztkf6rt';
$password = 'd89k0usi6geq';
$port = '5432';
 
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = pg_connect($conn_string);
 
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}
?>
