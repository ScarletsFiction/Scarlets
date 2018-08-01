<!DOCTYPE html><html><head>
  <title>Perfomance Test</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head><body><div class="container">
  <h2>Perfomance Test</h2>
  <p>This test describe your PHP interpreter performance in 1000k iteration</p>    

<?php
session_start();

// Reset
if(isset($_GET['reset']))
{
   session_destroy();
   header('Location: perf.php');
   exit;
}

function array_average($a)
{
   $sum = 0;
   foreach($a as $v) $sum += $v;
   return round($sum / count($a));
}


// Start session to store averages
if(!isset($_SESSION['var_overhead'])) $_SESSION['var_overhead'] = array();
if(!isset($_SESSION['func_overhead'])) $_SESSION['func_overhead'] = array();
if(!isset($_SESSION['test1'])) $_SESSION['test1'] = array();
if(!isset($_SESSION['test2'])) $_SESSION['test2'] = array();
if(!isset($_SESSION['test3'])) $_SESSION['test3'] = array();
if(!isset($_SESSION['test4'])) $_SESSION['test4'] = array();


// Test variable for assignment performance
$test = '';


// Class static attribute
class test1 {
   public static $data = 'Hello world!';
}

// Class attribute
class test2 {
   public $data = 'Hello world!';
}
$test2 = new test2();

// Array element
$test3 = [
   'data' => 'Hello world!'
];

// Object attribute
$test4 = json_decode('{"data":"Hello world!"}');

// Function
function test5(){
   return 'Hello world!';
}

// Variable
$test6 = 'Hello world!';


// Get variable overhead
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = $test6;
$_SESSION['var_overhead'][] = microtime(true) * 1000 - $start;

// Get function overhead
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = test5();
$_SESSION['func_overhead'][] = microtime(true) * 1000 - $start;


// Test 1
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = test1::$data;
$_SESSION['test1'][] = microtime(true) * 1000 - $start;

// Test 2
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = $test2->data;
$_SESSION['test2'][] = microtime(true) * 1000 - $start;

// Test 3
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = $test3['data'];
$_SESSION['test3'][] = microtime(true) * 1000 - $start;

// Test 4
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = $test4->data;
$_SESSION['test4'][] = microtime(true) * 1000 - $start;


// Show results (each refresh increases accuracy average)
$var_overhead = array_average($_SESSION['var_overhead']);
$func_overhead = array_average($_SESSION['func_overhead']);
$avg_test1 = array_average($_SESSION['test1']) - $var_overhead;
$avg_test2 = array_average($_SESSION['test2']) - $var_overhead;
$avg_test3 = array_average($_SESSION['test3']) - $var_overhead;
$avg_test4 = array_average($_SESSION['test4']) - $var_overhead;

echo 'Variable load overhead: '.$var_overhead.' ms<br>';
echo 'Function call overhead: '.$func_overhead.' ms<br><br>';
?>     
  <table class="table table-striped">
    <thead>
     <tr>
       <th>Test Name</th>
       <th>Self</th>
       <th>Obtain variable data</th>
       <th>Call to function</th>
     </tr>
    </thead>
    <tbody>
<?php
echo '<tr><td>Static Class</td><td>'.$avg_test1.' ms</td><td>'.($avg_test1+$var_overhead).' ms </td><td>'.($avg_test1+$func_overhead).' ms</td></tr>';
echo '<tr><td>Class</td><td>'.$avg_test2.' ms</td><td>'.($avg_test2+$var_overhead).' ms</td><td>'.($avg_test2+$func_overhead).' ms</td></tr>';
echo '<tr><td>Array</td><td>'.$avg_test3.' ms</td><td>'.($avg_test3+$var_overhead).' ms</td><td>'.($avg_test3+$func_overhead).' ms</td></tr>';
echo '<tr><td>Object</td><td>'.$avg_test4.' ms</td><td>'.($avg_test4+$var_overhead).' ms</td><td>'.($avg_test4+$func_overhead).' ms</td></tr><br>';
?>

</tbody></table>
<?php
echo 'Average over '.count($_SESSION['var_overhead']).' cycles.<br>';
echo 'PHP Version: '.phpversion().'<br>';
echo '<a href="perf.php?reset=true">Reset</a>';
?>
</div></body></html>