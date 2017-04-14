<?php

function tokenise($source) {
  $patterns = [
    '/\{/',
    '/\}/',
    '/\<([a-z]+)/i',
    '/\/\>/',
    '/\>/',
    '/\<\/([a-z]+)\>/i'
  ];

  $parts = [];
  $left = $source;

  do {
    $matches = [];

    // match all the "special" parts of the source code
    foreach ($patterns as $pattern) {
      preg_match($pattern, $left, $next, PREG_OFFSET_CAPTURE);

      if (count($next)) {
        array_push($matches, $next);
      }
    }

    $offset = PHP_INT_MAX;
    $nearest = null;

    // find the nearest pattern match
    foreach ($matches as $next) {
      if ($next[0][1] < $offset) {
        $offset = $next[0][1];
        $nearest = $next;
      }
    }

    // get all the "normal" text before
    array_push($parts, substr($left, 0, $nearest[0][1]));

    // trim that text from $left
    $left = substr($left, $nearest[0][1]);

    // get the token text
    array_push($parts, substr($left, 0, strlen($nearest[0][0])));

    // trim the token from $left
    $left = substr($left, strlen($nearest[0][0]));

    if (count($matches) < 1) {
      // if no patterns matched, add the rest of the text
      array_push($parts, $left);
    }

  // keep doing this while patterns are matched
  } while (count($matches));

  return array_values(array_filter($parts));
}

function normalise(array $tokens) {
  $clean = [];

  for ($i = 0; $i < count($tokens); $i++) {

    // if this is the first token...
    if (count($clean) < 1) {
      goto add;
    }

    $previous = $clean[count($clean) - 1];

    $isAngle = ($tokens[$i] === ">");
    $shouldBeArrow = (substr($previous, -1) === "-");

    $isNotCurly = ($tokens[$i] !== "{");
    $shouldBeMember = (substr($previous, -2) === "->");

    if ($isAngle && $shouldBeArrow) {
      $last = array_pop($clean);
      $last .= ">";

      array_push($clean, $last);
    }

    else if ($isNotCurly && $shouldBeMember) {
      $last = array_pop($clean);
      $last .= $tokens[$i];

      array_push($clean, $last);
    }

    else {
      add:
        array_push($clean, $tokens[$i]);
    }
  }

  return array_filter($clean);
}

$parts = normalise(tokenise('
  function Tweet($props) {
    return (
      <div className="tweet">
        {$props->user->handle}: {$props->content}
      </div>
    )
  }
'));

print_r($parts);
