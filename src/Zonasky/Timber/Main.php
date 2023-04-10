<?php

namespace Zonasky\Timber;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

    private $worlds;
    private $message;

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

    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $worldName = $block->getPosition()->getWorld()->getFolderName();
        if (!$this->isTimberWorld($worldName)) {
            return;
        }
        if ($block->getId() == BlockLegacyIds::LOG || $block->getId() == BlockLegacyIds::LOG2) {
            $treeBlocks = $this->getTreeBlocks($block);
            $level = $block->getPosition()->getWorld();
            foreach ($treeBlocks as $treeBlock) {
                $level->useBreakOn($treeBlock->getPosition());
            }
        }
    }

	private function getTreeBlocks(Block $block): array {
        $level = $block->getPosition()->getWorld();
        $blocks = [$block];
        for ($y = $block->getPosition()->getY() + 1; $y <= $level->getMaxY(); $y++) {
            $blockAbove = $level->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
            if ($blockAbove->getId() == BlockLegacyIds::LOG || $block->getId() == BlockLegacyIds::LOG2) {
                $blocks[] = $blockAbove;
            } else {
                break;
            }
        }
        for ($y = $block->getPosition()->getY() - 1; $y >= 0; $y--) {
            $blockBelow = $level->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
            if ($blockBelow->getId() == BlockLegacyIds::LOG || $block->getId() == BlockLegacyIds::LOG2) {
                $blocks[] = $blockBelow;
            } else {
                break;
            }
        }
        return $blocks;
    }
}