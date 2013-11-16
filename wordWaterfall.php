<?php

/* A dictionary class with two arrays
 * $dict - holds the word as the key and the count as the corresponding value;
 * $restricted - holds the restricted words that will never be seen in $dict.
 * $toks - are the characters to split the text on, forbidden chars essentially.
 */
class freq_dictionary
{
	private $dict = array(); //words and their counts
	private $restricted = array(); //restricted words
	private $toks = " []()<>+=\"*@_.,?!:;#$%^&1234567890\\§\n\t\r";
	private $max = 0;//the highest word count, for normalizing histogram

	//prints out the keys and contents of $dict
	public function printDict($toPrint, $top, $bottom)
	{
		asort($this->dict);
		reset($this->dict);
		//get max
		$this->max = end($this->dict);

		// PRINT ALL
		if($toPrint == "-a")
		{
			reset($this->dict);
			while(list($word, $number) = each($this->dict))
			{
				echo "$word \t $number\n";
				flush(); @ob_flush();
			}
		}
		// PRINT TOP AND BOTTOM
		else if ($toPrint == "-tb")
		{
			echo "-- Top $top --\n";

			$i = count($this->dict);
			while ($i > count($this->dict) - $top)
			{
				echo $this->word_tab(key($this->dict)) .
				$this->hash_line(current($this->dict)) . "\n";
				flush(); @ob_flush();
				$i--;
				prev($this->dict);
			}

			echo "\n-- Bottom $bottom --\n";

			$i = 0;
			reset($this->dict);
			while ($i < $bottom)
			{
				echo $this->word_tab(key($this->dict)) .
				$this->hash_line(current($this->dict)) . "\n";
				flush(); @ob_flush();
				$i++;
				next($this->dict);
			}
		}
		// DEFAULT PRINT TOP 10 and BOTTOM 5
		else if ($toPrint == NULL)
		{
			echo "-- Top 10 --\n";

			$i = count($this->dict);
			while ($i > count($this->dict) - 10)
			{
				echo $this->word_tab(key($this->dict)) .
				$this->hash_line(current($this->dict)) . "\n";
				flush(); @ob_flush();
				$i--;
				prev($this->dict);
			}

			echo "\n-- Bottom 5 --\n";

			$i = 0;
			reset($this->dict);
			while ($i < 5)
			{
				echo $this->word_tab(key($this->dict)) .
				$this->hash_line(current($this->dict)) . "\n";
				flush(); @ob_flush();
				$i++;
				next($this->dict);
			}
		}
	}

	//a single function that adds words to either the regular or restricted dictionary
	public function wordsToAdd($words, $which)
	{
		$words = strtolower($words);

		if($which == 1) //add words to Restricted dictionary
		{
			$restrictTok = strtok($words, $this->toks);
			while ($restrictTok !== false)
			{
				$this->addRestricted($restrictTok);
				$restrictTok = strtok($this->toks);
			}
		}
		else if ($which == 2) //add words to the Count dictionary
		{
			$textTok = strtok($words, $this->toks);
			while ($textTok !== false)
			{
				$textTok = preg_replace("/[^\x9\xA\xD\x20-\x7F]/", "", $textTok);
				if(strlen($textTok) > 1)
					$this->addWord($textTok);
				$textTok = strtok($this->toks);
			}
		}
	}

	//outs a csv file with all the data from the text.
	public function csvOut($fileName)
	{
		if( substr($fileName, strlen($fileName) - 4, strlen($fileName)) != ".csv")
			$fileName .= ".csv";

		$fh = fopen($fileName, 'w');
		while (list($word, $number) = each($this->dict))
		{
			fwrite($fh, "$word,$number\n");
			flush();
		}
		echo "file " . $fileName . " written.\n";
		fclose($fh);
	}

