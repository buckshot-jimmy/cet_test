<?php

//$a = ["a" => 1, "b" => 2, 1 => 1, "e" => 77];
//$b = ["b" => 3, "c" => 4, 3 => 2, "d" => 99];
//
//$result = array_merge($a, $b);
//print_r($result);
//var_dump(isset($result["a"]));
//echo isset($result["a"]);
//
//$a = ["a" => 1, "b" => 2, 1 => 1, "e" => 77];
//$b = ["b" => 3, "c" => 4, 3 => 2, "d" => 99];
//
//$result = $a + $b;
//print_r($result);
//echo array_key_exists("o", $result);
//
//print_r(array_values($result));

//echo "adsa2" + "a";

//$words = ['coffee', 'two', 'red'];
//list(, , $color) = $words;
//echo $color;
//$c = 'abc';
//$d = hash('sha256', $c);
//echo $d;
//echo $number;
//echo $drink;

function twoSum($nums, $target) {
    $map = [];

    foreach ($nums as $i => $number) {
        $diff = $target - $number;

        if (isset($map[$diff])) {
            print_r([$map[$diff], $i]);
            return [$map[$diff], $i];
        }

        $map[$number] = $i;
    }

    return [];
}
$nums = [4, -9, 12, 0, 7, 3, 6, 1, 5, 15, 9];
$target = 8;
call_user_func('twoSum', $nums, $target);

function isAnagram($s, $t) {
    if (strlen($s) !== strlen($t)) {
        return false;
    }

    $count = [];

    for ($i = 0; $i < strlen($s); $i++) {
        $count[$s[$i]] = ($count[$s[$i]] ?? 0) + 1; // count occurancies in first array and add 1 if exists
        $count[$t[$i]] = ($count[$t[$i]] ?? 0) - 1; // count occurancies in second array and subtract 1 if exists
    }

    foreach ($count as $value) {
        if ($value !== 0) {
            return false;
        }
    }

    return true;
}
//call_user_func('isAnagram', 'anagram', 'mgranaa');

// sliding window technique
function lengthOfLongestSubstring($string) {
    $seen = [];
    $left = 0;
    $maxLen = 0;
    $bestStart = 0;

    for ($right = 0; $right < strlen($string); $right++) {
        $char = $string[$right];

        // have we seen this character before? is it in the window?
        if (isset($seen[$char]) && $seen[$char] >= $left) {
            $left = $seen[$char] + 1;
        }

        $seen[$char] = $right;

        $currentLen = $right - $left + 1;

        if ($currentLen > $maxLen) {
            $maxLen = $currentLen;
            $bestStart = $left;
        }

        $inter = substr($string, $bestStart, $maxLen);
        $window = substr($string, $left, $currentLen);
    }

    echo $maxLen . " -> "; // the searched substring length
    echo substr($string, $bestStart, $maxLen); // the searched substring

    return $maxLen;
}
lengthOfLongestSubstring('abcatyu');
//call_user_func('lengthOfLongestSubstring', 'abcytabcbbyuiopkjl');

function fibonacci($n) {
    echo "Fibonacci sequence: \n";

    $a = 0;
    $b = 1;

    for ($i = 2; $i < $n; $i++) {
        echo $a . " ";

        $next = $a + $b;
        $a = $b;
        $b = $next;
    }

    echo PHP_EOL;
    echo $b;
    echo PHP_EOL;
}
fibonacci(8);

function quickSort(array $arr): array
{
    $length = count($arr);

    if ($length <= 1) {
        return $arr;
    }

    $pivot = $arr[intdiv($length, 2)];

    $left = [];
    $equal = [];
    $right = [];

    foreach ($arr as $value) {
        if ($value < $pivot) {
            $left[] = $value;
        } elseif ($value > $pivot) {
            $right[] = $value;
        } else {
            $equal[] = $value;
        }
    }

    $orderLeft = quickSort($left);
    $orderRight = quickSort($right);

    return array_merge(
        $orderLeft,
        $equal,
        $orderRight
    );
}

print_r(quickSort([5, 1, 4, 2, 8]));

function bubbleSort(&$arr) {
    $n = count($arr);
    // Outer loop (number of passes)
    for ($i = 0; $i < $n - 1; $i++) {
        $swapped = false;
        // Inner loop (comparisons)
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($arr[$j] > $arr[$j + 1]) {
                // Swap elements
                $temp = $arr[$j];
                $arr[$j] = $arr[$j + 1];
                $arr[$j + 1] = $temp;

                $swapped = true;
            }
        }
        // If no swaps happened, array is already sorted
        if (!$swapped) {
            break;
        }
    }
}

// Example array
$numbers = [5, 1, 4, 8, 2];

bubbleSort($numbers);
print_r($numbers);

function bestSumDownwardTreePath(array $parent, array $values): int
{
    $n = count($parent);

    // Build adjacency list
    $children = array_fill(0, $n, []);
    $root = 0;

    for ($i = 0; $i < $n; $i++) {
        if ($parent[$i] == -1) {
            $root = $i;
        } else {
            $children[$parent[$i]][] = $i;
        }
    }

    // We need post-order traversal → use two stacks
    $stack1 = [$root];
    $stack2 = [];

    while (!empty($stack1)) {
        $node = array_pop($stack1);
        $stack2[] = $node;

        foreach ($children[$node] as $c) {
            $stack1[] = $c;
        }
    }

    // DP array: best downward path starting at each node
    $dp = array_fill(0, $n, 0);
    $maxSum = PHP_INT_MIN;

    // Process nodes in post-order
    while (!empty($stack2)) {
        $node = array_pop($stack2);

        $bestChild = 0; // ignore negative children

        foreach ($children[$node] as $c) {
            $bestChild = max($bestChild, $dp[$c]);
        }

        $dp[$node] = $values[$node] + $bestChild;

        $maxSum = max($maxSum, $dp[$node]);
    }

    return $maxSum;
}

// Example
$parent = [-1, 0, 1, 2, 0, 4, 1];
$values = [5, 7, -10, 4, 15, 2, 3];

echo bestSumDownwardTreePath($parent, $values); // 20
