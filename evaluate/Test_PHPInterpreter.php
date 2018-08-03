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
   header('Location: Test_PHPInterpreter.php');
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
if(!isset($_SESSION['test5'])) $_SESSION['test5'] = array();
if(!isset($_SESSION['test6'])) $_SESSION['test6'] = array();
if(!isset($_SESSION['test7'])) $_SESSION['test7'] = array();
if(!isset($_SESSION['test8'])) $_SESSION['test8'] = array();
if(!isset($_SESSION['test9'])) $_SESSION['test9'] = array();
if(!isset($_SESSION['test10'])) $_SESSION['test10'] = array();
if(!isset($_SESSION['test11'])) $_SESSION['test11'] = array();


// Calling function will give more overload rather than getting variable value
class TempObject {
  private $o_data = [];
  public function &__get($property){
      return $this->o_data[$property];
  }
  
  public function __set($property, $val){
      $this->o_data[$property] = &$val;
  }

  public function &ref(){
      return $this->o_data;
  }
}

class ObjectStatic { // This will be used for global ScarletsRegistry 
  public static $o_data = [];
}

// Test variable for assignment performance
$test = '';

// Function
function funcTest(){
   return 'Hello world!';
}

// Variable
$varTest = 'Hello world!';

// Class static attribute
class test1 {
   public static $data = 'Hello world!';
}

// Class attribute
class test2 {
   public $data = 'Hello world!';
   public $array = []; // Direct access is more faster rather than TempObject's getter and setter
}
$test2 = new test2();

// Array element
$test3 = [
   'data' => 'Hello world!'
];

// Object attribute
$test4 = json_decode('{"data":"Hello world!"}');

// Namespace
include_once "dummy_namespace.php";

define('Test6', 'Hello world!');

// Object anonymouse attribute
$test7 = new TempObject();
$test7->array = [];
$test7ref = &$test7->ref()['array'];
$test7->data = 'Hello world!';

// Object anonymouse attribute
$test8 = new stdClass(); // class@anonymouse and object cast from array have same performance
$test8->data = 'Hello world!';

// Object static attribute
ObjectStatic::$o_data['data'] = 'Hello world!';


// Get variable overhead
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = $varTest;
$_SESSION['var_overhead'][] = microtime(true) * 1000 - $start;

// Get function overhead
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = funcTest();
$_SESSION['func_overhead'][] = microtime(true) * 1000 - $start;

$var_overhead = array_average($_SESSION['var_overhead']);
$func_overhead = array_average($_SESSION['func_overhead']);

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

// Test 5
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = \dummy\_class::$data;
$_SESSION['test5'][] = microtime(true) * 1000 - $start;

// Test 6
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = Test6;
$_SESSION['test6'][] = microtime(true) * 1000 - $start;

// Test 7 (Slowest, no need to test anymore)
$start = microtime(true) * 1000;
//for($i = 0; $i < 1000000; $i ++) $test = &$test7->data;
//$_SESSION['test7'][] = microtime(true) * 1000 - $start;
$_SESSION['test7'][] = 1000;

// Test 8
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = $test8->data;
$_SESSION['test8'][] = microtime(true) * 1000 - $start;

// Test 9
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = ObjectStatic::$o_data['data'];
$_SESSION['test9'][] = microtime(true) * 1000 - $start;

for($i = 0; $i < 1000000; $i ++) $test3['a'.$i] = 'Hello world!';
for($i = 0; $i < 1000000; $i ++) $test8->{'a'.$i} = 'Hello world!';


$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = 'a'.$i;
$concatOverload = microtime(true) * 1000 - $start - $var_overhead;

// Test 10
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = $test3['a'.$i];
$_SESSION['test10'][] = microtime(true) * 1000 - $start - $concatOverload;

// Test 11
$start = microtime(true) * 1000;
for($i = 0; $i < 1000000; $i ++) $test = $test8->{'a'.$i};
$_SESSION['test11'][] = microtime(true) * 1000 - $start - $concatOverload;

