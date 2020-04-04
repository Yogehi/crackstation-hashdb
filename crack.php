<?php
// modified "test.php" to look up a hash string immedietely
// root@KenGannonKaliVM:/media/root/yay/crackstation-hashdb# echo -n "Ken" | sha1sum
// 19f40a75cecc713623c04de47639dbac43cf9b56  -
// root@KenGannonKaliVM:/media/root/yay/crackstation-hashdb# php ./crack.php 19f40a75cecc713623c04de47639dbac43cf9b56
// [+] Successfully cracked [19f40a75cecc713623c04de47639dbac43cf9b56] using [sha1] as [Ken].
// [+] Successfully cracked [19f40a75cecc7136] using [sha1] as [Ken] (as partial match).
// [-] Failed to crack [19f40a75cecc713623c04de47639dbac43cf9b56] using [md5].
// [-] Failed to crack [19f40a75cecc713623c04de47639dbac43cf9b56] using [md5] (partial match).


require_once('LookupTable.php');
require_once('MoreHashes.php');

if (count($argv) !== 2) {
    echo "Usage: php test.php <hash string>\n";
    exit(1);
}

$algorithms = array("md5", "md5(md5)", "sha1", "NTLM", "LM");
$counter = 3;
$hash_string = $argv[1];

function crackHash($hash_algorithm, $hash_string) {
    $colors = new Colors();
    $fullCounter = 1;
    $halfCounter = 1;
    $lookup = new LookupTable("./hash_$hash_algorithm.idx", "./realuniq.lst", $hash_algorithm);

    $hasher = MoreHashAlgorithms::GetHashFunction($hash_algorithm);

    $fh = fopen("./realuniq.lst", "r");
    if ($fh === false) {
        echo "Error opening realuniq.lst";
        exit(1);
    }

    while (($line = fgets($fh)) !== false) {
        $word = rtrim($line, "\r\n");

        $to_crack = $hash_string;

        // words.txt must be in sorted order for this to work!
        $count = 1;
        while (($line = fgets($fh)) !== false) {
            if ($hasher->hash(rtrim($line, "\r\n"), false) !== $hasher->hash($word, false)) {
                fseek($fh, -1 * strlen($line), SEEK_CUR);
                break;
            }
            $count++;
        }

        // Full match.
        $results = $lookup->crack($to_crack);
        if (count($results) !== $count || $results[0]->isFullMatch() !== true) {
            $fullCounter = 0;
        } else {
            $cracked = $results[0]->getPlaintext();
           	echo $colors->getColoredString("[+] Successfully cracked [$to_crack] using [$hash_algorithm] as [$cracked].", "green", "black") . "\n";
        }

        foreach ($results as $result) {
            if ($result->getAlgorithmName() !== $hash_algorithm) {
                echo "Algorithm name is not set correctly (full match).";
            }
        }

        // Partial match (first 8 bytes, 16 hex chars).
        $to_crack = substr($to_crack, 0, 16);
        $results = $lookup->crack($to_crack);

        if (count($results) !== $count || $results[0]->isFullMatch() !== false) {
            $halfCounter = 0;
        } else {
            $cracked = $results[0]->getPlaintext();
            echo $colors->getColoredString("[+] Successfully cracked [$to_crack] using [$hash_algorithm] as [$cracked] (as partial match).", "green", "black") . "\n";
        }

        foreach ($results as $result) {
            if ($result->getAlgorithmName() !== $hash_algorithm) {
                echo "Algorithm name is not set correctly (partial match).";
            }
        }
        break;
    }
    
    if ($fullCounter == 0){
        printFailureFull($hash_algorithm, $hash_string);
    }

    if ($halfCounter == 0){
        printFailurePartial($hash_algorithm, $hash_string);
    }

    fclose($fh);

}

function printFailureFull($hash_algorithm, $hash_string){
    $colors = new Colors();
    echo $colors->getColoredString("[-] Failed to crack [$hash_string] using [$hash_algorithm].", "red", "black") . "\n";
}

function printFailurePartial($hash_algorithm, $hash_string){
    $colors = new Colors();
    echo $colors->getColoredString("[-] Failed to crack [$hash_string] using [$hash_algorithm] (partial match).", "red", "black") . "\n";
}

class Colors {
        private $foreground_colors = array();
        private $background_colors = array();

        public function __construct() {
            // Set up shell colors
            $this->foreground_colors['green'] = '0;32';
            $this->foreground_colors['red'] = '0;31';
            $this->background_colors['black'] = '40';
        }

        // Returns colored string
        public function getColoredString($string, $foreground_color = null, $background_color = null) {
            $colored_string = "";

            // Check if given foreground color found
            if (isset($this->foreground_colors[$foreground_color])) {
                $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
            }
            // Check if given background color found
            if (isset($this->background_colors[$background_color])) {
                $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
            }

            // Add string and end coloring
            $colored_string .=  $string . "\033[0m";

            return $colored_string;
        }

        // Returns all foreground color names
        public function getForegroundColors() {
            return array_keys($this->foreground_colors);
        }

        // Returns all background color names
        public function getBackgroundColors() {
            return array_keys($this->background_colors);
        }
    }

while ($counter >= 0) {
    $hash_algorithm = $algorithms[$counter];
    crackHash($hash_algorithm, $hash_string);
    $counter = $counter - 1;
}

?>
