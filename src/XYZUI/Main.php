<?php

namespace XYZUI;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class Main extends PluginBase{

    private array $enabled = [];

    public function onEnable() : void{
        $this->saveDefaultConfig();

        $interval = (int) $this->getConfig()->get("update-interval", 20);

        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function() : void{

                foreach($this->enabled as $playerName => $enabled){

                    if(!$enabled){
                        continue;
                    }

                    $player = $this->getServer()->getPlayerExact($playerName);

                    if(!$player instanceof Player){
                        continue;
                    }

                    $pos = $player->getPosition();

                    $format = $this->getConfig()->get("coords-format");

                    $message = str_replace(
                        ["{x}", "{y}", "{z}"],
                        [
                            round($pos->getX(), 1),
                            round($pos->getY(), 1),
                            round($pos->getZ(), 1)
                        ],
                        $format
                    );

                    $message = TextFormat::colorize(str_replace("\\n", "\n", $message));

                    $player->sendActionBarMessage($message);
                }

            }),
            $interval
        );
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{

        if(!$sender instanceof Player){
            return true;
        }

        $this->openForm($sender);
        return true;
    }

    private function openForm(Player $player) : void{

        $form = new SimpleForm(function(Player $player, ?int $data) : void{

            if($data === null){
                $player->sendMessage(
                    TextFormat::colorize(
                        $this->getMessage("cancelled")
                    )
                );
                return;
            }

            switch($data){

                case 0:
                    $this->enabled[$player->getName()] = true;

                    $player->sendMessage(
                        TextFormat::colorize(
                            $this->getMessage("enabled")
                        )
                    );
                break;

                case 1:
                    unset($this->enabled[$player->getName()]);

                    $player->sendMessage(
                        TextFormat::colorize(
                            $this->getMessage("disabled")
                        )
                    );
                break;

                case 2:
                    $player->sendMessage(
                        TextFormat::colorize(
                            $this->getMessage("cancelled")
                        )
                    );
                break;
            }
        });

        $form->setTitle(
            TextFormat::colorize(
                $this->getMessage("title")
            )
        );

        $form->setContent(
            TextFormat::colorize(
                str_replace("\\n", "\n", $this->getMessage("content"))
            )
        );

        $form->addButton("§aYes");
        $form->addButton("§cNo");
        $form->addButton("§eCancel");

        $player->sendForm($form);
    }

    private function getMessage(string $key) : string{
        return (string) $this->getConfig()->getNested("messages." . $key, "");
    }
}