// Show results (each refresh increases accuracy average)
$avg_test1 = array_average($_SESSION['test1']) - $var_overhead;
$avg_test2 = array_average($_SESSION['test2']) - $var_overhead;
$avg_test3 = array_average($_SESSION['test3']) - $var_overhead;
$avg_test4 = array_average($_SESSION['test4']) - $var_overhead;
$avg_test5 = array_average($_SESSION['test5']) - $var_overhead;
$avg_test6 = array_average($_SESSION['test6']) - $var_overhead;
$avg_test7 = array_average($_SESSION['test7']) - $var_overhead;
$avg_test8 = array_average($_SESSION['test8']) - $var_overhead;
$avg_test9 = array_average($_SESSION['test9']) - $var_overhead;
$avg_test10 = array_average($_SESSION['test10']) - $var_overhead;
$avg_test11 = array_average($_SESSION['test11']) - $var_overhead;

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
echo '<tr><td>Static Class</td><td>'.
  $avg_test1.' ms</td><td>'.
  ($avg_test1+$var_overhead).' ms </td><td>'.
  ($avg_test1+$func_overhead).' ms</td></tr>';

echo '<tr><td>Class</td><td>'.
  $avg_test2.' ms</td><td>'.
  ($avg_test2+$var_overhead).' ms</td><td>'.
  ($avg_test2+$func_overhead).' ms</td></tr>';

echo '<tr><td>Array</td><td>'.
  $avg_test3.' ms</td><td>'.
  ($avg_test3+$var_overhead).' ms</td><td>'.
  ($avg_test3+$func_overhead).' ms</td></tr>';

echo '<tr><td>Object (json)</td><td>'.
  $avg_test4.' ms</td><td>'.
  ($avg_test4+$var_overhead).' ms</td><td>'.
  ($avg_test4+$func_overhead).' ms</td></tr>';

echo '<tr><td>Namespace</td><td>'.
  $avg_test5.' ms</td><td>'.
  ($avg_test5+$var_overhead).' ms</td><td>'.
  ($avg_test5+$func_overhead).' ms</td></tr>';

echo '<tr><td>Constant</td><td>'.
  $avg_test6.' ms</td><td>'.
  ($avg_test6+$var_overhead).' ms</td><td>'.
  ($avg_test6+$func_overhead).' ms</td></tr>';

echo '<tr><td>Object (class getter & setter)</td><td>'.
  $avg_test7.' ms</td><td>&gt;'.
  ($avg_test7+$var_overhead).' ms</td><td>'.
  ($avg_test7+$func_overhead).' ms</td></tr>';

echo '<tr><td>Object (stdClass)</td><td>'.
  $avg_test8.' ms</td><td>'.
  ($avg_test8+$var_overhead).' ms</td><td>'.
  ($avg_test8+$func_overhead).' ms</td></tr>';

echo '<tr><td>ObjectStatic (class)</td><td>'.
  $avg_test9.' ms</td><td>'.
  ($avg_test9+$var_overhead).' ms</td><td>'.
  ($avg_test9+$func_overhead).' ms</td></tr>';

echo '<tr><td>Array indexing</td><td>'.
  $avg_test10.' ms</td><td>'.
  ($avg_test10+$var_overhead).' ms</td><td>'.
  ($avg_test10+$func_overhead).' ms</td></tr>';

echo '<tr><td>Object (stdClass) indexing</td><td>'.
  $avg_test11.' ms</td><td>'.
  ($avg_test11+$var_overhead).' ms</td><td>'.
  ($avg_test11+$func_overhead).' ms</td></tr>';
?>

</tbody></table>
<?php
echo 'Average over '.count($_SESSION['var_overhead']).' cycles.<br>';
echo 'PHP Version: '.phpversion().'<br>';
echo '<a href="Test_PHPInterpreter.php?reset=true">Reset</a><br>';
?>
</div></body></html>