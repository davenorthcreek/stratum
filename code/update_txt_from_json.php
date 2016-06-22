<?php
	if(!isset($argv[2])) {
		echo ("Usage: php update_txt_from_json.php <json filename> <txt filename>\n");
		echo ("       <json filename> should be the direct output from a REST call to Bullhorn.\n");
		echo ("       <txt filename> will be overwritten.  Ensure you have write permissions.\n");
	} else {
		$mapping_string = file_get_contents($argv[1]);
		$fh = fopen($argv[2], 'w');
		$mapping = json_decode($mapping_string, true);
		$index = 1;
		foreach ($mapping['data'] as $cat) {
			echo ("A".$index++."\t".$cat["label"]."\n");
			fwrite($fh, "A".$index++."\t".$cat["label"]."\n");
		}
		fclose($fh);
	}
