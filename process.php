<?php
function parseFDs($fdStr) {
    $fds = explode(',', $fdStr);
    $parsed = [];

    foreach ($fds as $fd) {
        list($lhs, $rhs) = explode('->', trim($fd));
        $parsed[] = [str_split(trim($lhs)), str_split(trim($rhs))];
    }
    return $parsed;
}

function closure($attrs, $fds) {
    $closure = $attrs;
    $changed = true;

    while ($changed) {
        $changed = false;
        foreach ($fds as [$lhs, $rhs]) {
            if (array_diff($lhs, $closure) === []) {
                foreach ($rhs as $attr) {
                    if (!in_array($attr, $closure)) {
                        $closure[] = $attr;
                        $changed = true;
                    }
                }
            }
        }
    }
    return $closure;
}

function isCandidateKey($attrs, $allAttrs, $fds) {
    $closureSet = closure($attrs, $fds);
    sort($closureSet);
    sort($allAttrs);
    return $closureSet === $allAttrs;
}

function findCandidateKeys($attributes, $fds) {
    $n = count($attributes);
    $candidateKeys = [];

    for ($i = 1; $i <= $n; $i++) {
        $comb = combinations($attributes, $i);
        foreach ($comb as $subset) {
            if (isCandidateKey($subset, $attributes, $fds)) {
                // Remove supersets of existing keys
                $isMinimal = true;
                foreach ($candidateKeys as $existingKey) {
                    if (array_diff($existingKey, $subset) === []) {
                        $isMinimal = false;
                        break;
                    }
                }
                if ($isMinimal) {
                    $candidateKeys[] = $subset;
                }
            }
        }
    }
    return $candidateKeys;
}

function combinations($arr, $k) {
    if ($k == 0) return [[]];
    if (empty($arr)) return [];

    $head = $arr[0];
    $tail = array_slice($arr, 1);

    $comb1 = combinations($tail, $k - 1);
    foreach ($comb1 as &$c) {
        array_unshift($c, $head);
    }

    $comb2 = combinations($tail, $k);

    return array_merge($comb1, $comb2);
}

function runNormalizationCheck($attributes, $fdStr) {
    $fds = parseFDs($fdStr);
    $candidateKeys = findCandidateKeys($attributes, $fds);

    echo "<h2>Results</h2>";
    echo "<p><strong>Attributes:</strong> " . implode(', ', $attributes) . "</p>";
    echo "<p><strong>Candidate Keys:</strong><ul>";
    foreach ($candidateKeys as $key) {
        echo "<li>{" . implode('', $key) . "}</li>";
    }
    echo "</ul></p>";

    echo "<h3>Normalization Check</h3>";
    echo "<p><strong>1NF:</strong> ✅ Assumed (atomic values)</p>";

    $is2NF = true;
    foreach ($fds as [$lhs, $rhs]) {
        foreach ($candidateKeys as $ck) {
            if (count(array_diff($lhs, $ck)) > 0 && count(array_intersect($lhs, $ck)) > 0) {
                $is2NF = false;
            }
        }
    }
    echo "<p><strong>2NF:</strong> " . ($is2NF ? "✅" : "❌") . "</p>";

    $is3NF = true;
    foreach ($fds as [$lhs, $rhs]) {
        $lhsIsKey = false;
        foreach ($candidateKeys as $ck) {
            if ($lhs == $ck) {
                $lhsIsKey = true;
                break;
            }
        }
        foreach ($rhs as $attr) {
            if (!in_array($attr, $lhs) && !$lhsIsKey) {
                $is3NF = false;
            }
        }
    }
    echo "<p><strong>3NF:</strong> " . ($is3NF ? "✅" : "❌") . "</p>";

    echo '<br><a href="index.html">← Back to Form</a>';
}

// === PROCESS FORM ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $attributes = str_split(str_replace(',', '', strtoupper(trim($_POST['attributes']))));
    $fdStr = strtoupper(trim($_POST['fds']));
    runNormalizationCheck($attributes, $fdStr);
}
?>