	public function svgOut($fileName, $title)
	{
		if( substr($fileName, strlen($fileName) - 4, strlen($fileName)) != ".svg")
			$fileName .= ".svg";

		$fh = fopen($fileName, 'w');
		$font = "Helvetica";
		$titleSize = 16;
		$width = 1000;
		$multiplier = 64;
		$xSpacing = 100;
		$ySpacing = 20;

		//graph line
		$y = $ySpacing;
		$strokeWidth = 5;

		//for the numbers on the graph
		$numSize = 12;
		$numSpacing = 10;
		$numX = 10;
		$numY = $ySpacing;

		fwrite($fh, "<?xml version='1.0' encoding='UTF-8' standalone='no'?>
			<svg
			xmlns:dc='http://purl.org/dc/elements/1.1/'
			xmlns:rdf='http://www.w3.org/1999/02/22-rdf-syntax-ns#'
			xmlns:svg='http://www.w3.org/2000/svg'
			xmlns='http://www.w3.org/2000/svg'
			width='".$width."'
			height='10000' >
			"); //I have not found a way to calculate height yet, 10000px is usually good though

		//write title
		fwrite($fh,"<text style='fill:black;' font-family='".$font."'
			text-decoration='underline'
			font-weight='bold'
			x='10' y='".$ySpacing."'
			font-size='".$titleSize."'>".$title."</text>");
 
		$ySpacing+=$titleSize;
		$exludedCount = 0;
 
		while (list($word, $number) = each($this->restricted))
		{
			if($number > 0)
				$excludedCount++;
		}
 
		fwrite($fh,"<text style='fill:black;' font-family='".$font."'
			font-weight='bold'
			x='10' y='".$ySpacing."'
			font-size='".$titleSize."'>".
			sizeof($this->dict)." unique words " .
			"excluding ".$excludedCount." common pronouns, 'be' and 'have' conjugations.</text>");
 
		asort($this->dict);
		$new_arr=array_reverse($this->dict, true);
		$this->max = end($this->dict);
 
		//for the words list
		while (list($word, $number) = each($new_arr))
		{
			//max font size is Multiplier *
			$fontSize = ceil(($number / $this->max) * $multiplier);
			$ySpacing += $fontSize;
 
			fwrite($fh, "\t<text style='fill:black;' font-family='".$font."'
			x='".$xSpacing."' y='".$ySpacing."'
			font-size='".$fontSize."'>".$word."</text>\n");
			flush();
		}

		//open the graph line
		fwrite($fh, "<path d='M".($width-$strokeWidth)."," .$y. " C");
		reset($new_arr);
		while (list($word, $number) = each($new_arr))
		{
			$x = ceil(($number / $this->max) * $width);
			$y += ceil(($number / $this->max) * $multiplier);
			fwrite($fh, $x.",".$y." ");
			flush();
		}
		fwrite($fh, "'fill='none' stroke='black' stroke-width='".$strokeWidth."'/>");
 
		reset($new_arr);
 
		//numbers on the graph
		while (list($word, $number) = each($new_arr))
		{
			$numY += ceil(($number / $this->max) * $multiplier);
 
			if($numSpacing % 5 == 0 && $numSpacing < 500)
			{
				fwrite($fh, "\t<text style='fill:black;' font-family='Helvetica'
				x='".$numX."' y='".$numY."'
				font-size='".$numSize."'>".$number."</text>\n");
			}
			$numSpacing++;
		}

		fwrite($fh, "</svg>");

		echo "file " . $fileName . " written.\n";
		fclose($fh);
	}

	//adds word to $dict
	private function addWord($word)
	{
		if ( $this->isRestricted($word) == 0)
		{
			if( $this->dict[$word] != NULL)
				$this->dict[$word]++;
			else
				$this->dict[$word] = 1;
		}
	}

	//adds word to $restricted
	private function addRestricted($word)
	{
		if( $this->restricted[$word] == NULL)
		{
			$this->restricted[$word] = 1;
		}
	}

	//boolean function that returns if the word is in the restricted dictionary or not
	private function isRestricted($word)
	{
		if($this->restricted[$word] == 1)
			return 1;
		else
			return 0;
	}

	//get a number, give a histogram line.
	private function hash_line($number)
	{
		$line = "";
		$multiplier = 40;
		$length = ceil(($number / $this->max) * $multiplier); //normalize
		for($i = 0; $i < $length; $i++)
			$line .= "#";
		return $line;
	}

	private function word_tab($word)
	{
		//two tabs by default
		if (strlen($word) <= 7)
			$word .= "\t\t";
		//one tab if the word is more than 7 chars
		else if (strlen($word) > 7 && strlen($word) <= 14)
			$word .= "\t";
		return $word;
	}
} //close class

/* Using the class:
 * - Error handling
 * - Get files
 * - Get strings from files
 * - Fill up restricted dictionary
 * - Fill up the regular dictionary
 * - Print it out.
 */

//Error handling, could be much shorter.
if ($argc < 3)
{
	echo "Error: usage: [exluded words file] [words file] optional:
	[-a, -tb # #, -csv||-svg filename]\n";
	exit(1);
}
else if (file_exists($argv[1]) == FALSE)
	{
	echo "Error: file '" . $argv[1] . " 'not found.\n";
	exit(1);
}
else if(file_exists($argv[2]) == FALSE)
{
	echo "Error: file '" . $argv[2] . "' not found.\n";
	exit(1);
}
else if ($argv[3] != NULL && ($argv[3] != "-a" && $argv[3] != "-tb" && $argv[3] != "-csv" && $argv[3] != "-svg"))
{
	echo "Error: Invalid 3rd parameter. -a All, -tb Top-n words Bottom-n words,
	-csv CSV file output. -svg Word Cloud\n";
	exit(1);
}
else if ($argv[3] == "-tb" && ($argv[4] == NULL || $argv[5] == NULL))
{
	echo "Error: Invalid top-n bottom-n params, must be of type integer.\n";
	exit(1);
}
else if ($argv[3] == "-csv" && $argv[4] == NULL)
{
	echo "Error: need file name to write to.\n";
	exit(1);
}
else
{
	//get file in
	$restrictedFile = $argv[1];
	$textFile = $argv[2];

	//get strings from files
	$restricted = implode( '', file($restrictedFile));
	$text = implode( '', file($textFile));

	//declare an instance of the dictionary class
	$dictionary = new freq_dictionary;

	//add words to dictionary, 1 for restricted, 2 for regular
	$dictionary->wordsToAdd($restricted, 1);
	$dictionary->wordsToAdd($text, 2);

	//print it all out, yay.
	if($argv[3] == "-a")
		$dictionary->printDict($argv[3], NULL, NULL);
	else if ( $argv[3] == "-tb")
		$dictionary->printDict($argv[3], $argv[4], $argv[5]);
	else if ( $argv[3] == "-csv")
		$dictionary->csvOut($argv[4]);
	else if ( $argv[3] == "-svg")
		$dictionary->svgOut($argv[4], strstr($text, "\n", true));
	else
		$dictionary->printDict(NULL, NULL, NULL);
}
?>
