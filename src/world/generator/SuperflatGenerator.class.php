<?php

/*

           -
         /   \
      /         \
   /   PocketMine  \
/          MP         \
|\     @shoghicp     /|
|.   \           /   .|
| ..     \   /     .. |
|    ..    |    ..    |
|       .. | ..       |
\          |          /
   \       |       /
      \    |    /
         \ | /

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.


*/


class SuperflatGenerator implements LevelGenerator{
	private $config, $structure, $chunks, $options, $floorLevel;
	
	public function __construct(array $options = array()){
		$this->preset = "2;7,2x3,2;1;spawn(radius=10 block=24)";
		$this->options = $options;
		if(isset($options["preset"])){
			$this->parsePreset($options["preset"]);
		}else{
			$this->parsePreset($this->preset);
		}
	}
	
	public function parsePreset($preset){
		$this->preset = $preset;
		$preset = explode(";", $preset);
		$version = (int) $preset[0];
		$blocks = @$preset[1];
		$biome = isset($preset[2]) ? $preset[2]:1;
		$options = isset($preset[3]) ? $preset[3]:"";
		preg_match_all('#(([0-9]{0,})x?([0-9]{1,3}:?[0-9]{0,2})),?#', $blocks, $matches);
		$y = 0;
		$this->structure = array();
		$this->chunks = array();
		foreach($matches[3] as $i => $b){
			$b = BlockAPI::fromString($b);
			$cnt = $matches[2][$i] === "" ? 1:intval($matches[2][$i]);
			for($cY = $y, $y += $cnt; $cY < $y; ++$cY){
				$this->structure[$cY] = $b;
			}
		}
		
		$this->floorLevel = $y;
		
		for(;$y < 0xFF; ++$y){
			$this->structure[$y] = new AirBlock();
		}
		
		
		for($Y = 0; $Y < 8; ++$Y){
			$this->chunks[$Y] = "";
			$startY = $Y << 4;
			$endY = $startY + 16;
			for($Z = 0; $Z < 16; ++$Z){
				for($X = 0; $X < 16; ++$X){
					$blocks = "";
					$metas = "";
					for($y = $startY; $y < $endY; ++$y){
						$blocks .= chr($this->structure[$y]->getID());
						$metas .= substr(dechex($this->structure[$y]->getMetadata()), -1);
					}
					$this->chunks[$Y] .= $blocks.Utils::hexToStr($metas)."\x00\x00\x00\x00\x00\x00\x00\x00";
				}
			}
		}
		
		preg_match_all('#(([0-9a-z_]{1,})\(?([0-9a-z_ =:]{0,})\)?),?#', $options, $matches);
		foreach($matches[2] as $i => $option){
			$params = true;
			if($matches[3][$i] !== ""){
				$params = array();
				$p = explode(" ", $matches[3][$i]);
				foreach($p as $k){
					$k = explode("=", $k);
					if(isset($k[1])){
						$params[$k[0]] = $k[1];
					}
				}
			}
			$this->options[$option] = $params;
		}
	}
		
	public function generateChunk(Level $level, $chunkX, $chunkY, $chunkZ, Random $random){
		$level->setMiniChunk($chunkX, $chunkZ, $chunkY, $this->chunks[$chunkY]);
	}
	
	public function populateChunk(Level $level, $chunkX, $chunkY, $chunkZ, Random $random){

	}
	
	public function populateLevel(Level $level, Random $random){
		if(isset($this->options["spawn"])){
			$spawn = array(10, new SandstoneBlock());
			if(isset($this->options["spawn"]["radius"])){
				$spawn[0] = intval($this->options["spawn"]["radius"]);
			}
			if(isset($this->options["spawn"]["block"])){
				$spawn[1] = BlockAPI::fromString($this->options["spawn"]["block"])->getBlock();
				if(!($spawn[1] instanceof Block)){
					$spawn[1] = new SandstoneBlock();
				}
			}

			$start = 128 - $spawn[0];
			$end = 128 + $spawn[0];
			for($x = $start; $x <= $end; ++$x){
				for($z = $start; $z <= $end; ++$z){
					if(floor(sqrt(pow($x - 128, 2) + pow($z - 128, 2))) <= $spawn[0]){
						$level->setBlock(new Vector3($x, $this->floorLevel - 1, $z), $spawn[1]);
					}
				}
			}
		}
	}
	
	public function getSpawn(Random $random){
		return new Vector3(128, $this->floorLevel, 128);
	}
}