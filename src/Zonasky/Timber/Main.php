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

declare(strict_types = 1);

namespace Zonasky\Timber;

use Throwable;
use Generator;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use vennv\vapm\Async;
use vennv\vapm\Promise;
use vennv\vapm\VapmPMMP;

class Main extends PluginBase implements Listener {

	private array $worlds;

	/**
	 * @return void
	 */
	protected function onLoad() : void {
		$this->saveDefaultConfig();
	}

	/**
	 * @return void
	 */
	protected function onEnable() : void {
		VapmPMMP::init($this);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->loadConfig();
	}

	/**
	 * @return void
	 */
	private function loadConfig() : void {
		$config = $this->getConfig();
		$this->worlds = $config->get("worlds", []);
	}

	/**
	 * @param string $worldName
	 * @return bool
	 */
	private function isTimberWorld(string $worldName) : bool {
		return in_array($worldName, $this->worlds);
	}

	private function isTreeBlock(Block $block) : bool {

		$blocks = [
			VanillaBlocks::OAK_LOG()->getTypeId(),
			VanillaBlocks::SPRUCE_LOG()->getTypeId(),
			VanillaBlocks::BIRCH_LOG()->getTypeId(),
			VanillaBlocks::JUNGLE_LOG()->getTypeId(),
			VanillaBlocks::ACACIA_LOG()->getTypeId(),
			VanillaBlocks::DARK_OAK_LOG()->getTypeId(),
			VanillaBlocks::MANGROVE_LOG()->getTypeId()
		];

		return in_array($block->getTypeId(), $blocks);
	}

	private function isLeavesBlock(Block $block) : bool {

		$blocks = [
			VanillaBlocks::OAK_LEAVES()->getTypeId(),
			VanillaBlocks::SPRUCE_LEAVES()->getTypeId(),
			VanillaBlocks::BIRCH_LEAVES()->getTypeId(),
			VanillaBlocks::JUNGLE_LEAVES()->getTypeId(),
			VanillaBlocks::ACACIA_LEAVES()->getTypeId(),
			VanillaBlocks::DARK_OAK_LEAVES()->getTypeId(),
			VanillaBlocks::MANGROVE_LEAVES()->getTypeId()
		];

		return in_array($block->getTypeId(), $blocks);
	}

	/**
	 * @param BlockBreakEvent $event
	 * @return void
	 * @throws Throwable
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void {

		$block = $event->getBlock();
		$worldName = $block->getPosition()->getWorld()->getFolderName();

		if ($this->isTimberWorld($worldName)) {

			if ($this->isTreeBlock($block)) {

				$treeBlocks = $this->getTreeBlocks($block);
				$world = $block->getPosition()->getWorld();

				$leavesConfig = $this->getConfig()->get("leaves");

				new Async(function() use ($world, $treeBlocks, &$leaves, $leavesConfig) : void {

					foreach ($treeBlocks as $treeBlock) {

						Async::await(new Promise(function($resolve) use ($world, $treeBlock) : void {
							$resolve($world->useBreakOn($treeBlock->getPosition()));
						}));

						if ($leavesConfig) {

							foreach ($this->getLeavesBlocks($treeBlock) as $leaf) {

								Async::await(new Promise(function($resolve) use ($world, $leaf) : void {
									$resolve($world->useBreakOn($leaf->getPosition()));
								}));
							}
						}
					}
				});
			}
		}
	}

	/**
	 * @param Block $block
	 * @return Generator
	 */
	private function getTreeBlocks(Block $block) : Generator {

		$world = $block->getPosition()->getWorld();

		yield $block;

		$checkBlock = function(World $world, Block $block, float|int $y): ?Block {

			$blockBelow = $world->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));

			if ($this->isTreeBlock($blockBelow)) {
				return $blockBelow;
			}

			return null;
		};

		for ($y = $block->getPosition()->getY() - 1; $y >= $world->getMinY(); $y--) {

			$check = $checkBlock($world, $block, $y);

			if ($check !== null) {
				yield $check;
			} else {
				break;
			}
		}

		for ($y = $block->getPosition()->getY() + 1; $y <= $world->getMaxY(); $y++) {

			$check = $checkBlock($world, $block, $y);

			if ($check !== null) {
				yield $check;
			} else {
				break;
			}
		}
	}

	/**
	 * @param Block $block
	 * @return Generator
	 */
	private function getLeavesBlocks(Block $block) : Generator {

		$world = $block->getPosition()->getWorld();

		for ($x = $block->getPosition()->getX() - 1; $x <= $block->getPosition()->getX() + 1; $x++) {

			for ($y = $block->getPosition()->getY() - 1; $y <= $block->getPosition()->getY() + 1; $y++) {

				for ($z = $block->getPosition()->getZ() - 1; $z <= $block->getPosition()->getZ() + 1; $z++) {

					$leaf = $world->getBlock(new Vector3($x, $y, $z));

					if ($this->isLeavesBlock($leaf)) {
						yield $leaf;
					}
				}
			}
		}
	}

}