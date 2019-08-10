<?php

/*
---------------------------------------------------------------------------
| Unit Testing Folder
---------------------------------------------------------------------------
|
| In this folder you can add as many file as you want for testing
| But you shouldn't declare any function/class/variable outside of function
| scope if you don't want any conflict with other test file.
|
| Every test file will be tested by calling this command line
| $ scarlets test
|
*/

$describe("This is an example test");

// This test will return partial because 1 !== "1" but 1 == "1"
$it("compare number and string", function($assert){
	$assert::equal(1, "1");

	echo "henlo";
});

$it("doing calculation", function($assert){
	$assert::equal(5 + 2 * 10, 70);
});

$it("some condition", function($assert){
	$assert::true("human" === "human");

	// This will throw error and this test will fail
	$z = 234;
	$z*[12, 43];
});

$it("dummy stuff", function($assert){
	$arr = [];
	for ($i=0; $i < 100000; $i++) { 
		$arr[] = "$i: lorem ipsum dolor sit amet";
	}

	$assert::equal(count($arr), 100000);
});