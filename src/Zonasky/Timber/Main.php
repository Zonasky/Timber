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
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

	private $worlds;
	private $logTypes = [
		VanillaBlocks::OAK_LOG(),
		VanillaBlocks::SPRUCE_LOG(),
		VanillaBlocks::BIRCH_LOG(),
		VanillaBlocks::JUNGLE_LOG(),
		VanillaBlocks::ACACIA_LOG(),
		VanillaBlocks::DARK_OAK_LOG(),
		VanillaBlocks::MANGROVE_LOG(),
		VanillaBlocks::CHERRY_LOG()
	];
	private $leafTypes = [
		VanillaBlocks::OAK_LEAVES(),
		VanillaBlocks::SPRUCE_LEAVES(),
		VanillaBlocks::BIRCH_LEAVES(),
		VanillaBlocks::JUNGLE_LEAVES(),
		VanillaBlocks::ACACIA_LEAVES(),
		VanillaBlocks::DARK_OAK_LEAVES(),
		VanillaBlocks::MANGROVE_LEAVES(),
		VanillaBlocks::CHERRY_LEAVES()
	];

	/**
	 * @return void
	 */
	public function onLoad(): void {
		$this->saveDefaultConfig();
	}

	/**
	 * @return void
	 */
	public function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->loadConfig();
	}

	/**
	 * @return void
	 */
	private function loadConfig(): void {
		$config = $this->getConfig();
		$this->worlds = $config->get("worlds", []);
	}

	/**
	 * @param string $worldName
	 * @return bool
	 */
	private function isTimberWorld(string $worldName): bool {
		return in_array($worldName, $this->worlds);
	}

	/**
	 * @param BlockBreakEvent $event
	 * @return void
	 */
	public function onBlockBreak(BlockBreakEvent $event): void {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$worldName = $block->getPosition()->getWorld()->getFolderName();
		if (!$this->isTimberWorld($worldName)) {
			return;
		}
		if (in_array($block->getTypeId(), $this->logTypes->getTypeId())) {
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

	/**
	 * @param Block $block
	 * @return array
	 */
	private function getTreeBlocks(Block $block): array {
		$world = $block->getPosition()->getWorld();
		$blocks = [$block];
		for ($y = $block->getPosition()->getY() - 1; $y >= $world->getMinY(); $y--) {
			$blockBelow = $world->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
			if (in_array($blockBelow->getTypeId(), $this->logTypes->getTypeId())) {
				$blocks[] = $blockBelow;
			} else {
				break;
			}
		}
		for ($y = $block->getPosition()->getY() + 1; $y <= $world->getMaxY(); $y++) {
			$blockAbove = $world->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
			if (in_array($blockAbove->getTypeId(), $this->logTypes->getTypeId())) {
				$blocks[] = $blockAbove;
			} else {
				break;
			}
		}
		return $blocks;
	}

	/**
	 * @param Block $block
	 * @return array
	 */
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
						if (in_array($leaf->getTypeId(), $this->leafTypes->getTypeId())) {
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