<?php
		$mapping_string = file_get_contents("mapping.json");
		$mapping = json_decode($mapping_string, true);
		echo $mapping["Q47"],"<br>";
		$inverted = array_flip($mapping);
		echo $inverted["Q21741498"];
