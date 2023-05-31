<?php

/**
 *
 *  ██ ███    ██  █████   █████  ██    ██  █████  ████████
 *  ██ ████   ██ ██   ██ ██   ██  ██  ██  ██   ██    ██
 *  ██ ██ ██  ██ ███████ ███████   ████   ███████    ██
 *  ██ ██  ██ ██ ██   ██ ██   ██    ██    ██   ██    ██
 *  ██ ██   ████ ██   ██ ██   ██    ██    ██   ██    ██

 *
 * @author Inaayat
 * @link https://github.com/Inaay
 *
 */

declare(strict_types=1);

namespace Zonasky\Timber;

use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

	// 17 | BlockLegacyIds::LOG
	// 162 | BlockLegacyIds::LOG2
	// 18 | BlockLegacyIds::LEAVES
	// 161 | BlockLegacyIds::LEAVES2

	private $worlds;

	public function onLoad(): void {
		$this->saveDefaultConfig();
	}

	public function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->loadConfig();
	}

	private function loadConfig(): void {
		$config = $this->getConfig();
		$this->worlds = $config->get("worlds", []);

	}

	private function isTimberWorld(string $worldName): bool {
		return in_array($worldName, $this->worlds);
	}

	public function onBlockBreak(BlockBreakEvent $event): void {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$worldName = $block->getPosition()->getWorld()->getFolderName();
		if (!$this->isTimberWorld($worldName)) {
			return;
		}
		if ($block->getTypeId() == 17 || $block->getTypeId() == 162) {
			$treeBlocks = $this->getTreeBlocks($block);
			$world = $block->getPosition()->getWorld();
			$leaves = [];
			foreach ($treeBlocks as $treeBlock) {
				$world->useBreakOn($treeBlock->getPosition());
				$leaves = array_merge($leaves, $this->getLeavesBlocks($treeBlock));
			}
			$leavesConfig = $this->getConfig()->get("leaves");
			if ($leavesConfig) {
				foreach ($leaves as $leaf) {
					$world->useBreakOn($leaf->getPosition());
				}
			}
		}
	}

	private function getTreeBlocks(Block $block): array {
		$world = $block->getPosition()->getWorld();
		$blocks = [$block];
		for ($y = $block->getPosition()->getY() - 1; $y >= $world->getMinY(); $y--) {
			$blockBelow = $world->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
			if ($blockBelow->getTypeId() == 17 || $blockBelow->getTypeId() == 162) {
				$blocks[] = $blockBelow;
			} else {
				break;
			}
		}
		for ($y = $block->getPosition()->getY() + 1; $y <= $world->getMaxY(); $y++) {
			$blockAbove = $world->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
			if ($blockAbove->getTypeId() == 17 || $blockAbove->getTypeId() == 162) {
				$blocks[] = $blockAbove;
			} else {
				break;
			}
		}
		return $blocks;
	}

	private function getLeavesBlocks(Block $block): array {
		$world = $block->getPosition()->getWorld();
		$blocks = [];
		$visited = [];
		$queue = [$block];
		while (!empty($queue)) {
			$current = array_shift($queue);
			if (in_array($current, $visited)) {
				continue;
			}
			$visited[] = $current;
			for ($x = $current->getPosition()->getX() - 1; $x <= $current->getPosition()->getX() + 1; $x++) {
				for ($y = $current->getPosition()->getY() - 1; $y <= $current->getPosition()->getY() + 1; $y++) {
					for ($z = $current->getPosition()->getZ() - 1; $z <= $current->getPosition()->getZ() + 1; $z++) {
						$leaf = $world->getBlock(new Vector3($x, $y, $z));
						if ($leaf->getTypeId() == 18 || $leaf->getTypeId() == 161) {
							$blocks[] = $leaf;
							$queue[] = $leaf;
						}
					}
				}
			}
		}
		return $blocks;
	}
}