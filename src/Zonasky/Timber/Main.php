<?php

namespace Zonasky\Timber;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

    private $worlds;
    private $message;
	public const PREFIX = "§7[§cTimber§7] §r";

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->loadConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    private function loadConfig(): void {
        $config = $this->getConfig();
        $this->worlds = $config->get("world", []);
        $this->message = $config->get("message", "");
    }

    private function isTimberWorld(string $worldName): bool {
        return in_array($worldName, $this->worlds);
    }

    private function sendMessage(Player $player, int $count): void {
        $message = str_replace("{count}", $count, $this->message);
		$message = str_replace("&", "§", $this->message);
        $player->sendMessage(self::PREFIX . $message);
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $worldName = $block->getPosition()->getWorld()->getFolderName();
        if (!$this->isTimberWorld($worldName)) {
            return;
        }
        if ($block->getId() == BlockLegacyIds::LOG || $block->getId() == BlockLegacyIds::LOG2) {
            $this->timber($block, $player);
        }
    }

    public function timber(Block $block, Player $player) {
        $item = $player->getInventory()->getItemInHand();
        $logCount = 0;
        for ($i = 0; $i <= 5; $i++) {
            $side = $block->getSide($i);
            if ($side->getId() !== BlockLegacyIds::LOG && $side->getId() !== BlockLegacyIds::LOG2) {
                continue;
            }
            $player->getWorld()->useBreakOn($side->getPosition(), $item, $player);
            $logCount++;
            $this->timber($side, $player);
        }
        if ($logCount > 0 && $this->message !== "") {
            $this->sendMessage($player, $logCount);
        }
    }
}